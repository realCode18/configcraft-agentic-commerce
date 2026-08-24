# Roadmap

The only active objective is a complete, local, WordPress.org-ready 1.0.0 release of DestinX AI Commerce for WooCommerce.

## Completed foundation

- [x] Installable WooCommerce plugin.
- [x] Deterministic product scoring and structured findings.
- [x] Quick preview and full-catalog background scan.
- [x] Persistent, paginated results.
- [x] Product editor remediation panel.
- [x] Unit, coding-standard, and WordPress Playground tests.
- [x] DestinX product identity and WordPress.org slug decision.

## 1.0.0 submission track

- [x] Atomic scan snapshots, locks, retries, and stale-scan recovery.
- [x] Complete simple/variable/virtual/downloadable product rules.
- [x] Store readiness checklist.
- [x] Dashboard search and filters.
- [x] Secure CSV export.
- [x] Security, privacy, accessibility, and i18n audit.
- [x] Multisite and WooCommerce compatibility declarations.
- [x] Expanded version, upgrade-lifecycle, performance, and catalog-size test matrix.
- [x] Plugin Check with zero errors and zero warnings in strict mode.
- [x] Stable pricing-adapter registry and verified compatibility contract.
- [ ] WordPress.org icons, banners, screenshots, readme, and release ZIP.
- [ ] Real staging test and 1.0.0 submission.

The detailed scope, schedule, gates, and acceptance criteria are in [mvp-plan.md](mvp-plan.md). The release cannot be submitted until every item in [wordpress-org-submission-checklist.md](wordpress-org-submission-checklist.md) is verified.

## Post-MVP parking lot

No post-MVP item is developed before the WordPress.org release is stable.

### Free 1.1–1.2

- [ ] Manual fix workspace with preview, approval, audit log, and undo.
- [ ] Bounded local scan history and before/after comparison.
- [ ] Product exclusions and public-visibility test suite.
- [ ] Read-only `llms.txt` and JSON catalog with atomic snapshots.
- [ ] Structured-data diagnostics and agent-view preview.
- [ ] Stable rule, dashboard-module, and export-column extension registries.

### Separate Pro add-on

- [ ] AI suggestions and rewrites with human approval.
- [ ] Safe bulk workflows.
- [ ] Scheduled monitoring, alerts, and long-term trends.
- [ ] Advanced protocol adapters after specification validation.
- [ ] Privacy-aware agent traffic and order attribution.
- [ ] ConfigCraft multi-store, team, reporting, and automation services.

Free remains the required engine. The Pro add-on will depend on it and will never duplicate or replace it. See [competitive-analysis.md](competitive-analysis.md) and [free-pro-architecture.md](free-pro-architecture.md).
