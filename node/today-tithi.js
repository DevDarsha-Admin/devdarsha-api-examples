// Print just today's tithi — the smallest useful call.
//
// Run:
//   export DEVDARSHA_API_KEY=your_api_key_here
//   node node/today-tithi.js

const API_URL = "https://panchang.devdarsha.com/v1/panchang/daily";

const API_KEY = process.env.DEVDARSHA_API_KEY;
const CITY_ID = process.env.DEVDARSHA_CITY_ID || "ujjain";

if (!API_KEY) {
  console.error("Missing DEVDARSHA_API_KEY. Get a free key at https://platform.devdarsha.com");
  process.exit(1);
}

// Today's date in YYYY-MM-DD using IST, the API's default timezone.
const today = new Intl.DateTimeFormat("en-CA", {
  timeZone: "Asia/Kolkata",
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
}).format(new Date());

const res = await fetch(API_URL, {
  method: "POST",
  headers: {
    "X-Api-Key": API_KEY,           // key in header, not in body
    "Content-Type": "application/json",
  },
  body: JSON.stringify({ date: today, city_id: CITY_ID }), // city_id works on all plans
});

const result = await res.json();

if (!res.ok) {
  console.error(`HTTP ${res.status} — ${result.error}: ${result.message}`);
  process.exit(1);
}

const tithi = result.data.tithi[0];
console.log(`Today (${result.data.date}) in ${result.data.city}: ${tithi.name} — ${tithi.paksha} paksha`);
