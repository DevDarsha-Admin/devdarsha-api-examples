"""Get a full calendar year of Panchang data and print a per-month summary.

Run:
    pip install -r python/requirements.txt
    export DEVDARSHA_API_KEY=your_api_key_here
    python python/get_yearly_panchang.py

The yearly endpoint needs a Sapphire plan or higher. It fans out to all 12
months (X-Quota-Cost: 12) and is metered by the coverage meter — see the README.

Get a free API key at https://platform.devdarsha.com
"""

import os
import sys

import requests

API_URL = "https://panchang.devdarsha.com/v1/panchang/yearly"

API_KEY = os.environ.get("DEVDARSHA_API_KEY")
CITY_ID = os.environ.get("DEVDARSHA_CITY_ID", "ujjain")
YEAR = os.environ.get("DEVDARSHA_YEAR", "2026")  # 1900–2100


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
        # `year` must be an integer between 1900 and 2100.
        # city_id works on every plan; lat/lon lookups need Amethyst or higher.
        json={"year": int(YEAR), "city_id": CITY_ID},
        timeout=60,
    )

    result = resp.json()

    if not resp.ok:
        # Errors carry {error, message, docs_url}.
        print(f"HTTP {resp.status_code} — {result.get('error')}: {result.get('message')}")
        print(f"See {result.get('docs_url')}")
        sys.exit(1)

    # The payload lives under `data`; `data.months` holds the 12 months in order.
    data = result["data"]

    print(f"Yearly Panchang for {CITY_ID} — {data['year']} ({len(data['months'])} months)")
    print(
        f"  Quota cost: {resp.headers.get('X-Quota-Cost')} | "
        f"Coverage remaining: {resp.headers.get('X-Coverage-Remaining')}/"
        f"{resp.headers.get('X-Coverage-Limit')}"
    )
    for m in data["months"]:
        # A partial month carries `error` instead of day data (X-Degraded: true).
        if m.get("error"):
            summary = f"({m['error']})"
        else:
            summary = f"{m.get('total_days', len(m.get('days', [])))} days"
        print(f"  {str(m['month']).zfill(2)} {m['month_name']}: {summary}")


if __name__ == "__main__":
    main()
