# Architecture

The plugin is split into small services with explicit responsibilities:

1. `Product_Data_Extractor` converts WooCommerce products into a stable, filterable payload.
2. `Product_Readiness_Evaluator` applies pure readiness rules and returns machine-readable issues.
3. `Catalog_Auditor` provides the non-persistent quick catalog preview.
4. `Background_Audit` processes the full published catalog in bounded batches.
5. `Audit_Repository` persists product results in the plugin-owned audit table.
6. `Issue_Catalog` maps stable issue codes to translated labels and practical guidance.
7. `Product_Meta_Box` shows live readiness guidance in the WooCommerce product editor.
8. `Admin_Page` renders progress, aggregate metrics, and paginated results without exposing a public API.

The evaluator is kept independent from WooCommerce objects so its rules can be unit tested and later reused by WP-CLI commands, read-only protocol adapters, and optional add-ons. Full scans use WooCommerce Action Scheduler when available and fall back to a single WordPress Cron event. Results and scan state are removed on uninstall.

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
