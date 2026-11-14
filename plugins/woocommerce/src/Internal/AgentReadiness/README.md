# Agent readiness

Checks published simple and variable products against the product data specs of AI shopping channels and reports what keeps them from being listed or found. Behind the experimental `agent_readiness` feature flag (disabled by default, not shown on the Features screen).

## What the checks read

The checks read product data and store settings, because most gaps are empty fields only the merchant can fill, and a check can name the product and field. When data is filled in but a store output (UCP catalog, product structured data) doesn't send it, that is a bug fixed in that output's code, not a check. Where a plugin can add or replace what core builds (robots.txt, sitemaps, `llms.txt`, page markup), store checks read what the store returns instead (`StoreOutputChecks`), and pass when any source provides the item. A crawler block at a CDN or firewall shows only when the store's request to itself goes through it; an outside scan of the store's public pages covers the rest, along with page speed and layout shift.

## Channels

| Id | Channel | Spec |
| --- | --- | --- |
| `chatgpt` | ChatGPT | [OpenAI product feed spec](https://developers.openai.com/commerce/specs/feed) |
| `google` | Google (Shopping, AI Mode, Gemini) | [Google Merchant Center product data spec](https://support.google.com/merchants/answer/7052112) |
| `copilot` | Microsoft Copilot | [Microsoft Merchant Center product data](https://learn.microsoft.com/en-us/advertising/shopping-content/products-resource) |

## Checks

Each check is a `ReadinessRule` in `Checks/`: a row in `SimpleCheck::table()` when it is only data, or its own class when its result changes per product (price, brand, GTIN, duplicate GTIN, image size). Per channel it names the feed field it tests and an effect:

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
| `image_alt_text` | | | |

`image_alt_text` (the main image has no alt text) is about the product page, not a feed: no channel's spec asks for it. A check with no channel map lists its products but doesn't change the channel totals. Like every check, it runs once a rules file is stored only when the file names it, here with an empty channel map (`"image_alt_text": { "channels": {} }`), so removing it from the file turns it off. The rules filter can still remove it; a check whose channels the filter empties doesn't run.

A variable product with some priced variations fails `missing_regular_price` but counts as `gaps`, not `not_listable`, in the channel totals, since its priced variations still list.

Two Google exceptions, per product: `missing_brand` leaves Google out when Google for WooCommerce sends its own brand (`_wc_gla_brand`), and `missing_gtin` leaves Google out when the product has a brand and an MPN (`_wc_gla_mpn` or an "MPN" attribute). Brands set through Google for WooCommerce attribute mapping rules are not read yet.

## Scope

Only published simple and variable products are checked, the types agents can buy today. Agents find other types through the UCP catalog, but its cart sends only a product and a quantity: grouped and external products aren't purchasable, and subscriptions, bookings and bundles need a plan, a date or options it doesn't send. Two groups are counted instead of checked, so the merchant sees them without being asked to fix them:

- Products with catalog visibility "Hidden". Agents see the products shoppers see in the shop, so hiding one is the merchant's choice, not a problem. "Search results only" products are checked, since agents find them by search.
- Products of any other type: grouped, external, subscription, booking, bundle and so on. A product keeps its stored type when its plugin is inactive, so it stays in this group.

Store-wide conditions (`StoreConditions`) are reported first because they affect every product: coming soon mode and "Discourage search engines" hide the products, UCP off and no HTTPS hide the catalog from UCP agents, and guest checkout off or no payment method turned on stops agents placing orders. Missing policy pages and order emails off weaken agent orders. Each condition's `blocks` says which, so a reader can tell a hidden store from one agents can't buy from.

## Store output checks

`StoreOutputChecks` runs in the background scan only. It loads the store's own pages as a logged-out visitor (`StoreResponses`): only URLs on the store's host, redirects followed on that host only, a 5 second timeout and 512 KB per response, each URL once per scan, nothing at all in coming soon mode. A product page is read up to 2 MB, because its JSON-LD is printed in the footer and the usual cap can cut it off. Its results go into the report's `store_checks` with the same keys, with `ok` set to `null` when the check could not run ("Couldn't check"), or when that read was cut off before any Product JSON-LD. They are always `recommended`, so they never block the UCP switch.

| Id | Passes when |
| --- | --- |
| `sitemap_lists_products` | A sitemap listed in robots.txt, or `/wp-sitemap.xml`, `/sitemap_index.xml` or `/sitemap.xml`, names a product sitemap or product pages. An index that does not has its child sitemaps opened, at any depth, up to five child requests. |
| `product_jsonld_from_core` | The product's JSON-LD is WooCommerce's own and isn't repeated; a failure shows as a likely conflict. |
| `product_jsonld_present` | A recent product page has Product (or ProductGroup) JSON-LD that parses, from WooCommerce, an SEO plugin or the theme. |
| `product_jsonld_required_fields` | That JSON-LD gives a name, an image and an offer, from one block or several. `null` when the page has no Product JSON-LD; the settings page hides it then. |
| `product_offer_jsonld` | One of its offers has a price (`price`, `lowPrice` or a price specification, as a number or with a currency) and an availability. `null` when the page has no Product JSON-LD; the settings page hides it then. |
| `llms_txt_present` | `/llms.txt` at the host root is served as text. |
| `llms_txt_valid` | It starts with a `# Name` heading. |
| `llms_txt_links_resolve` | The first five links in it to the store's own pages open (no 4xx). Links to other sites, or with a query string, aren't loaded. |
| `open_graph_tags` | The home and product page have `og:title` and `og:image`. |
| `meta_description` | The home, product, shop and category page have a meta description, of those that loaded. |
| `canonical_url` | The product, shop and category page have a canonical link, of those that loaded. WordPress prints one on product pages only, so a stock store fails on the shop and category page until WooCommerce adds them. |
| `organization_schema` | The home page's JSON-LD has an Organization, or a type stores use for themselves such as OnlineStore. |
| `ai_snippet_directives` | No robots meta tag or `X-Robots-Tag` header on the home or product page sets `nosnippet` or a `max-snippet` limit. |
| `content_in_raw_html` | The product page's text, without scripts or comments, has the product's name and its price as a whole amount. |
| `no_noindex_directives` | No robots meta tag or `X-Robots-Tag` header on the home or product page sets `noindex`, `none`, `noai` or `noimageai`. |
| `pages_public` | The home, shop and product page don't answer 401 or 403, or redirect to `wp-login.php` or My account. |
| `sitemap_lastmod` | A sitemap robots.txt names or at a default path, or the first one a sitemap index lists, has `lastmod` dates; every candidate is tried before it fails. |
| `rss_feed` | The home page links to an RSS or Atom feed. |
| `viewport_meta` | The home page has a viewport meta tag. |
| `html_lang_and_charset` | The home page's `html` element has `lang`, and a meta tag or the `Content-Type` header gives a charset. |
| `images_have_alt` | Every image on the home and product page has an `alt` attribute (empty counts); images hidden from assistive technology, 1×1 tracking pixels and `noscript` fallbacks are skipped. |
| `single_h1` | The product page has exactly one `h1`. |
| `schema_matches_product` | A Product item names the product and, for a product with one price, one of its own items offers that price with or without tax, at any tax rate of its tax class since the page was served to one location; a failure shows as a likely conflict. |
| `product_gtin_jsonld` | The product's JSON-LD or its offers give a valid GTIN (`gtin`, `gtin8`, `gtin12`, `gtin13` or `gtin14`), from any source. Fails as a likely conflict only when the product's own GTIN is valid but the page gives none or an invalid one. Skipped when the product doesn't need one, or its own GTIN is missing or invalid: `missing_gtin` and `invalid_gtin` report those. |
| `product_brand_jsonld` | The product's JSON-LD gives a brand, from any source. Fails as a likely conflict only when the product has a WooCommerce brand (`product_brand`), the only brand WooCommerce prints. A brand kept in an attribute or another plugin's taxonomy isn't printed by WooCommerce, so the check is skipped then and `missing_brand` reports the stored value. |
| `product_review_markup` | The newest visible published product with reviews (of the five newest with reviews) has `aggregateRating` or `review` in its JSON-LD. Passes when reviews are off or no product has any. |
| `policy_pages_linked` | The home page links to the privacy policy and refund and returns pages the store set. Skipped when neither is set. |
| `ucp_profile` | With UCP on: `/.well-known/ucp` is served and names a REST endpoint. |
| `ucp_endpoints_live` | With UCP on: a plain GET to the endpoint the profile names, or WooCommerce's when there is no profile, answers 200 with JSON. Nothing is searched, so it isn't counted as agent traffic. |
| `ucp_profile_version` | With UCP on: the served profile declares the UCP version WooCommerce implements (`UcpUtils::VERSION`). A different version usually means another plugin serves its own profile. |
| `ucp_checkout` | With UCP on: the served profile declares `dev.ucp.shopping.checkout`. Pending (WOOAI-1435). |
| `product_return_policy_schema` | A product offer, or the store's OnlineStore or Organization item, in the product page's JSON-LD has `hasMerchantReturnPolicy`. |
| `product_shipping_schema` | A product offer has `shippingDetails`. "Couldn't check" for a product that doesn't ship. Pending (WOOPRD-3908). |
| `product_buy_action_schema` | The product or an offer has a `BuyAction` in `potentialAction`. "Couldn't check" for a variable product without one: WooCommerce links only a chosen variation to checkout (WOOPRD-3946, after the ProductGroup work in WOOPRD-3907). Pending (WOOPRD-3918). |

A likely conflict names the page where it was seen (`seen_at`), not a plugin: a list of plugins goes stale, and several plugins can change the same output (WOOAI-1618).

The checks judge what the store returns, whoever provides it. Some things they can't see, by design (WOOAI-1618):

- What a plugin sends a channel directly. Google for WooCommerce sends its own GTIN and brand to Google, so the page can pass while Google gets another value (WOOAI-1595).
- Output that depends on the shopper. The scan is a logged-out visitor with the store's default currency and prices; a currency switcher or wholesale pricing shows other shoppers other values.
- Values added in the browser. The page is read as the server sends it, so JSON-LD a script adds after load is missed.
- Every product. Output checks read one product page; the stored-data checks run on every product.

A check listed in `StoreOutputChecks::PENDING` tests output WooCommerce doesn't build yet. It doesn't run: it's reported with `ok` null and `pending` set to the issue that builds the output, so it reads as coming soon, not as failed or couldn't check. Remove its entry when that output ships.

A check listed in `ReadinessCheckPage::SAME_CAUSE` isn't shown while the setup step it names fails, so one cause shows once: `no_noindex_directives` while "Discourage search engines" is on, and `product_return_policy_schema` while no published refund and returns page is chosen. A stock store creates that page as a draft, so the setup step shows instead.

robots.txt is loaded from the host root, only to find the sitemaps it lists. Its crawler rules aren't checked: a host or CDN can rewrite the file or block crawlers without it, and the store's request to itself skips both, so they wait for an outside view of the store (WOOPRD-3953). The sitemap check is "couldn't check" when robots.txt could not be read, or a candidate sitemap could not be loaded or answered other than 200, 404 or 410, and no fetched sitemap names products. A category sitemap does not count. When an index does not name products, its child sitemaps on this host are checked, nested indexes included (Jetpack serves one past 1,000 URLs), up to five child requests in all. Sitemap lines on another host, or written as `//host/path`, are skipped and do not use up the five listed slots. Checks on the home and product page read the ones that loaded. The product structured data checks also read the variants a ProductGroup lists in `hasVariant`, where a variable product's offers sit once it is printed as a ProductGroup (WOOPRD-3907). The category page is one of the sampled product's categories, or the largest category when it has none. The home page loads first; when it can't be reached, the scan makes no more requests, so a store that can't reach itself waits for one timeout. The product checks load one product page: among the five newest published simple or variable products not set to Hidden, the newest with a price and no password, or the newest without a password when none of them has a price. With no such product, or when its page doesn't answer 200, they are "couldn't check".

The checks from `no_noindex_directives` down pass on a stock store today. They stay as regression guards, so a merchant hears when a host, theme or plugin breaks one.

## Failures per product

`refresh_report()` stores what each product fails in product meta (`ProductFailures`), one row per check and per channel status, plus a `checked` row on every product the checks cover, passing ones included. `ProductsList` updates a product's rows when it is saved, except for a save that changes only stock or nothing, removes them when it is trashed, tests the parent again when a variation is trashed, restored or deleted, and filters the Products list on them for the links from the readiness pages (`?agent_readiness=<value>`); there is no column or dropdown on the list. A covered product without rows was not tested, by the scan's product limit. The stored report's product counts are read from these rows, the total from the `checked` rows, so a product fixed, added or removed since the scan counts now. The example products per check can be out of date; the count links to the filtered Products list. The scan stores the GTINs more than one product shares in the report (`shared_gtins`, not returned by the report readers, up to `ProductScan::SHARED_GTINS_LIMIT`), so checking one product on save or with the run ability doesn't read every published product. Past the limit, nothing is stored and a check of one product reads the catalog. A GTIN shared since the scan, on the saved product or another, is flagged at the next scan. Core refuses an exact duplicate on save, so this is one that differs only by separators or leading zeros, or a direct meta write.

## What the store declares to agents

`DeclaredToAgents` lists protocols and files as found or not found, for information. Each has a path or signal from its spec, and a JSON document has to have the fields its spec requires. Its results go into the report's `declared` and never count as a problem. It lists only what the scan reads, so nothing is "not checked" on every store. Sales readiness lists only the items found, and has no declared card when none is.

Markdown is asked for on the product page, or the home page without a product: with `Accept: text/markdown`. An HTML answer doesn't count. A copy at the page's `.md` address isn't requested: each declared item reads a response the scan already loads. `Vary: Accept` isn't listed: hosts and CDNs set it, and the store's request to itself skips them.

## Report format

`AgentReadiness::get_report()` returns:

```php
array(
	'generated_at' => '2026-10-28T12:00:00+00:00',
	'products'            => 120,  // Published simple and variable products checked.
	'hidden_from_catalog' => 4,    // Not checked: catalog visibility "Hidden", the merchant's choice.
	'unsupported_types'   => 6,    // Not checked: grouped, external, subscription, bundle and other types agents can find but can't add to a cart yet.
	'store_checks' => array(
		// blocks: 'listing' (every product on every channel), 'discovery' (harder to find and read), 'ucp' (the catalog for UCP agents) or 'checkout' (agent orders).
		// ok: true, false, or null. Null means the page could not be loaded, or a product field check found no Product JSON-LD (the settings page hides that).
		array( 'id' => 'launched', 'ok' => true, 'blocks' => 'listing', 'level' => 'required', 'name' => '…', 'label' => '…', 'why' => '…', 'fix' => '…', 'fix_url' => '…' ),
	),
	// What the store declares to agents, for information: found is true, false, or null when it couldn't be checked.
	'declared' => array(
		array( 'id' => 'security-txt', 'name' => '…', 'found' => false ),
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

## One product

`AgentReadiness::check_product( $product )` judges one product as the scan does: each failing check with its field and effect per channel for that product (after per-product exceptions such as a partly priced variable product), and the product's status per channel (`not_listable`, `with_gaps`, `complete`). It returns null for products the scan leaves out, and judges a variation by its parent. Extensions that rank or fix products one at a time use it, since the stored report keeps only counts and up to 10 example ids per check.

## Rules file

Channel specs change faster than WooCommerce releases, so each background scan first fetches a published rules file (`RemoteRules`). Only a GET of the file is made; the request's user agent includes the site URL, as other WooCommerce.com polls do. The `woocommerce_agent_readiness_rules_url` filter changes the URL; an empty URL turns the fetch off. With no stored file, or a failed fetch, the bundled checks run.

The file has a `version` and a `checks` object. The ids in `checks` are the checks that run, and each one has to match a check shipped in code. An id the file leaves out does not run. An id code does not know, or an unknown channel, is dropped. A check whose entry has a known channel with a missing field, an effect other than `not_listable` or `gaps`, or a bad `effect_from` runs with its bundled channels, so a typo never turns a check off. With no stored file, or a file that names nothing this code can run, the bundled checks run. A failed or malformed fetch keeps the last good copy (option `woocommerce_agent_readiness_remote_rules`).

Per check, `channels` maps a channel id to the feed field and the effect, the same shape as the `woocommerce_agent_readiness_rules` filter. A channel entry may carry `effect_from` (`{ "2027-01-31": "not_listable" }`) for a spec change announced ahead. The filter runs last, so it can still drop a check or put a bundled one back. The file cannot add a check the code has never seen: that still needs a class.

`rules.json` in this directory is the file to publish. It lists every check the code has today, so the first publish changes nothing. Removing an id there turns that check off without a WooCommerce release. It will be served from `https://woocommerce.com/wp-json/wccom/agent-readiness/1.0/rules.json`, the same host as the payment gateway suggestions (`wccom-plugins` on WooCommerce.com).

## Background scan and REST

"Run checks" on WooCommerce > Sales readiness, or `POST wc/v3/agent-readiness/scan`, queues the scan in Action Scheduler (`ScheduledScan`). The report is stored in the `woocommerce_agent_readiness_report` option (not autoloaded). Each run schedules the next one a day later, before scanning, so a failed scan doesn't stop the daily refresh; a run that finds the feature disabled does. If a queued scan still hasn't started 10 minutes after it was due and no other scheduled action has finished, failed or started in that time (Action Scheduler isn't running), the Sales readiness page says so and the scan stays queued. The scan never runs in a page or REST request; splitting the product pass into one action per batch is WOOAI-1661.

On a store with 11,000 products, a scan (stopping at 5,000) took about 4 seconds and 144 MB. A product saved since the scan is already reflected in the counts, as described under Failures per product.

`GET wc/v3/agent-readiness` returns `{ "report": <report or null>, "scan_pending": <bool>, "scan_stalled": <bool> }` from `AgentReadiness::get_served_report()`, so `store_checks` are read now; `scan_stalled` is true while a queued scan waits on a stalled Action Scheduler. `GET wc/v3/agent-readiness/view` returns what the Sales readiness page and the AI tools Overview show (`ReadinessCheckPage::view()`): text, badges and links only, so the React views render it without knowing the checks. All three routes need `manage_woocommerce` and exist only while the feature is enabled.

The same shape is meant for every consumer: WooCommerce > Sales readiness and AI shopping, an outside scan of a store, and extensions that rank the findings by sales or offer fixes. Check and channel ids are stable keys.

The scan walks published simple and variable products not set to Hidden in batches of 200, most recently modified first, primes meta, terms, images and variations per batch, and stops at 5,000 products.

## Abilities

While the feature is enabled, `AbilitiesLoader` registers these abilities in the WordPress Abilities API with the `Ability` class, which converts their input to the schema types. Each definition class in this directory owns its arguments. They show in REST and to MCP clients, for users with `manage_woocommerce`:

- `woocommerce/get-agent-readiness` and `GET wc/v3/agent-readiness` both return `{ report, scan_pending, scan_stalled }` from `AgentReadiness::get_served_report()`: settings changed since the scan show their current state, and store output checks come from the stored report.
- `woocommerce/run-agent-readiness-check` with `product_id` checks that product now (`check_product()`) and returns its failing checks with label and fix, and its status per channel. Without one it queues the background scan, like `POST wc/v3/agent-readiness/scan`, and returns `scan_pending`, `scan_stalled` and `report_generated_at`, so the agent knows when to read the report again, or that it won't change until scheduled actions run.

## AI tools and Sales readiness

WooCommerce > Settings > AI tools (`AgenticCommerceSettings`, `AgenticCommerceSettingsPage`; the tab id stays `agentic-commerce`) and WooCommerce > Sales readiness (`ReadinessCheckPage`, a WooCommerce Admin page at `admin.php?page=wc-admin&path=/sales-readiness`, between Reports and Settings in the menu) show while the feature is enabled. They follow the WOOPRD-3796 and WOOPRD-3947 designs. Both are React views on `@wordpress/ui` in `client/admin/client/sales-readiness/`, fed by `GET wc/v3/agent-readiness/view`; every string, badge and ranking is built in `ReadinessCheckPage::view()`, where the tests are. AI tools opens on the Overview, a React root mounted by the `settings-embed` script, with two cards. The AI shopping card has the Status badge, whether AI assistants can shop the store and how many setup steps are left, and Manage, which opens AI shopping (`&section=shopping`). Before the UCP setting is first saved, the card says what AI shopping does and offers Set up AI shopping instead; once UCP is turned off, it says AI shopping is off. The Sales readiness card has Fix first (or Run checks before the first scan) and View all checks, which opens the page. AI shopping's Status card also ends with a link to it and its issue count. The markup AI shopping still shares with the old views (check rows, badges, folds and the script) is in the `ReadinessMarkup` trait.

AI shopping has one card per part:

- The UCP switch, first. It saves through `POST wc/v3/agent-readiness/ucp` (`{ "enabled": true }`). A failing required check keeps it from being turned on, and that POST answers 409; turning it off still works. While it is on and the required checks pass it says "AI assistants can now find and shop your store.", and the page reloads after it changes. In coming soon mode the card says agents can't shop the store yet and links to Site visibility.
- Product visibility: Available to AI assistants, Hidden from your catalog and Product type not supported yet, from the stored report (`products`, `hidden_from_catalog`, `unsupported_types`). Each links to the Products list filtered to those products (`ProductScan::count_query()`, the query the scan counts with), and a footnote names the supported types.
- Status: a badge (Needs attention while a required step fails, with UCP on or off; otherwise Active or Off as UCP is), then the store conditions from `StoreConditions`, the same list the readiness report shows. Open conditions are one list, failing `required` ones first (coming soon mode and HTTPS), marked Fix; failing `recommended` ones, marked Check, such as a missing privacy, terms, refund and returns, or shipping policy page (policy pages are not required by the UCP spec), follow. The card says how many required steps are left to fix, or how many steps are left when none is required. Each step says why it matters and links to where to fix it; a chosen policy page that isn't published links to its editor, and a store page chosen as one asks for another page. Passing conditions fold behind "Show N completed steps", named by their passing state (`name`) and marked Pass. The last row links to Sales readiness with how many issues the store and product checks found together.
- Store policy pages. Refund and returns, privacy and terms are the existing options, so they stay in sync with Advanced > Page setup and Settings > Privacy; shipping policy, FAQ and warranty are new. They are for the policy links a UCP checkout can carry (`links`); the UCP endpoints don't send them yet. The dropdowns list published pages other than the cart, checkout, shop and My account pages, and saving leaves a stored page in place even when it's a draft or one of those store pages, which then fails its check. A link from Status opens the page on its field, highlighted.
- The store address AI assistants start from (`/.well-known/ucp`), with Copy.

Sales readiness opens with when the last check ran and Check again ("Run checks" before the first), then Fix first, then a card per question. It shows the setup steps from `StoreConditions` (UCP aside, as the switch) under the question each answers, a failing required one marked Required, and the store output checks from the stored report, since they only run in the scan. Each check names the question it answers in `group` (`access`, `find`, `understand` or `buy`), and each question's card has a badge: how many issues, All passed, or No issues when a check couldn't run. Failing and unchecked rows come first, then passed ones; each card shows the first two and folds the rest behind "Show N more checks", with "Hide" below the opened list. Checks waiting on a later update (`pending`) aren't shown. Output checks are never required, so they don't affect the switch or Status. A failing row is named by its failing title (`label`), then what it affects ("Affects: how AI assistants summarize your store", by check, or from `blocks`), why it matters, what to do and a link. The product checks are their own question, before "Can shoppers buy through AI assistants?". The last card, "What does your store share with AI assistants?", has no badge: it says how many declared items were found ("Found 4 of 33"), with every item behind a fold.

The product checks, under "Can AI assistants list your products?", show how many products each channel can list ("2 of 15 can be listed"), then each failing product check, titled with what to do and its count ("Add a brand to 13 products"), why it matters, and "View N products", which opens the Products list filtered to them. With up to three failing products the title names them ("Add a brand to Blue hoodie and Red cap") when their failures are stored; one product without them is "a product", and more than three are counted. The copy is in `ReadinessCopy`. Product image alt text isn't listed there, since the `images_have_alt` store check covers it.

Fix first, a card of its own, lists the required setup steps that fail, then the top three other issues. Issues are ranked by what they stop, then by sales: setup steps that stop every agent sale (`payment_method`, `guest_checkout`) and `access` store checks that block `listing` (pages agents can't reach), then other store checks that block `listing` (such as structured data) and product checks that keep products off a channel (`not_listable`), then product checks that only make products harder to find. Within each, the larger share of sales on the affected products comes first, then the larger product count. Store checks with no sales link, such as a missing meta description, stay out of Fix first. `ProductSales` reads each product's net sales over the last 90 days from the Analytics lookup tables (`wc_order_product_lookup` joined with `wc_order_stats`), with the order statuses Analytics excludes left out and variations counted toward their parent. The scan adds up the sales of each check's failing products, and the report stores each check's `sales_share` (0 to 1) and `sales` (`days`, `orders`). While Analytics is off, `sales` is null; with fewer than `ProductSales::MIN_ORDERS` (20) orders in the period, too few to rank by, shares are null. In both cases, and when no failing check has a share, Fix first ranks by product count and says why; otherwise it says to start with the fixes that affect the most sales, over the period the figures cover. A share is dropped once a check's count changes after the scan, since it was measured on the products failing then. Under headings for what each group stops (Required for AI shopping, Needed to list products, Helps shoppers find products), a product issue is badged by its share of sales ("38% of sales"); its line starts with the share, the first saying over which period ("38% of your sales in the last 90 days."); products that sold nothing in the period say "No sales" instead of 0%.

Tracks event: `agentic_commerce_ucp_toggled` (`enabled`), sent only when the store allows tracking.

### Tracking

The React views send Tracks events through `@woocommerce/tracks` (`client/admin/client/sales-readiness/tracks.ts`), so they get the `wcadmin_` prefix and are sent only when the store allows usage tracking. `surface` is `page` (Sales readiness) or `overview` (AI tools). Check ids are the report's ids (`refund_policy`, `image`, `llms_txt`), never translated titles; `ReadinessCheckPage::view()` adds an `id` to every row and Fix first item for this. wc-admin's own `wcadmin_page_view` already counts visits to the page.

| Event | When | Properties |
| --- | --- | --- |
| `wcadmin_sales_readiness_viewed` | The view first loads, once per visit | `surface`, `checked`, `pending`, `fix_first_count`, `open_count`, `required_count`, `unknown_count`, `pass_count` |
| `wcadmin_sales_readiness_run_check_click` | Run checks or Check again | `surface`, `first_check` |
| `wcadmin_sales_readiness_link_click` | A fix link, View all checks, or Manage | `surface`, `area` (`fix_first`, `section`, `view_all`, `shopping`); for fixes `check_id`, plus `position` in Fix first or `section` and `state` in a section card |
| `wcadmin_sales_readiness_fold_toggle` | Show N passed checks or Hide passed checks | `section` (a section id, or `declared`), `open` |
| `wcadmin_sales_readiness_error` | The view can't load, or a check can't start | `surface`, `action` (`load` or `run`) |

## Updating rules

The per-channel field and effect live in each check's `channels()`, and that is what runs until a rules file is stored. After that, `rules.json` is the list. The `woocommerce_agent_readiness_rules` filter still runs last; unknown channels and effects in what it returns are dropped.

## Product feeds

`AgentReadiness::get_failing_check_ids( $product )` returns the ids of the checks a product fails, so a feed integration can reuse them. It judges a variation by its parent, as the scan counts it, and returns an empty list for products the scan leaves out.

Core's UCP catalog mapper (`src/Internal/ProductFeed/Mapping/UcpProductMapper.php`) sends each product's or variation's own valid GTIN as a barcode, so `missing_gtin` fails a variable product when any published variation has no GTIN of its own. UCP has no brand field, so `missing_brand` applies to the other channels, not UCP.

## Trying it

```sh
wp option update woocommerce_feature_agent_readiness_enabled yes
```

Then open WooCommerce > Sales readiness and select "Run checks". AI shopping is under WooCommerce > Settings > AI tools.
