const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { BASE_URL } = process.env;
const shouldSkip = BASE_URL !== undefined && ! BASE_URL.includes( 'localhost' );

test.describe( 'System Status API tests', () => {
	test( 'can view all system status items', async ( { request } ) => {
		// call API to view all system status items
		const response = await request.get( '/wp-json/wc/v3/system_status' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		// local environment differs from external hosts.  Local listed first.
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( ! shouldSkip ) {
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					environment: expect.objectContaining( {
						home_url: expect.any( String ),
						site_url: expect.any( String ),
						version: expect.any( String ),
						log_directory: expect.any( String ),
						log_directory_writable: expect.any( Boolean ),
						wp_version: expect.any( String ),
						wp_multisite: expect.any( Boolean ),
						wp_memory_limit: expect.any( Number ),
						wp_debug_mode: expect.any( Boolean ),
						wp_cron: expect.any( Boolean ),
						language: expect.any( String ),
						external_object_cache: null,
						server_info: expect.any( String ),
						php_version: expect.any( String ),
						php_post_max_size: expect.any( Number ),
						php_max_execution_time: expect.any( Number ),
						php_max_input_vars: expect.any( Number ),
						curl_version: expect.any( String ),
						suhosin_installed: expect.any( Boolean ),
						max_upload_size: expect.any( Number ),
						mysql_version: expect.any( String ),
						mysql_version_string: expect.any( String ),
						default_timezone: expect.any( String ),
						fsockopen_or_curl_enabled: expect.any( Boolean ),
						soapclient_enabled: expect.any( Boolean ),
						domdocument_enabled: expect.any( Boolean ),
						gzip_enabled: expect.any( Boolean ),
						mbstring_enabled: expect.any( Boolean ),
						remote_post_successful: expect.any( Boolean ),
						remote_post_response: expect.any( String ),
						remote_get_successful: expect.any( Boolean ),
						remote_get_response: expect.any( String ),
					} ),
				} )
			);
		} else {
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					environment: expect.objectContaining( {
						home_url: expect.any( String ),
						site_url: expect.any( String ),
						version: expect.any( String ),
						log_directory: expect.any( String ),
						log_directory_writable: expect.any( Boolean ),
						wp_version: expect.any( String ),
						wp_multisite: expect.any( Boolean ),
						wp_memory_limit: expect.any( Number ),
						wp_debug_mode: expect.any( Boolean ),
						wp_cron: expect.any( Boolean ),
						language: expect.any( String ),
						external_object_cache: expect.any( Boolean ),
						server_info: expect.any( String ),
						php_version: expect.any( String ),
						php_post_max_size: expect.any( Number ),
						php_max_execution_time: expect.any( Number ),
						php_max_input_vars: expect.any( Number ),
						curl_version: expect.any( String ),
						suhosin_installed: expect.any( Boolean ),
						max_upload_size: expect.any( Number ),
						mysql_version: expect.any( String ),
						mysql_version_string: expect.any( String ),
						default_timezone: expect.any( String ),
						fsockopen_or_curl_enabled: expect.any( Boolean ),
						soapclient_enabled: expect.any( Boolean ),
						domdocument_enabled: expect.any( Boolean ),
						gzip_enabled: expect.any( Boolean ),
						mbstring_enabled: expect.any( Boolean ),
						remote_post_successful: expect.any( Boolean ),
						remote_post_response: expect.any( Number ),
						remote_get_successful: expect.any( Boolean ),
						remote_get_response: expect.any( Number ),
					} ),
				} )
			);
		}

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				database: expect.objectContaining( {
					wc_database_version: expect.any( String ),
					database_prefix: expect.any( String ),
					maxmind_geoip_database: expect.any( String ),
					database_tables: expect.objectContaining( {
						woocommerce: expect.objectContaining( {
							wp_woocommerce_sessions: expect.objectContaining( {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							} ),
							wp_woocommerce_api_keys: expect.objectContaining( {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							} ),
							wp_woocommerce_attribute_taxonomies:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_downloadable_product_permissions:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_order_items: expect.objectContaining(
								{
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								}
							),
							wp_woocommerce_order_itemmeta:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_tax_rates: expect.objectContaining( {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							} ),
							wp_woocommerce_tax_rate_locations:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_shipping_zones:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_shipping_zone_locations:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_shipping_zone_methods:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_payment_tokens:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_payment_tokenmeta:
								expect.objectContaining( {
									data: expect.any( String ),
									index: expect.any( String ),
									engine: expect.any( String ),
								} ),
							wp_woocommerce_log: expect.objectContaining( {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							} ),
						} ),
						other: expect.objectContaining( {
							wp_actionscheduler_actions: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_actionscheduler_claims: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_actionscheduler_groups: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_actionscheduler_logs: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_commentmeta: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_comments: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_links: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_options: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_postmeta: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_posts: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_termmeta: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_terms: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_term_relationships: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_term_taxonomy: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_usermeta: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_users: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_admin_notes: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_admin_note_actions: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_category_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_customer_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_download_log: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_order_coupon_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_order_product_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_order_stats: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_order_tax_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_product_attributes_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_product_download_directories: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_product_meta_lookup: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_rate_limits: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_reserved_stock: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_tax_rate_classes: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
							wp_wc_webhooks: {
								data: expect.any( String ),
								index: expect.any( String ),
								engine: expect.any( String ),
							},
						} ),
					} ),
					database_size: {
						data: expect.any( Number ),
						index: expect.any( Number ),
					},
				} ),
			} )
		);

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				active_plugins: expect.arrayContaining( [
					{
						plugin: expect.any( String ),
						name: expect.any( String ),
						version: expect.any( String ),
						version_latest: expect.any( String ),
						url: expect.any( String ),
						author_name: expect.any( String ),
						author_url: expect.any( String ),
						network_activated: expect.any( Boolean ),
					},
					{
						plugin: expect.any( String ),
						name: expect.any( String ),
						version: expect.any( String ),
						version_latest: expect.any( String ),
						url: expect.any( String ),
						author_name: expect.any( String ),
						author_url: expect.any( String ),
						network_activated: expect.any( Boolean ),
					},
					{
						plugin: expect.any( String ),
						name: expect.any( String ),
						version: expect.any( String ),
						version_latest: expect.any( String ),
						url: expect.any( String ),
						author_name: expect.any( String ),
						author_url: expect.any( String ),
						network_activated: expect.any( Boolean ),
					},
					{
						plugin: expect.any( String ),
						name: expect.any( String ),
						version: expect.any( String ),
						version_latest: expect.any( String ),
						url: expect.any( String ),
						author_name: expect.any( String ),
						author_url: expect.any( String ),
						network_activated: expect.any( Boolean ),
					},
				] ),
			} )
		);

		// local environment differs from external hosts.  Local listed first.
		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( ! shouldSkip ) {
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					dropins_mu_plugins: expect.objectContaining( {
						dropins: expect.arrayContaining( [] ),
						mu_plugins: expect.arrayContaining( [] ),
					} ),
				} )
			);
		} else {
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					dropins_mu_plugins: expect.objectContaining( {
						dropins: expect.arrayContaining( [
							{
								name: expect.any( String ),
								plugin: expect.any( String ),
							},
							{
								name: expect.any( String ),
								plugin: expect.any( String ),
							},
						] ),
						mu_plugins: [],
					} ),
				} )
			);
		}
		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				theme: expect.objectContaining( {
					name: expect.any( String ),
					version: expect.any( String ),
					version_latest: expect.any( String ),
					author_url: expect.any( String ),
					is_child_theme: expect.any( Boolean ),
					has_woocommerce_support: expect.any( Boolean ),
					has_woocommerce_file: expect.any( Boolean ),
					has_outdated_templates: expect.any( Boolean ),
					overrides: expect.any( Array ),
					parent_name: expect.any( String ),
					parent_version: expect.any( String ),
					parent_version_latest: expect.any( String ),
					parent_author_url: expect.any( String ),
				} ),
			} )
		);
		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				settings: expect.objectContaining( {
					api_enabled: expect.any( Boolean ),
					force_ssl: expect.any( Boolean ),
					currency: expect.any( String ),
					currency_symbol: expect.any( String ),
					currency_position: expect.any( String ),
					thousand_separator: expect.any( String ),
					decimal_separator: expect.any( String ),
					number_of_decimals: expect.any( Number ),
					geolocation_enabled: expect.any( Boolean ),
					taxonomies: {
						external: expect.any( String ),
						grouped: expect.any( String ),
						simple: expect.any( String ),
						variable: expect.any( String ),
					},
					product_visibility_terms: {
						'exclude-from-catalog': expect.any( String ),
						'exclude-from-search': expect.any( String ),
						featured: expect.any( String ),
						outofstock: expect.any( String ),
						'rated-1': expect.any( String ),
						'rated-2': expect.any( String ),
						'rated-3': expect.any( String ),
						'rated-4': expect.any( String ),
						'rated-5': expect.any( String ),
					},
					woocommerce_com_connected: expect.any( String ),
				} ),
			} )
		);
		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				security: expect.objectContaining( {
					secure_connection: expect.any( Boolean ),
					hide_errors: expect.any( Boolean ),
				} ),
			} )
		);
		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				pages: expect.arrayContaining( [
					{
						page_name: expect.any( String ),
						page_id: expect.any( String ),
						page_set: expect.any( Boolean ),
						page_exists: expect.any( Boolean ),
						page_visible: expect.any( Boolean ),
						shortcode: expect.any( String ),
						block: expect.any( String ),
						shortcode_required: expect.any( Boolean ),
						shortcode_present: expect.any( Boolean ),
						block_present: expect.any( Boolean ),
						block_required: expect.any( Boolean ),
					},
					{
						page_name: expect.any( String ),
						page_id: expect.any( String ),
						page_set: expect.any( Boolean ),
						page_exists: expect.any( Boolean ),
						page_visible: expect.any( Boolean ),
						shortcode: expect.any( String ),
						block: expect.any( String ),
						shortcode_required: expect.any( Boolean ),
						shortcode_present: expect.any( Boolean ),
						block_present: expect.any( Boolean ),
						block_required: expect.any( Boolean ),
					},
					{
						page_name: expect.any( String ),
						page_id: expect.any( String ),
						page_set: expect.any( Boolean ),
						page_exists: expect.any( Boolean ),
						page_visible: expect.any( Boolean ),
						shortcode: expect.any( String ),
						block: expect.any( String ),
						shortcode_required: expect.any( Boolean ),
						shortcode_present: expect.any( Boolean ),
						block_present: expect.any( Boolean ),
						block_required: expect.any( Boolean ),
					},
					{
						page_name: expect.any( String ),
						page_id: expect.any( String ),
						page_set: expect.any( Boolean ),
						page_exists: expect.any( Boolean ),
						page_visible: expect.any( Boolean ),
						shortcode: expect.any( String ),
						block: expect.any( String ),
						shortcode_required: expect.any( Boolean ),
						shortcode_present: expect.any( Boolean ),
						block_present: expect.any( Boolean ),
						block_required: expect.any( Boolean ),
					},
					{
						page_name: expect.any( String ),
						page_id: expect.any( String ),
						page_set: expect.any( Boolean ),
						page_exists: expect.any( Boolean ),
						page_visible: expect.any( Boolean ),
						shortcode: expect.any( String ),
						block: expect.any( String ),
						shortcode_required: expect.any( Boolean ),
						shortcode_present: expect.any( Boolean ),
						block_present: expect.any( Boolean ),
						block_required: expect.any( Boolean ),
					},
				] ),
			} )
		);
		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				post_type_counts: expect.arrayContaining( [
					{
						type: expect.any( String ),
						count: expect.any( String ),
					},
					{
						type: expect.any( String ),
						count: expect.any( String ),
					},
					{
						type: expect.any( String ),
						count: expect.any( String ),
					},
				] ),
			} )
		);
	} );

	test( 'can view all system status tools', async ( { request } ) => {
		// call API to view system status tools
		const response = await request.get(
			'/wp-json/wc/v3/system_status/tools'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					id: 'clear_transients',
					name: 'WooCommerce transients',
					action: 'Clear transients',
					description:
						'This tool will clear the product/shop transients cache.',
				} ),
				expect.objectContaining( {
					id: 'clear_expired_transients',
					name: 'Expired transients',
					action: 'Clear transients',
					description:
						'This tool will clear ALL expired transients from WordPress.',
				} ),
				expect.objectContaining( {
					id: 'clear_expired_download_permissions',
					name: 'Used-up download permissions',
					action: 'Clean up download permissions',
					description:
						'This tool will delete expired download permissions and permissions with 0 remaining downloads.',
				} ),
				expect.objectContaining( {
					id: 'regenerate_product_lookup_tables',
					name: 'Product lookup tables',
					action: 'Regenerate',
					description:
						'This tool will regenerate product lookup table data. This process may take a while.',
				} ),
			] )
		);
	} );

	test( 'can retrieve a system status tool', async ( { request } ) => {
		// call API to retrieve a system status tool
		const response = await request.get(
			'/wp-json/wc/v3/system_status/tools/clear_transients'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				id: 'clear_transients',
				name: 'WooCommerce transients',
				action: 'Clear transients',
				description:
					'This tool will clear the product/shop transients cache.',
			} )
		);
	} );

	test( 'can run a tool from system status', async ( { request } ) => {
		// call API to run a system status tool
		const response = await request.put(
			'/wp-json/wc/v3/system_status/tools/clear_transients'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				id: 'clear_transients',
				name: 'WooCommerce transients',
				action: 'Clear transients',
				description:
					'This tool will clear the product/shop transients cache.',
				success: true,
				message: 'Product transients cleared',
			} )
		);
	} );
} );
