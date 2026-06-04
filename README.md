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
    "festivals": { "total": 1, "data": [ ... ] }
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
```

**Python** (3.9+):

```bash
pip install -r python/requirements.txt
python python/get_daily_panchang.py
```

**PHP** (8.x, with the cURL extension):

```bash
php php/panchang-client.php
```

The PHP file also exposes a `devdarsha_get_panchang_wp()` function built on `wp_remote_post()`
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
| `403` | `plan_required` | Used `lat`/`lon` or a higher-tier route on a plan that doesn't allow it | Use `city_id`, or upgrade your plan |
| `429` | `rate_limit_exceeded` | Quota or rate limit hit | Back off and retry after a short wait |
| `502` | `engine_parse_error` | Transient upstream issue | Retry shortly |
| `503` | `engine_unavailable` | Engine temporarily down | Retry shortly |
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
