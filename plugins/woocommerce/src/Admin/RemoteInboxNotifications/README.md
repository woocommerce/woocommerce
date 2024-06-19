# Remote Inbox Notifications

This is a remote inbox notifications engine in WooCommerce admin, which polls some JSON feeds (see `DataSourcePoller.php`) containing specifications of remote inbox notifications, including rules that, when satisfied, display the notification in the WooCommerce Admin screens.

The interesting entry points are the poller (`DataSourcePoller.php`) which fetches the rule specifications from a number of feeds, and the engine (`RemoteInboxNotificationsEngine.php`), both of which run daily as part of the `wc-admin-daily` cron task.

During the fetching of the specifications, each specification and the rules within the specification are validated. If a specification fails validation it is not imported or processed.

Following is the structure of the JSON feed, including the different rules that can be used to build up an inbox notification specification.

## JSON file

```json
[
	<spec>,
	...
]
```

## Spec

```json
{
	"slug": "ad-for-automate-woo-2020-04-20",
	"type": "info",
	"status": "unactioned",
	"is_snoozable": 0,
	"source": "woo.com",
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

The slug _must_ be unique across all notes (not just the notes that are being implemented in the feed) otherwise it could lead to unexpected behavior.

Valid values for `type` are:

-   `error`: used for presenting error conditions
-   `warning`: used for presenting warning conditions.
-   `update`: used when a new version is available.
-   `info`: used for presenting informational messages.
-   `marketing`: used for adding marketing messages.
-   `survey`: used for adding survey messages.
-   `email`: used for adding notes that will be sent by email.

`info`, `marketing`, and `survey` types will appear in the inbox. `email` type will be sent by email. Other types appear in the head of the page.

The `status` is the _initial_ note status to be set when the rules are satisfied. Valid values for `status` are:

-   `unactioned`: the note has not yet been actioned by a user.
-   `actioned`: the note has had its action completed by a user.
-   `snoozed`: the note has been snoozed by a user.

The status will usually be `unactioned`, as this will get the note to appear.

There are other note statuses but we just use them **internally**:

-   `pending`: the note is pending - hidden but not actioned. When the spec/rules are invalid, the note status will be set to pending.
-   `sent`: the note has been sent by email to the user.

### Locale

The note locales contain the title and content of the note. Having this broken up by locale allows different translations of the note to be used. The default locale used if none of the locales match the WordPress locale is `en_US`.

```json
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

```json
{
	"name": "install-automate-woo",
	 "locales": [
		<ActionLocale>,
		...
	],
	"url": "?page=automatewoo-dashboard",
	"url_is_admin_query": true,
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
	"status": "actioned"
},
```

`name` must be unique to the created note.

The action locales contain the label of the action. Having this broken up by locale allows different translations of the note to be used. The default locale used if none of the locales match the WordPress locale is `en_US`.

The `status` is what the status of the created note will be set to after interacting with the action.

#### ActionLocale

```json
{
	"locale": "en_US",
	"label": "Install"
}
```

## Rule

You can find the readme for RuleProcessors and Transformers in [this README](../RemoteSpecs/RuleProcessors/README.md)
