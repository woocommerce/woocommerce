<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils as UcpUtils;

defined( 'ABSPATH' ) || exit;

/**
 * The AI access section on WooCommerce > Settings > Site visibility, while the agent_readiness feature
 * is enabled. Saves the two AI access options with the page and passes them, with the section's status,
 * to the React section, along with whether AI shopping is on (read only). Prototype: the status only
 * comes from a preview query argument.
 *
 * @since 11.4.0
 */
class AiAccessSettings implements RegisterHooksInterface {

	/**
	 * Whether AI tools may use the site's content in answers: yes or no.
	 */
	public const ANSWERS_OPTION = 'woocommerce_ai_answers_allowed';

	/**
	 * Whether AI companies may use the site's content to train models: yes or no.
	 */
	public const TRAINING_OPTION = 'woocommerce_ai_training_allowed';

	/**
	 * Prototype only: forces a status, for example &wc_ai_access_preview=robots_file.
	 */
	public const PREVIEW_QUERY_ARG = 'wc_ai_access_preview';

	/**
	 * The settings are in use.
	 */
	public const STATUS_DEFAULT = 'default';

	/**
	 * The site serves its own robots.txt file, so the settings can't be added to it.
	 */
	public const STATUS_ROBOTS_FILE = 'robots_file';

	/**
	 * Another plugin already sets AI preferences.
	 */
	public const STATUS_OTHER_PLUGIN = 'other_plugin';

	/**
	 * Jetpack's AI access controls (Jetpack > SEO > GEO) are active, so Jetpack manages AI access.
	 */
	public const STATUS_JETPACK = 'jetpack';

	/**
	 * Jetpack's SEO page, which holds its AI access controls on the GEO tab.
	 */
	private const JETPACK_SEO_PAGE = 'admin.php?page=jetpack-seo';

	/**
	 * Default value of each option.
	 */
	private const DEFAULTS = array(
		self::ANSWERS_OPTION  => 'yes',
		self::TRAINING_OPTION => 'no',
	);

	/**
	 * Features controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller Features controller.
	 */
	final public function init( FeaturesController $features_controller ): void {
		$this->features_controller = $features_controller;
	}

	/**
	 * Register hooks. The feature check waits for init, since feature definitions load translations.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Hook into the Site visibility page when the feature is enabled.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! $this->features_controller->feature_is_enabled( AgenticCommerceSettings::FEATURE_ID ) ) {
			return;
		}
		add_action( 'woocommerce_update_options_site-visibility', array( $this, 'handle_save' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'handle_admin_shared_settings' ) );
	}

	/**
	 * Save the options posted with the Site visibility page. Values other than yes or no are ignored.
	 *
	 * @internal
	 */
	public function handle_save(): void {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'woocommerce-settings' ) ) {
			return;
		}

		foreach ( array_keys( self::DEFAULTS ) as $name ) {
			if ( ! isset( $_POST[ $name ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
			if ( in_array( $value, array( 'yes', 'no' ), true ) ) {
				update_option( $name, $value );
			}
		}
	}

	/**
	 * Pass the section's values and status to the settings page as wcSettings.admin.aiAccessSettings.
	 *
	 * @internal
	 *
	 * @param mixed $settings Shared admin settings.
	 * @return mixed
	 */
	public function handle_admin_shared_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return $settings;
		}

		$settings['aiAccessSettings'] = array(
			self::ANSWERS_OPTION  => $this->get_value( self::ANSWERS_OPTION ),
			self::TRAINING_OPTION => $this->get_value( self::TRAINING_OPTION ),
			'status'              => $this->get_status(),
			'aiShoppingEnabled'   => UcpUtils::is_enabled(),
			'aiShoppingUrl'       => AgenticCommerceSettings::url( AgenticCommerceSettings::SHOPPING_SECTION ),
			'jetpackUrl'          => admin_url( self::JETPACK_SEO_PAGE ),
		);

		return $settings;
	}

	/**
	 * Whether the settings are in use. Until real detection exists, this is the default status unless
	 * a preview is forced, so the section never shows a warning it can't back up.
	 *
	 * @return string One of the STATUS_* constants.
	 */
	public function get_status(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview switch.
		$preview = isset( $_GET[ self::PREVIEW_QUERY_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ self::PREVIEW_QUERY_ARG ] ) ) : '';

		return in_array( $preview, array( self::STATUS_ROBOTS_FILE, self::STATUS_JETPACK, self::STATUS_OTHER_PLUGIN ), true ) ? $preview : self::STATUS_DEFAULT;
	}

	/**
	 * An option's saved value, or its default when the saved value isn't yes or no.
	 *
	 * @param string $name Option name.
	 */
	private function get_value( string $name ): string {
		$value = get_option( $name, self::DEFAULTS[ $name ] );

		return in_array( $value, array( 'yes', 'no' ), true ) ? $value : self::DEFAULTS[ $name ];
	}
}
