# Remote Inbox Notifications

This is a remote inbox notifications engine in WooCommerce admin, which polls some JSON feeds (see `DataSourcePoller.php`) containing specifications of remote inbox notifications, including rules that, when satisfied, display the notification in the WooCommerce Admin screens.

The interesting entry points are the poller (`DataSourcePoller.php`) which fetches the rule specifications from a number of feeds, and the engine (`RemoteInboxNotificationsEngine.php`), both of which run daily as part of the `wc-admin-daily` cron task.

During the fetching of the specifications, each specification and the rules within the specification are validated. If a specification fails validation it is not imported or processed.

Following is the structure of the JSON feed, including the different rules that can be used to build up an inbox notification specification.

## JSON file:
```
[
	<spec>,
	...
]
```

## Spec:
```
{
	"slug": "ad-for-automate-woo-2020-04-20",
	"type": "info",
	"status": "unactioned",
	"is_snoozable": 0,
	"source": "woocommerce.com",
	"locales": [
		<Locale>,
		...
	],
	"actions": [
		<Action>,
		...
	],
	"rules": [
		<Rule>,
		...
	]
}
```

The slug *must* be unique across all notes (not just the notes that are being implemented in the feed) otherwise it could lead to unexpected behavior.

Valid values for `type` are:

- `error`
- `warning`
- `update`
- `info`
- `marketing`
- `survey`

`info`, `marketing`, and `survey` types will appear in the inbox. Other types appear in the head of the page.

The `status` is the *initial* note status to be set when the rules are satisfied. Valid values for `status` are:

- `unactioned`
- `actioned`
- `snoozed`

The status will usually be `unactioned`, as this will get the note to appear.

### Locale
The note locales contain the title and content of the note. Having this broken up by locale allows different translations of the note to be used. The default locale used if none of the locales match the WordPress locale is `en_US`.

```
{
	"locale": "en_US",
	"title": "Ad for Automate Woo",
	"content": "Hi There! This is an ad for Automate Woo."
},
{
	"locale": "en_AU",
	"title": "G'day. Ad for Automate Woo, in en-AU.",
	"content": "en-AU content"
},
{
	"locale": "fr_FR",
	"title": "Annonce pour automatiser woo",
	"content": "Salut! Ceci est une publicité pour Automate Woo."
}
```

### Action
These are the actions that can be interacted with on the note. This might be a link to a blog post, or an internal link to an admin page.

```
{
	"name": "install-automate-woo",
	 "locales": [
		<ActionLocale>,
		...
	],
	"url": "?page=automatewoo-dashboard",
	"url_is_admin_query": true,
	"is_primary": true,
	"status": "actioned"
},
{
	"name": "set-up-concierge",
	"locales": [
		{
			"locale": "en_US",
			"label": "Schedule free session"
		}
	],
	"url": "https://wordpress.com/me/concierge",
	"url_is_admin_query": false,
	"is_primary": false,
	"status": "actioned"
},
```

`name` must be unique to the created note.

The action locales contain the label of the action. Having this broken up by locale allows different translations of the note to be used. The default locale used if none of the locales match the WordPress locale is `en_US`.

The `status` is what the status of the created note will be set to after interacting with the action.

#### ActionLocale
```
{
	"locale": "en_US",
	"label": "Install"
}
```

## Rule
Rules in an array are executed as an AND operation. If there are no rules in the array the result is false and the specified notification is not shown.

### Plugins activated
This passes if all of the listed plugins are installed and activated.

`plugins` is required.

```
{
	"type": "plugins_activated",
	"plugins": [
		"plugin-slug-1',
		"plugin-slug-2"
	]
}
```

### Publish after time
This passes if the system time is after the specified date/time.

Note that using both `publish_after_time` and `publish_before_time` allows timeboxing a note which could be useful for promoting a sale.

`publish_after` is required.

```
{
	"type": "publish_after_time",
	"publish_after": "2020-04-22 00:00:00"
}
```

### Publish before time
This passes if the system time is before the specified date/time.

Note that using both `publish_after_time` and `publish_before_time` allows timeboxing a note which could be useful for promoting a sale.

`publish_before` is required.

```
{
	"type": "publish_before_time",
	"publish_before": "2020-04-22 00:00:00"
}
```

### Not
This negates the rules in the provided set of rules. Note that the rules in `operand` get ANDed together into a single boolean.

`operand` is required.

```
{
	"type": "not",
	"operand": [
		<Rule>,
		...
	]
}
```

### Or
This performs an OR operation on the operands, passing if any of the operands evaluates to true. Note that if the operands are an array of `Rule`s (as in the first example), each operand is treated as an AND operation.

`operands` is required.

```
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

```
{
	"type": "or",
	"operands": [
		<Rule>,
		...
	]
}
```

### Fail
This just returns a false value. This is useful if you want to keep a specification around, but don't want it displayed.

```
{
	"type": "fail"
}
```

### Plugin version
This compares the installed version of the plugin to the required version, using the comparison operator. If the plugin isn’t activated this returns false.

`plugin`, `version`, and `operator` are required.

This example passes if Jetpack 8.4.1 is installed and activated.

```
{
	"type": "plugin_version",
	"plugin": "jetpack",
	"version": "8.4.1",
	"operator": "="
}
```

### Stored state
This allows access to a stored state containing calculated values that otherwise would be impossible to reproduce using other rules. It performs the comparison operation against the stored state value.

This example passes if the `there_were_no_products` index is equal to `true`.

```
{
	"type": "stored_state",
	"index": "there_were_no_products",
	"operation": "=",
	"value": true
}
```

There are only a limited amount of indices available to this rule, and new indices will need a new version of the WC Admin plugin to be installed.

The currently available indices are:

```
there_were_no_products
there_are_now_products
```

`index`, `operation`, and `value` are required.

### Product count
This passes if the number of products currently in the system match the comparison operation.

This example passes if there are more than 10 products currently in the system.

```
{
	"type": "product_count",
	"operation": ">",
	"value": 10
}
```

`operation` and `value` are required.

### Order count
This passes if the number of orders currently in the system match the comparison operation.

This example passes if there are more than 10 orders currently in the system.

```
{
	"type": "order_count",
	"operation": ">",
	"value": 10
}
```

`operation` and `value` are required.

### WooCommerce Admin active for
This passes if the time WooCommerce Admin has been active for (in days) matches the comparison operation.

This is used as a proxy indicator of the age of the shop.

This example passes if it has been active for more than 8 days.

```
{
	"type": "wcadmin_active_for",
	"operation": ">",
	"days": 8
}
```

`operation` and `days` are required.

### Onboarding profile
This allows access to the onboarding profile that was built up in the onboarding wizard. The below example passes when the current revenue selected was "none".

```
{
	"type": "onboarding_profile",
	"index": "revenue",
	"operation": "=",
	"value": "none"
}
```

`index`, `operation`, and `value` are all required.

### Is eCommerce
This passes when the store is on a WordPress.com site with the eCommerce plan.

```
{
	"type": "is_ecommerce",
	"value": true
}
```

`value` is required.

### Base location - country
This passes when the store is located in the specified country.

```
{
	"type": "base_location_country",
	"value": "US",
	"operation": "="
}
```

`value` and `operation` are both required.

### Base location - state
This passes when the store is located in the specified state.

```
{
	"type": "base_location_state",
	"value": "TX",
	"operation": "="
}
```

`value` and `operation` are both required.

### Note status
This passes when the status of the specified note matches the specified status.
The below example passes when the `wc-admin-mobile-app` note has not been
actioned.

```
{
	"type": "note_status",
	"note_name": "wc-admin-mobile-app",
	"status": "actioned",
	"operation": "!="
}
```

### Option
This passes when the option value matches the value using the operation.

```
{
	"type": "option",
	"option_name": "woocommerce_currency",
	"value": "USD",
	"default": "USD",
	"operation": "="
}
```

`option_name`, `value`, and `operation` are all required. `default` is not required and allows a default value to be used if the option does not exist.
