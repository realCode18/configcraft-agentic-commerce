# Architecture

The plugin starts with four small services:

1. `Product_Data_Extractor` converts WooCommerce products into a stable, filterable payload.
2. `Product_Readiness_Evaluator` applies pure readiness rules and returns machine-readable issues.
3. `Catalog_Auditor` loads products and aggregates product-level results.
4. `Admin_Page` renders the WooCommerce dashboard without exposing a public API.

The evaluator is kept independent from WooCommerce objects so its rules can be unit tested and later reused by background jobs, WP-CLI commands, REST endpoints, and hosted ConfigCraft services.

## Product boundaries

- The free WordPress.org plugin must remain useful without payment.
- Premium code will not be committed to this public repository.
- Hosted features must require explicit opt-in before sending store data.
- Protocol adapters must remain separate from the normalized catalog audit model.
- AI platforms decide eligibility and ranking; the plugin must never promise placement or compliance.
