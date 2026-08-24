# MVP plan - DestinX AI Commerce for WooCommerce

## Product record

| Item | Decision |
| --- | --- |
| Public name | DestinX AI Commerce for WooCommerce |
| Short name | DestinX AI Commerce |
| WordPress.org slug | `destinx-ai-commerce` |
| Text domain | `destinx-ai-commerce` |
| Publisher | DestinX |
| Required plugin | WooCommerce |
| License | GPL-2.0-or-later |
| Free release line | 0.13.0 toward WordPress.org 1.0.0 |
| Pro release line | Separately installed add-on; 0.2.0 assisted remediation |
| Product language | English source, UI, readme, public documentation, screenshots, and commercial copy |

## Strategic decision

Build one complete main product: a WooCommerce plugin that measures whether catalog and store configuration are clear, complete, and usable by automated search and shopping systems.

The WordPress.org Free plugin is not a demo. It owns the normalization engine, scoring rules, scans, snapshots, results, guidance, Store Readiness, filters, and export. It must remain fully usable without payment, account, key, Pro, ConfigCraft, or an AI provider.

Pro is a separate add-on that requires Free and consumes its versioned read-only API. Pro may accelerate work through reviewable proposals, approved writes, safe bulk operations, monitoring, and optional services. It must not copy the Free scanner, evaluator, audit tables, or WordPress.org assets.

ConfigCraft Suite owns commerce, annual licensing, private Pro delivery, renewal, and future metered services. It is not part of the required Free runtime.

## Customer promise

Within minutes, a merchant should be able to answer:

1. Which products are difficult for machines to understand?
2. Which issues create the greatest catalog risk?
3. What should be corrected first?
4. Can the correction be reviewed and reversed safely?
5. Did a new scan prove that the catalog improved?

The product improves data quality and evidence. It does not guarantee indexing, placement, ranking, recommendation, traffic, or sales on ChatGPT, Google, Gemini, or any third-party service.

## Target users

### Store owner

Needs a clear diagnosis, an ordered list of priorities, and practical instructions without learning feed or protocol terminology.

### E-commerce manager

Needs filters, repeatable remediation, before/after evidence, and export across a changing catalog.

### WooCommerce agency

Needs a reproducible method, compatibility evidence, and client-ready reporting. Centralized multi-store management remains outside the first commercial release.

## Free 1.0 scope

### Product audit

- Quick preview of the latest published products.
- Complete published-catalog scan in bounded batches.
- Atomic active snapshots: partial replacement results are never published.
- Deduplicated scheduling, retries, locks, heartbeat, stale-scan recovery, and catalog reconciliation.
- Deterministic score from 0 to 100 with stable finding codes, severity, penalty, and model version.
- Product guidance in the WooCommerce editor.

### Product checks

| Area | Rule |
| --- | --- |
| Title | Specific enough to identify the product |
| Description | Substantial main description |
| Price | Native or verified external/dynamic availability |
| Image | Featured image present |
| Category | Product assigned to a category |
| Brand | Recognized taxonomy, attribute, or supported metadata |
| Identifier | GTIN/EAN/UPC/ISBN or another global identifier when available |
| SKU | Stable internal reference |
| Attributes | Structured product properties present |
| Shipping | Weight or complete dimensions for physical products |
| Variations | At least one valid variation and complete price/attribute state |
| Purchasability | WooCommerce purchase state consistent with the pricing owner |
| Availability | Recognized WooCommerce stock state |

Virtual and downloadable products do not receive physical shipping findings. Variable products are evaluated through their children without loading the full catalog into memory.

### Pricing compatibility

Free owns a deterministic, failure-isolated pricing adapter registry. Built-in adapters cover supported paths for:

- Name Your Price;
- request-a-quote;
- call-for-price;
- composite products;
- bundles;
- mix and match;
- measurement pricing;
- product add-ons.

A provider must explicitly declare price availability and whether it replaces WooCommerce purchase-state rules. An empty native price is not automatically treated as valid.

### Catalog operations

- Search by partial title and exact SKU.
- Combined status, finding, and category filters.
- Matching-result counts and pagination.
- Spreadsheet-safe UTF-8 CSV export protected by capability and nonce.
- Snapshot timestamp and scoring-model version.
- Empty, queued, running, retrying, complete, failed, and stale states.

### Store Readiness

A separate checklist covers HTTPS, search visibility, permalinks, WooCommerce location/currency, required store pages, privacy/terms/refund information, REST availability, published products, and shipping configuration.

Store checks remain separate from product scores and do not claim legal compliance.

### Privacy and security

- No customer, order, payment, checkout, or visitor data is read.
- No telemetry, cookies, remote assets, account, or external runtime requests.
- All audit data remains in the local WordPress database.
- Every administration action requires `manage_woocommerce` and a valid nonce.
- Output is escaped at the final rendering boundary.
- CSV cells are neutralized against formula execution.
- Uninstall removes Free tables, options, locks, and scheduled work, including Multisite cleanup.

### Public extension API

Free 0.13.0 exposes read-only API 1.1.0:

- snapshot metadata;
- aggregate summary;
- bounded result pages;
- exact result lookup by product ID;
- normalized scan state;
- plugin and contract versions.

The API exposes no write method, database object, scheduler object, or license state.

## Pro 0.2 scope

Pro 0.2 is the first assisted-remediation milestone:

- requires Free 0.13.0 and API 1.1.0;
- builds a priority plan from the completed Free snapshot;
- resolves one exact product result;
- offers only allow-listed fields connected to current findings;
- creates a local proposal without writing the product;
- shows every before and after value;
- requires explicit field selection and final apply action;
- writes through WooCommerce CRUD only;
- stops the complete change set when any original value changed;
- stores a local audit record;
- permits idempotent undo while written values remain unchanged;
- keeps safety-only undo available after license expiration.

Pro 0.2 does not automate images, categories, attributes, variation structure, or purchasability. Those findings route to the native WooCommerce editor until a safe structured workflow is implemented.

Optional AI is not part of Pro 0.2. Future AI must be opt-in, show source data and diff, enforce timeout and quota, disclose provider/retention, and never write autonomously.

## User journeys

### Free full scan

1. Merchant opens WooCommerce > AI Commerce.
2. Merchant starts the full-catalog scan.
3. The queue processes bounded batches.
4. The previous completed snapshot remains visible.
5. The new snapshot becomes active only after complete reconciliation.
6. The merchant filters findings and opens products or exports evidence.

### Pro assisted remediation

1. Merchant opens WooCommerce > AI Commerce Pro.
2. Pro reads the priority plan from the Free snapshot.
3. Merchant selects a product and verified fields.
4. Pro stores an immutable before/after preview without writing.
5. Merchant confirms apply.
6. Pro compares every live value with the recorded before value.
7. Pro saves the approved fields once through WooCommerce CRUD.
8. Merchant can undo only while current values still match the applied values.
9. Merchant runs Free again to measure the result.

## Architecture services

### Free

- `Pricing_Context`
- `Pricing_Adapter_Registry`
- pricing adapters
- `Product_Data_Extractor`
- `Product_Readiness_Evaluator`
- `Catalog_Auditor`
- `Background_Audit`
- `Audit_Repository`
- `Public_API`
- `Issue_Catalog`
- `Store_Data_Extractor`
- `Store_Readiness_Evaluator`
- `Catalog_Csv_Exporter`
- `Product_Meta_Box`
- `Admin_Page`

### Pro

- `License_Manager`
- `Action_Plan`
- `Remediation_Database`
- `Remediation_Repository`
- `Proposal_Factory`
- `Remediation_Service`
- `Pro_Page`

## Quality matrix

| Area | Required coverage |
| --- | --- |
| PHP | 7.4, 8.1, and 8.4 |
| WordPress | Minimum supported, current, and latest supported release |
| WooCommerce | Minimum supported, previous major, and latest stable |
| Catalog size | 0, 1, 26, 500, and 5,000 products |
| Product type | Simple, variable, virtual, downloadable |
| Pricing | Native, dynamic, quote, external, container, measurement, add-on |
| Scan | Initial, replacement, retry, stale recovery, modified/deleted product |
| Permission | Administrator, shop manager, unauthorized user |
| Lifecycle | Clean install, upgrade, deactivate/reactivate, uninstall, Multisite |
| Free/Pro | Compatible activation, missing dependency, expired license, outage grace |
| Remediation | Draft, apply, duplicate apply, concurrent edit, undo, duplicate undo, expired-license recovery |
| Language | PHP/JS/UI/readmes/docs/POT/screenshots in English source |

## Release gates

### Free gate

- Composer validation succeeds.
- PHP lint succeeds on every distributed PHP file.
- PHPCS has zero errors and zero warnings.
- PHPUnit succeeds.
- WordPress/WooCommerce Playground smoke tests succeed.
- Scale, upgrade, Multisite, and pricing compatibility suites succeed.
- Plugin Check strict succeeds on the extracted distribution ZIP.
- The ZIP contains only runtime files, license, uninstall, readme, POT, and required source.
- No remote request, updater, premium code, or license path exists in Free.
- UI, readme, public docs, and screenshots are English.

### Pro 0.2 gate

- Composer validation, lint, PHPCS, and PHPUnit succeed.
- Dual-plugin Playground test succeeds with the exact Free/API requirement.
- Proposal creation cannot modify a product.
- Apply requires capability, nonce, active entitlement, allow-list, and current-value match.
- Undo requires capability, nonce, applied state, and current-value match.
- Concurrent edits are never overwritten.
- Safety undo remains available after license expiration.
- License payload contains no catalog, customer, order, payment, or proposal data.
- Disabling Pro leaves Free and the storefront available.

## Roadmap after Pro 0.2

### Pro 0.3 - safe bulk

- finding/product selection;
- proposal generation in bounded batches;
- review and exclusions;
- resumable apply jobs;
- retry limits and concurrency controls;
- batch and product-level undo;
- Free re-scan and result delta.

### Pro 0.4 - monitoring

- scheduled Free scans through a documented contract;
- model-versioned local history;
- significant-regression alerts;
- before/after management report;
- optional ConfigCraft multi-store reporting with explicit consent.

### Deferred

- autonomous AI writes;
- checkout, payment, coupon, or order creation;
- customer/order/payment transmission;
- automatic order attribution;
- unlimited AI;
- speculative protocol coverage without a stable specification;
- multi-store catalog synchronization.

## Final product definition

The MVP is complete when a merchant can install Free, scan an entire catalog reliably, understand every score, locate and export every finding, use the plugin without any commercial dependency, and optionally use Pro to preview, apply, undo, and prove safe product-data improvements.
