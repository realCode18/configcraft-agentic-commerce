=== DestinX AI Commerce for WooCommerce ===
Contributors: realcode18
Tags: woocommerce, artificial intelligence, product feed, catalog, ecommerce
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.13.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit WooCommerce product catalogs for AI discovery and agentic commerce readiness.

== Description ==

DestinX AI Commerce for WooCommerce finds incomplete or ambiguous WooCommerce product data that may prevent AI shopping assistants from understanding and recommending products.

The plugin adds an AI Commerce screen under WooCommerce. It provides a quick preview of the latest products and a background scan of the full published catalog for:

* Product title and description quality.
* Price and featured image availability.
* Category, brand, SKU, and global identifier coverage.
* Product attributes and physical shipping data.
* Variable completeness, variation prices, attributes, and purchasability.

Every product receives a score and a list of actionable findings. Full-scan results are saved locally, ordered by the lowest score, and split into manageable pages. A product editor panel explains how to correct each finding.

Dynamic and externally managed prices are evaluated through a local compatibility registry. The plugin automatically recognizes supported Name Your Price, request-a-quote, call-for-price, composite, bundle, mix-and-match, measurement, and product add-on pricing paths. The dashboard shows whether pricing was read natively, verified automatically, or declared by another integration.

Saved results can be searched by partial product name or exact SKU and filtered by status, finding, and product category. The current result set can be exported as a UTF-8 CSV. Export cells are neutralized against spreadsheet formulas, and the download requires the same WooCommerce management permission and a valid WordPress nonce.

A separate Store Readiness checklist checks HTTPS, search visibility, permalinks, WooCommerce location and currency, cart and checkout pages, privacy, terms, refunds and returns, REST availability, published products, and shipping configuration. Store checks never change product scores and do not claim legal compliance.

Full scans run in small background batches through WooCommerce Action Scheduler, with WordPress Cron as a fallback. The previous completed snapshot remains visible until the replacement scan finishes. Duplicate jobs, temporary failures, catalog changes, and stale scans are handled without publishing partial results.

All analysis runs locally. This version does not contact external services and does not collect telemetry.

The plugin does not read customer, order, payment, or checkout data. It does not set cookies or load remote assets. It stores only catalog audit results and operational scan state in the local WordPress database, and removes those records on uninstall, including across WordPress Multisite networks.

Important: The plugin improves catalog data quality. It does not guarantee placement, ranking, recommendation, or legal compliance on any third-party AI platform.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP through Plugins > Add New.
3. Activate DestinX AI Commerce for WooCommerce.
4. Open WooCommerce > AI Commerce.

== Frequently Asked Questions ==

= Does the plugin send product data to an AI provider? =

No. The current Free plugin performs its audit locally and makes no external requests.

= What data does the plugin collect or store? =

It stores product IDs, readiness scores, finding codes, normalized pricing context, a product-data hash, scoring-model version, timestamps, and scan state in the local WordPress database. It does not read or store customer, order, payment, checkout, or visitor data, and it does not add cookies or telemetry.

= What happens to plugin data on uninstall? =

Uninstall removes the plugin's audit tables, options, locks, and scheduled work. On WordPress Multisite, cleanup runs separately for every site in the network. Deactivation alone preserves the latest audit.

= Does it guarantee that ChatGPT or Gemini will show my products? =

No. It identifies catalog quality issues that can make products difficult for machines to understand, but third-party platforms control their own eligibility and ranking.

= How many products are scanned? =

The dashboard initially previews the latest 25 published products. Select Scan full catalog to process every published product in small background batches and persist the results locally.

== Screenshots ==

1. Catalog readiness summary, full-scan progress, and product findings.
2. Search, status, finding, and category filters with secure CSV export.
3. Store readiness checklist with direct links to relevant local settings.
4. Product editor panel with score and remediation guidance.

== Changelog ==

= 0.13.2 =

* Applied catalog filters, clearing, and pagination without reloading the full WordPress admin page.
* Preserved the current viewport while results update, with an accessible loading state and a full-page fallback when JavaScript is unavailable.

= 0.13.1 =

* Prevented false purchasability findings for external and grouped WooCommerce products.
* Prevented false shipping-data findings on products fulfilled outside the store or through grouped children.
* Added permanent mixed-catalog coverage for virtual, downloadable, external, grouped, and draft products.

= 0.13.0 =

* Extended the read-only add-on API with exact product-result lookup from the active completed snapshot.
* Kept the extension boundary local, bounded, and free of write operations or remote requests.

= 0.12.0 =

* Added a versioned, read-only extension API for compatible add-ons.
* Added a public engine-ready hook and an atomic full-scan completion event.
* Exposed bounded snapshot results, summary data, metadata, and scan state without exposing mutable internals.

= 0.11.1 =

* Started the first bounded catalog batch directly from the authenticated dashboard action.
* Removed the initial zero-progress wait caused by delayed Action Scheduler or WP-Cron runners.
* Added accurate success feedback when a small catalog finishes before the dashboard reloads.

= 0.11.0 =

* Added a deterministic, failure-isolated pricing adapter registry for third-party extensions.
* Added verified compatibility for Name Your Price, YITH Request a Quote, Call for Price, Composite Products, Product Bundles, Mix and Match, Measurement Price Calculator, and Product Add-Ons.
* Distinguished extensions that replace WooCommerce purchase rules from extensions that retain native base-price and variation validation.
* Added pricing verification details to catalog results, product panels, stored snapshots, and CSV exports.
* Added unit and real-plugin integration tests for the pricing compatibility layer.

= 0.10.0 =

* Added a public pricing-context contract for dynamic, quote-based, and externally managed product prices.
* External pricing engines can now prevent false missing-price and purchasability findings by explicitly declaring price availability.
* Added visible pricing-source context to catalog results, product panels, and CSV exports.
* Changed SKU lookup to exact matching while preserving partial product-title search.

= 0.9.0 =

* Redesigned the AI Commerce dashboard with a spacious, Apple-inspired interface aligned with the ConfigCraft visual system.
* Added a clearer dashboard hero, privacy-first service information, softer metric cards, and more focused scan controls.
* Refined store checks, catalog filters, tables, status pills, buttons, focus states, and responsive layouts.
* Updated the product editor readiness panel to use the same visual language.

= 0.8.0 =

* Added automated compatibility coverage for WordPress 6.6 through 7.1 and WooCommerce 8.2.5, 10.9.4, and the latest stable release.
* Added progressive catalog tests for 0, 1, 26, 500, and 5,000 products with batch, dashboard, query, and memory budgets.
* Added upgrade tests from versions 0.6.0 and 0.7.0, including data-preserving deactivation and reactivation.
* Deactivation now cancels every pending scan action, clears scan locks, and discards only incomplete staging results.
* Expanded scoring tests for physical, virtual, downloadable, purchasability, shipping, and stock-status behavior.

= 0.7.0 =

* Added network-wide activation, new-site initialization, and complete Multisite uninstall cleanup.
* Declared WooCommerce HPOS compatibility and added tested WooCommerce version headers.
* Added an official translation template and documented its reproducible WP-CLI command.
* Added accessible live status, progress labeling, table-region semantics, and descriptive control names.
* Added pause and resume controls for automatic scan refresh, with reduced-motion support.
* Documented the exact local data inventory, privacy boundaries, and uninstall behavior.
* Added automated WooCommerce compatibility, accessibility markup, Multisite lifecycle, and uninstall tests.
* Added official WordPress Plugin Check to the distribution-package CI gate.

= 0.6.0 =

* Added product-name and SKU search across the active full-catalog snapshot.
* Added combinable status, finding, and product-category filters.
* Added matching-result counts and filter-preserving pagination.
* Added protected UTF-8 CSV export for the current filtered result set.
* Added spreadsheet formula-injection protection to exported cells.
* Added visible snapshot freshness and scoring-model information.
* Added SKU context directly below each catalog result.

= 0.5.0 =

* Added a 15-point technical Store Readiness checklist separate from product scoring.
* Added checks for HTTPS, indexing visibility, permalinks, location, address, and currency.
* Added checks for WooCommerce cart, checkout, account, privacy, terms, and refund pages.
* Added published-product, REST API, and physical-store shipping checks.
* Added digital-only handling so shipping is marked not applicable when appropriate.
* Added direct remediation guidance and local settings links for every problem.

= 0.4.0 =

* Added atomic scan snapshots with a stable active-result pointer.
* Added Action Scheduler and WP-Cron job deduplication.
* Added expiring locks, bounded retries, heartbeats, and stale-scan recovery.
* Added catalog reconciliation when products change during a scan.
* Added migration of pre-snapshot audit results without losing the last valid scan.
* Added product data hashes and scoring model version 1.0.0.
* Expanded variable-product, variation, stock-status, and purchasability checks.

= 0.3.0 =

* Renamed the plugin to DestinX AI Commerce for WooCommerce.
* Aligned the plugin slug, text domain, namespace, hooks, and internal prefixes.
* Added the detailed WordPress.org MVP and submission plan.

= 0.2.0 =

* Added full-catalog scans using Action Scheduler or WordPress Cron.
* Added persistent, paginated results ordered by lowest readiness score.
* Added scan progress and aggregate status totals.
* Added product editor remediation guidance.
* Added complete cleanup on uninstall.

= 0.1.0 =

* Initial development release.
* Added product readiness scoring.
* Added the WooCommerce AI Commerce dashboard.
* Added checks for core catalog fields and variable products.
