"""Get the daily Panchang for a single date and print the key fields.

Run:
    pip install -r python/requirements.txt
    export DEVDARSHA_API_KEY=your_api_key_here
    python python/get_daily_panchang.py

Get a free API key at https://platform.devdarsha.com
"""

import os
import sys

import requests

API_URL = "https://panchang.devdarsha.com/v1/panchang/daily"

API_KEY = os.environ.get("DEVDARSHA_API_KEY")
CITY_ID = os.environ.get("DEVDARSHA_CITY_ID", "ujjain")
DATE = os.environ.get("DEVDARSHA_DATE", "2026-04-15")


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
        # city_id works on every plan. Coordinate lookups (lat/lon) need an
        # Amethyst plan or higher.
        json={"date": DATE, "city_id": CITY_ID},
        timeout=30,
    )

    result = resp.json()

    if not resp.ok:
        # Errors carry {error, message, docs_url}.
        print(f"HTTP {resp.status_code} — {result.get('error')}: {result.get('message')}")
        print(f"See {result.get('docs_url')}")
        sys.exit(1)

    # The payload lives under `data`; `meta` and `dev_notes` wrap it.
    data = result["data"]

    print(f"Panchang for {data['city']} on {data['date']} ({data['weekday']})")
    print(f"  Tithi:     {data['tithi'][0]['name']} ({data['tithi'][0]['paksha']})")
    print(f"  Nakshatra: {data['nakshatra'][0]['name']}")
    print(f"  Sunrise:   {data['sun']['rise']}")
    print(f"  Sunset:    {data['sun']['set']}")
    print(f"  API version (meta): {result['meta']['version']}")


if __name__ == "__main__":
    main()
