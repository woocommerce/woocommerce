<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;

defined( 'ABSPATH' ) || exit;


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
		$bundles = array(
			array(
				'key'     => 'obw/basics',
				'title'   => __( 'Get the basics', 'woocommerce' ),
				'plugins' => array(
					self::get_plugin( 'woocommerce-payments' ),
					self::get_plugin( 'woocommerce-services:shipping' ),
					self::get_plugin( 'woocommerce-services:tax' ),
					self::get_plugin( 'jetpack' ),
				),
			),
			array(
				'key'     => 'obw/grow',
				'title'   => __( 'Grow your store', 'woocommerce' ),
				'plugins' => array(
					self::get_plugin( 'mailpoet' ),
					self::get_plugin( 'google-listings-and-ads' ),
					self::get_plugin( 'pinterest-for-woocommerce' ),
					self::get_plugin( 'facebook-for-woocommerce' ),
				),
			),
			array(
				'key'     => 'task-list/reach',
				'title'   => __( 'Reach out to customers', 'woocommerce' ),
				'plugins' => array(
					self::get_plugin( 'mailpoet:alt' ),
					self::get_plugin( 'mailchimp-for-woocommerce' ),
					self::get_plugin( 'klaviyo' ),
				),
			),
			array(
				'key'     => 'task-list/grow',
				'title'   => __( 'Grow your store', 'woocommerce' ),
				'plugins' => array(
					self::get_plugin( 'google-listings-and-ads:alt' ),
					self::get_plugin( 'tiktok-for-business' ),
					self::get_plugin( 'pinterest-for-woocommerce:alt' ),
					self::get_plugin( 'facebook-for-woocommerce:alt' ),
				),
			),
			array(
				'key'     => 'obw/core-profiler',
				'title'   => __( 'Grow your store', 'woocommerce' ),
				'plugins' => self::with_core_profiler_fields(
					array(
						self::get_plugin( 'woocommerce-payments' ),
						self::get_plugin( 'woocommerce-services:shipping' ),
						self::get_plugin( 'jetpack' ),
						self::get_plugin( 'pinterest-for-woocommerce' ),
						self::get_plugin( 'mailpoet' ),
						self::get_plugin( 'google-listings-and-ads' ),
						self::get_plugin( 'woocommerce-services:tax' ),
						self::get_plugin( 'tiktok-for-business' ),
					)
				),
			),
		);

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
			'google-listings-and-ads'       => array(
				'min_php_version' => '7.4',
				'name'            => __( 'Google for WooCommerce', 'woocommerce' ),
				'description'     => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Drive sales with %1$sGoogle for WooCommerce%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/google-listings-and-ads" target="_blank">',
					'</a>'
				),
				'image_url'       => plugins_url( '/assets/images/onboarding/google.svg', WC_PLUGIN_FILE ),
				'manage_url'      => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc'  => true,
				'is_visible'      => array(
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'google-listings-and-ads' ),
							),
						),
					),
				),
			),
			'google-listings-and-ads:alt'   => array(
				'name'           => __( 'Google for WooCommerce', 'woocommerce' ),
				'description'    => __( 'Reach more shoppers and drive sales for your store. Integrate with Google to list your products for free and launch paid ad campaigns.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/google.svg', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc' => true,
			),
			'facebook-for-woocommerce'      => array(
				'name'           => __( 'Facebook for WooCommerce', 'woocommerce' ),
				'description'    => __( 'List products and create ads on Facebook and Instagram with <a href="https://woocommerce.com/products/facebook/">Facebook for WooCommerce</a>', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/facebook.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-facebook',
				'is_visible'     => false,
				'is_built_by_wc' => false,
			),
			'facebook-for-woocommerce:alt'  => array(
				'name'           => __( 'Facebook for WooCommerce', 'woocommerce' ),
				'description'    => __( 'List products and create ads on Facebook and Instagram.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/facebook.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-facebook',
				'is_visible'     => false,
				'is_built_by_wc' => false,
			),
			'pinterest-for-woocommerce'     => array(
				'name'            => __( 'Pinterest for WooCommerce', 'woocommerce' ),
				'description'     => __( 'Get your products in front of Pinners searching for ideas and things to buy.', 'woocommerce' ),
				'image_url'       => plugins_url( '/assets/images/onboarding/pinterest.png', WC_PLUGIN_FILE ),
				'manage_url'      => 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
				'is_built_by_wc'  => true,
				'min_php_version' => '7.3',
			),
			'pinterest-for-woocommerce:alt' => array(
				'name'           => __( 'Pinterest for WooCommerce', 'woocommerce' ),
				'description'    => __( 'Get your products in front of Pinterest users searching for ideas and things to buy. Get started with Pinterest and make your entire product catalog browsable.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/pinterest.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
				'is_built_by_wc' => true,
			),
			'mailpoet'                      => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailpoet.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			),
			'mailchimp-for-woocommerce'     => array(
				'name'           => __( 'Mailchimp', 'woocommerce' ),
				'description'    => __( 'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailchimp-for-woocommerce.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailchimp-woocommerce',
				'is_built_by_wc' => false,
			),
			'klaviyo'                       => array(
				'name'           => __( 'Klaviyo', 'woocommerce' ),
				'description'    => __( 'Grow and retain customers with intelligent, impactful email and SMS marketing automation and a consolidated view of customer interactions.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/klaviyo.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=klaviyo_settings',
				'is_built_by_wc' => false,
			),
			'woocommerce-payments'          => array(
				'name'           => __( 'WooPayments', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/wcpay.svg', WC_PLUGIN_FILE ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Accept credit cards and other popular payment methods with %1$sWooPayments%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/woocommerce-payments" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					array(
						'type'      => 'base_location_country',
						'value'     => array(
							'US',
							'PR',
							'AU',
							'CA',
							'DE',
							'ES',
							'FR',
							'GB',
							'IE',
							'IT',
							'NZ',
							'AT',
							'BE',
							'NL',
							'PL',
							'PT',
							'CH',
							'HK',
							'SG',
							'CY',
							'DK',
							'EE',
							'FI',
							'GR',
							'LU',
							'LT',
							'LV',
							'NO',
							'MT',
							'SI',
							'SK',
							'BG',
							'CZ',
							'HR',
							'HU',
							'RO',
							'SE',
							'JP',
							'AE',
						),
						'operation' => 'in',
					),
					DefaultPaymentGateways::get_rules_for_cbd( false ),
				),
				'is_built_by_wc' => true,
				'min_wp_version' => '5.9',
			),
			'woocommerce-services:shipping' => array(
				'name'           => __( 'WooCommerce Shipping', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/woo.svg', WC_PLUGIN_FILE ),
				'description'    => sprintf(
				/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Print shipping labels with %1$sWooCommerce Shipping%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/shipping" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					array(
						'type'      => 'base_location_country',
						'value'     => 'US',
						'operation' => '=',
					),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-services' ),
							),
						),
					),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-shipping' ),
							),
						),
					),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-tax' ),
							),
						),
					),
					array(
						'type'     => 'or',
						'operands' => array(
							array(
								array(
									'type'         => 'option',
									'transformers' => array(
										array(
											'use'       => 'dot_notation',
											'arguments' => array(
												'path' => 'product_types',
											),
										),
										array(
											'use' => 'count',
										),
									),
									'option_name'  => 'woocommerce_onboarding_profile',
									'value'        => 1,
									'default'      => array(),
									'operation'    => '!=',
								),
							),
							array(
								array(
									'type'         => 'option',
									'transformers' => array(
										array(
											'use'       => 'dot_notation',
											'arguments' => array(
												'path' => 'product_types.0',
											),
										),
									),
									'option_name'  => 'woocommerce_onboarding_profile',
									'value'        => 'downloads',
									'default'      => '',
									'operation'    => '!=',
								),
							),
						),
					),
				),
				'is_built_by_wc' => true,
			),
			'woocommerce-services:tax'      => array(
				'name'           => __( 'WooCommerce Tax', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/woo.svg', WC_PLUGIN_FILE ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Get automated sales tax with %1$sWooCommerce Tax%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/tax" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					self::get_rules_for_wcservices_tax_countries(),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-services' ),
							),
						),
					),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-shipping' ),
							),
						),
					),
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'woocommerce-tax' ),
							),
						),
					),
				),
				'is_built_by_wc' => true,
			),
			'jetpack'                       => array(
				'name'           => __( 'Jetpack', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/jetpack.svg', WC_PLUGIN_FILE ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Enhance speed and security with %1$sJetpack%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/jetpack" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'jetpack' ),
							),
						),
					),
				),
				'is_built_by_wc' => false,
				'min_wp_version' => '6.0',
			),
			'mailpoet'                      => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailpoet.png', WC_PLUGIN_FILE ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Level up your email marketing with %1$sMailPoet%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/mailpoet" target="_blank">',
					'</a>'
				),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_visible'     => array(
					array(
						'type'    => 'not',
						'operand' => array(
							array(
								'type'    => 'plugins_activated',
								'plugins' => array( 'mailpoet' ),
							),
						),
					),
				),
				'is_built_by_wc' => true,
			),
			'mailpoet:alt'                  => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailpoet.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			),
			'tiktok-for-business'           => array(
				'name'           => __( 'TikTok for WooCommerce', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/tiktok.svg', WC_PLUGIN_FILE ),
				'description'    =>
					__( 'Grow your online sales by promoting your products on TikTok to over one billion monthly active users around the world.', 'woocommerce' ),
				'manage_url'     => 'admin.php?page=tiktok',
				'is_visible'     => array(
					array(
						'type'      => 'base_location_country',
						'value'     => array(
							'US',
							'CA',
							'MX',
							'AT',
							'BE',
							'CZ',
							'DK',
							'FI',
							'FR',
							'DE',
							'GR',
							'HU',
							'IE',
							'IT',
							'NL',
							'PL',
							'PT',
							'RO',
							'ES',
							'SE',
							'GB',
							'CH',
							'NO',
							'AU',
							'NZ',
							'SG',
							'MY',
							'PH',
							'ID',
							'VN',
							'TH',
							'KR',
							'IL',
							'AE',
							'RU',
							'UA',
							'TR',
							'SA',
							'BR',
							'JP',
						),
						'operation' => 'in',
					),
				),
				'is_built_by_wc' => false,
			),
			'tiktok-for-business:alt'       => array(
				'name'           => __( 'TikTok for WooCommerce', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/tiktok.svg', WC_PLUGIN_FILE ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Create ad campaigns and reach one billion global users with %1$sTikTok for WooCommerce%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/tiktok-for-woocommerce" target="_blank">',
					'</a>'
				),
				'manage_url'     => 'admin.php?page=tiktok',
				'is_built_by_wc' => false,
				'is_visible'     => false,
			),
		);

		$plugin        = $plugins[ $slug ];
		$plugin['key'] = $slug;

		return $plugin;
	}

	/**
	 * Decorate plugin data with core profiler fields.
	 *
	 * - Updated description for the core-profiler.
	 * - Adds learn_more_link and label.
	 * - Adds install_priority, which is used to sort the plugins. The value is determined by the plugin size. Lower = smaller.
	 *
	 * @param array $plugins Array of plugins.
	 *
	 * @return array
	 */
	public static function with_core_profiler_fields( array $plugins ) {
		$_plugins = array(
			'woocommerce-payments'          => array(
				'label'            => __( 'Get paid with WooPayments', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-woo.svg', WC_PLUGIN_FILE ),
				'description'      => __( "Securely accept payments and manage payment activity straight from your store's dashboard", 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/woocommerce-payments?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 5,
			),
			'woocommerce-services:shipping' => array(
				'label'            => __( 'Print shipping labels with WooCommerce Shipping', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-woo.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Print USPS and DHL labels directly from your dashboard and save on shipping.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/woocommerce-shipping?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 3,
			),
			'jetpack'                       => array(
				'label'            => __( 'Boost content creation with Jetpack AI Assistant', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-jetpack.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Save time on content creation — unlock high-quality blog posts and pages using AI.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/jetpack?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 8,
			),
			'pinterest-for-woocommerce'     => array(
				'label'            => __( 'Showcase your products with Pinterest', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-pinterest.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Get your products in front of a highly engaged audience.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/pinterest-for-woocommerce?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 2,
			),
			'mailpoet'                      => array(
				'label'            => __( 'Reach your customers with MailPoet', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-mailpoet.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Send purchase follow-up emails, newsletters, and promotional campaigns.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/mailpoet?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 7,
			),
			'tiktok-for-business'           => array(
				'label'            => __( 'Create ad campaigns with TikTok', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-tiktok.png', WC_PLUGIN_FILE ),
				'description'      => __( 'Create advertising campaigns and reach one billion global users.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/tiktok-for-woocommerce?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 1,
			),
			'google-listings-and-ads'       => array(
				'label'            => __( 'Drive sales with Google for WooCommerce', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-google.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Reach millions of active shoppers across Google with free product listings and ads.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/google-listings-and-ads?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 6,
			),
			'woocommerce-services:tax'      => array(
				'label'            => __( 'Get automated tax rates with WooCommerce Tax', 'woocommerce' ),
				'image_url'        => plugins_url( '/assets/images/core-profiler/logo-woo.svg', WC_PLUGIN_FILE ),
				'description'      => __( 'Automatically calculate how much sales tax should be collected – by city, country, or state.', 'woocommerce' ),
				'learn_more_link'  => 'https://woocommerce.com/products/tax?utm_source=storeprofiler&utm_medium=product&utm_campaign=freefeatures',
				'install_priority' => 4,
			),
		);

		/*
		 * Overwrite the is_visible conditions to just the country restriction
		 * and the requirement for WooCommerce Shipping and WooCommerce Tax
		 * to not be active.
		 */
		$_plugins['woocommerce-services:shipping']['is_visible'] = array(
			array(
				'type'      => 'base_location_country',
				'value'     => 'US',
				'operation' => '=',
			),
			array(
				'type'    => 'not',
				'operand' => array(
					array(
						'type'    => 'plugins_activated',
						'plugins' => array( 'woocommerce-shipping' ),
					),
				),
			),
			array(
				'type'    => 'not',
				'operand' => array(
					array(
						'type'    => 'plugins_activated',
						'plugins' => array( 'woocommerce-tax' ),
					),
				),
			),
		);

		$_plugins['woocommerce-services:tax']['is_visible'] = array(
			self::get_rules_for_wcservices_tax_countries(),
			array(
				'type'    => 'not',
				'operand' => array(
					array(
						'type'    => 'plugins_activated',
						'plugins' => array( 'woocommerce-shipping' ),
					),
				),
			),
			array(
				'type'    => 'not',
				'operand' => array(
					array(
						'type'    => 'plugins_activated',
						'plugins' => array( 'woocommerce-tax' ),
					),
				),
			),
		);

		$remove_plugins_activated_rule = function ( $is_visible ) {
			$is_visible = array_filter(
				array_map(
					function ( $rule ) {
						if ( is_object( $rule ) || ! isset( $rule['operand'] ) ) {
							return $rule;
						}

						return array_filter(
							$rule['operand'],
							function ( $operand ) {
								return 'plugins_activated' !== $operand['type'];
							}
						);
					},
					$is_visible
				)
			);

			return empty( $is_visible ) ? true : $is_visible;
		};

		foreach ( $plugins as &$plugin ) {
			if ( isset( $_plugins[ $plugin['key'] ] ) ) {
				$plugin = array_merge( $plugin, $_plugins[ $plugin['key'] ] );

				/*
				 * Removes the "not plugins_activated" rules from the "is_visible"
				 * ruleset except for the WooCommerce Services plugin.
				 *
				 * WC Services is a plugin that provides shipping and tax features.
				 * WC Services is sometimes labelled as "WooCommerce Shipping" or
				 * "WooCommerce Tax", depending on which functionality of the plugin
				 * is advertised.
				 *
				 * We have two new upcoming, standalone plugins: "WooCommerce Shipping" and
				 * "WooCommerce Tax" (same names as sometimes used for WC Services).
				 * The new plugins are incompatible with the old WC Services plugin.
				 * In order to prevent merchants from running into this plugin conflict,
				 * we want to keep the "not plugins_activated" rules for recommending
				 * WC Services.
				 *
				 * If WC Services and the new plugins are installed together,
				 * a notice is displayed and the plugin functionality is not registered
				 * by either WC Services or WC Shipping and WC Tax.
				 */
				if (
					isset( $plugin['is_visible'] ) &&
					is_array( $plugin['is_visible'] ) &&
					! in_array( $plugin['key'], array( 'woocommerce-services:shipping', 'woocommerce-services:tax' ), true )
				) {
					$plugin['is_visible'] = $remove_plugins_activated_rule( $plugin['is_visible'] );
				}
			}
		}

		return $plugins;
	}

	/**
	 * Returns the country restrictions for use in the `is_visible` key for
	 * recommending the tax functionality of WooCommerce Shipping & Tax.
	 *
	 * @return array
	 */
	private static function get_rules_for_wcservices_tax_countries() {
		return array(
			'type'      => 'base_location_country',
			'operation' => 'in',
			'value'     => array(
				'US',
				'FR',
				'GB',
				'DE',
				'CA',
				'AU',
				'GR',
				'BE',
				'PT',
				'DK',
				'SE',
			),
		);
	}
}
