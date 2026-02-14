# Warm Cloud Run Instance — Potential Improvement

Keeping the Cloud Run scraper instance warm (min-instances &gt; 0) can reduce cold-start latency by ~15–35 seconds. This document summarizes the cost/benefit trade-off.

## Current Configuration

| Setting | Value |
|--------|--------|
| Memory | 1 GiB |
| CPU | 1 vCPU |
| Timeout | 600 seconds |
| Min instances | 0 (scale to zero) |
| Region | europe-west1 |
| Image | Playwright base (~1–2 GB with Chromium) |

## Cold Start Impact

The Playwright container is heavy:

- **Container cold start** (pull, start): ~10–30 s
- **Node.js + dependencies**: ~2–5 s
- **Chromium launch**: ~5–15 s (occurs on every request, warm or cold)

Docs indicate first request after idle can take **30–60 seconds**. A warm instance avoids container + Node startup (~15–35 s). Chromium still launches on each request.

## Warm Instance Cost (1 vCPU, 1 GiB, 24/7)

Approximate idle billing (europe-west1):

- CPU (idle): ~$0.000024 / vCPU-second
- Memory (idle): ~$0.0000025 / GiB-second

Rough monthly total: **~$63–65** after free tier.

## When It May Be Worth It

| Imports/day | Imports/month | Recommendation |
|------------|---------------|----------------|
| &lt; 5 | &lt; 150 | Stay scale-to-zero |
| 5–20 | 150–600 | Consider only if latency is critical |
| 20–50 | 600–1,500 | Often justified |
| 50+ | 1,500+ | Usually justified |

## Middle Ground: Business Hours Only

Cloud Run cannot schedule min-instances natively. Workaround:

- **Cloud Scheduler** jobs: one to set `--min-instances=1` in the morning, one to set `0` at night
- **Savings**: ~24% if 8 h/day × 22 weekdays (extra setup and Scheduler cost)

## How to Enable

```bash
gcloud run deploy playwright-scraper \
  --min-instances 1 \
  # ... existing flags (--memory 1Gi, --timeout 600, etc.)
```

## Summary

For low-volume usage (&lt; 10 imports/day), the cold-start penalty is typically acceptable and the ~$63/month cost is hard to justify. For higher volume and latency-sensitive workflows, a warm instance is a viable option.
