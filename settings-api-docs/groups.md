# Groups

## Basic Info

Groups are settings thats are grouped together on setting pages _only_ (type=page). That means setting locations like metaboxes don't have groups.

For example, a location would be the 'Products' page.
The products page would ave the following groups: 'General', 'Display', 'Inventory', and 'Downloadable Products'.

![](https://cldup.com/qXlfpvItr6-3000x3000.png)

	{
		"id": "general",
		"label": "General",
		"description": ""
	}

There are 3 fields that make up a group:

* _id_: A unique identifier that can be used to link settings together. The identifiers for groups only need to be unique on a specific page, so you can have a 'general' group under 'Products' and a 'general' group under Shipping. Alphanumeric string that contains no spaces. Required.
* _label_: A human readable label. This is a translated string that can be used in the UI. Required.
* _description_: A human readable description. This is a translated string that can be used in the UI. Optional.

Any other fields passed will be stripped out before the JSON response is sent back to the client.

## Registering a group

Groups can be registered with the `woocommerce_settings_groups_{$location_id}` filter:

	add_filter( 'woocommerce_settings_groups_products', function( $groups ) {
		$groups[] = array(
			'id'          => 'extra-settings',
			'label'       => __( 'Extras', 'woocommerce-test-extension' ),
			'description' => '',
		);
		return $groups;
	} );


## Endpoints

There are no separate endpoints for groups. You can access a list of groups for a specific page/location by calling that location's endpoint.

### GET /settings/locations/$id

	{
		"id": "test",
		"type": "page",
		"label": "Test Extension",
		"description": "My awesome test settings.",
		"groups": [
			{
				"id": "general",
				"label": "General",
				"description": ""
			}
		]
	}