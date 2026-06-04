// Get the daily Panchang for a single date and print the full response.
//
// Run:
//   export DEVDARSHA_API_KEY=your_api_key_here   (or set it in .env and source it)
//   node node/get-daily-panchang.js
//
// Requires Node 18+ (uses the built-in global `fetch`). No npm install needed.

const API_URL = "https://panchang.devdarsha.com/v1/panchang/daily";

const API_KEY = process.env.DEVDARSHA_API_KEY;
const CITY_ID = process.env.DEVDARSHA_CITY_ID || "ujjain";
const DATE    = process.env.DEVDARSHA_DATE    || "2026-04-15";

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
    // `city_id` works on every plan. To look up by coordinates instead
    // (lat/lon) you need an Amethyst plan or higher.
    body: JSON.stringify({ date: DATE, city_id: CITY_ID }),
  });

  const result = await res.json();

  if (!res.ok) {
    // Errors carry { error, message, docs_url }.
    console.error(`HTTP ${res.status} — ${result.error}: ${result.message}`);
    console.error(`See ${result.docs_url}`);
    process.exit(1);
  }

  // The payload lives under `data`; `meta` and `dev_notes` wrap it.
  const data = result.data;

  console.log(`Panchang for ${data.city} on ${data.date} (${data.weekday})`);
  console.log(`  Tithi:     ${data.tithi[0]?.name} (${data.tithi[0]?.paksha})`);
  console.log(`  Nakshatra: ${data.nakshatra[0]?.name}`);
  console.log(`  Sunrise:   ${data.sun.rise}`);
  console.log(`  Sunset:    ${data.sun.set}`);
  console.log(`  API version (meta): ${result.meta.version}`);
}

main().catch((err) => {
  console.error("Request failed:", err.message);
  process.exit(1);
});
