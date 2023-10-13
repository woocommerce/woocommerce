# WC CLI: Commands

<!-- DOCTOC SKIP -->
<h1>Commands</h1>
<ul><li><a href="#wc-shop_coupon">wc shop_coupon</a></li>
	<li><a href="#wc-customer_download">wc customer_download</a></li>
	<li><a href="#wc-customer">wc customer</a></li>
	<li><a href="#wc-order_note">wc order_note</a></li>
	<li><a href="#wc-shop_order_refund">wc shop_order_refund</a></li>
	<li><a href="#wc-shop_order">wc shop_order</a></li>
	<li><a href="#wc-product_attribute_term">wc product_attribute_term</a></li>
	<li><a href="#wc-product_attribute">wc product_attribute</a></li>
	<li><a href="#wc-product_cat">wc product_cat</a></li>
	<li><a href="#wc-product_review">wc product_review</a></li>
	<li><a href="#wc-product_shipping_class">wc product_shipping_class</a></li>
	<li><a href="#wc-product_tag">wc product_tag</a></li>
	<li><a href="#wc-product">wc product</a></li>
	<li><a href="#wc-product_variation">wc product_variation</a></li>
	<li><a href="#wc-setting">wc setting</a></li>
	<li><a href="#wc-shipping_zone">wc shipping_zone</a></li>
	<li><a href="#wc-shipping_zone_location">wc shipping_zone_location</a></li>
	<li><a href="#wc-shipping_zone_method">wc shipping_zone_method</a></li>
	<li><a href="#wc-tax_class">wc tax_class</a></li>
	<li><a href="#wc-tax">wc tax</a></li>
	<li><a href="#wc-webhook_delivery">wc webhook_delivery</a></li>
	<li><a href="#wc-webhook">wc webhook</a></li>
	<li><a href="#wc-shipping_method">wc shipping_method</a></li>
	<li><a href="#wc-payment_gateway">wc payment_gateway</a></li>
	<li><a href="#wc-com">wc com</a></li>
</ul>
<h2>wc shop_coupon</h2>
<h3>wc shop_coupon list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--code</h4>
<p>Limit result set to resources with a specific code.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_coupon create</h3>
<h4>--code</h4>
<p>Coupon code. (<strong>Required</strong>)</p>
<h4>--amount</h4>
<p>The amount of discount. Should always be numeric, even if setting a percentage.</p>
<h4>--discount_type</h4>
<p>Determines the type of discount that will be applied.</p>
<h4>--description</h4>
<p>Coupon description.</p>
<h4>--date_expires</h4>
<p>The date the coupon expires, in the site's timezone.</p>
<h4>--date_expires_gmt</h4>
<p>The date the coupon expires, as GMT.</p>
<h4>--individual_use</h4>
<p>If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.</p>
<h4>--product_ids</h4>
<p>List of product IDs the coupon can be used on.</p>
<h4>--excluded_product_ids</h4>
<p>List of product IDs the coupon cannot be used on.</p>
<h4>--usage_limit</h4>
<p>How many times the coupon can be used in total.</p>
<h4>--usage_limit_per_user</h4>
<p>How many times the coupon can be used per customer.</p>
<h4>--limit_usage_to_x_items</h4>
<p>Max number of items in the cart the coupon can be applied to.</p>
<h4>--free_shipping</h4>
<p>If true and if the free shipping method requires a coupon, this coupon will enable free shipping.</p>
<h4>--product_categories</h4>
<p>List of category IDs the coupon applies to.</p>
<h4>--excluded_product_categories</h4>
<p>List of category IDs the coupon does not apply to.</p>
<h4>--exclude_sale_items</h4>
<p>If true, this coupon will not be applied to items that have sale prices.</p>
<h4>--minimum_amount</h4>
<p>Minimum order amount that needs to be in the cart before coupon applies.</p>
<h4>--maximum_amount</h4>
<p>Maximum order amount allowed when using the coupon.</p>
<h4>--email_restrictions</h4>
<p>List of email addresses that can use this coupon.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shop_coupon get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_coupon update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--code</h4>
<p>Coupon code.</p>
<h4>--amount</h4>
<p>The amount of discount. Should always be numeric, even if setting a percentage.</p>
<h4>--discount_type</h4>
<p>Determines the type of discount that will be applied.</p>
<h4>--description</h4>
<p>Coupon description.</p>
<h4>--date_expires</h4>
<p>The date the coupon expires, in the site's timezone.</p>
<h4>--date_expires_gmt</h4>
<p>The date the coupon expires, as GMT.</p>
<h4>--individual_use</h4>
<p>If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.</p>
<h4>--product_ids</h4>
<p>List of product IDs the coupon can be used on.</p>
<h4>--excluded_product_ids</h4>
<p>List of product IDs the coupon cannot be used on.</p>
<h4>--usage_limit</h4>
<p>How many times the coupon can be used in total.</p>
<h4>--usage_limit_per_user</h4>
<p>How many times the coupon can be used per customer.</p>
<h4>--limit_usage_to_x_items</h4>
<p>Max number of items in the cart the coupon can be applied to.</p>
<h4>--free_shipping</h4>
<p>If true and if the free shipping method requires a coupon, this coupon will enable free shipping.</p>
<h4>--product_categories</h4>
<p>List of category IDs the coupon applies to.</p>
<h4>--excluded_product_categories</h4>
<p>List of category IDs the coupon does not apply to.</p>
<h4>--exclude_sale_items</h4>
<p>If true, this coupon will not be applied to items that have sale prices.</p>
<h4>--minimum_amount</h4>
<p>Minimum order amount that needs to be in the cart before coupon applies.</p>
<h4>--maximum_amount</h4>
<p>Maximum order amount allowed when using the coupon.</p>
<h4>--email_restrictions</h4>
<p>List of email addresses that can use this coupon.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shop_coupon delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc customer_download</h2>
<h3>wc customer_download list &lt;customer_id&gt;</h3>
<h4>--customer_id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h2>wc customer</h2>
<h3>wc customer list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific IDs.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--email</h4>
<p>Limit result set to resources with a specific email.</p>
<h4>--role</h4>
<p>Limit result set to resources with a specific role.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc customer create</h3>
<h4>--email</h4>
<p>New user email address. (<strong>Required</strong>)</p>
<h4>--first_name</h4>
<p>Customer first name.</p>
<h4>--last_name</h4>
<p>Customer last name.</p>
<h4>--username</h4>
<p>New user username.</p>
<h4>--password</h4>
<p>New user password. (<strong>Required</strong>)</p>
<h4>--billing</h4>
<p>List of billing address data.</p>
<h4>--shipping</h4>
<p>List of shipping address data.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc customer get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc customer update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--email</h4>
<p>The email address for the customer.</p>
<h4>--first_name</h4>
<p>Customer first name.</p>
<h4>--last_name</h4>
<p>Customer last name.</p>
<h4>--username</h4>
<p>Customer login name.</p>
<h4>--password</h4>
<p>Customer password.</p>
<h4>--billing</h4>
<p>List of billing address data.</p>
<h4>--shipping</h4>
<p>List of shipping address data.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc customer delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--reassign</h4>
<p>ID to reassign posts to.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc order_note</h2>
<h3>wc order_note list &lt;order_id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--type</h4>
<p>Limit result to customers or internal notes.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc order_note create &lt;order_id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--note</h4>
<p>Order note content. (<strong>Required</strong>)</p>
<h4>--customer_note</h4>
<p>If true, the note will be shown to customers and they will be notified. If false, the note will be for admin reference only.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc order_note get &lt;order_id&gt; &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc order_note delete &lt;order_id&gt; &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc shop_order_refund</h2>
<h3>wc shop_order_refund list &lt;order_id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--parent</h4>
<p>Limit result set to those of particular parent IDs.</p>
<h4>--parent_exclude</h4>
<p>Limit result set to all items except those of a particular parent ID.</p>
<h4>--dp</h4>
<p>Number of decimal points to use in each resource.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_order_refund create &lt;order_id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--amount</h4>
<p>Refund amount.</p>
<h4>--reason</h4>
<p>Reason for refund.</p>
<h4>--refunded_by</h4>
<p>User ID of user who created the refund.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--line_items</h4>
<p>Line items data.</p>
<h4>--api_refund</h4>
<p>When true, the payment gateway API is used to generate the refund.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shop_order_refund get &lt;order_id&gt; &lt;id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_order_refund delete &lt;order_id&gt; &lt;id&gt;</h3>
<h4>--order_id</h4>
<p>The order ID.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc shop_order</h2>
<h3>wc shop_order list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--parent</h4>
<p>Limit result set to those of particular parent IDs.</p>
<h4>--parent_exclude</h4>
<p>Limit result set to all items except those of a particular parent ID.</p>
<h4>--status</h4>
<p>Limit result set to orders assigned a specific status.</p>
<h4>--customer</h4>
<p>Limit result set to orders assigned a specific customer.</p>
<h4>--product</h4>
<p>Limit result set to orders assigned a specific product.</p>
<h4>--dp</h4>
<p>Number of decimal points to use in each resource.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_order create</h3>
<h4>--parent_id</h4>
<p>Parent order ID.</p>
<h4>--status</h4>
<p>Order status.</p>
<h4>--currency</h4>
<p>Currency the order was created with, in ISO format.</p>
<h4>--customer_id</h4>
<p>User ID who owns the order. 0 for guests.</p>
<h4>--customer_note</h4>
<p>Note left by customer during checkout.</p>
<h4>--billing</h4>
<p>Billing address.</p>
<h4>--shipping</h4>
<p>Shipping address.</p>
<h4>--payment_method</h4>
<p>Payment method ID.</p>
<h4>--payment_method_title</h4>
<p>Payment method title.</p>
<h4>--transaction_id</h4>
<p>Unique transaction ID.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--line_items</h4>
<p>Line items data.</p>
<h4>--shipping_lines</h4>
<p>Shipping lines data.</p>
<h4>--fee_lines</h4>
<p>Fee lines data.</p>
<h4>--coupon_lines</h4>
<p>Coupons line data.</p>
<h4>--set_paid</h4>
<p>Define if the order is paid. It will set the status to processing and reduce stock items.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shop_order get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shop_order update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--parent_id</h4>
<p>Parent order ID.</p>
<h4>--status</h4>
<p>Order status.</p>
<h4>--currency</h4>
<p>Currency the order was created with, in ISO format.</p>
<h4>--customer_id</h4>
<p>User ID who owns the order. 0 for guests.</p>
<h4>--customer_note</h4>
<p>Note left by customer during checkout.</p>
<h4>--billing</h4>
<p>Billing address.</p>
<h4>--shipping</h4>
<p>Shipping address.</p>
<h4>--payment_method</h4>
<p>Payment method ID.</p>
<h4>--payment_method_title</h4>
<p>Payment method title.</p>
<h4>--transaction_id</h4>
<p>Unique transaction ID.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--line_items</h4>
<p>Line items data.</p>
<h4>--shipping_lines</h4>
<p>Shipping lines data.</p>
<h4>--fee_lines</h4>
<p>Fee lines data.</p>
<h4>--coupon_lines</h4>
<p>Coupons line data.</p>
<h4>--set_paid</h4>
<p>Define if the order is paid. It will set the status to processing and reduce stock items.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shop_order delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_attribute_term</h2>
<h3>wc product_attribute_term list &lt;attribute_id&gt;</h3>
<h4>--attribute_id</h4>
<p>Unique identifier for the attribute of the terms.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific ids.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by resource attribute.</p>
<h4>--hide_empty</h4>
<p>Whether to hide resources not assigned to any products.</p>
<h4>--parent</h4>
<p>Limit result set to resources assigned to a specific parent.</p>
<h4>--product</h4>
<p>Limit result set to resources assigned to a specific product.</p>
<h4>--slug</h4>
<p>Limit result set to resources with a specific slug.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_attribute_term create &lt;attribute_id&gt;</h3>
<h4>--attribute_id</h4>
<p>Unique identifier for the attribute of the terms.</p>
<h4>--name</h4>
<p>Name for the resource. (<strong>Required</strong>)</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_attribute_term get &lt;attribute_id&gt; &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--attribute_id</h4>
<p>Unique identifier for the attribute of the terms.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_attribute_term update &lt;attribute_id&gt; &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--attribute_id</h4>
<p>Unique identifier for the attribute of the terms.</p>
<h4>--name</h4>
<p>Term name.</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_attribute_term delete &lt;attribute_id&gt; &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--attribute_id</h4>
<p>Unique identifier for the attribute of the terms.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_attribute</h2>
<h3>wc product_attribute list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_attribute create</h3>
<h4>--name</h4>
<p>Name for the resource. (<strong>Required</strong>)</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--type</h4>
<p>Type of attribute.</p>
<h4>--order_by</h4>
<p>Default sort order.</p>
<h4>--has_archives</h4>
<p>Enable/Disable attribute archives.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_attribute get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_attribute update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>Attribute name.</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--type</h4>
<p>Type of attribute.</p>
<h4>--order_by</h4>
<p>Default sort order.</p>
<h4>--has_archives</h4>
<p>Enable/Disable attribute archives.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_attribute delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_cat</h2>
<h3>wc product_cat list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific ids.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by resource attribute.</p>
<h4>--hide_empty</h4>
<p>Whether to hide resources not assigned to any products.</p>
<h4>--parent</h4>
<p>Limit result set to resources assigned to a specific parent.</p>
<h4>--product</h4>
<p>Limit result set to resources assigned to a specific product.</p>
<h4>--slug</h4>
<p>Limit result set to resources with a specific slug.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_cat create</h3>
<h4>--name</h4>
<p>Name for the resource. (<strong>Required</strong>)</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--parent</h4>
<p>The ID for the parent of the resource.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--display</h4>
<p>Category archive display type.</p>
<h4>--image</h4>
<p>Image data.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_cat get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_cat update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>Category name.</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--parent</h4>
<p>The ID for the parent of the resource.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--display</h4>
<p>Category archive display type.</p>
<h4>--image</h4>
<p>Image data.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_cat delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_review</h2>
<h3>wc product_review list &lt;product_id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the variation.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_review create &lt;product_id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the variation.</p>
<h4>--review</h4>
<p>Review content. (<strong>Required</strong>)</p>
<h4>--date_created</h4>
<p>The date the review was created, in the site's timezone.</p>
<h4>--date_created_gmt</h4>
<p>The date the review was created, as GMT.</p>
<h4>--rating</h4>
<p>Review rating (0 to 5).</p>
<h4>--name</h4>
<p>Name of the reviewer. (<strong>Required</strong>)</p>
<h4>--email</h4>
<p>Email of the reviewer. (<strong>Required</strong>)</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_review get &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_review update &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--review</h4>
<p>The content of the review.</p>
<h4>--date_created</h4>
<p>The date the review was created, in the site's timezone.</p>
<h4>--date_created_gmt</h4>
<p>The date the review was created, as GMT.</p>
<h4>--rating</h4>
<p>Review rating (0 to 5).</p>
<h4>--name</h4>
<p>Reviewer name.</p>
<h4>--email</h4>
<p>Reviewer email.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_review delete &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_shipping_class</h2>
<h3>wc product_shipping_class list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific ids.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by resource attribute.</p>
<h4>--hide_empty</h4>
<p>Whether to hide resources not assigned to any products.</p>
<h4>--product</h4>
<p>Limit result set to resources assigned to a specific product.</p>
<h4>--slug</h4>
<p>Limit result set to resources with a specific slug.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_shipping_class create</h3>
<h4>--name</h4>
<p>Name for the resource. (<strong>Required</strong>)</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_shipping_class get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_shipping_class update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>Shipping class name.</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_shipping_class delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_tag</h2>
<h3>wc product_tag list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific ids.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by resource attribute.</p>
<h4>--hide_empty</h4>
<p>Whether to hide resources not assigned to any products.</p>
<h4>--product</h4>
<p>Limit result set to resources assigned to a specific product.</p>
<h4>--slug</h4>
<p>Limit result set to resources with a specific slug.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_tag create</h3>
<h4>--name</h4>
<p>Name for the resource. (<strong>Required</strong>)</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_tag get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_tag update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>Tag name.</p>
<h4>--slug</h4>
<p>An alphanumeric identifier for the resource unique to its type.</p>
<h4>--description</h4>
<p>HTML description of the resource.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_tag delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product</h2>
<h3>wc product list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--parent</h4>
<p>Limit result set to those of particular parent IDs.</p>
<h4>--parent_exclude</h4>
<p>Limit result set to all items except those of a particular parent ID.</p>
<h4>--slug</h4>
<p>Limit result set to products with a specific slug.</p>
<h4>--status</h4>
<p>Limit result set to products assigned a specific status.</p>
<h4>--type</h4>
<p>Limit result set to products assigned a specific type.</p>
<h4>--sku</h4>
<p>Limit result set to products with a specific SKU.</p>
<h4>--featured</h4>
<p>Limit result set to featured products.</p>
<h4>--category</h4>
<p>Limit result set to products assigned a specific category ID.</p>
<h4>--tag</h4>
<p>Limit result set to products assigned a specific tag ID.</p>
<h4>--shipping_class</h4>
<p>Limit result set to products assigned a specific shipping class ID.</p>
<h4>--attribute</h4>
<p>Limit result set to products with a specific attribute.</p>
<h4>--attribute_term</h4>
<p>Limit result set to products with a specific attribute term ID (required an assigned attribute).</p>
<h4>--tax_class</h4>
<p>Limit result set to products with a specific tax class.</p>
<h4>--in_stock</h4>
<p>Limit result set to products in stock or out of stock.</p>
<h4>--on_sale</h4>
<p>Limit result set to products on sale.</p>
<h4>--min_price</h4>
<p>Limit result set to products based on a minimum price.</p>
<h4>--max_price</h4>
<p>Limit result set to products based on a maximum price.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product create</h3>
<h4>--name</h4>
<p>Product name.</p>
<h4>--slug</h4>
<p>Product slug.</p>
<h4>--type</h4>
<p>Product type.</p>
<h4>--status</h4>
<p>Product status (post status).</p>
<h4>--featured</h4>
<p>Featured product.</p>
<h4>--catalog_visibility</h4>
<p>Catalog visibility.</p>
<h4>--description</h4>
<p>Product description.</p>
<h4>--short_description</h4>
<p>Product short description.</p>
<h4>--sku</h4>
<p>Unique identifier.</p>
<h4>--regular_price</h4>
<p>Product regular price.</p>
<h4>--sale_price</h4>
<p>Product sale price.</p>
<h4>--date_on_sale_from</h4>
<p>Start date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_from_gmt</h4>
<p>Start date of sale price, as GMT.</p>
<h4>--date_on_sale_to</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_to_gmt</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--virtual</h4>
<p>If the product is virtual.</p>
<h4>--downloadable</h4>
<p>If the product is downloadable.</p>
<h4>--downloads</h4>
<p>List of downloadable files.</p>
<h4>--download_limit</h4>
<p>Number of times downloadable files can be downloaded after purchase.</p>
<h4>--download_expiry</h4>
<p>Number of days until access to downloadable files expires.</p>
<h4>--external_url</h4>
<p>Product external URL. Only for external products.</p>
<h4>--button_text</h4>
<p>Product external button text. Only for external products.</p>
<h4>--tax_status</h4>
<p>Tax status.</p>
<h4>--tax_class</h4>
<p>Tax class.</p>
<h4>--manage_stock</h4>
<p>Stock management at product level.</p>
<h4>--stock_quantity</h4>
<p>Stock quantity.</p>
<h4>--in_stock</h4>
<p>Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.</p>
<h4>--backorders</h4>
<p>If managing stock, this controls if backorders are allowed.</p>
<h4>--sold_individually</h4>
<p>Allow one item to be bought in a single order.</p>
<h4>--weight</h4>
<p>Product weight (lbs).</p>
<h4>--dimensions</h4>
<p>Product dimensions.</p>
<h4>--shipping_class</h4>
<p>Shipping class slug.</p>
<h4>--reviews_allowed</h4>
<p>Allow reviews.</p>
<h4>--upsell_ids</h4>
<p>List of up-sell products IDs.</p>
<h4>--cross_sell_ids</h4>
<p>List of cross-sell products IDs.</p>
<h4>--parent_id</h4>
<p>Product parent ID.</p>
<h4>--purchase_note</h4>
<p>Optional note to send the customer after purchase.</p>
<h4>--categories</h4>
<p>List of categories.</p>
<h4>--tags</h4>
<p>List of tags.</p>
<h4>--images</h4>
<p>List of images.</p>
<h4>--attributes</h4>
<p>List of attributes.</p>
<h4>--default_attributes</h4>
<p>Defaults variation attributes.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort products.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>Product name.</p>
<h4>--slug</h4>
<p>Product slug.</p>
<h4>--type</h4>
<p>Product type.</p>
<h4>--status</h4>
<p>Product status (post status).</p>
<h4>--featured</h4>
<p>Featured product.</p>
<h4>--catalog_visibility</h4>
<p>Catalog visibility.</p>
<h4>--description</h4>
<p>Product description.</p>
<h4>--short_description</h4>
<p>Product short description.</p>
<h4>--sku</h4>
<p>Unique identifier.</p>
<h4>--regular_price</h4>
<p>Product regular price.</p>
<h4>--sale_price</h4>
<p>Product sale price.</p>
<h4>--date_on_sale_from</h4>
<p>Start date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_from_gmt</h4>
<p>Start date of sale price, as GMT.</p>
<h4>--date_on_sale_to</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_to_gmt</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--virtual</h4>
<p>If the product is virtual.</p>
<h4>--downloadable</h4>
<p>If the product is downloadable.</p>
<h4>--downloads</h4>
<p>List of downloadable files.</p>
<h4>--download_limit</h4>
<p>Number of times downloadable files can be downloaded after purchase.</p>
<h4>--download_expiry</h4>
<p>Number of days until access to downloadable files expires.</p>
<h4>--external_url</h4>
<p>Product external URL. Only for external products.</p>
<h4>--button_text</h4>
<p>Product external button text. Only for external products.</p>
<h4>--tax_status</h4>
<p>Tax status.</p>
<h4>--tax_class</h4>
<p>Tax class.</p>
<h4>--manage_stock</h4>
<p>Stock management at product level.</p>
<h4>--stock_quantity</h4>
<p>Stock quantity.</p>
<h4>--in_stock</h4>
<p>Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.</p>
<h4>--backorders</h4>
<p>If managing stock, this controls if backorders are allowed.</p>
<h4>--sold_individually</h4>
<p>Allow one item to be bought in a single order.</p>
<h4>--weight</h4>
<p>Product weight (lbs).</p>
<h4>--dimensions</h4>
<p>Product dimensions.</p>
<h4>--shipping_class</h4>
<p>Shipping class slug.</p>
<h4>--reviews_allowed</h4>
<p>Allow reviews.</p>
<h4>--upsell_ids</h4>
<p>List of up-sell products IDs.</p>
<h4>--cross_sell_ids</h4>
<p>List of cross-sell products IDs.</p>
<h4>--parent_id</h4>
<p>Product parent ID.</p>
<h4>--purchase_note</h4>
<p>Optional note to send the customer after purchase.</p>
<h4>--categories</h4>
<p>List of categories.</p>
<h4>--tags</h4>
<p>List of tags.</p>
<h4>--images</h4>
<p>List of images.</p>
<h4>--attributes</h4>
<p>List of attributes.</p>
<h4>--default_attributes</h4>
<p>Defaults variation attributes.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort products.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc product_variation</h2>
<h3>wc product_variation list &lt;product_id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--parent</h4>
<p>Limit result set to those of particular parent IDs.</p>
<h4>--parent_exclude</h4>
<p>Limit result set to all items except those of a particular parent ID.</p>
<h4>--slug</h4>
<p>Limit result set to products with a specific slug.</p>
<h4>--status</h4>
<p>Limit result set to products assigned a specific status.</p>
<h4>--type</h4>
<p>Limit result set to products assigned a specific type.</p>
<h4>--sku</h4>
<p>Limit result set to products with a specific SKU.</p>
<h4>--featured</h4>
<p>Limit result set to featured products.</p>
<h4>--category</h4>
<p>Limit result set to products assigned a specific category ID.</p>
<h4>--tag</h4>
<p>Limit result set to products assigned a specific tag ID.</p>
<h4>--shipping_class</h4>
<p>Limit result set to products assigned a specific shipping class ID.</p>
<h4>--attribute</h4>
<p>Limit result set to products with a specific attribute.</p>
<h4>--attribute_term</h4>
<p>Limit result set to products with a specific attribute term ID (required an assigned attribute).</p>
<h4>--tax_class</h4>
<p>Limit result set to products with a specific tax class.</p>
<h4>--in_stock</h4>
<p>Limit result set to products in stock or out of stock.</p>
<h4>--on_sale</h4>
<p>Limit result set to products on sale.</p>
<h4>--min_price</h4>
<p>Limit result set to products based on a minimum price.</p>
<h4>--max_price</h4>
<p>Limit result set to products based on a maximum price.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_variation create &lt;product_id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--description</h4>
<p>Variation description.</p>
<h4>--sku</h4>
<p>Unique identifier.</p>
<h4>--regular_price</h4>
<p>Variation regular price.</p>
<h4>--sale_price</h4>
<p>Variation sale price.</p>
<h4>--date_on_sale_from</h4>
<p>Start date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_from_gmt</h4>
<p>Start date of sale price, as GMT.</p>
<h4>--date_on_sale_to</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_to_gmt</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--visible</h4>
<p>Define if the attribute is visible on the "Additional information" tab in the product's page.</p>
<h4>--virtual</h4>
<p>If the variation is virtual.</p>
<h4>--downloadable</h4>
<p>If the variation is downloadable.</p>
<h4>--downloads</h4>
<p>List of downloadable files.</p>
<h4>--download_limit</h4>
<p>Number of times downloadable files can be downloaded after purchase.</p>
<h4>--download_expiry</h4>
<p>Number of days until access to downloadable files expires.</p>
<h4>--tax_status</h4>
<p>Tax status.</p>
<h4>--tax_class</h4>
<p>Tax class.</p>
<h4>--manage_stock</h4>
<p>Stock management at variation level.</p>
<h4>--stock_quantity</h4>
<p>Stock quantity.</p>
<h4>--in_stock</h4>
<p>Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.</p>
<h4>--backorders</h4>
<p>If managing stock, this controls if backorders are allowed.</p>
<h4>--weight</h4>
<p>Variation weight (lbs).</p>
<h4>--dimensions</h4>
<p>Variation dimensions.</p>
<h4>--shipping_class</h4>
<p>Shipping class slug.</p>
<h4>--image</h4>
<p>Variation image data.</p>
<h4>--attributes</h4>
<p>List of attributes.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort products.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_variation get &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the variation.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc product_variation update &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the variation.</p>
<h4>--description</h4>
<p>Variation description.</p>
<h4>--sku</h4>
<p>Unique identifier.</p>
<h4>--regular_price</h4>
<p>Variation regular price.</p>
<h4>--sale_price</h4>
<p>Variation sale price.</p>
<h4>--date_on_sale_from</h4>
<p>Start date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_from_gmt</h4>
<p>Start date of sale price, as GMT.</p>
<h4>--date_on_sale_to</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--date_on_sale_to_gmt</h4>
<p>End date of sale price, in the site's timezone.</p>
<h4>--visible</h4>
<p>Define if the attribute is visible on the "Additional information" tab in the product's page.</p>
<h4>--virtual</h4>
<p>If the variation is virtual.</p>
<h4>--downloadable</h4>
<p>If the variation is downloadable.</p>
<h4>--downloads</h4>
<p>List of downloadable files.</p>
<h4>--download_limit</h4>
<p>Number of times downloadable files can be downloaded after purchase.</p>
<h4>--download_expiry</h4>
<p>Number of days until access to downloadable files expires.</p>
<h4>--tax_status</h4>
<p>Tax status.</p>
<h4>--tax_class</h4>
<p>Tax class.</p>
<h4>--manage_stock</h4>
<p>Stock management at variation level.</p>
<h4>--stock_quantity</h4>
<p>Stock quantity.</p>
<h4>--in_stock</h4>
<p>Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.</p>
<h4>--backorders</h4>
<p>If managing stock, this controls if backorders are allowed.</p>
<h4>--weight</h4>
<p>Variation weight (lbs).</p>
<h4>--dimensions</h4>
<p>Variation dimensions.</p>
<h4>--shipping_class</h4>
<p>Shipping class slug.</p>
<h4>--image</h4>
<p>Variation image data.</p>
<h4>--attributes</h4>
<p>List of attributes.</p>
<h4>--menu_order</h4>
<p>Menu order, used to custom sort products.</p>
<h4>--meta_data</h4>
<p>Meta data.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc product_variation delete &lt;product_id&gt; &lt;id&gt;</h3>
<h4>--product_id</h4>
<p>Unique identifier for the variable product.</p>
<h4>--id</h4>
<p>Unique identifier for the variation.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc setting</h2>
<h3>wc setting get &lt;id&gt;</h3>
<h4>--group</h4>
<p>Settings group ID.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc setting get &lt;id&gt;</h3>
<h4>--group</h4>
<p>Settings group ID.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc setting update &lt;id&gt;</h3>
<h4>--group</h4>
<p>Settings group ID.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--value</h4>
<p>Setting value.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc shipping_zone</h2>
<h3>wc shipping_zone list</h3>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shipping_zone create</h3>
<h4>--name</h4>
<p>Shipping zone name. (<strong>Required</strong>)</p>
<h4>--order</h4>
<p>Shipping zone order.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shipping_zone get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique ID for the resource.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shipping_zone update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique ID for the resource.</p>
<h4>--name</h4>
<p>Shipping zone name.</p>
<h4>--order</h4>
<p>Shipping zone order.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shipping_zone delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique ID for the resource.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc shipping_zone_location</h2>
<h3>wc shipping_zone_location list</h3>
<h4>--id</h4>
<p>Unique ID for the resource.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h2>wc shipping_zone_method</h2>
<h3>wc shipping_zone_method list</h3>
<h4>--zone_id</h4>
<p>Unique ID for the zone.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shipping_zone_method create</h3>
<h4>--zone_id</h4>
<p>Unique ID for the zone.</p>
<h4>--order</h4>
<p>Shipping method sort order.</p>
<h4>--enabled</h4>
<p>Shipping method enabled status.</p>
<h4>--settings</h4>
<p>Shipping method settings.</p>
<h4>--method_id</h4>
<p>Shipping method ID. (<strong>Required</strong>)</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shipping_zone_method get &lt;id&gt;</h3>
<h4>--zone_id</h4>
<p>Unique ID for the zone.</p>
<h4>--instance_id</h4>
<p>Unique ID for the instance.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shipping_zone_method update &lt;id&gt;</h3>
<h4>--zone_id</h4>
<p>Unique ID for the zone.</p>
<h4>--instance_id</h4>
<p>Unique ID for the instance.</p>
<h4>--order</h4>
<p>Shipping method sort order.</p>
<h4>--enabled</h4>
<p>Shipping method enabled status.</p>
<h4>--settings</h4>
<p>Shipping method settings.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc shipping_zone_method delete &lt;id&gt;</h3>
<h4>--zone_id</h4>
<p>Unique ID for the zone.</p>
<h4>--instance_id</h4>
<p>Unique ID for the instance.</p>
<h4>--force</h4>
<p>Whether to bypass trash and force deletion.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc tax_class</h2>
<h3>wc tax_class list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc tax_class create</h3>
<h4>--name</h4>
<p>Tax class name. (<strong>Required</strong>)</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc tax_class delete &lt;id&gt;</h3>
<h4>--slug</h4>
<p>Unique slug for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc tax</h2>
<h3>wc tax list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific IDs.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--class</h4>
<p>Sort by tax class.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc tax create</h3>
<h4>--country</h4>
<p>Country ISO 3166 code.</p>
<h4>--state</h4>
<p>State code.</p>
<h4>--postcode</h4>
<p>Postcode / ZIP.</p>
<h4>--city</h4>
<p>City name.</p>
<h4>--rate</h4>
<p>Tax rate.</p>
<h4>--name</h4>
<p>Tax rate name.</p>
<h4>--priority</h4>
<p>Tax priority.</p>
<h4>--compound</h4>
<p>Whether or not this is a compound rate.</p>
<h4>--shipping</h4>
<p>Whether or not this tax rate also gets applied to shipping.</p>
<h4>--order</h4>
<p>Indicates the order that will appear in queries.</p>
<h4>--class</h4>
<p>Tax class.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc tax get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc tax update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--country</h4>
<p>Country ISO 3166 code.</p>
<h4>--state</h4>
<p>State code.</p>
<h4>--postcode</h4>
<p>Postcode / ZIP.</p>
<h4>--city</h4>
<p>City name.</p>
<h4>--rate</h4>
<p>Tax rate.</p>
<h4>--name</h4>
<p>Tax rate name.</p>
<h4>--priority</h4>
<p>Tax priority.</p>
<h4>--compound</h4>
<p>Whether or not this is a compound rate.</p>
<h4>--shipping</h4>
<p>Whether or not this tax rate also gets applied to shipping.</p>
<h4>--order</h4>
<p>Indicates the order that will appear in queries.</p>
<h4>--class</h4>
<p>Tax class.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc tax delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc webhook_delivery</h2>
<h3>wc webhook_delivery list</h3>
<h4>--webhook_id</h4>
<p>Unique identifier for the webhook.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc webhook_delivery get &lt;id&gt;</h3>
<h4>--webhook_id</h4>
<p>Unique identifier for the webhook.</p>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h2>wc webhook</h2>
<h3>wc webhook list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--page</h4>
<p>Current page of the collection.</p>
<h4>--per_page</h4>
<p>Maximum number of items to be returned in result set. Defaults to 100 items.</p>
<h4>--search</h4>
<p>Limit results to those matching a string.</p>
<h4>--after</h4>
<p>Limit response to resources published after a given ISO8601 compliant date.</p>
<h4>--before</h4>
<p>Limit response to resources published before a given ISO8601 compliant date.</p>
<h4>--exclude</h4>
<p>Ensure result set excludes specific IDs.</p>
<h4>--include</h4>
<p>Limit result set to specific ids.</p>
<h4>--offset</h4>
<p>Offset the result set by a specific number of items.</p>
<h4>--order</h4>
<p>Order sort attribute ascending or descending.</p>
<h4>--orderby</h4>
<p>Sort collection by object attribute.</p>
<h4>--status</h4>
<p>Limit result set to webhooks assigned a specific status.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc webhook create</h3>
<h4>--name</h4>
<p>A friendly name for the webhook.</p>
<h4>--status</h4>
<p>Webhook status.</p>
<h4>--topic</h4>
<p>Webhook topic. (<strong>Required</strong>)</p>
<h4>--secret</h4>
<p>Webhook secret. (<strong>Required</strong>)</p>
<h4>--delivery_url</h4>
<p>Webhook delivery URL. (<strong>Required</strong>)</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc webhook get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc webhook update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--name</h4>
<p>A friendly name for the webhook.</p>
<h4>--status</h4>
<p>Webhook status.</p>
<h4>--topic</h4>
<p>Webhook topic.</p>
<h4>--secret</h4>
<p>Secret key used to generate a hash of the delivered webhook and provided in the request headers. This will default is a MD5 hash from the current user's ID|username if not provided.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h3>wc webhook delete &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--force</h4>
<p>Required to be true, as resource does not support trashing.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc shipping_method</h2>
<h3>wc shipping_method list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc shipping_method get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h2>wc payment_gateway</h2>
<h3>wc payment_gateway list</h3>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc payment_gateway get &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--context</h4>
<p>Scope under which the request is made; determines fields present in response.</p>
<h4>--fields</h4>
<p>Limit response to specific fields. Defaults to all fields.</p>
<h4>--field</h4>
<p>Get the value of an individual field.</p>
<h4>--format</h4>
<p>Render response in a particular format.<br>Default: table<br />Options: table, json, csv, ids, yaml, count, headers, body, envelope</p>
<h3>wc payment_gateway update &lt;id&gt;</h3>
<h4>--id</h4>
<p>Unique identifier for the resource.</p>
<h4>--title</h4>
<p>Payment gateway title on checkout.</p>
<h4>--description</h4>
<p>Payment gateway description on checkout.</p>
<h4>--order</h4>
<p>Payment gateway sort order.</p>
<h4>--enabled</h4>
<p>Payment gateway enabled status.</p>
<h4>--settings</h4>
<p>Payment gateway settings.</p>
<h4>--porcelain</h4>
<p>Output just the id when the operation is successful.</p>
<h2>wc com</h2>
<p>The `com` command allows interacting with the WooCommerce.com marketplace via CLI. Connecting to the marketplace via _WooCommerce > Extensions > My Subscriptions_ in your WooCommerce store is required.</p>
<h3>wc com extension list</h3>
<p>Gets a list of extensions available for the store from the marketplace.</p>
<h4>--format</h4>
<p>Render output in a particular format.<br>
---<br>
default: table<br>
options:<br>
– table<br>
– csv<br>
– json<br>
– yaml<br>
---</p>
<h4>--fields</h4>
<p>Limit the output to specific object fields.<br/>
---<br>
default: all<br>
options:<br>
– product_slug<br>
– product_name<br>
– auto_renew<br>
– expires_on<br>
– expired<br>
– sites_max<br>
– sites_active<br>
– maxed<br>
---</p>
<h3>wc com extension install &lt;extension&gt;</h3>
<h4>--extension</h4>
<p>Install one plugin from the available extensions.Accepts a plugin slug</p>
<h4>--force</h4>
<p>If set, the command will overwrite any installed version of the extension without prompting for confirmation.</p>
<h4>--activate</h4>
<p>If set, after installation, the plugin will activate it.</p>
<h4>--activate-network</h4>
<p>If set, the plugin will be network activated immediately after installation </p>
<h4>--insecure</h4>
<p>Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.</p>
