# Agent readiness

Checks published simple and variable products against the product data specs of AI shopping channels and reports what keeps them from being listed or found. Behind the experimental `agent_readiness` feature flag (disabled by default, not shown on the Features screen).

## Channels

| Id | Channel | Spec |
| --- | --- | --- |
| `chatgpt` | ChatGPT | [OpenAI product feed spec](https://developers.openai.com/commerce/specs/feed) |
| `google` | Google (Shopping, AI Mode, Gemini) | [Google Merchant Center product data spec](https://support.google.com/merchants/answer/7052112) |
| `copilot` | Microsoft Copilot | [Microsoft Merchant Center product data](https://learn.microsoft.com/en-us/advertising/shopping-content/products-resource) |

## Checks

Each check is a `ReadinessRule` in `Checks/`. Per channel it names the feed field it tests and an effect:

- `not_listable`: the channel's spec requires the field, so the product can't be listed there without it.
- `gaps`: the product lists, but is harder to find or shows a warning.

A channel whose spec does not ask for the field is left out of the check's map.

| Id | ChatGPT | Google | Copilot |
| --- | --- | --- | --- |
| `missing_regular_price` | price, not_listable | price, not_listable | price, not_listable |
| `missing_images` | image_url, not_listable | image_link, not_listable | imageLink, not_listable |
| `missing_product_text` | description, not_listable | description, gaps | description, gaps |
| `missing_brand` | brand, not_listable | brand, gaps | brand, gaps |
| `invalid_gtin` | gtin, gaps | gtin, not_listable | gtin, gaps |
| `missing_gtin` | gtin, gaps | gtin, gaps | gtin, gaps |
| `duplicate_gtin` | | gtin, gaps | |
| `image_too_small` | | image_link, gaps (not_listable from 2027-01-31) | |
| `uncategorised` | product_category, gaps | product_type, gaps | productType, gaps |
| `variation_attributes_not_set` | variant_dict, gaps | color, size, gaps | color, sizes, gaps |
| `missing_variation_images` | image_url, gaps | image_link, gaps | imageLink, gaps |
| `title_too_long` | title, gaps | title, gaps | title, gaps |

A variable product with some priced variations fails `missing_regular_price` but counts as `gaps`, not `not_listable`, in the channel totals, since its priced variations still list.

Two Google exceptions, per product: `missing_brand` leaves Google out when Google for WooCommerce sends its own brand (`_wc_gla_brand`), and `missing_gtin` leaves Google out when the product has a brand and an MPN (`_wc_gla_mpn` or an "MPN" attribute). Brands set through Google for WooCommerce attribute mapping rules are not read yet.

## Scope

Only published simple and variable products are checked, the types agents support today. Two groups are counted instead of checked, so the merchant sees them without being asked to fix them:

- Products hidden from the shop catalog (catalog visibility "Search results only" or "Hidden"). Agents see the products shoppers see in the shop, so hiding one is the merchant's choice, not a problem.
- Products of any other type: grouped, external, subscription, booking, bundle and so on. A product keeps its stored type when its plugin is inactive, so it stays in this group.

Store-wide conditions (`StoreConditions`) are reported first because they affect every product: coming soon mode and "Discourage search engines" hide the products, UCP off and Plain permalinks (no `/.well-known/ucp`) hide the catalog from UCP agents, and guest checkout off stops agents placing orders. Each condition's `blocks` says which, so a reader can tell a hidden store from one agents can't buy from.

## Report format

`AgentReadiness::get_report()` returns:

```php
array(
	'generated_at' => '2026-10-28T12:00:00+00:00',
	'products'             => 120, // Published simple and variable products checked.
	'products_with_issues' => 31,  // Not complete on at least one channel.
	'hidden_from_catalog'  => 4,   // Not checked: catalog visibility "Search results only" or "Hidden", the merchant's choice.
	'unsupported_types'    => 6,   // Not checked: grouped, external, subscription, bundle and other types agents don't support yet.
	'store_checks' => array(
		// blocks: 'listing' (every product on every channel), 'ucp' (the catalog for UCP agents) or 'checkout' (agent orders).
		array( 'id' => 'launched', 'ok' => true, 'blocks' => 'listing', 'label' => '…', 'why' => '…', 'fix' => '…' ),
	),
	'channels'     => array(
		'chatgpt' => array(
			'name'         => 'ChatGPT',
			'spec'         => 'OpenAI product feed spec',
			'url'          => 'https://…',
			'listable'     => 100,  // products - not_listable.
			'not_listable' => 20,
			'with_gaps'    => 60,
			'complete'     => 40,
		),
	),
	'checks'       => array(
		array(
			'id'          => 'missing_brand',
			'label'       => 'Products with no brand',
			'why'         => '…',
			'fix'         => '…',
			'channels'    => array( 'chatgpt' => array( 'field' => 'brand', 'effect' => 'not_listable' ) ),
			'count'       => 20,
			'product_ids' => array( 12, 34 ), // Up to 10, most recently modified first.
			'fix_url'     => 'https://example.com/wp-admin/edit.php?post_type=product',
		),
	),
	'truncated'    => false, // True when the scan stopped at 5,000 products.
)
```

## Background scan and REST

"Run check" on the tab, or `POST wc/v3/agent-readiness/scan`, queues the scan in Action Scheduler (`ScheduledScan`). The report is stored in the `woocommerce_agent_readiness_report` option (not autoloaded). Each run schedules the next one a day later, before scanning, so a failed scan doesn't stop the daily refresh; a run that finds the feature disabled does. If a queued scan still hasn't started 10 minutes after it was due (Action Scheduler isn't running), the next "Run check" or `POST` runs the scan in that request.

`GET wc/v3/agent-readiness` returns `{ "report": <report or null>, "scan_pending": <bool> }`. Both routes need `manage_woocommerce` and exist only while the feature is enabled.

The same shape is meant for every consumer: this Status tab, an outside scan of a store, and extensions that rank the findings by sales or offer fixes. Check and channel ids are stable keys.

The scan walks published simple and variable products shown in the shop catalog in batches of 200, most recently modified first, primes meta, terms, images and variations per batch, and stops at 5,000 products.

## Agentic commerce settings page

WooCommerce > Settings > Agentic commerce (`AgenticCommerceSettings`, `AgenticCommerceSettingsPage`) shows while the feature is enabled:

- Product counts from the stored report (`products`, `hidden_from_catalog`, `unsupported_types`), each linking to a filtered product list.
- The UCP switch. It saves through `POST wc/v3/agent-readiness/ucp` (`{ "enabled": true }`), which answers 409 while a required setup check fails. Turning it off always works.
- Setup checks (`SetupChecks`), required or recommended. Permalinks and guest checkout read `StoreConditions`.
- Store policy pages and a return policy summary. Refund and returns, privacy and terms are the existing options, so they stay in sync with Advanced > Page setup and Settings > Privacy. The dropdowns list published pages only, and saving leaves a stored draft page in place. The summary (`woocommerce_return_policy_summary`, 300 characters) is stored only; UCP has no field for it yet.

Tracks events: `agentic_commerce_ucp_toggled` (`enabled`) and `agentic_commerce_ucp_blocked` (`failing_checks`), sent only when the store allows tracking.

## Updating rules

The per-channel field and effect live in each check's `channels()`. The final map passes through the `woocommerce_agent_readiness_rules` filter, keyed by check id, so it can change without a release. Removing a check id stops that check. Unknown channels and effects are dropped.

Next step, out of scope here: fetch the rules map from a remote JSON spec with the bundled `channels()` as the fallback, the way core polls remote specs for payment and marketing suggestions with bundled defaults.

## Product feed integration

`ReadinessFeedValidator` implements `ProductFeed\Feed\FeedValidatorInterface` and returns the ids of the checks a product fails, so feed integrations can reuse the checks. It judges a variation by its parent, as the scan counts it, and returns `not_checked` for products the scan leaves out (other types, hidden from the shop catalog, unpublished), so a feed skips them instead of exporting them unchecked. The rules are built once per request. The feed framework skips rows with issues, so an integration that only wants to report gaps should wrap it.

Core's UCP catalog mapper (`src/Internal/ProductFeed/Mapping/UcpProductMapper.php`) sends no brand and no GTIN today, so a product that passes `missing_brand` and `missing_gtin` still doesn't give agents those fields through it.

## Trying it

```sh
wp option update woocommerce_feature_agent_readiness_enabled yes
```

Then open WooCommerce > Status > Agent readiness and select "Run check".
