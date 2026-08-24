# WordPress.org submission checklist - DestinX AI Commerce

This checklist is a release gate. Any unchecked mandatory item blocks the WordPress.org submission.

## Identity and metadata

- [ ] Public name is `DestinX AI Commerce for WooCommerce`.
- [ ] Requested slug is `destinx-ai-commerce`.
- [ ] Distribution directory is `destinx-ai-commerce`.
- [ ] Main file is `destinx-ai-commerce.php`.
- [ ] Text domain is `destinx-ai-commerce`.
- [ ] Public name and slug begin with the DestinX brand.
- [ ] `for WooCommerce` describes compatibility without suggesting Automattic ownership.
- [ ] `Plugin URI` is a unique product page or the dedicated public repository.
- [ ] `Author` and `Author URI` identify DestinX.
- [ ] Header version, `DXAIC_VERSION`, Stable tag, changelog, package name, and Git tag match.
- [ ] `Requires Plugins: woocommerce` is present.
- [ ] WordPress, PHP, `WC requires at least`, and `WC tested up to` values are verified.
- [ ] No `Update URI` or external updater exists in the WordPress.org build.

## Product completeness

- [ ] Free provides a complete local audit without payment, account, key, Pro, ConfigCraft, or AI provider.
- [ ] Full-catalog scanning is not artificially limited.
- [ ] Score, findings, guidance, filters, pagination, CSV, Store Readiness, and product panel work.
- [ ] Locked placeholders, dormant Pro code, and trialware paths are absent.
- [ ] Pro is a separate external add-on and is not bundled in the Free ZIP.
- [ ] Removing or expiring Pro cannot disable any Free capability.
- [ ] Upgrade links are factual, contextual, limited to the relevant plugin screen, and non-disruptive.

## License and source

- [ ] Plugin is declared GPL-2.0-or-later.
- [ ] Every distributed PHP, JavaScript, CSS, image, font, and library is GPL-compatible.
- [ ] `LICENSE.md` is included.
- [ ] No encrypted, obfuscated, minified-only, or machine-generated-only source is distributed.
- [ ] Human-readable source is included for any compiled asset.
- [ ] Composer production metadata is included only when required by runtime code.
- [ ] Development dependencies and vendor tools are excluded.
- [ ] No real key, token, credential, customer data, private package URL, or environment file exists.

## External services and privacy

- [ ] Free makes no runtime request to DestinX, ConfigCraft, AI providers, analytics, CDNs, or tracking services.
- [ ] Free loads no remote asset.
- [ ] Free sets no cookie and collects no telemetry.
- [ ] No external service is required for local catalog analysis.
- [ ] Readme privacy statements match observed network behavior.
- [ ] Customer, order, payment, checkout, and visitor data are not read.
- [ ] Stored audit fields are documented precisely.
- [ ] Uninstall removes all plugin-owned tables, options, locks, schedules, and Multisite data.
- [ ] Any future external service is substantive, opt-in, documented, and linked to Terms and Privacy before first use.

## Security

- [ ] Every PHP entry point exits when `ABSPATH` is not defined.
- [ ] Every state-changing action checks capability and nonce.
- [ ] Dashboard and product operations use `manage_woocommerce`.
- [ ] Inputs are unslashed, validated, sanitized, and length-bounded.
- [ ] Product IDs and numeric inputs are normalized before use.
- [ ] SQL uses `$wpdb->prepare()` and explicit formats.
- [ ] Table names use the WordPress prefix and trusted identifier placeholders.
- [ ] Output is escaped at the final rendering boundary.
- [ ] URLs use `esc_url()` and attributes use `esc_attr()`.
- [ ] Translation calls are escaped where output occurs.
- [ ] CSV export neutralizes formula prefixes and requires capability plus nonce.
- [ ] Redirects use `wp_safe_redirect()`.
- [ ] No dynamic include, eval, shell execution, unsafe unserialize, or arbitrary file write exists.
- [ ] Public endpoints, if introduced later, receive a separate visibility and privacy review.

## WordPress and WooCommerce APIs

- [ ] Product reads and writes use WooCommerce CRUD where appropriate.
- [ ] The plugin does not access order tables.
- [ ] HPOS compatibility is declared and tested.
- [ ] Action Scheduler is used when available; WP-Cron fallback is bounded and deduplicated.
- [ ] Activation uses the official activation hook.
- [ ] Deactivation preserves completed audit data and cancels incomplete scheduled work safely.
- [ ] Uninstall uses `uninstall.php` and supports site/network cleanup.
- [ ] Multisite activation and new-site initialization are tested.
- [ ] WooCommerce missing/inactive behavior is actionable and does not cause a fatal error.

## Database and scan integrity

- [ ] Schema installation and upgrades use `dbDelta()` correctly.
- [ ] Database version is stored and upgraded idempotently.
- [ ] Every full scan writes to an isolated `scan_id`.
- [ ] Previous completed snapshot remains visible while a replacement runs.
- [ ] Active pointer changes only after complete processing and reconciliation.
- [ ] Duplicate jobs cannot run the same scan concurrently.
- [ ] Process locks expire and stale scans recover.
- [ ] Retry count and batch size are bounded.
- [ ] Products changed, deleted, or unpublished during a scan are handled.
- [ ] No operation loads the complete product catalog into memory.
- [ ] A database failure produces a recoverable state instead of partial published results.

## Performance

- [ ] Quick preview is bounded.
- [ ] Full scan processes small batches.
- [ ] Result pages and exports use bounded database queries or streaming.
- [ ] Dashboard does not perform remote calls.
- [ ] Frontend and checkout requests load no scan or license work.
- [ ] 0, 1, 26, 500, and 5,000-product test gates pass.
- [ ] Reference execution and memory budgets are recorded in `test-matrix.md`.

## Accessibility and administration UI

- [ ] All controls are keyboard reachable.
- [ ] Every form control has a visible or screen-reader label.
- [ ] Scan status uses an accessible live region.
- [ ] Tables have headings and scrollable regions have an accessible label.
- [ ] Color is not the only status indicator.
- [ ] Focus styles are visible.
- [ ] Reduced-motion preference is respected.
- [ ] Empty, loading, complete, failed, and permission states are understandable.
- [ ] UI works at narrow viewport widths.
- [ ] Plugin notices remain scoped and dismissible when appropriate.

## Internationalization and English source policy

- [ ] Plugin header, UI, finding labels, guidance, readme, FAQ, changelog, POT msgids, public documentation, screenshots, and support copy are written in English.
- [ ] No hard-coded Italian or mixed-language source string remains.
- [ ] Every user-facing PHP string uses the `destinx-ai-commerce` text domain.
- [ ] Dynamic translation strings include translator comments where needed.
- [ ] POT is regenerated after the final source change.
- [ ] Future translations may localize the UI, but English remains the source and msgid language.

## Readme and claims

- [ ] `readme.txt` validates against WordPress.org readme rules.
- [ ] Short description is factual and within the allowed length.
- [ ] Tags are relevant and not stuffed.
- [ ] Installation instructions match the actual menu and dependency.
- [ ] FAQ covers data, AI-provider behavior, uninstall, catalog size, and no-ranking guarantee.
- [ ] Screenshots match the current UI.
- [ ] No claim guarantees indexing, placement, ranking, recommendation, traffic, revenue, or legal compliance.
- [ ] External platform trademarks are used descriptively.
- [ ] Prices, discounts, and commercial claims do not appear in the Free plugin package.

## Automated quality gate

- [ ] `composer validate --strict` passes.
- [ ] `composer lint` passes.
- [ ] `composer phpcs` passes with zero errors and zero warnings.
- [ ] `composer test` passes.
- [ ] WordPress/WooCommerce Playground smoke test passes.
- [ ] Minimum/current/latest compatibility matrix passes.
- [ ] Multisite lifecycle test passes.
- [ ] Upgrade from supported previous releases passes.
- [ ] Pricing adapter unit and real-plugin compatibility tests pass.
- [ ] Scale test passes.
- [ ] Plugin Check strict passes on the extracted distribution package with zero errors and zero warnings.
- [ ] PHP debug log contains no plugin warning, notice, or fatal error.

## Distribution ZIP audit

- [ ] ZIP contains one top-level `destinx-ai-commerce/` directory.
- [ ] ZIP contains runtime PHP, CSS, JavaScript, `readme.txt`, `LICENSE.md`, `uninstall.php`, and POT only as required.
- [ ] `.git`, `.github`, tests, docs, build artifacts, caches, reports, Composer dev tools, and local environment files are excluded.
- [ ] No Pro file or ConfigCraft license/update client is present.
- [ ] No secret or personal data is present.
- [ ] Extracted ZIP passes syntax, Plugin Check, and a clean installation test.
- [ ] ZIP checksum is recorded.
- [ ] ZIP content matches the intended Git tag.

## Submission process

- [ ] WordPress.org account and monitored email are confirmed.
- [ ] Requested slug availability is checked.
- [ ] Final ZIP is uploaded once; duplicate submissions are avoided.
- [ ] Submission description explains the complete local product and WooCommerce dependency.
- [ ] Reviewer questions are answered in the same email thread.
- [ ] Every requested correction is reproduced locally, documented, and retested before reply.
- [ ] Approval timing is treated as external and is not promised to customers.

## Final release decision

Submission is authorized only when:

1. every mandatory checkbox above is complete;
2. the Free plugin works independently on a clean WooCommerce site;
3. the package contains no commercial control path;
4. the full automated and manual staging gates are green;
5. English source policy is verified across the repository;
6. the final ZIP is reproducible from the approved commit.
