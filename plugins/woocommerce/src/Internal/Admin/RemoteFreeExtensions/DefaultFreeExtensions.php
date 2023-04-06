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
					self::get_plugin( 'codistoconnect' ),
					self::get_plugin( 'google-listings-and-ads' ),
					self::get_plugin( 'pinterest-for-woocommerce' ),
					self::get_plugin( 'facebook-for-woocommerce' ),
					self::get_plugin( 'tiktok-for-business:alt' ),
				),
			),
			array(
				'key'     => 'task-list/reach',
				'title'   => __( 'Reach out to customers', 'woocommerce' ),
				'plugins' => array(
					self::get_plugin( 'mailpoet:alt' ),
					self::get_plugin( 'mailchimp-for-woocommerce' ),
					self::get_plugin( 'creative-mail-by-constant-contact' ),
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
					self::get_plugin( 'codistoconnect:alt' ),
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
			'google-listings-and-ads'           => array(
				'name'            => __( 'Google Listings & Ads', 'woocommerce' ),
				'description'     => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Drive sales with %1$sGoogle Listings and Ads%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/google-listings-and-ads" target="_blank">',
					'</a>'
				),
				'image_url'       => plugins_url( '/assets/images/onboarding/google.svg', WC_PLUGIN_FILE ),
				'manage_url'      => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc'  => true,
				'min_php_version' => '7.4',
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
			'google-listings-and-ads:alt'       => array(
				'name'           => __( 'Google Listings & Ads', 'woocommerce' ),
				'description'    => __( 'Reach more shoppers and drive sales for your store. Integrate with Google to list your products for free and launch paid ad campaigns.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/google.svg', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fgoogle%2Fstart',
				'is_built_by_wc' => true,
			),
			'facebook-for-woocommerce'          => array(
				'name'           => __( 'Facebook for WooCommerce', 'woocommerce' ),
				'description'    => __( 'List products and create ads on Facebook and Instagram with <a href="https://woocommerce.com/products/facebook/">Facebook for WooCommerce</a>', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/facebook.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-facebook',
				'is_visible'     => false,
				'is_built_by_wc' => false,
			),
			'facebook-for-woocommerce:alt'      => array(
				'name'           => __( 'Facebook for WooCommerce', 'woocommerce' ),
				'description'    => __( 'List products and create ads on Facebook and Instagram.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/facebook.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-facebook',
				'is_visible'     => false,
				'is_built_by_wc' => false,
			),
			'pinterest-for-woocommerce'         => array(
				'name'            => __( 'Pinterest for WooCommerce', 'woocommerce' ),
				'description'     => __( 'Get your products in front of Pinners searching for ideas and things to buy.', 'woocommerce' ),
				'image_url'       => plugins_url( '/assets/images/onboarding/pinterest.png', WC_PLUGIN_FILE ),
				'manage_url'      => 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
				'min_php_version' => '7.3',
				'is_built_by_wc'  => true,
			),
			'pinterest-for-woocommerce:alt'     => array(
				'name'           => __( 'Pinterest for WooCommerce', 'woocommerce' ),
				'description'    => __( 'Get your products in front of Pinterest users searching for ideas and things to buy. Get started with Pinterest and make your entire product catalog browsable.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/pinterest.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
				'is_built_by_wc' => true,
			),
			'mailpoet'                          => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailpoet.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			),
			'mailchimp-for-woocommerce'         => array(
				'name'           => __( 'Mailchimp', 'woocommerce' ),
				'description'    => __( 'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailchimp-for-woocommerce.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailchimp-woocommerce',
				'is_built_by_wc' => false,
			),
			'creative-mail-by-constant-contact' => array(
				'name'           => __( 'Creative Mail for WooCommerce', 'woocommerce' ),
				'description'    => __( 'Create on-brand store campaigns, fast email promotions and customer retargeting with Creative Mail.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/creative-mail-by-constant-contact.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=creativemail',
				'is_built_by_wc' => false,
			),
			'codistoconnect'                    => array(
				'name'           => __( 'Codisto for WooCommerce', 'woocommerce' ),
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Sell on Amazon, eBay, Walmart and more directly from WooCommerce with  %1$sCodisto%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/pt-br/products/amazon-ebay-integration/?quid=c247a85321c9e93e7c3c6f1eb072e6e5" target="_blank">',
					'</a>'
				),
				'image_url'      => plugins_url( '/assets/images/onboarding/codistoconnect.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=codisto-settings',
				'is_built_by_wc' => true,
			),
			'codistoconnect:alt'                => array(
				'name'           => __( 'Codisto for WooCommerce', 'woocommerce' ),
				'description'    => __( 'Sell on Amazon, eBay, Walmart and more directly from WooCommerce.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/codistoconnect.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=codisto-settings',
				'is_built_by_wc' => true,
			),
			'woocommerce-payments'              => array(
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Accept credit cards and other popular payment methods with %1$sWooCommerce Payments%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/woocommerce-payments" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					array(
						'type'     => 'or',
						'operands' => array(
							array(
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CA',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'ES',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'FR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GB',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'IE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'IT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NZ',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'BE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NL',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PL',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CH',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'HK',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SG',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CY',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DK',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'EE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'FI',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'LU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'LT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'LV',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NO',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'MT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SI',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SK',
								'operation' => '=',
							),
						),
					),
					DefaultPaymentGateways::get_rules_for_cbd( false ),
				),
				'min_wp_version' => '5.9',
				'is_built_by_wc' => true,
			),
			'woocommerce-services:shipping'     => array(
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
			'woocommerce-services:tax'          => array(
				'description'    => sprintf(
					/* translators: 1: opening product link tag. 2: closing link tag */
					__( 'Get automated sales tax with %1$sWooCommerce Tax%2$s', 'woocommerce' ),
					'<a href="https://woocommerce.com/products/tax" target="_blank">',
					'</a>'
				),
				'is_visible'     => array(
					array(
						'type'     => 'or',
						'operands' => array(
							array(
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'FR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GB',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CA',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'BE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DK',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SE',
								'operation' => '=',
							),
						),
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
				),
				'is_built_by_wc' => true,
			),
			'jetpack'                           => array(
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
				'min_wp_version' => '6.0',
				'is_built_by_wc' => false,
			),
			'mailpoet'                          => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
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
			'mailpoet:alt'                      => array(
				'name'           => __( 'MailPoet', 'woocommerce' ),
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/mailpoet.png', WC_PLUGIN_FILE ),
				'manage_url'     => 'admin.php?page=mailpoet-newsletters',
				'is_built_by_wc' => true,
			),
			'tiktok-for-business'               => array(
				'name'           => __( 'TikTok for WooCommerce', 'woocommerce' ),
				'image_url'      => plugins_url( '/assets/images/onboarding/tiktok.svg', WC_PLUGIN_FILE ),
				'description'    =>
					__( 'Grow your online sales by promoting your products on TikTok to over one billion monthly active users around the world.', 'woocommerce' ),
				'manage_url'     => 'admin.php?page=tiktok',
				'is_visible'     => array(
					array(
						'type'     => 'or',
						'operands' => array(
							array(
								'type'      => 'base_location_country',
								'value'     => 'US',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CA',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'MX',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'BE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CZ',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DK',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'FI',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'FR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'DE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'HU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'IE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'IT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NL',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PL',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PT',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'RO',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'ES',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'GB',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'CH',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NO',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'NZ',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SG',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'MY',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'PH',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'ID',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'VN',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'TH',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'KR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'IL',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'AE',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'RU',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'UA',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'TR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'SA',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'BR',
								'operation' => '=',
							),
							array(
								'type'      => 'base_location_country',
								'value'     => 'JP',
								'operation' => '=',
							),
						),
					),
				),
				'is_built_by_wc' => false,
			),
			'tiktok-for-business:alt'           => array(
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
}
