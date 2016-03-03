# Settings API Proposal

The idea of this proposal is to handle custom settings for both WooCommerce > Settings pages and settings panels for "metaboxes" (product data, coupon data).

All settings are registered with PHP filters. Not through the API. The API is only for retrieving settings and updating them.

## GET /settings/sections

Dedicated settings sections/pages (for screens currently under WooCommerce > Settings)
For example we might have 'General', 'Products', 'Tax', 'Shipping', 'Checkout', 'Accounts', 'Emails', and 'API'. Adding a new page to this area should automatically create a new tab in the UI.

Metaboxes (Coupon Data or Product Data for example) are also considered sections, and will be listed by this endpoint. They won't be automatically displayed anywhere. 

Here is how they would be registered:

	// Default WooCommerce Setting Sections
	apply_filters( 'woocommerce_settings_sections', array(
		array(
			'id'          => 'general', // ID (required)
			'type'        => 'page', // page or metabox
			'label'       => __( 'General', 'woocommerce' ), // human readable label (required)
			'description' => '', // human readable description (optional)
		),
		array(
			'id'		  => 'products',
			'type'        => 'page', // page or metabox
			'label'       => __( 'Products', 'woocommerce' ),
			'description' => '',
		),
		.....
	) );

To add a new top level tab/page to WooCommerce > Settings

	add_filter( 'woocommerce_settings_sections', function( $pages ) {
		$pages[] = array(
			'id'      	  => 'bookings',
			'type'        => 'page',
			'label'       => __( 'Bookings', 'woocommerce-bookings' ),
			'description' => __( 'Bookings settings.', 'woocommerce-bookings' ),
		);
		return $pages;
	} );

The actual endpoint would return a JSON object like so:

	[
	    {
	        "id": "general",
	        "type": "page",
	        "label": "General",
	        "description": ""
	    },
	    {
	        "id": "products",
	        "type": "page",
	        "label": "Products",
	        "description": ""
	    },
	    {
	        "id": "coupon-data",
	        "type": "metabox",
	        "label": "Coupon Data",
	        "description": ""
	    }
	]

There should be a filter for this endpoint (?type=page) to get only sections of a specific type.

![](https://cldup.com/HHAZ-JBOxU.thumb.png)

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
