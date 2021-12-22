<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Init as OnboardingTasks;

/**
 * Default Free Extensions
 */
class DefaultFreeExtensions {

	/**
	 * Get default specs.
	 *
	 * @return array Default specs.
	 */
	public static function get_all() {
		$bundles = [
			[
				'key'     => 'obw/basics',
				'title'   => __( 'Get the basics', 'woocommerce-admin' ),
				'plugins' => [
					self::get_plugin( 'woocommerce-payments' ),
					self::get_plugin( 'woocommerce-services:shipping' ),
					self::get_plugin( 'woocommerce-services:tax' ),
					self::get_plugin( 'jetpack' ),
				],
			],
			[
				'key'     => 'obw/grow',
				'title'   => __( 'Grow your store', 'woocommerce-admin' ),
				'plugins' => [
					self::get_plugin( 'mailpoet' ),
					self::get_plugin( 'google-listings-and-ads' ),
				],
			],
			[
				'key'     => 'task-list/reach',
				'title'   => __( 'Reach out to customers', 'woocommerce-admin' ),
				'plugins' => [
					self::get_plugin( 'mailpoet:alt' ),
					self::get_plugin( 'mailchimp-for-woocommerce' ),
					self::get_plugin( 'creative-mail-by-constant-contact' ),
				],
			],
			[
				'key'     => 'task-list/grow',
				'title'   => __( 'Grow your store', 'woocommerce-admin' ),
				'plugins' => [
					self::get_plugin( 'google-listings-and-ads:alt' ),
				],
			],
		];

		$bundles = wp_json_encode( $bundles );
		return json_decode( $bundles );
	}

	/**
	 * Get the plugin arguments by slug.
	 *
	 * @param string $slug Slug.
	 * @return array
	 */
	public static function get_plugin( $slug ) {
		$plugins = array(
			'google-listings-and-ads'           => [
				'name'           => __( 'Google Listings & Ads', 'woocommerce-admin' ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Drive sales with %1$sGoogle Listings and Ads%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/google-listings-and-ads" target="_blank">',
					'</a>'
				),
				'image_url'      => plugins_url( 'images/onboarding/google-listings-and-ads.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc' => true,
			],
			'google-listings-and-ads:alt'       => [
				'name'           => __( 'Google Listings & Ads', 'woocommerce-admin' ),
				'description'    => __( 'Reach more shoppers and drive sales for your store. Integrate with Google to list your products for free and launch paid ad campaigns.', 'woocommerce-admin' ),
				'image_url'      => plugins_url( 'images/onboarding/google-listings-and-ads.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc' => true,
			],
			'mailpoet'                          => [
				'name'           => __( 'MailPoet', 'woocommerce-admin' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce-admin' ),
				'image_url'      => plugins_url( 'images/onboarding/mailpoet.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			],
			'mailchimp-for-woocommerce'         => [
				'name'           => __( 'Mailchimp', 'woocommerce-admin' ),
				'description'    => __( 'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.', 'woocommerce-admin' ),
				'image_url'      => plugins_url( 'images/onboarding/mailchimp-for-woocommerce.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailchimp-woocommerce',
				'is_built_by_wc' => false,
			],
			'creative-mail-by-constant-contact' => [
				'name'           => __( 'Creative Mail for WooCommerce', 'woocommerce-admin' ),
				'description'    => __( 'Create on-brand store campaigns, fast email promotions and customer retargeting with Creative Mail.', 'woocommerce-admin' ),
				'image_url'      => plugins_url( 'images/onboarding/creative-mail-by-constant-contact.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=creativemail',
				'is_built_by_wc' => false,
			],
			'woocommerce-payments'              => [
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Accept credit cards and other popular payment methods with %1$sWooCommerce Payments%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/woocommerce-payments" target="_blank">',
					'</a>'
				),
				'is_visible'     => [
					[
						'type'     => 'or',
						'operands' => [
							[
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'PR',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'AU',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'CA',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'DE',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'ES',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'FR',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'GB',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'IE',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'IT',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'NZ',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'AT',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'BE',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'NL',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'PL',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'PT',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'CH',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'HK',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'SG',
								'operation' => '=',
							],
						],
						[
							'type'         => 'option',
							'transformers' => [
								[
									'use'       => 'dot_notation',
									'arguments' => [
										'path' => 'industry',
									],
								],
								[
									'use'       => 'array_column',
									'arguments' => [
										'key' => 'slug',
									],
								],
								[
									'use'       => 'array_search',
									'arguments' => [
										'value' => 'cbd-other-hemp-derived-products',
									],
								],
							],
							'option_name'  => 'woocommerce_onboarding_profile',
							'value'        => 'cbd-other-hemp-derived-products',
							'default'      => '',
							'operation'    => '!=',
						],
					],
				],
				'is_built_by_wc' => true,
			],
			'woocommerce-services:shipping'     => [
				'description'    => sprintf(
				/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Print shipping labels with %1$sWooCommerce Shipping%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/shipping" target="_blank">',
					'</a>'
				),
				'is_visible'     => [
					[
						'type'      => 'base_location_country',
						'value'     => 'US',
						'operation' => '=',
					],
					[
						'type'    => 'not',
						'operand' => [
							[
								'type'    => 'plugins_activated',
								'plugins' => [ 'woocommerce-services' ],
							],
						],
					],
					[
						'type'     => 'or',
						'operands' => [
							[
								[
									'type'         => 'option',
									'transformers' => [
										[
											'use'       => 'dot_notation',
											'arguments' => [
												'path' => 'product_types',
											],
										],
										[
											'use' => 'count',
										],
									],
									'option_name'  => 'woocommerce_onboarding_profile',
									'value'        => 1,
									'default'      => '',
									'operation'    => '!=',
								],
							],
							[
								[
									'type'         => 'option',
									'transformers' => [
										[
											'use'       => 'dot_notation',
											'arguments' => [
												'path' => 'product_types.0',
											],
										],
									],
									'option_name'  => 'woocommerce_onboarding_profile',
									'value'        => 'downloads',
									'default'      => '',
									'operation'    => '!=',
								],
							],
						],
					],
				],
				'is_built_by_wc' => true,
			],
			'woocommerce-services:tax'          => [
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Get automated sales tax with %1$sWooCommerce Tax%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/tax" target="_blank">',
					'</a>'
				),
				'is_visible'     => [
					[
						'type'     => 'or',
						'operands' => [
							[
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'FR',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'GB',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'DE',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'CA',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'AU',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'GR',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'BE',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'PT',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'DK',
								'operation' => '=',
							],
							[
								'type'      => 'base_location_country',
								'value'     => 'SE',
								'operation' => '=',
							],
						],
					],
					[
						'type'    => 'not',
						'operand' => [
							[
								'type'    => 'plugins_activated',
								'plugins' => [ 'woocommerce-services' ],
							],
						],
					],
				],
				'is_built_by_wc' => true,
			],
			'jetpack'                           => [
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Enhance speed and security with %1$sJetpack%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/jetpack" target="_blank">',
					'</a>'
				),
				'is_visible'     => [
					[
						'type'    => 'not',
						'operand' => [
							[
								'type'    => 'plugins_activated',
								'plugins' => [ 'jetpack' ],
							],
						],
					],
				],
				'is_built_by_wc' => false,
			],
			'mailpoet'                          => [
				'name'           => __( 'MailPoet', 'woocommerce-admin' ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Level up your email marketing with %1$sMailPoet%2$s', 'woocommerce-admin' ),
					'<a href="https://woocommerce.com/products/mailpoet" target="_blank">',
					'</a>'
				),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_visible'     => [
					[
						'type'    => 'not',
						'operand' => [
							[
								'type'    => 'plugins_activated',
								'plugins' => [ 'mailpoet' ],
							],
						],
					],
				],
				'is_built_by_wc' => true,
			],
			'mailpoet:alt'                      => [
				'name'           => __( 'MailPoet', 'woocommerce-admin' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce-admin' ),
				'image_url'      => plugins_url( 'images/onboarding/mailpoet.png', WC_ADMIN_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			],
		);

		$plugin        = $plugins[ $slug ];
		$plugin['key'] = $slug;

		return $plugin;
	}
}
