// Get the monthly Panchang — every day in a month — and print a short summary.
//
// Run:
//   export DEVDARSHA_API_KEY=your_api_key_here   (or set it in .env and source it)
//   node node/get-monthly-panchang.js
//
// Requires Node 18+ (uses the built-in global `fetch`). No npm install needed.
//
// The monthly endpoint needs an Amethyst plan or higher. It is also metered by
// the coverage meter (distinct location-date pairs per account) — see README.

const API_URL = "https://panchang.devdarsha.com/v1/panchang/monthly";

const API_KEY = process.env.DEVDARSHA_API_KEY;
const CITY_ID = process.env.DEVDARSHA_CITY_ID || "ujjain";
const MONTH   = process.env.DEVDARSHA_MONTH   || "2026-06"; // YYYY-MM

if (!API_KEY) {
  console.error("Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com");
  process.exit(1);
}

async function main() {
  const res = await fetch(API_URL, {
    method: "POST",
    headers: {
      // The API key goes in the X-Api-Key header, NOT in the JSON body.
      "X-Api-Key": API_KEY,
      "Content-Type": "application/json",
    },
    // Pass the month as `date: "YYYY-MM"`, or equivalently `{ year, month }`.
    // `city_id` works on every plan; lat/lon coordinate lookups need Amethyst+.
    body: JSON.stringify({ date: MONTH, city_id: CITY_ID }),
  });

  const result = await res.json();

  if (!res.ok) {
    // Errors carry { error, message, docs_url }.
    console.error(`HTTP ${res.status} — ${result.error}: ${result.message}`);
    console.error(`See ${result.docs_url}`);
    process.exit(1);
  }

  // The payload lives under `data`; the per-day array is `data.days`.
  const data = result.data;

  console.log(`Monthly Panchang for ${data.city} — ${data.month} (${data.total_days} days)`);
  // Coverage headers let you self-throttle before hitting a 429 (see README).
  console.log(`  Coverage remaining: ${res.headers.get("X-Coverage-Remaining")} / ${res.headers.get("X-Coverage-Limit")} (month)`);
  for (const day of data.days) {
    console.log(`  ${day.date} (${day.weekday}): ${day.tithi[0]?.name}`);
  }
}

main().catch((err) => {
  console.error("Request failed:", err.message);
  process.exit(1);
});
