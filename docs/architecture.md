# Architecture

The plugin is split into small services with explicit responsibilities:

1. `Pricing_Context` normalizes native, dynamic, quote-based, externally managed, and not-applicable pricing metadata.
2. `Pricing_Adapter_Registry` runs read-only compatibility adapters in deterministic priority order and isolates adapter failures.
3. Built-in adapters recognize supported customer-priced, quote, configurable-container, measurement, and add-on pricing paths.
4. `Product_Data_Extractor` converts WooCommerce products into a stable, filterable payload, applies the registry, then exposes the generic product-data filter.
5. `Product_Readiness_Evaluator` applies pure readiness rules and returns machine-readable issues.
6. `Catalog_Auditor` provides the non-persistent quick catalog preview.
7. `Background_Audit` processes the full published catalog in bounded batches.
8. `Audit_Repository` persists product results in the plugin-owned audit table.
9. `Issue_Catalog` maps stable issue codes to translated labels and practical guidance.
10. `Product_Meta_Box` shows live readiness guidance in the WooCommerce product editor.
11. `Store_Data_Extractor` normalizes local WordPress and WooCommerce configuration.
12. `Store_Readiness_Evaluator` applies a versioned, product-score-independent checklist.
13. `Store_Issue_Catalog` maps store checks to translated guidance and local settings links.
14. `Catalog_Csv_Exporter` streams permission-checked, nonce-protected, spreadsheet-safe filtered exports without creating public files.
15. `Admin_Page` renders progress, aggregate metrics, store checks, searchable and filterable product results, pricing provenance, and snapshot freshness without exposing a public API.

The evaluator is kept independent from WooCommerce objects so its versioned rules can be unit tested and later reused by WP-CLI commands, read-only protocol adapters, and optional add-ons. Full scans use WooCommerce Action Scheduler when available and fall back to a single WordPress Cron event.

Each full scan writes to an isolated `scan_id`. The active snapshot pointer changes only after every batch succeeds, unpublished products are pruned, and one reconciliation pass handles catalog changes detected during processing. The previous snapshot therefore remains readable during queued, running, retrying, failed, and stale-scan states. Results, locks, schedules, and scan state are removed on uninstall.

## Product boundaries

- The free WordPress.org plugin is the primary product and the only owner of the core engine.
- The free plugin must remain fully functional without payment, an account, ConfigCraft, or a premium add-on.
- Premium code will not be committed to this public repository.
- The future Pro product is a separate dependent add-on. It extends public contracts and must not copy or replace the engine.
- The core never checks a Pro license and must not install or update premium code.
- Hosted features must require explicit opt-in before sending store data.
- Protocol adapters must remain separate from the normalized catalog audit model.
- AI platforms decide eligibility and ranking; the plugin must never promise placement or compliance.

The detailed ownership, bootstrap, compatibility, data integrity, and ConfigCraft boundaries are defined in [free-pro-architecture.md](free-pro-architecture.md).
