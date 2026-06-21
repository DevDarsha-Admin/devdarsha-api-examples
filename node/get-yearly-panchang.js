// Get a full calendar year of Panchang data and print a per-month summary.
//
// Run:
//   export DEVDARSHA_API_KEY=your_api_key_here   (or set it in .env and source it)
//   node node/get-yearly-panchang.js
//
// Requires Node 18+ (uses the built-in global `fetch`). No npm install needed.
//
// The yearly endpoint needs a Sapphire plan or higher. It fans out to all 12
// months (X-Quota-Cost: 12) and is metered by the coverage meter — see README.

const API_URL = "https://panchang.devdarsha.com/v1/panchang/yearly";

const API_KEY = process.env.DEVDARSHA_API_KEY;
const CITY_ID = process.env.DEVDARSHA_CITY_ID || "ujjain";
const YEAR    = process.env.DEVDARSHA_YEAR    || "2026"; // 1900–2100

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
    // `year` must be an integer between 1900 and 2100.
    // `city_id` works on every plan; lat/lon coordinate lookups need Amethyst+.
    body: JSON.stringify({ year: Number(YEAR), city_id: CITY_ID }),
  });

  const result = await res.json();

  if (!res.ok) {
    // Errors carry { error, message, docs_url }.
    console.error(`HTTP ${res.status} — ${result.error}: ${result.message}`);
    console.error(`See ${result.docs_url}`);
    process.exit(1);
  }

  // The payload lives under `data`; `data.months` holds the 12 months in order.
  const data = result.data;

  console.log(`Yearly Panchang for ${CITY_ID} — ${data.year} (${data.months.length} months)`);
  console.log(`  Quota cost: ${res.headers.get("X-Quota-Cost")} | Coverage remaining: ${res.headers.get("X-Coverage-Remaining")}/${res.headers.get("X-Coverage-Limit")}`);
  for (const m of data.months) {
    // A partial month carries `error` instead of day data (X-Degraded: true).
    const summary = m.error ? `(${m.error})` : `${m.total_days ?? m.days?.length ?? "?"} days`;
    console.log(`  ${String(m.month).padStart(2, "0")} ${m.month_name}: ${summary}`);
  }
}

main().catch((err) => {
  console.error("Request failed:", err.message);
  process.exit(1);
});
