# Agent readiness

Checks published products against the product data specs of AI shopping channels and reports what keeps them from being listed or found. Behind the experimental `agent_readiness` feature flag (disabled by default, not shown on the Features screen).

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
| `hidden_from_catalog` | is_eligible_search, not_listable | | |
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

Store-wide conditions (`StoreConditions`) are reported first because they hide every product: coming soon mode and "Discourage search engines".

## Report format

`AgentReadiness::get_report()` returns:

```php
array(
	'generated_at' => '2026-10-28T12:00:00+00:00',
	'products'     => 120,          // Published products checked.
	'products_with_issues' => 31,   // Not complete on at least one channel.
	'store_checks' => array(
		array( 'id' => 'launched', 'ok' => true, 'label' => '…', 'why' => '…', 'fix' => '…' ),
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

## Rules file

Channel specs change faster than WooCommerce releases, so each background scan first fetches a published rules file (`RemoteRules`). Only a GET of the file is made; no store data is sent. The URL comes from the `woocommerce_agent_readiness_rules_url` filter and is empty by default until the file has a home, so nothing is fetched unless it is set.

The file has a `version` and, per check id, a `channels` map in the same shape as the `woocommerce_agent_readiness_rules` filter. A channel entry may carry `effect_from` (`{ "2027-01-31": "not_listable" }`) for a spec change announced ahead. A check in the file uses the file's map; other checks keep the bundled one, and the rules filter runs last. Unknown checks, channels and effects are dropped before the filter sees them, a file entry with nothing valid left keeps the bundled map, and a failed or malformed fetch keeps the last good copy (option `woocommerce_agent_readiness_remote_rules`). The file can change fields and effects, not add checks: a new check still needs code.

## Background scan and REST

"Run check" on the tab, or `POST wc/v3/agent-readiness/scan`, queues the scan in Action Scheduler (`ScheduledScan`). The report is stored in the `woocommerce_agent_readiness_report` option (not autoloaded). Each run schedules the next one a day later, before scanning, so a failed scan doesn't stop the daily refresh; a run that finds the feature disabled does.

`GET wc/v3/agent-readiness` returns `{ "report": <report or null>, "scan_pending": <bool> }`. Both routes need `manage_woocommerce` and exist only while the feature is enabled.

The same shape is meant for every consumer: this Status tab, an outside scan of a store, and extensions that rank the findings by sales or offer fixes. Check and channel ids are stable keys.

The scan walks published products (not grouped or external) in batches of 200, most recently modified first, primes meta, terms, images and variations per batch, and stops at 5,000 products. The Status tab runs it on request and keeps the result in a transient for an hour.

## Updating rules

The per-channel field and effect live in each check's `channels()`. The final map passes through the `woocommerce_agent_readiness_rules` filter, keyed by check id, so it can change without a release. Removing a check id stops that check. Unknown channels and effects are dropped.

Next step, out of scope here: fetch the rules map from a remote JSON spec with the bundled `channels()` as the fallback, the way core polls remote specs for payment and marketing suggestions with bundled defaults.

## Product feed integration

`ReadinessFeedValidator` implements `ProductFeed\Feed\FeedValidatorInterface` and returns the ids of the checks a product fails, so feed integrations can reuse the checks. It judges a variation by its parent, as the scan counts it, and returns no issues for products the scan leaves out (grouped, external, unpublished). The rules are built once per request. The feed framework skips rows with issues, so an integration that only wants to report gaps should wrap it.

Core's UCP catalog mapper (`src/Internal/ProductFeed/Mapping/UcpProductMapper.php`) sends no brand and no GTIN today, so a product that passes `missing_brand` and `missing_gtin` still doesn't give agents those fields through it.

## Trying it

```sh
wp option update woocommerce_feature_agent_readiness_enabled yes
```

Then open WooCommerce > Status > Agent readiness and select "Run check".
