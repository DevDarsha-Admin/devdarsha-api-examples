"""Get the monthly Panchang — every day in a month — and print a short summary.

Run:
    pip install -r python/requirements.txt
    export DEVDARSHA_API_KEY=your_api_key_here
    python python/get_monthly_panchang.py

The monthly endpoint needs an Amethyst plan or higher and is metered by the
coverage meter (distinct location-date pairs per account) — see the README.

Get a free API key at https://platform.devdarsha.com
"""

import os
import sys

import requests

API_URL = "https://panchang.devdarsha.com/v1/panchang/monthly"

API_KEY = os.environ.get("DEVDARSHA_API_KEY")
CITY_ID = os.environ.get("DEVDARSHA_CITY_ID", "ujjain")
MONTH = os.environ.get("DEVDARSHA_MONTH", "2026-06")  # YYYY-MM


def main() -> None:
    if not API_KEY:
        sys.exit(
            "Missing DEVDARSHA_API_KEY. "
            "Get a free key at https://platform.devdarsha.com"
        )

    resp = requests.post(
        API_URL,
        # The API key goes in the X-Api-Key header, NOT in the JSON body.
        headers={"X-Api-Key": API_KEY, "Content-Type": "application/json"},
        # Pass the month as `date: "YYYY-MM"` (or `{"year": ..., "month": ...}`).
        # city_id works on every plan; lat/lon lookups need Amethyst or higher.
        json={"date": MONTH, "city_id": CITY_ID},
        timeout=30,
    )

    result = resp.json()

    if not resp.ok:
        # Errors carry {error, message, docs_url}.
        print(f"HTTP {resp.status_code} — {result.get('error')}: {result.get('message')}")
        print(f"See {result.get('docs_url')}")
        sys.exit(1)

    # The payload lives under `data`; the per-day array is `data.days`.
    data = result["data"]

    print(f"Monthly Panchang for {data['city']} — {data['month']} ({data['total_days']} days)")
    # Coverage headers let you self-throttle before a 429 (see README).
    print(
        f"  Coverage remaining: {resp.headers.get('X-Coverage-Remaining')} / "
        f"{resp.headers.get('X-Coverage-Limit')} (month)"
    )
    for day in data["days"]:
        print(f"  {day['date']} ({day['weekday']}): {day['tithi'][0]['name']}")


if __name__ == "__main__":
    main()
