<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

defined( 'ABSPATH' ) || exit;

/**
 * Default Marketing Recommendations
 */
class DefaultMarketingRecommendations {
	/**
	 * Get default specs.
	 *
	 * @return array Default specs.
	 */
	public static function get_all() {
		// Icon directory URL.
		$icon_dir_url   = WC_ADMIN_IMAGES_FOLDER_URL . '/marketing';

		$utm_string     = '?utm_source=marketingtab&utm_medium=product&utm_campaign=wcaddons';

		// Categories. Note that these are keys used in code, not texts to be displayed in the UI.
		$marketing = 'marketing';
		$coupons = 'coupons';

		// Subcategories.
		$sales_channels = [
			'slug' => 'sales-channels',
			'name' => __( 'Sales channels', 'wccom' ),
		];
		$email = [
			'slug' => 'email',
			'name' => __( 'Email', 'wccom' ),
		];
		$automations = [
			'slug' => 'automations',
			'name' => __( 'Automations', 'wccom' ),
		];
		$conversion = [
			'slug' => 'conversion',
			'name' => __( 'Conversion', 'wccom' ),
		];
		$crm = [
			'slug' => 'crm',
			'name' => __( 'CRM', 'wccom' ),
		];

		// Tags.
		$built_by_woocommerce = [
			'slug' => 'built-by-woocommerce',
			'name' => __( 'Built by WooCommerce', 'wccom' ),
		];

		return [
			[
				'title'          => 'Google Listings and Ads',
				'description'    => __( 'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.', 'wccom' ),
				'url'            => "https://woo.com/products/google-listings-and-ads/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/google.svg",
				'product'        => 'google-listings-and-ads',
				'plugin'         => 'google-listings-and-ads/google-listings-and-ads.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$sales_channels,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'Pinterest for WooCommerce',
				'description'    => __( 'Grow your business on Pinterest! Use this official plugin to allow shoppers to Pin products while browsing your store, track conversions, and advertise on Pinterest.', 'wccom' ),
				'url'            => "https://woo.com/products/pinterest-for-woocommerce/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/pinterest.svg",
				'product'        => 'pinterest-for-woocommerce',
				'plugin'         => 'pinterest-for-woocommerce/pinterest-for-woocommerce.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$sales_channels,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'TikTok for WooCommerce',
				'description'    => __( 'Create advertising campaigns and reach one billion global users with TikTok for WooCommerce.', 'wccom' ),
				'url'            => "https://woo.com/products/tiktok-for-woocommerce/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/tiktok.jpg",
				'product'        => 'tiktok-for-business',
				'plugin'         => 'tiktok-for-business/tiktok-for-woocommerce.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$sales_channels,
				],
				'tags'           => [],
			],
			[
				'title'          => 'MailPoet',
				'description'    => __( 'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.', 'wccom' ),
				'url'            => "https://woo.com/products/mailpoet/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/mailpoet.svg",
				'product'        => 'mailpoet',
				'plugin'         => 'mailpoet/mailpoet.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$email,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'Mailchimp for WooCommerce',
				'description'    => __( 'Send targeted campaigns, recover abandoned carts and more with Mailchimp.', 'wccom' ),
				'url'            => "https://woo.com/products/mailchimp-for-woocommerce/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/mailchimp.svg",
				'product'        => 'mailchimp-for-woocommerce',
				'plugin'         => 'mailchimp-for-woocommerce/mailchimp-woocommerce.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$email,
				],
				'tags'           => [],
			],
			[
				'title'          => 'Klaviyo for WooCommerce',
				'description'    => __( 'Grow and retain customers with intelligent, impactful email and SMS marketing automation and a consolidated view of customer interactions.', 'wccom' ),
				'url'            => "https://woo.com/products/klaviyo-for-woocommerce/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/klaviyo.png",
				'product'        => 'klaviyo',
				'plugin'         => 'klaviyo/klaviyo.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$email,
				],
				'tags'           => [],
			],
			[
				'title'          => 'AutomateWoo',
				'description'    => __( 'Convert and retain customers with automated marketing that does the hard work for you.', 'wccom' ),
				'url'            => "https://woo.com/products/automatewoo/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/automatewoo.svg",
				'product'        => 'automatewoo',
				'plugin'         => 'automatewoo/automatewoo.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$automations,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'AutomateWoo Refer a Friend',
				'description'    => __( 'Boost your organic sales by adding a customer referral program to your WooCommerce store.', 'wccom' ),
				'url'            => "https://woo.com/products/automatewoo-refer-a-friend/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/automatewoo.svg",
				'product'        => 'automatewoo-referrals',
				'plugin'         => 'automatewoo-referrals/automatewoo-referrals.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$automations,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'AutomateWoo Birthdays',
				'description'    => __( 'Delight customers and boost organic sales with a special WooCommerce birthday email (and coupon!) on their special day.', 'wccom' ),
				'url'            => "https://woo.com/products/automatewoo-birthdays/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/automatewoo.svg",
				'product'        => 'automatewoo-birthdays',
				'plugin'         => 'automatewoo-birthdays/automatewoo-birthdays.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$automations,
				],
				'tags'           => [
					$built_by_woocommerce,
				],
			],
			[
				'title'          => 'Trustpilot Reviews',
				'description'    => __( 'Collect and showcase verified reviews that consumers trust.', 'wccom' ),
				'url'            => "https://woo.com/products/trustpilot-reviews/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/trustpilot.png",
				'product'        => 'trustpilot-reviews',
				'plugin'         => 'trustpilot-reviews/wc_trustpilot.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$conversion,
				],
				'tags'           => [],
			],
			[
				'title'          => 'Vimeo for WooCommerce',
				'description'    => __( 'Turn your product images into stunning videos that engage and convert audiences - no video experience required.', 'wccom' ),
				'url'            => "https://woo.com/products/vimeo/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/vimeo.png",
				'product'        => 'vimeo',
				'plugin'         => 'vimeo/Core.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$conversion,
				],
				'tags'           => [],
			],
			[
				'title'          => 'Jetpack CRM for WooCommerce',
				'description'    => __( 'Harness data from WooCommerce to grow your business. Manage leads, customers, and segments, through automation, quotes, invoicing, billing, and email marketing. Power up your store with CRM.', 'wccom' ),
				'url'            => "https://woo.com/products/jetpack-crm/{$utm_string}",
				'direct_install' => true,
				'icon'           => "{$icon_dir_url}/jetpack-crm.svg",
				'product'        => 'zero-bs-crm',
				'plugin'         => 'zero-bs-crm/ZeroBSCRM.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$crm,
				],
				'tags'           => [],
			],
			[
				'title'          => 'WooCommerce Zapier',
				'description'    => __( 'Integrate your WooCommerce store with 5000+ cloud apps and services today. Trusted by 11,000+ users.', 'wccom' ),
				'url'            => "https://woo.com/products/woocommerce-zapier/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/zapier.png",
				'product'        => 'woocommerce-zapier',
				'plugin'         => 'woocommerce-zapier/woocommerce-zapier.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$crm,
				],
				'tags'           => [],
			],
			[
				'title'          => 'Salesforce',
				'description'    => __( 'Sync your website\'s data like contacts, products, and orders over Salesforce CRM with Salesforce Integration for WooCommerce.', 'wccom' ),
				'url'            => "https://woo.com/products/integration-with-salesforce-for-woocommerce/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/salesforce.jpg",
				'product'        => 'integration-with-salesforce',
				'plugin'         => 'integration-with-salesforce/integration-with-salesforce.php',
				'categories'     => [
					$marketing,
				],
				'subcategories'  => [
					$crm,
				],
				'tags'           => [],
			],
			[
				'title'          => 'Personalized Coupons',
				'description'    => __( 'Generate dynamic personalized coupons for your customers that increase purchase rates.', 'wccom' ),
				'url'            => "https://woo.com/products/automatewoo/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/automatewoo-personalized-coupons.svg",
				'product'        => 'automatewoo',
				'plugin'         => 'automatewoo/automatewoo.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
			[
				'title'          => 'Smart Coupons',
				'description'    => __( 'Powerful, "all in one" solution for gift certificates, store credits, discount coupons and vouchers.', 'wccom' ),
				'url'            => "https://woo.com/products/smart-coupons/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/woocommerce-smart-coupons.svg",
				'product'        => 'woocommerce-smart-coupons',
				'plugin'         => 'woocommerce-smart-coupons/woocommerce-smart-coupons.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
			[
				'title'          => 'URL Coupons',
				'description'    => __( 'Create a unique URL that applies a discount and optionally adds one or more products to the customer\'s cart.', 'wccom' ),
				'url'            => "https://woo.com/products/url-coupons/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/woocommerce-url-coupons.svg",
				'product'        => 'woocommerce-url-coupons',
				'plugin'         => 'woocommerce-url-coupons/woocommerce-url-coupons.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
			[
				'title'          => 'WooCommerce Store Credit',
				'description'    => __( 'Create "store credit" coupons for customers which are redeemable at checkout.', 'wccom' ),
				'url'            => "https://woo.com/products/store-credit/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/woocommerce-store-credit.svg",
				'product'        => 'woocommerce-store-credit',
				'plugin'         => 'woocommerce-store-credit/woocommerce-store-credit.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
			[
				'title'          => 'Free Gift Coupons',
				'description'    => __( 'Give away a free item to any customer with the coupon code.', 'wccom' ),
				'url'            => "https://woo.com/products/free-gift-coupons/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/woocommerce-free-gift-coupons.svg",
				'product'        => 'woocommerce-free-gift-coupons',
				'plugin'         => 'woocommerce-free-gift-coupons/woocommerce-free-gift-coupons.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
			[
				'title'          => 'Group Coupons',
				'description'    => __( 'Coupons for groups. Provides the option to have coupons that are restricted to group members or roles. Works with the free Groups plugin.', 'wccom' ),
				'url'            => "https://woo.com/products/group-coupons/{$utm_string}",
				'direct_install' => false,
				'icon'           => "{$icon_dir_url}/woocommerce-group-coupons.svg",
				'product'        => 'woocommerce-group-coupons',
				'plugin'         => 'woocommerce-group-coupons/woocommerce-group-coupons.php',
				'categories'     => [
					$coupons,
				],
				'subcategories'  => [],
				'tags'           => [],
			],
		];
	}
}
