=== DestinX AI Commerce for WooCommerce ===
Contributors: destinx
Tags: woocommerce, artificial intelligence, product feed, catalog, ecommerce
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.13.4
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

A first-run guide explains the complete workflow with four concrete steps and direct actions. Each user can hide and reopen that guide. A separate, contextual banner explains the independently installed Pro add-on, keeps the Free/Pro boundary explicit, appears only on the plugin screen, and can be dismissed for 24 hours.

All analysis runs locally. This version does not contact external services and does not collect telemetry.

The plugin does not read customer, order, payment, or checkout data. It does not set cookies or load remote assets. It stores catalog audit results, operational scan state, and per-user dashboard display preferences in the local WordPress database, and removes those records on uninstall, including across WordPress Multisite networks.

Important: The plugin improves catalog data quality. It does not guarantee placement, ranking, recommendation, or legal compliance on any third-party AI platform.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin directory to `/wp-content/plugins/` or install the ZIP through Plugins > Add New.
3. Activate DestinX AI Commerce for WooCommerce.
4. Select Open AI Commerce on the Plugins screen, or open WooCommerce > AI Commerce.
5. In Getting started, select Go to full scan, then select Scan full catalog.
6. When the scan finishes, review the lowest-scoring products first.
7. Select a product name, correct the findings in its WooCommerce editor, save it, and scan again.

== Frequently Asked Questions ==

= Does the plugin send product data to an AI provider? =

No. The current Free plugin performs its audit locally and makes no external requests.

= What data does the plugin collect or store? =

It stores product IDs, readiness scores, finding codes, normalized pricing context, a product-data hash, scoring-model version, timestamps, scan state, and the current user's dashboard display preferences in the local WordPress database. It does not read or store customer, order, payment, checkout, or visitor data, and it does not add cookies or telemetry.

= What happens to plugin data on uninstall? =

Uninstall removes the plugin's audit tables, options, locks, scheduled work, and per-user dashboard display preferences. On WordPress Multisite, site data is cleaned separately and shared user preferences are removed once. Deactivation alone preserves the latest audit.

= Is the optional add-on required? =

No. The WordPress.org plugin is the complete Free engine. The Pro banner is informational, appears only on WooCommerce > AI Commerce, can be hidden for 24 hours, and does not lock or limit any Free feature.

= Does it guarantee that ChatGPT or Gemini will show my products? =

No. It identifies catalog quality issues that can make products difficult for machines to understand, but third-party platforms control their own eligibility and ranking.

= How many products are scanned? =

The dashboard initially previews the latest 25 published products. Select Scan full catalog to process every published product in small background batches and persist the results locally.

== Screenshots ==

1. Four-step Getting started guide and full-scan control.
2. Catalog readiness summary, full-scan progress, and product findings.
3. Search, status, finding, and category filters with secure CSV export.
4. Store readiness checklist with direct links to relevant local settings.
5. Product editor panel with score and remediation guidance.

== Changelog ==

= 0.13.4 =

* Replaced the generic add-on note with a factual, dismissible DestinX AI Commerce Pro banner.
* Explained the Pro proposal, before-and-after review, approved apply, conflict detection, audit, and undo workflow.
* Kept the commercial link contextual, untracked, and limited to the Free plugin screen.

= 0.13.3 =

* Added a four-step first-run guide with direct actions, per-user dismissal, and a persistent reopen control.
* Added an Open AI Commerce link to the Plugins screen without forcing an activation redirect.
* Added a contextual optional add-on note that can be dismissed for 24 hours per user and never limits the Free plugin.
* Removed the optional note's user metadata during uninstall, including on Multisite.
* Strengthened the WordPress.org package audit against dormant commercial code and shortened this readme below the repository limit.

= 0.13.2 =

* Applied catalog filters, clearing, and pagination without reloading the full WordPress admin page.
* Preserved the current viewport while results update, with an accessible loading state and a full-page fallback when JavaScript is unavailable.

= 0.13.1 =

* Prevented false purchasability findings for external and grouped WooCommerce products.
* Prevented false shipping-data findings on products fulfilled outside the store or through grouped children.
* Added permanent mixed-catalog coverage for virtual, downloadable, external, grouped, and draft products.

Earlier release history is included in `changelog.txt` inside the plugin package.

== Upgrade Notice ==

= 0.13.4 =

Clarifies the separately installed Pro workflow in one dismissible, contextual banner without changing or limiting any Free feature.
