# Locations

## Basic Info

Settings can be grouped together by location.

A location is just a grouping of settings that all share a common 'location' key.
This is so you can pull all settings meant to be displayed together in a particular area.

Example:

In wp-admin there is a "Coupon Data" box on the "Add New Coupon" page.
The coupon data box is considered location and would be represented like so:

	{
		"id": "coupon-data",
		"type": "metabox",
		"label": "Coupon Data",
		"description": ""
	}


There are 4 fields that make up a location:

* _id_: A unique identifier that can be used to link settings together. This shoud be a unique (for the whole system) value. Prefixing with your plugin slug is recommended for non-core changes. Alphanumeric string that contains no spaces. Required.
* _type_: Context for where the settings in this location are going to be displayed. Right now core accepts 'page' for settings pages (pages currently under WooCommerce > Settings), 'metabox' (for metabox grouped settings like Coupon Data - this name is subject to change as this API develops), and 'shipping-zone' for settings associated with shipping zone settings. Alphanumeric string that contains no spaces. Required, defaults to 'page'.
* _label_: A human readable label. This is a translated string that can be used in the UI. Required.
* _description_: A human readable description. This is a translated string that can be used in the UI. Optional.

Any other fields passed will be stripped out before the JSON response is sent back to the client.

## Registering a Location

Locations can be registered with the `woocommerce_settings_locations` filter:

	add_filter( 'woocommerce_settings_locations', function( $locations ) {
		$locations[] = array(
			'id'          => 'test-extension',
			'type'        => 'page',
			'label'       => __( 'Test Extension', 'woocommerce-test-extension' ),
			'description' => __( 'My awesome test settings.', 'woocommerce-test-extension' ),
		);
		return $locations;
	} );


## Endpoints

### GET /settings/locations

Returns a list of all locations supported by WC.
There is an optional ?type parameter that allows you to return only locations for a specific context (like all page locations).

### GET /settings/locations/$id

Returns information on a single location.