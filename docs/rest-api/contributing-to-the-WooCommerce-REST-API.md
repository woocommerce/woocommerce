# Contribtuing to the WooCommerce REST API

## About the WooCommerce REST API

WooCommerce is fully integrated with the [WordPress REST API](https://developer.wordpress.org/rest-api/). This allows WooCommerce data to be created, read, updated, and deleted using requests in JSON format and using various WordPress REST API Authentication methods with standard HTTP verbs which are understood by most HTTP clients.

To read more about usage, [see our getting started guide here](https://github.com/woocommerce/woocommerce/wiki/Getting-started-with-the-REST-API).

The API in WooCommerce ships with WooCommerce core, so each instance of the API is running on a user's store/server. **This is important** because it means clients could be running on different versions which you need to account for in your application. We use API versioning to help with this.

## API Versioning and how it affects development

Core hosts multiple versions if the API for backwards compatibility and so apps can continue to use the same API version after updates to target as many of their users as possible.

Accessing a certain version is done in the URL. For example, to target `v1` of the Rest API you can call:

``` text
/wp-json/wc/v1
```

And to access `v2`:

``` text
/wp-json/wc/v2
```

The benefit to this is the client knows that endpoints are available in each version and can account for those specifically. Adding endpoints without this kind of discovery would be a nightmare; you wouldn't know if the API on a user's store actually had the endpoints and schemas you need because it would also depend on WooCommerce version.

With this in mind:

* When a **new feature** is added to the REST API, the version needs to be increased. 
* When any endpoint or data is **changed**, the version needs to be increased. 
* Fixes do not require a version increase.

When a new version is added:

* The old version is moved under a version-based directory so it can still be accessed. For example, [see v1 here](https://github.com/woocommerce/woocommerce/tree/trunk/includes/api/v1). 
* A new set of API docs are generated and the old ones are kept accessible.

## Adding new endpoints

So you want to add a new data endpoint to WC core? Great! To get started you'll want to first familiarise yourself with how the WordPress REST API is structured.

[Adding custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/)

### Schema first development

Arguably the most important part of any endpoint is the **schema**. This essentially dictates how the endpoint should operate and what data it will return.

> A schema is metadata that tells us how our data is structured. Most databases implement some form of schema which enables us to reason about our data in a more structured manner. The WordPress REST API utilizes JSON Schema to handle the structuring of its data. You can implement endpoints without using a schema, but you will be missing out on a lot of things. It is up to you to decide what suits you best.

If you cannot explain how your endpoint will work via a schema, it's unlikely it's fit for purpose. Schemas have a few important uses:

* [For discovery](https://developer.wordpress.org/rest-api/using-the-rest-api/discovery/). The root endpoint lists all endpoints that are available, and the schema/data available under each.
* For validation. Data sent to the API is validated against the schema which is important for sanitization and validating data is in the correct and expected format.
* For documentation. Endpoints are self-documented which helps keep users aware of the data they should expect in each request.
* As a guideline for development. Having the schema done first makes development easier; you have a spec to work to!

You can also use the `OPTIONS` command to return all information about an endpoint. Here is an example of the options command being ran on the customers endpoint:

![Customer schema](https://woocommerce.files.wordpress.com/2017/05/2017-05-24-at-10-24.png)

To look at an example schema, [see the customers API](https://github.com/woocommerce/woocommerce/blob/3.0.0/includes/api/class-wc-rest-customer-downloads-controller.php#L78-L168).

Each item in the schema represents data. Data has fixed names/keys and types. Take downloads for example:

``` php
				'file' => array(
					'description' => __( 'File details.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties' => array(
						'name' => array(
							'description' => __( 'File name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'file' => array(
							'description' => __( 'File URL.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					),
				),
```

This shows that the API will return an array of file objects, and each object has 2 properties; name and file.

[You can read more about schemas in the handbook](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/).

## Deprecation of older versions

API Versions are kept around for 2 years after being replaced and they can be removed at any point after the timeline indicated below.

- WC Rest API v2 is deprecated and can be removed at any point after October 2020.
- WC Rest API v1 is deprecated and can be removed at any point after April 2019.
- The Legacy REST API v3 is deprecated and can be removed at any point after June 2018.
- The Legacy REST API v2 is deprecated and can be removed at any point after August 2017.
- The Legacy REST API v1 is deprecated and can be removed at any point after September 2016.
