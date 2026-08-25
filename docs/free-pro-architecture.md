# Free + Pro add-on architecture

## Non-negotiable rule

`DestinX AI Commerce for WooCommerce` is the main plugin, the catalog engine, and the only product submitted to WordPress.org. It must work on its own and continue to work when Pro is missing, inactive, incompatible, expired, or unable to reach ConfigCraft.

`DestinX AI Commerce Pro` is a separately installed commercial add-on distributed outside WordPress.org. It must not copy the Free engine and cannot operate without the compatible Free API.

## Ownership boundaries

| Area | Free engine | Pro add-on |
| --- | --- | --- |
| WooCommerce reads | Normalizes products through public WooCommerce APIs | Reads exact completed-snapshot results through the Free API |
| Rules | Owns base finding codes, severity, penalties, and scoring-model version | May add premium workflow logic without hiding the base explanation |
| Scans | Owns quick scan, full scan, locks, batches, retries, recovery, and atomic snapshots | Never creates a parallel product scanner |
| Data | Owns audit tables and base operational state | Owns only proposal, apply, undo, licensing, and premium-service data |
| UI | Owns the complete diagnostic dashboard and product guidance | Owns a separate licensed workflow page |
| Writes | No automatic catalog writes | Uses explicit field selection, preview, WooCommerce CRUD, audit, and conflict-safe undo |
| Network | No runtime external requests | License, update, and optional service calls after documented setup |
| License | No license code or state | ConfigCraft/DXLS annual entitlement and private update channel |
| Updates | WordPress.org only | Signed private packages from ConfigCraft |

## Runtime architecture

```text
WooCommerce products
        |
        v
DestinX AI Commerce Free
  - normalized product data
  - pricing adapter registry
  - scoring model and findings
  - full-scan state machine
  - active completed snapshot
  - read-only extension API
        |
        | exact result lookup, summary, metadata, scan state
        v
DestinX AI Commerce Pro
  - priority plan
  - remediation proposal
  - before/after preview
  - approved WooCommerce CRUD write
  - local audit record
  - optimistic conflict check and undo
        |
        | license/update metadata only, or future explicit service opt-in
        v
ConfigCraft Suite
  - commerce
  - annual DXLS licensing
  - signed Pro updates
  - future metered AI or multi-store services
```

## Free extension contract

The public entry point is available after `plugins_loaded`:

```php
$api = destinx_ai_commerce_api();
```

The independent contract version is exposed through `DXAIC_EXTENSION_API_VERSION`. Free 0.13.0 provides API 1.1.0 with these read-only methods:

- `get_version()`;
- `get_plugin_version()`;
- `get_snapshot_metadata()`;
- `get_summary()`;
- `get_results( $page, $per_page, $filters )`;
- `get_result( $product_id )`;
- `get_scan_state()`.

The exact-result method returns one product only when it belongs to the active completed snapshot. The API does not expose repository objects, database handles, write methods, product mutation, scheduling internals, or license state.

The Free plugin emits `destinx_ai_commerce_scan_completed` after a new snapshot becomes active. Event payloads include the extension API version so add-ons can validate the contract.

## Activation dependency

Pro checks all of the following before it boots:

- WooCommerce is active;
- Free is active;
- `DXAIC_VERSION` meets the minimum version;
- `DXAIC_EXTENSION_API_VERSION` meets the minimum contract;
- `destinx_ai_commerce_api()` exists and returns the public API.

An incompatible Pro add-on must show one actionable administration notice and remain inactive. It must never break the storefront, checkout, Free dashboard, Free scan jobs, or WordPress administration.

## Remediation transaction

Pro 0.2 uses the following transaction boundary:

1. Resolve the exact result through Free API 1.1.
2. Load the live WooCommerce product.
3. Build only allow-listed fields that correspond to current Free findings.
4. Let an authorized administrator select fields and enter verified values.
5. Sanitize and bound every value by field semantics.
6. Store a local draft containing field, finding, before value, and after value.
7. Display the immutable before/after preview. No product write has occurred.
8. On explicit apply, atomically claim the draft.
9. Re-read every live field and compare it with the recorded before value.
10. Stop the complete change set if any field changed.
11. Set approved values on one WooCommerce product object and save once.
12. Mark the audit record applied.
13. Allow undo only when every current value still equals the recorded after value.

Apply and undo are idempotent at the proposal-state boundary. Two simultaneous requests cannot claim the same draft transition. Safety-only undo remains available after license expiration.

## Pro-owned local data

The Pro remediation table stores:

- opaque proposal UUID;
- product ID;
- Free snapshot ID;
- draft, applying, applied, undoing, undone, conflict, or failed status;
- allow-listed before/after change JSON;
- administrator user ID;
- UTC create, apply, and undo timestamps;
- a sanitized operational message.

It does not store customer, order, payment, checkout, or visitor data. Uninstall removes the Pro table and Pro options without touching the Free audit.

## License and update boundary

ConfigCraft license requests contain only:

- license key;
- product slug;
- site URL and host;
- random installation ID;
- environment type;
- Free, Pro, and API versions.

No catalog content, findings, product values, proposal data, customer data, order data, or payment data is sent for licensing. Checks run only in authenticated administration requests and never during storefront or checkout execution.

The Free plugin contains no external updater. Only Pro registers the private update client, and only for an active update entitlement.

## Failure rules

| Failure | Required behavior |
| --- | --- |
| Pro missing or inactive | Free remains complete. |
| Pro version incompatible | Pro remains inactive and shows one admin notice. |
| License expired or blocked | New Pro workflows lock; Free remains complete; safety undo remains available. |
| ConfigCraft temporarily unavailable | A recent verified entitlement receives a bounded local grace period. |
| Product edited after preview | Apply or undo stops without overwriting the product. |
| WooCommerce setter/save error | Proposal records failure and no second background write is attempted. |
| New Free scan completes after preview | Product current-value checks still protect the write; the merchant should create a new preview when context changed. |
| Pro disabled during a future bulk job | New work stops; Free data and storefront remain available. |

## WordPress.org compliance boundary

The WordPress.org ZIP must contain a useful, complete Free plugin. It must not contain:

- dormant Pro code;
- license validation;
- private update logic;
- misleading locked screens;
- automatic Pro installation;
- undisclosed tracking or external requests;
- an account requirement for local catalog analysis.

A single contextual link to the separately sold add-on may be used inside the relevant Free screen when it is factual, non-disruptive, and does not obscure Free functionality.

## Release gates

A Free/Pro pair is releasable only when:

- Free passes its own test suite without Pro installed;
- Pro refuses incompatible Free/API versions cleanly;
- both plugins activate together in a real WordPress/WooCommerce environment;
- proposal creation performs no product write;
- apply and undo use nonce, capability, and allow-list checks;
- concurrent edits are never overwritten;
- license calls respect the documented minimal payload;
- expired licenses cannot disable Free or safety recovery;
- uninstall removes only data owned by the plugin being removed;
- all source strings, UI, readmes, public documentation, and screenshots are written in English.
