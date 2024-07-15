# Rule

Rules in an array are executed as an AND operation. If there are no rules in the array the result is false and the specified notification is not shown.

## Operations

Some rule types support an `operation` value, which is used to compare two
values. The following operations are implemented:

- `=`
- `<`
- `<=`
- `>`
- `>=`
- `!=`
- `contains`
- `!contains`
- `in` (Added in WooCommerce 8.2.0)
- `!in` (Added in WooCommerce 8.2.0)
- `range` (Added in WooCommerce 8.8.0)

### contains and !contains

`contains` and `!contains` allow checking if the provided value is present (or
not present) in the haystack value. An example of this is using the
`onboarding_profile` rule to match on a value in the `product_types` array -
this rule matches if `physical` was selected as a product type in the
onboarding profile:

```json
{
	"type": "onboarding_profile",
	"index": "product_types",
	"operation": "contains",
	"value": "physical"
}
```

### in and !in

`in` and `!in` allow checking if a value is found (or not found) in a provided array. For example, using the `in` comparison operator to check if the base country location value is found in a given array, as below. This rule matches if the `base_location_country` is `US`, `NZ`, or `ZA`. **NOTE:** These comparisons were added in **WooCommerce 8.2.0**. If the spec is read by an older version of WooCommerce, the rule will evaluate to `false`.

```json
{
	"type": "base_location_country",
  	"value": [
		"US",
		"NZ",
		"ZA"
	],
	"operation": "in"
}
```

### range

`range` operator performs an inclusive check to determine if a number falls within a certain range.
This means that both the 'from' and 'to' values of the specified range are included in the check. 

The following rule returns true when `woocommerce_remote_variant_assignment` value is between 1 and 10.

```json
{
  "type": "option",
  "value": [ 1, 10 ],
  "default": 0,
  "operation": "range",
  "option_name": "woocommerce_remote_variant_assignment",
}
```

## Plugins activated

This passes if all of the listed plugins are installed and activated.

`plugins` is required.

```json
{
	"type": "plugins_activated",
	"plugins": [
		"plugin-slug-1',
		"plugin-slug-2"
	]
}
```

## Publish after time

This passes if the system time is after the specified date/time.

Note that using both `publish_after_time` and `publish_before_time` allows timeboxing a note which could be useful for promoting a sale.

`publish_after` is required.

```json
{
	"type": "publish_after_time",
	"publish_after": "2020-04-22 00:00:00"
}
```

## Publish before time

This passes if the system time is before the specified date/time.

Note that using both `publish_after_time` and `publish_before_time` allows timeboxing a note which could be useful for promoting a sale.

`publish_before` is required.

```json
{
	"type": "publish_before_time",
	"publish_before": "2020-04-22 00:00:00"
}
```

## Not

This negates the rules in the provided set of rules. Note that the rules in `operand` get ANDed together into a single boolean.

`operand` is required.

```json
{
	"type": "not",
	"operand": [
		<Rule>,
		...
	]
}
```

## Or

This performs an OR operation on the operands, passing if any of the operands evaluates to true. Note that if the operands are an array of `Rule`s (as in the first example), each operand is treated as an AND operation.

`operands` is required.

```json
{
	"type": "or",
	"operands": [
		[
			<Rule>,
			...
		],
		[
			<Rule>,
			...
		]
	]
}
```

Alternatively:

```json
{
	"type": "or",
	"operands": [
		<Rule>,
		...
	]
}
```

## Fail

This just returns a false value. This is useful if you want to keep a specification around, but don't want it displayed.

```json
{
	"type": "fail"
}
```

## Plugin version

This compares the installed version of the plugin to the required version, using the comparison operator. If the plugin isn’t activated this returns false.

`plugin`, `version`, and `operator` are required.

This example passes if Jetpack 8.4.1 is installed and activated.

```json
{
	"type": "plugin_version",
	"plugin": "jetpack",
	"version": "8.4.1",
	"operator": "="
}
```

## Stored state

This allows access to a stored state containing calculated values that otherwise would be impossible to reproduce using other rules. It performs the comparison operation against the stored state value.

This example passes if the `there_were_no_products` index is equal to `true`.

```json
{
	"type": "stored_state",
	"index": "there_were_no_products",
	"operation": "=",
	"value": true
}
```

There are only a limited amount of indices available to this rule, and new indices will need a new version of the WC Admin plugin to be installed.

The currently available indices are:

```json
there_were_no_products
there_are_now_products
new_product_count
```

`index`, `operation`, and `value` are required.

## Product count

This passes if the number of products currently in the system match the comparison operation.

This example passes if there are more than 10 products currently in the system.

```json
{
	"type": "product_count",
	"operation": ">",
	"value": 10
}
```

`operation` and `value` are required.

## Order count

This passes if the number of orders currently in the system match the comparison operation.

This example passes if there are more than 10 orders currently in the system.

```json
{
	"type": "order_count",
	"operation": ">",
	"value": 10
}
```

`operation` and `value` are required.

## WooCommerce Admin active for

This passes if the time WooCommerce Admin has been active for (in days) matches the comparison operation.

This is used as a proxy indicator of the age of the shop.

This example passes if it has been active for more than 8 days.

```json
{
	"type": "wcadmin_active_for",
	"operation": ">",
	"days": 8
}
```

`operation` and `days` are required.

## Onboarding profile

This allows access to the onboarding profile that was built up in the onboarding wizard. The below example passes when the current revenue selected was "none".

```json
{
	"type": "onboarding_profile",
	"index": "revenue",
	"operation": "=",
	"value": "none"
}
```

`index`, `operation`, and `value` are all required.

## Is eCommerce

This passes when the store is on a WordPress.com site with the eCommerce plan.

```json
{
	"type": "is_ecommerce",
	"value": true
}
```

`value` is required.

## Is Woo Express

This passes when the store is on a WordPress.com site with a Woo Express plan active.
You can optionally pass the `plan` name to target a specific Woo Express plan, e.g. `performance`.

```json
{
	"type": "is_woo_express",
	"plan": "trial|essential|performance",
	"value": true
}
```

`value` is required.
`plan` is optional, e.g. `trial`, `essential`, `performance`.

## Base location - country

This passes when the store is located in the specified country.

```json
{
	"type": "base_location_country",
	"value": "US",
	"operation": "="
}
```

`value` and `operation` are both required.

## Base location - state

This passes when the store is located in the specified state.

```json
{
	"type": "base_location_state",
	"value": "TX",
	"operation": "="
}
```

`value` and `operation` are both required.

## Note status

This passes when the status of the specified note matches the specified status.
The below example passes when the `wc-admin-mobile-app` note has not been
actioned.

```json
{
	"type": "note_status",
	"note_name": "wc-admin-mobile-app",
	"status": "actioned",
	"operation": "!="
}
```

## Total Payments Value

This passes when the total value of all payments for a given timeframe
compared to the provided value pass the operation test.

`timeframe` can be one of `last_week`, `last_month`, `last_quarter`, `last_6_months`, `last_year`

```json
{
	"type": "total_payments_value",
	"timeframe": "last_month",
	"value": 2300,
	"operation": ">"
}
```

`timeframe`, `value`, and `operation` are all required.

## Option

This passes when the option value matches the value using the operation.

```json
{
	"type": "option",
	"option_name": "woocommerce_currency",
	"value": "USD",
	"default": "USD",
	"operation": "="
}
```

`option_name`, `value`, and `operation` are all required. `default` is not required and allows a default value to be used if the option does not exist.

### Option Transformer

This transforms the given option value into a different value by a series of transformers.

Example option value:

```php
Array
(
    [setup_client] =>
    [industry] => Array
        (
            [0] => Array
                (
                    [slug] => food-drink
                )

            [1] => Array
                (
                    [slug] => fashion-apparel-accessories
                )
        )
)
```

If you want to ensure that the industry array contains `fashion-apparel-accessories`, you can use the following Option definition with transformers.

```json
{
	"type": "option",
	"transformers": [
	    {
	        "use": "dot_notation",
	        "arguments": {
	            "path": "industry"
	        }
	    },
	    {
	        "use": "array_column",
	        "arguments": {
	            "key": "slug"
	        }
	    },
	    {
	        "use": "array_search",
	        "arguments": {
	            "value": "fashion-apparel-accessories"
	        }
	    }
	],
	"option_name": "woocommerce_onboarding_profile",
	"value": "fashion-apparel-accessories",
	"default": "USD",
	"operation": "="
}
```

You can find a list of transformers and examples in the transformer [README](./Transformers/README.md).

## WCA updated

This passes when WooCommerce Admin has just been updated. The specs will be run
on update. Note that this doesn't provide a way to check the version number as
the `plugin_version` rule can be used to check for a specific version of the
WooCommerce Admin plugin.

```json
{
	type: "wca_updated"
}
```

No other values are needed.

## Debugging Specification

You can see the evaluation of each specification by turning on an optional evaluation logger.

1. Define `WC_ADMIN_DEBUG_RULE_EVALUATOR` constant in `wp-config.php`. `define('WC_ADMIN_DEBUG_RULE_EVALUATOR', true);`
2. Run `wc_admin_daily`
3. cd to `wp-content/uploads/wc-logs/` and check a log file in `remote-inbox-notifications-:date-hash.log` format.

You can tail the log file with a slug name to see the evaluation of a rule that you are testing.

Example:

`tail -f remote-inbox-notifications-2021-06-15-128.log | grep 'wcpay-promo-2021-6-incentive-2'`
