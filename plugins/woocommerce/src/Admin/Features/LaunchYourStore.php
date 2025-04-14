<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Internal\Admin\WCAdminUser;


/**
 * Takes care of Launch Your Store related actions.
 */
class LaunchYourStore {
	const BANNER_DISMISS_USER_META_KEY = 'coming_soon_banner_dismissed';
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_update_options_site-visibility', array( $this, 'save_site_visibility_options' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_settings' ) );
		add_action( 'wp_footer', array( $this, 'maybe_add_coming_soon_banner_on_frontend' ) );
		add_action( 'init', array( $this, 'register_launch_your_store_user_meta_fields' ) );
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'append_coming_soon_global_tracks' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_woocommerce_coming_soon_banner_dismissed' ), 10, 2 );
		add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'add_user_data_fields' ) );
		if ( Features::is_enabled( 'coming-soon-newsletter-template' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_newsletter_scripts' ) );
			add_action( 'save_post_wp_template', array( $this, 'maybe_track_template_change' ), 10, 3 );
		}
	}

	/**
	 * Save values submitted from WooCommerce -> Settings -> General.
	 *
	 * @return void
	 */
	public function save_site_visibility_options() {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		// New Settings API uses wp_rest nonce.
		$nonce_string = Features::is_enabled( 'settings' ) ? 'wp_rest' : 'woocommerce-settings';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_string ) ) {
			return;
		}

		// options to allowed update and their allowed values.
		$options = array(
			'woocommerce_coming_soon'      => array( 'yes', 'no' ),
			'woocommerce_store_pages_only' => array( 'yes', 'no' ),
			'woocommerce_private_link'     => array( 'yes', 'no' ),
		);

		$event_data = array();

		foreach ( $options as $name => $allowed_values ) {
			$current_value = get_option( $name, 'not set' );
			$new_value     = $current_value;

			if ( isset( $_POST[ $name ] ) ) {
				$input_value = sanitize_text_field( wp_unslash( $_POST[ $name ] ) );

				// no-op if input value is invalid.
				if ( in_array( $input_value, $allowed_values, true ) ) {
					update_option( $name, $input_value );
					$new_value = $input_value;

					// log the transition if there is one.
					if ( $current_value !== $new_value ) {
						$enabled_or_disabled              = 'yes' === $new_value ? 'enabled' : 'disabled';
						$event_data[ $name . '_toggled' ] = $enabled_or_disabled;
					}
				}
			}
			$event_data[ $name ] = $new_value;
		}
		wc_admin_record_tracks_event( 'site_visibility_saved', $event_data );
	}

	/**
	 * Append coming soon prop tracks globally.
	 *
	 * @param array $event_properties Event properties array.
	 *
	 * @return array
	 */
	public function append_coming_soon_global_tracks( $event_properties ) {
		if ( is_array( $event_properties ) ) {
			$coming_soon = 'no';
			if ( 'yes' === get_option( 'woocommerce_coming_soon', 'no' ) ) {
				if ( 'yes' === get_option( 'woocommerce_store_pages_only', 'no' ) ) {
					$coming_soon = 'store';
				} else {
					$coming_soon = 'site';
				}
			}
			$event_properties['coming_soon'] = $coming_soon;
		}
		return $event_properties;
	}


	/**
	 * Preload settings for Site Visibility.
	 *
	 * @param array $settings settings array.
	 *
	 * @return mixed
	 */
	public function preload_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		$current_screen  = get_current_screen();
		$is_setting_page = $current_screen && 'woocommerce_page_wc-settings' === $current_screen->id;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_woopayments_connect = isset( $_GET['path'] ) &&
								isset( $_GET['page'] ) &&
								( '/payments/connect' === sanitize_text_field( wp_unslash( $_GET['path'] ) ) || '/payments/onboarding' === sanitize_text_field( wp_unslash( $_GET['path'] ) ) ) &&
								'wc-admin' === $_GET['page'];
		// phpcs:enable

		if ( $is_setting_page || $is_woopayments_connect ) {
			// Regnerate the share key if it's not set.
			add_option( 'woocommerce_share_key', wp_generate_password( 32, false ) );

			$settings['siteVisibilitySettings'] = array(
				'shop_permalink'               => get_permalink( wc_get_page_id( 'shop' ) ),
				'woocommerce_coming_soon'      => get_option( 'woocommerce_coming_soon' ),
				'woocommerce_store_pages_only' => get_option( 'woocommerce_store_pages_only' ),
				'woocommerce_private_link'     => get_option( 'woocommerce_private_link' ),
				'woocommerce_share_key'        => get_option( 'woocommerce_share_key' ),
			);
		}

		return $settings;
	}

	/**
	 * User must be an admin or editor.
	 *
	 * @return bool
	 */
	private function is_manager_or_admin() {
		// phpcs:ignore
		if ( ! current_user_can( 'shop_manager' ) && ! current_user_can( 'administrator' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add 'coming soon' banner on the frontend when the following conditions met.
	 *
	 * - User must be either an admin or store editor (must be logged in).
	 * - 'woocommerce_coming_soon' option value must be 'yes'
	 * - The page must not be the Coming soon page itself.
	 */
	public function maybe_add_coming_soon_banner_on_frontend() {
		// Do not show the banner if the site is being previewed.
		if ( isset( $_GET['site-preview'] ) ) { // @phpcs:ignore
			return false;
		}

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return false;
		}

		$has_dismissed_banner = WCAdminUser::get_user_data_field( $current_user_id, self::BANNER_DISMISS_USER_META_KEY )
				// Remove this check in WC 9.4.
				|| get_user_meta( $current_user_id, 'woocommerce_' . self::BANNER_DISMISS_USER_META_KEY, true ) === 'yes';
		if ( $has_dismissed_banner ) {
			return false;
		}

		if ( ! $this->is_manager_or_admin() ) {
			return false;
		}

		// 'woocommerce_coming_soon' must be 'yes'
		if ( get_option( 'woocommerce_coming_soon', 'no' ) !== 'yes' ) {
			return false;
		}

		$store_pages_only = get_option( 'woocommerce_store_pages_only' ) === 'yes';
		if ( $store_pages_only && ! WCAdminHelper::is_store_page() ) {
			return false;
		}

		$link       = admin_url( 'admin.php?page=wc-settings&tab=site-visibility' );
		$rest_url   = rest_url( 'wp/v2/users/' . $current_user_id );
		$rest_nonce = wp_create_nonce( 'wp_rest' );

		$text = sprintf(
			// translators: no need to translate it. It's a link.
			__(
				"
			This page is in \"Coming soon\" mode and is only visible to you and those who have permission. To make it public to everyone,&nbsp;<a href='%s'>change visibility settings</a>
		",
				'woocommerce'
			),
			$link
		);
		// phpcs:ignore
		echo "<div id='coming-soon-footer-banner'><div class='coming-soon-footer-banner__content'>$text</div><a class='coming-soon-footer-banner-dismiss' data-rest-url='$rest_url' data-rest-nonce='$rest_nonce'></a></div>";
	}

	/**
	 * Register user meta fields for Launch Your Store.
	 *
	 * This should be removed in WC 9.4.
	 */
	public function register_launch_your_store_user_meta_fields() {
		if ( ! $this->is_manager_or_admin() ) {
			return;
		}

		register_meta(
			'user',
			'woocommerce_launch_your_store_tour_hidden',
			array(
				'type'         => 'string',
				'description'  => 'Indicate whether the user has dismissed the site visibility tour on the home screen.',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		register_meta(
			'user',
			'woocommerce_coming_soon_banner_dismissed',
			array(
				'type'         => 'string',
				'description'  => 'Indicate whether the user has dismissed the coming soon notice or not.',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Register user meta fields for Launch Your Store.
	 *
	 * @param array $user_data_fields user data fields.
	 * @return array
	 */
	public function add_user_data_fields( $user_data_fields ) {
		return array_merge(
			$user_data_fields,
			array(
				'launch_your_store_tour_hidden',
				self::BANNER_DISMISS_USER_META_KEY,
			)
		);
	}

	/**
	 * Reset 'woocommerce_coming_soon_banner_dismissed' user meta to 'no'.
	 *
	 * Runs when a user logs-in successfully.
	 *
	 * @param string $user_login user login.
	 * @param object $user user object.
	 */
	public function reset_woocommerce_coming_soon_banner_dismissed( $user_login, $user ) {
		$existing_meta = WCAdminUser::get_user_data_field( $user->ID, self::BANNER_DISMISS_USER_META_KEY );
		if ( 'yes' === $existing_meta ) {
			WCAdminUser::update_user_data_field( $user->ID, self::BANNER_DISMISS_USER_META_KEY, 'no' );
		}
	}

	/**
	 * Check if the Mailpoet is connected.
	 *
	 * @return bool true if Mailpoet is fully connected, meaning the API key is valid and approved.
	 */
	private function is_mailpoet_connected() {
		if ( ! class_exists( '\MailPoet\DI\ContainerWrapper' ) || ! class_exists( '\MailPoet\Settings\SettingsController' ) ) {
			return false;
		}

		$container = \MailPoet\DI\ContainerWrapper::getInstance( WP_DEBUG );

		// SettingController retrieves data from wp_mailpoet_settings table.
		$settings = $container->get( \MailPoet\Settings\SettingsController::class );

		if ( false === $settings instanceof \MailPoet\Settings\SettingsController ) {
			return false;
		}

		$mta       = $settings->get( 'mta' );
		$api_state = $mta['mailpoet_api_key_state'] ?? null;

		if ( ! $api_state || ! isset( $api_state['state'], $api_state['code'] ) ) {
			return false;
		}

		return 'valid' === $api_state['state'] && 200 === $api_state['code'];
	}

	/**
	 * Track when coming soon template is changed.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @param bool    $update Whether the post is being updated.
	 */
	public function maybe_track_template_change( $post_id, $post, $update ) {
		if ( ! $post instanceof \WP_Post || ! isset( $post->post_name, $post->post_title ) ) {
			return;
		}

		// Check multiple fields to avoid false matches with non-WooCommerce templates.
		if ( 'coming-soon' === $post->post_name && 'Page: Coming soon' === $post->post_title ) {
			$matches = array();
			$content = $post->post_content;
			preg_match( '/"comingSoonPatternId":"([^"]+)"/', $content, $matches );

			if ( isset( $matches[1] ) ) {
				wc_admin_record_tracks_event(
					'coming_soon_template_saved',
					array(
						'pattern_id' => $matches[1],
						'is_update'  => $update,
					)
				);
			}
		}
	}

	/**
	 * Load slotfill script and JS variables for the newsletter.
	 * The comingSoonNewsletter is used in client/wp-admin-scripts/coming-soon-newsletter-panel
	 *
	 * @return void
	 */
	public function load_newsletter_scripts() {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen ) {
			return;
		}

		if ( 'site-editor' !== $screen->id ) {
			return;
		}

		$mailpoet = array(
			'mailpoet_installed' => PluginsHelper::is_plugin_installed( 'mailpoet' ),
			'mailpoet_connected' => $this->is_mailpoet_connected(),
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'coming-soon-newsletter-mailpoet', '' );
		wp_enqueue_script( 'coming-soon-newsletter-mailpoet' );
		wp_add_inline_script( 'coming-soon-newsletter-mailpoet', 'var comingSoonNewsletter = ' . wp_json_encode( $mailpoet ) . ';' );
	}
}
