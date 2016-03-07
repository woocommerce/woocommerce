# Settings API Proposal

The Settings API is a set of WP-API endpoints that return information about WooCommerce settings. Settings can also be updated with the API.

The API should be capable of handling settings in many different contexts including pages (WooCommerce > Settings), "metaboxes" (product data, coupon data), shipping zones, and be extendable to other contexts in the future.

All settings are registered with PHP filters. Not through the REST API. The REST API is only for retrieving settings and updating them.

## Locations
[locations.md](locations.md)

# The below sections are being moved to their own doc files as the API gets fleshed out.

## GET /settings/sections/$section/

Lists all settings "groups" on a specific page. 

On the 'Products' page this would be 'General', 'Display', 'Inventory', and 'Downloadable Products'.

Metaboxes won't need to use this.

![](https://cldup.com/qXlfpvItr6-3000x3000.png)

Here is how you would register groups:

	// products would be replaced with whatever page ID you want to register for. These filters automatically exist after registering a page
	add_filter( 'woocommerce_settings_groups_products', function( $groups ) {
		$groups[] = array(
			'id'          => 'general',
			'label'       => __( 'General', 'woocommerce' ), // human readable label (required)
			'description' => '', // human readable description (optional)
		);
		$groups[] = array(
			'id'		  => 'display',
			'label'       => __( 'Display', 'woocommerce' ),
			'description' => '',
		);
		$groups[] = array(
			'id'		  => 'inventory',
			'label'       => __( 'Inventory', 'woocommerce' ),
			'description' => '',
		);
		return $groups;
	} );

To retrive the groups for the 'products' page:

GET /settings/sections/products

	{
	    "label": "Products",
	    "description": "",
	    "groups": [
	        {
	            "id": "general",
	            "label": "General",
	            "description": ""
	        },
	        {
	            "id": "display",
	            "label": "Display",
	            "description": ""
	        }
	    ]
	}


## /settings/$identifer

Gets the actual settings to be displayed in a specific area. You can load settings for a specific group, or for a specific metabox.

GET /settings/page:products:general would return settings for Settings > Products > General.
GET /settings/metabox:coupons would return settings for Coupons > Add New Coupon > Coupon Data metabox.

To register settings:

	// The filter (page_products_general) should match the identifer for the area we are loading settings for.
	apply_filters( 'woocommerce_settings_page_products_general', array(
		array(
			'label' 	=> __( 'Measurements', 'woocommerce' ),
			'type' 		=> 'title',
			'id' 		=> 'product_measurement_options'
		),
		array(
			'label'       => __( 'Weight Unit', 'woocommerce' ),
			'description' => __( 'This controls what unit you will define weights in.', 'woocommerce' ),
			'id'          => 'woocommerce_weight_unit',
			'default'  => 'kg',
			'type'     => 'select',
			'options'  => array(
				'kg'  => __( 'kg', 'woocommerce' ),
				'g'   => __( 'g', 'woocommerce' ),
				'lbs' => __( 'lbs', 'woocommerce' ),
				'oz'  => __( 'oz', 'woocommerce' ),
			),
		)
	) );
	
Settings response:

	[
	    {
	        "label": "Measurements",
	        "type": "title",
	        "id": "product_measurement_options"
	    },
	    {
	        "label": "Weight Unit",
	        "description": "This controls what unit you will define weights in.",
	        "id": "woocommerce_weight_unit",
	        "default": "kg",
	        "type": "select",
	        "options": {
	            "kg": "kg",
	            "g": "g",
	            "lbs": "lbs",
	            "oz": "oz"
	        }
	    }
	]

We should different form input types for 'type', text, textarea, select, radio, checkbox, ...

## GET /settings/$identifer/$setting

Get data for an individual setting. 

## PUT /settings/$identifer/$setting

Update a setting by passing a new 'value' in the body. A success response will be returned.

## PUT /settings/$identifer

Update multiple settings at the same time with key => value pairs. A success response will be returned.
