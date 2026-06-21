# DevDarsha Panchang API — Code Examples

Official, copy-paste examples for the [DevDarsha](https://devdarsha.com) Panchang API,
maintained by the DevDarsha team. Get accurate Hindu calendar data — **tithi, nakshatra,
yoga, karana, festivals, muhurat, choghadiya, and sunrise/sunset** — for any date and city
with a single HTTP request.

- **Get a free API key:** https://platform.devdarsha.com
- **Full documentation:** https://platform.devdarsha.com/documentation
- **Examples in this repo:** Node.js, Python, PHP (plain + WordPress), and cURL.

> These examples exist to help developers get a working first request fast. They are not an SEO
> trick — they're a maintained reference so your integration matches the live API contract.

---

## Quick start (cURL)

```bash
curl -X POST "https://panchang.devdarsha.com/v1/panchang/daily" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"date":"2026-04-15","city_id":"ujjain"}'
```

The response is an envelope — the Panchang payload is under `data`:

```jsonc
{
  "data": {
    "date": "2026-04-15",
    "city": "Ujjain",
    "tithi":     [{ "number": 28, "name": "Trayodashi", "paksha": "Krishna", ... }],
    "nakshatra": [{ "number": 25, "name": "Purva Bhadrapada", ... }],
    "sun":       { "rise": "15-04-2026 06:06:30", "set": "15-04-2026 18:47:38", ... },
    "festivals": { "total": 2, "data": [ ... ] }
    // ... yoga, karana, muhurat, choghadiya, metadata
  },
  "meta": { "version": "1.0", "computed_at": "...", "resolved_timezone": "Asia/Kolkata", ... },
  "dev_notes": []
}
```

A complete real-shape response is in
[`examples/daily-panchang-response-sample.json`](examples/daily-panchang-response-sample.json).
Calculated values in snapshots can change as the Panchang engine is improved; rely on the live
response for current values.

---

## The request

| | |
|---|---|
| **Endpoint** | `POST https://panchang.devdarsha.com/v1/panchang/daily` |
| **Auth** | `X-Api-Key: <your key>` header *(or `?api_key=<key>` query param)* |
| **Body** | `{ "date": "YYYY-MM-DD", "city_id": "ujjain" }` — `date` is required |
| **Location** | `city_id` (works on **all plans**) **or** `lat` + `lon` (**Amethyst plan and up**) |
| **Optional** | `faith_filter` (`hindu` / `islamic` / `sikh` / `buddhist` / `christian` / `all`), `timezone` |

> **Free tier tip:** use `city_id`. Coordinate lookups (`lat`/`lon`) require an Amethyst plan
> or higher — calling them on the free plan returns `403 plan_required`.

Reading the response: tithi name is `data.tithi[0].name`, nakshatra is `data.nakshatra[0].name`,
sunrise is `data.sun.rise`, and festivals are in `data.festivals.data`.

---

## Monthly & yearly endpoints

Same auth (`X-Api-Key` header), same JSON-body style, same envelope (`data` / `meta` / `dev_notes`).

| Endpoint | Plan | Body | Payload |
|---|---|---|---|
| `POST .../v1/panchang/monthly` | **Amethyst** and up | `{ "date": "YYYY-MM", "city_id": "ujjain" }` *(or `{ "year": 2026, "month": 6, ... }`)* | `data.month`, `data.total_days`, `data.days[]` (each day has the daily shape) |
| `POST .../v1/panchang/yearly` | **Sapphire** and up | `{ "year": 2026, "city_id": "ujjain" }` — `year` is `1900`–`2100` | `data.year`, `data.months[]` (each with `month`, `month_name`, `days[]`) |

Location and `faith_filter` work exactly as on daily (`city_id` on every plan; `lat`/`lon` on
Amethyst+). The yearly route fans out to all 12 months, so it reports `X-Quota-Cost: 12`; a month
that could not be computed comes back with `error` instead of day data and `X-Degraded: true`.

Real-shape (abbreviated) responses:
[`examples/monthly-panchang-response-sample.json`](examples/monthly-panchang-response-sample.json),
[`examples/yearly-panchang-response-sample.json`](examples/yearly-panchang-response-sample.json).

---

## Coverage limits

Beyond the request rate limit, paid plans are metered by a **coverage meter**: the number of
**distinct `(location, date)` pairs** an account retrieves. It is enforced per account on a fixed
**UTC calendar month** (resetting on the 1st), with a secondary **UTC-day ceiling**. The exact
allowances vary by plan — see your [dashboard](https://platform.devdarsha.com) / the
[docs](https://platform.devdarsha.com/documentation); don't hard-code them.

Every successful data response carries headers so you can self-throttle before hitting a `429`:

| Header | Meaning |
|---|---|
| `X-Coverage-Limit` / `X-Coverage-Used` / `X-Coverage-Remaining` | Monthly distinct location-date allowance |
| `X-Coverage-Daily-Limit` / `X-Coverage-Daily-Used` / `X-Coverage-Daily-Remaining` | Same, for the UTC-day ceiling |

> Note: a `429` now has **two distinct causes** — `rate_limit_exceeded` (the request quota) and
> the coverage errors below. They are orthogonal: both must pass.

---

## Setup

Copy the example env file and add your key (it is gitignored — never commit a real key):

```bash
cp .env.example .env
# edit .env and set DEVDARSHA_API_KEY
```

The examples read environment variables; they do not load `.env` automatically. Load the
file into your current Bash/Zsh session before running them:

```bash
set -a
source .env
set +a
```

Alternatively, export the variables directly:

```bash
export DEVDARSHA_API_KEY=your_api_key_here
export DEVDARSHA_CITY_ID=ujjain
export DEVDARSHA_DATE=2026-04-15
```

In PowerShell, set the key for the current session with:

```powershell
$env:DEVDARSHA_API_KEY = "your_api_key_here"
```

---

## Run the examples

**Node.js** (18+, no dependencies — uses native `fetch`):

```bash
node node/get-daily-panchang.js
node node/today-tithi.js
node node/festival-calendar-range.js 2026-04-01 2026-04-30
node node/get-monthly-panchang.js   # Amethyst+ ; DEVDARSHA_MONTH=2026-06
node node/get-yearly-panchang.js    # Sapphire+ ; DEVDARSHA_YEAR=2026
```

**Python** (3.9+):

```bash
pip install -r python/requirements.txt
python python/get_daily_panchang.py
python python/get_monthly_panchang.py   # Amethyst+
python python/get_yearly_panchang.py    # Sapphire+
```

**PHP** (8.x, with the cURL extension):

```bash
php php/panchang-client.php
php php/get-monthly-panchang.php   # Amethyst+
php php/get-yearly-panchang.php    # Sapphire+
```

Each PHP file also exposes a `*_wp()` function built on `wp_remote_post()`
for **WordPress** plugins and temple/astrology themes.

---

## Common errors

Every error response is JSON with `{ "error", "message", "docs_url" }`.

| HTTP | `error` | What it means | Fix |
|------|---------|---------------|-----|
| `401` | `missing_api_key` | The `X-Api-Key` header is absent | Add the header; get a key at platform.devdarsha.com |
| `401` | `invalid_api_key` | The supplied API key is not valid | Check or replace the key at platform.devdarsha.com |
| `400` | `missing_parameter` | `date` (or another required field) is absent | Send `date` as `YYYY-MM-DD` |
| `400` | `invalid_*` | Malformed `date`, `city_id`, or `faith_filter` | Verify the value against the docs |
| `400` | `missing_location` | Neither a valid `city_id` nor a complete `lat`+`lon` pair was supplied | Send one of them |
| `403` | `plan_required` | Used `lat`/`lon` or a higher-tier route on a plan that doesn't allow it | Use `city_id`, or upgrade your plan |
| `429` | `rate_limit_exceeded` | Request quota or rate limit hit | Back off and retry after a short wait |
| `429` | `coverage_limit_exceeded` | Monthly distinct location-date allowance used up (resets 1st of UTC month) | Wait for the reset, or upgrade your plan |
| `429` | `coverage_daily_limit_exceeded` | The UTC-day coverage ceiling was hit | Retry the next UTC day, or upgrade |
| `429` | `city_year_cap_exceeded` | Per-city-year coverage cap hit (yearly/monthly) | Spread requests across cities/years, or upgrade |
| `502` | `engine_parse_error` | Transient upstream issue | Retry shortly |
| `503` | `engine_unavailable` | Engine temporarily down | Retry shortly |
| `503` | `coverage_enforcement_unavailable` | Coverage backend temporarily unavailable | Retry shortly |
| `504` | `upstream_timeout` | Upstream took too long | Retry shortly |

The `5xx` errors are transient — a short exponential backoff is the right response.

---

## Useful links

- **Free API key & dashboard:** https://platform.devdarsha.com
- **API documentation:** https://platform.devdarsha.com/documentation
- **2026 Hindu festival dates (India):** https://devdarsha.com/india-hindu-festival-dates-2026
- **2027 Hindu festival dates (India):** https://devdarsha.com/india-hindu-festival-dates-2027

---

## License

MIT — see [LICENSE](LICENSE). Example code is free to use in your own projects.
