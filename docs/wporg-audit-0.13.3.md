# WordPress.org compliance audit - Free 0.13.3

Audit date: 27 August 2026

## Scope

This review covers the WordPress.org Free package only. It uses the current WordPress.org Detailed Plugin Guidelines, Plugin Check documentation, readme requirements, security guidance, and the corrections previously required for DestinX Product Configurator for WooCommerce.

The audit does not treat the commercial add-on repository, ConfigCraft licensing, private updates, or private distribution as part of the Free package.

## Release decision

The Free codebase remains a complete standalone product. The onboarding and optional add-on changes do not introduce premium implementation, licensing, remote requests, tracking, locked controls, external updates, or feature limits.

The release is suitable for a WordPress.org submission after the release commit passes the repository's complete CI matrix and the final ZIP is manually checked against the package manifest below.

## Corrections made during this audit

- Added an English four-step Getting started guide on the plugin's own WooCommerce screen.
- Added direct actions for the full scan, catalog results, product editor, and repeat scan workflow.
- Added an `Open AI Commerce` link on the Plugins screen instead of an automatic activation redirect.
- Made the contextual optional add-on card dismissible for 24 hours per user.
- Kept the optional card at the bottom of the plugin screen, outside the core workflow and away from global admin notices.
- Added a permanent `Show setup guide` control after a user hides onboarding.
- Added capability, nonce, allowlist, sanitization, same-site AJAX, and no-JavaScript fallback protection to preference changes.
- Added uninstall cleanup for both user metadata keys, including Multisite.
- Reduced `readme.txt` from 11,465 bytes to less than 10,000 bytes and moved older history to `changelog.txt`.
- Replaced the GitHub-only contributor name with the valid WordPress.org account `destinx` after an official readme-validator warning.
- Removed `composer.json` and all development-only files from the WordPress.org distribution manifest.
- Regenerated the English translation template.
- Added automated checks for 24-hour expiry, preference rendering, user-metadata cleanup, and dormant commercial controls.

## Lessons carried forward from Product Configurator

| Previous risk | Control in DestinX AI Commerce Free |
| --- | --- |
| Premium implementation left inactive in Free | Runtime scan rejects Pro namespaces, prefixes, license/update code, trial controls, and commercial feature implementations. |
| Free presented as a limited shell | Full catalog scan, scoring, guidance, filters, CSV, Store Readiness, product panel, and pricing compatibility work without payment or account. |
| Commercial updater or license client in the public package | No `Update URI`, license key, entitlement, ConfigCraft endpoint, updater, or remote request exists in Free. |
| Unused premium controls or assets | The curated package contains only active Free PHP, CSS, JavaScript, POT, license, readme, changelog, and uninstall files. |
| Slug and main filename mismatch | Directory, requested slug, main file, text domain, and primary prefixes all use `destinx-ai-commerce`. |
| Incomplete privacy or external-service disclosure | Readme and privacy inventory state the exact local data, user preferences, network behavior, and uninstall result. |
| Unclear first-use flow | The Plugins screen links directly to a numbered guide with concrete actions and a reopen control. |

## Free and add-on boundary

- Free never checks whether a license, account, subscription, entitlement, or paid package exists.
- Free never disables, hides, limits, or expires a local feature.
- Free exposes a bounded read-only result API; it does not expose catalog write operations.
- The optional card contains no commercial implementation and no remote URL. Its only action opens the local Plugins screen.
- Add-ons are installed independently and are excluded from the Free ZIP.
- A generic display filter lets a compatible installed extension suppress the optional card without Free knowing any commercial product slug or namespace.

## Admin UI and promotion review

- The onboarding and optional card appear only under `WooCommerce > AI Commerce`.
- No activation redirect is used.
- No site-wide marketing notice, dashboard widget, toolbar item, editor interruption, or front-end credit is added.
- The add-on card is non-blocking, placed after the Free results, and dismissible for 24 hours per user.
- The card contains no price, discount, countdown, affiliate parameter, tracking identifier, or guaranteed result claim.
- The Free workflow remains visually and functionally complete when the card is hidden.

## Security review

- All state changes require `manage_woocommerce` and a WordPress nonce.
- Preference names are sanitized and restricted to an explicit allowlist.
- Preference values accept only the expected hidden state.
- Query filters are normalized, length-bounded where applicable, and read-only.
- CSV export and scan start actions retain capability and nonce checks.
- Output is escaped at render time using context-specific WordPress functions.
- Redirects use `wp_safe_redirect()` to a fixed local admin URL.
- No `eval`, shell execution, unsafe unserialize, arbitrary file write, or public unauthenticated endpoint exists.

## Privacy and network review

- Catalog analysis, scoring, persistence, filters, and exports remain local.
- Free makes no `wp_remote_*` request and loads no remote asset.
- Free sets no cookie and includes no telemetry or analytics SDK.
- Customer, order, payment, checkout, and visitor data are not read.
- Per-user metadata contains only the onboarding hidden flag and optional-card dismissal timestamp.
- Uninstall removes tables, options, scheduled actions, locks, and both user metadata keys.

## Metadata and readme review

- Main header version, constant, Stable tag, changelog, release name, and Git tag must all be `0.13.3`.
- WordPress minimum is 6.6; PHP minimum is 7.4; WooCommerce minimum is 8.2.
- `Requires Plugins: woocommerce` is declared in both the main header and readme.
- `readme.txt` remains below 10,000 bytes, uses five relevant tags, contains a factual short description, and explains the custom setup.
- All shipped source strings and translation msgids are English.
- Claims explicitly avoid guarantees about ranking, recommendation, placement, or legal compliance.

## Distribution manifest

The release ZIP must contain one top-level `destinx-ai-commerce/` directory and only:

- `LICENSE.md`;
- `assets/`;
- `changelog.txt`;
- `destinx-ai-commerce.php`;
- `includes/`;
- `languages/`;
- `readme.txt`;
- `uninstall.php`.

It must not contain `composer.json`, `vendor/`, `.git/`, `.github/`, tests, docs, scripts, build output, reports, caches, credentials, private URLs, or commercial add-on files.

## Required release evidence

- `composer validate --strict` passes.
- PHP syntax and WordPress Coding Standards pass with zero errors and warnings.
- PHPUnit passes, including Free/commercial boundary checks.
- Standard, mixed-catalog, Multisite, compatibility, pricing-integration, upgrade, and 5,000-product Playground gates pass.
- The authenticated AJAX preference flow returns success; onboarding hides and reopens; the optional card remains hidden during its 24-hour window and returns after expiry.
- Official Plugin Check strict passes on the extracted curated package.
- The final ZIP integrity test passes and its SHA-256 checksum is recorded.

## Official references

- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- https://developer.wordpress.org/plugins/wordpress-org/common-issues/
- https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- https://developer.wordpress.org/apis/security/
- https://developer.wordpress.org/plugins/plugin-basics/best-practices/
- https://wordpress.org/plugins/plugin-check/
