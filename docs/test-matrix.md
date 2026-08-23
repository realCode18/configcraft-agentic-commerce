# Test and performance matrix

This document records the reproducible quality gate for DestinX AI Commerce 0.10.0. Measurements are development-environment reference values, not hosting guarantees.

## Supported-version matrix

| Area | Automated coverage |
| --- | --- |
| PHP | 7.4, 8.1, and 8.4 lint, coding standards, compatibility rules, and unit tests |
| WordPress minimum | WordPress 6.6, PHP 7.4, WooCommerce 8.2.5, full integration smoke test |
| WordPress latest | WordPress 7.1, latest stable WooCommerce, full integration and Multisite smoke tests |
| Previous WooCommerce major | WordPress 7.1, WooCommerce 10.9.4, full integration smoke test |
| Upgrade | Persistent fixtures created on plugin 0.6.0 and 0.7.0, then updated to the current build |
| Lifecycle | Clean activation, schema upgrade, deactivate/reactivate, site and network uninstall, and post-network-activation site creation |

The integration smoke test covers simple and variable products, the external-pricing contract, persisted pricing metadata, exact-SKU collision handling, catalog reconciliation, retries, stale scans, atomic snapshots, search, combined filters, pagination, protected CSV export, Store Readiness, permissions, invalid nonces, accessibility markup, and the product editor panel. Unit fixtures additionally cover virtual and downloadable products.

## Catalog scale gate

The scale test progressively grows one catalog in the same WordPress/WooCommerce environment. Test fixture insertion time is excluded; every product is read and evaluated by the runtime plugin through WooCommerce CRUD. The scan batch size is deliberately raised to its supported maximum of 100 to exercise the highest per-request load.

Reference run on 23 August 2026:

| Products | Batches | Total scan | Slowest batch |
| ---: | ---: | ---: | ---: |
| 0 | 0 | 0.021 s | 0 s |
| 1 | 1 | 0.057 s | 0.033 s |
| 26 | 1 | 0.196 s | 0.174 s |
| 500 | 5 | 2.606 s | 0.521 s |
| 5,000 | 50 | 28.891 s | 0.662 s |

After the 5,000-product scan, the results dashboard rendered in 0.071 seconds using 24 database queries. Peak process memory was 126,877,696 bytes, below the 128 MiB project ceiling of 134,217,728 bytes. Every batch remained below the 10-second project ceiling, and dashboard rendering remained below 1.5 seconds.

## Lifecycle invariants

The automated upgrade test must prove all of the following:

- the plugin remains active while its files are replaced;
- the versioned database schema and last completed snapshot survive the update;
- deactivation cancels every Action Scheduler and WP-Cron batch;
- deactivation removes start and process locks;
- an incomplete staging snapshot is discarded and marked failed with an actionable reason;
- the last completed snapshot remains visible during deactivation;
- reactivation succeeds and preserves the completed snapshot.

## Reproduction

CI commands and pinned Playground versions live in [`.github/workflows/ci.yml`](../.github/workflows/ci.yml). Local commands for the standard, Multisite, scale, and upgrade lifecycle runs are documented in the project [README](../README.md).
