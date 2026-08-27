# WordPress.org audit - 0.13.4

## Scope

Version 0.13.4 replaces the generic optional add-on note with one factual DestinX AI Commerce Pro banner inside `WooCommerce > AI Commerce`.

## Guideline review

The implementation was reviewed against the WordPress.org Detailed Plugin Guidelines updated on 11 March 2026.

| Review area | 0.13.4 result |
| --- | --- |
| Complete Free product | Full scans, scoring, findings, filters, CSV export, Store Readiness, product guidance, and the extension API remain available without Pro. |
| Trialware | No Free capability is locked, delayed, quota-limited, or disabled. |
| Admin experience | The banner appears only at the bottom of the plugin-owned screen and never as a site-wide notice or dashboard widget. |
| Dismissal | Each authorized user can hide the banner for 24 hours; the existing nonce, capability, AJAX, and no-JavaScript fallback remain in use. |
| Commercial code | The Free runtime contains no Pro implementation, license validation, entitlement state, private updater, or remote service request. |
| Link behavior | One static HTTPS link is used. It contains no affiliate, referral, or analytics parameters and loads no remote asset. |
| Claims | The text describes only the implemented proposal, preview, approval, WooCommerce CRUD, conflict, audit, and undo workflow. It makes no autonomous-AI or guaranteed-result claim. |
| Dependency boundary | The banner states that Pro is installed separately and requires Free, while Free remains the complete catalog engine. |

## Automated gates

- PHP syntax validation passes.
- WordPress Coding Standards passes without errors or warnings.
- PHPUnit passes with 44 tests and 522 assertions.
- The WordPress/WooCommerce integration smoke test passes.
- The WordPress Multisite lifecycle and per-user preference cleanup test passes.
- The architecture boundary test rejects Pro namespaces, prefixes, license code, updater code, remote requests, tracked links, and more than the one allow-listed marketing destination.
- The translation template and release metadata identify version 0.13.4.
- The WordPress.org readme remains below 10,000 bytes.

## Remaining commercial dependency

The banner destination is a public marketing link only. ConfigCraft commerce, download delivery, annual licensing, private updates, and all Pro runtime behavior remain outside the WordPress.org package.
