// Build a festival calendar across a date range.
//
// NOTE: there is no dedicated "festival calendar" endpoint. This script loops
// over the daily Panchang endpoint, one call per day, and collects any
// festivals each day reports. We pass faith_filter=hindu so only Hindu
// festivals come back; change or drop it to widen the results.
//
// Run:
//   export DEVDARSHA_API_KEY=your_api_key_here
//   node node/festival-calendar-range.js 2026-04-01 2026-04-30

const API_URL = "https://panchang.devdarsha.com/v1/panchang/daily";

const API_KEY = process.env.DEVDARSHA_API_KEY;
const CITY_ID = process.env.DEVDARSHA_CITY_ID || "ujjain";

if (!API_KEY) {
  console.error("Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com");
  process.exit(1);
}

// Date range from argv, defaulting to a one-week window.
const startArg = process.argv[2] || "2026-04-12";
const endArg   = process.argv[3] || "2026-04-18";

function* eachDay(start, end) {
  const cur = new Date(start + "T00:00:00Z");
  const last = new Date(end + "T00:00:00Z");
  while (cur <= last) {
    yield cur.toISOString().slice(0, 10);
    cur.setUTCDate(cur.getUTCDate() + 1);
  }
}

async function fetchDay(date) {
  const res = await fetch(API_URL, {
    method: "POST",
    headers: {
      "X-Api-Key": API_KEY,            // key in header, not body
      "Content-Type": "application/json",
    },
    // city_id works on all plans; faith_filter narrows festivals to one tradition.
    body: JSON.stringify({ date, city_id: CITY_ID, faith_filter: "hindu" }),
  });

  const result = await res.json();
  if (!res.ok) {
    throw new Error(`HTTP ${res.status} on ${date} — ${result.error}: ${result.message}`);
  }
  return result.data;
}

async function main() {
  console.log(`Hindu festivals ${startArg} → ${endArg} (${CITY_ID}):\n`);
  for (const date of eachDay(startArg, endArg)) {
    const data = await fetchDay(date);
    // festivals.data is the array of festivals for that day (festivals.total = count).
    const festivals = data.festivals?.data || [];
    if (festivals.length > 0) {
      for (const f of festivals) {
        console.log(`  ${date}  ${f.name}`);
      }
    }
  }
  console.log("\nDone.");
}

main().catch((err) => {
  console.error("Request failed:", err.message);
  process.exit(1);
});
