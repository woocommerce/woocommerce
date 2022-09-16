<?php
/**
 * FeaturesController class file
 */

namespace Automattic\WooCommerce\Internal\Features;

use Automattic\WooCommerce\Admin\Features\Analytics;
use Automattic\WooCommerce\Admin\Features\Navigation\Init;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Class to define the WooCommerce features that can be enabled and disabled by admin users,
 * provides also a mechanism for WooCommerce plugins to declare that they are compatible
 * (or incompatible) with a given feature.
 */
class FeaturesController {

	use AccessiblePrivateMethods;

	public const FEATURE_ENABLED_CHANGED_ACTION = 'woocommerce_feature_enabled_changed';

	/**
	 * The existing feature definitions.
	 *
	 * @var array[]
	 */
	private $features;

	/**
	 * The registered compatibility info for WooCommerce plugins, with plugin names as keys.
	 *
	 * @var array
	 */
	private $compatibility_info_by_plugin;

	/**
	 * The registered compatibility info for WooCommerce plugins, with feature ids as keys.
	 *
	 * @var array
	 */
	private $compatibility_info_by_feature;

	/**
	 * The LegacyProxy instance to use.
	 *
	 * @var LegacyProxy
	 */
	private $proxy;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		$features = array(
			'analytics'           => array(
				'name'            => __( 'Analytics', 'woocommerce' ),
				'description'     => __( 'Enables WooCommerce Analytics', 'woocommerce' ),
				'is_experimental' => false,
			),
			'new_navigation'      => array(
				'name'            => __( 'Navigation', 'woocommerce' ),
				'description'     => __( 'Adds the new WooCommerce navigation experience to the dashboard', 'woocommerce' ),
				'is_experimental' => false,
			),
			'custom_order_tables' => array(
				'name'            => __( 'Custom order tables', 'woocommerce' ),
				'description'     => __( 'Enable the custom orders tables feature (still in development)', 'woocommerce' ),
				'is_experimental' => true,
			),
		);

		$this->init_features( $features );

		foreach ( array_keys( $this->features ) as $feature_id ) {
			$this->compatibility_info_by_feature[ $feature_id ] = array(
				'compatible'   => array(),
				'incompatible' => array(),
			);
		}

		self::add_filter( 'updated_option', array( $this, 'process_updated_option' ), 999, 3 );
		self::add_filter( 'added_option', array( $this, 'process_added_option' ), 999, 3 );
		self::add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_features_section' ), 10, 1 );
		self::add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_feature_settings' ), 10, 2 );
	}

	/**
	 * Initialize the class according to the existing features.
	 *
	 * @param array $features Information about the existing features.
	 */
	private function init_features( array $features ) {
		$this->compatibility_info_by_plugin  = array();
		$this->compatibility_info_by_feature = array();

		$this->features = $features;

		foreach ( array_keys( $this->features ) as $feature_id ) {
			$this->compatibility_info_by_feature[ $feature_id ] = array(
				'compatible'   => array(),
				'incompatible' => array(),
			);
		}
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $proxy The instance of LegacyProxy to use.
	 */
	final public function init( LegacyProxy $proxy ) {
		$this->proxy = $proxy;
	}

	/**
	 * Get all the existing WooCommerce features.
	 *
	 * Returns an associative array where keys are unique feature ids
	 * and values are arrays with these keys:
	 *
	 * - name (string)
	 * - description (string)
	 * - is_experimental (bool)
	 * - is_enabled (bool) (only if $include_enabled_info is passed as true)
	 *
	 * @param bool $include_experimental Include also experimental/work in progress features in the list.
	 * @param bool $include_enabled_info True to include the 'is_enabled' field in the returned features info.
	 * @returns array An array of information about existing features.
	 */
	public function get_features( bool $include_experimental = false, bool $include_enabled_info = false ): array {
		$features = $this->features;

		if ( ! $include_experimental ) {
			$features = array_filter(
				$features,
				function( $feature ) {
					return ! $feature['is_experimental'];
				}
			);
		}

		if ( $include_enabled_info ) {
			foreach ( array_keys( $features ) as $feature_id ) {
				$is_enabled                            = $this->feature_is_enabled( $feature_id );
				$features[ $feature_id ]['is_enabled'] = $is_enabled;
			}
		}

		return $features;
	}

	/**
	 * Check if a given feature is currently enabled.
	 *
	 * @param  string $feature_id Unique feature id.
	 * @return bool True if the feature is enabled, false if not or if the feature doesn't exist.
	 */
	public function feature_is_enabled( string $feature_id ): bool {
		return $this->feature_exists( $feature_id ) && 'yes' === get_option( $this->feature_enable_option_name( $feature_id ) );
	}

	/**
	 * Change the enabled/disabled status of a feature.
	 *
	 * @param string $feature_id Unique feature id.
	 * @param bool   $enable True to enable the feature, false to disable it.
	 * @return bool True on success, false if feature doesn't exist or the new value is the same as the old value.
	 */
	public function change_feature_enable( string $feature_id, bool $enable ): bool {
		if ( ! $this->feature_exists( $feature_id ) ) {
			return false;
		}

		return update_option( $this->feature_enable_option_name( $feature_id ), $enable ? 'yes' : 'no' );
	}

	/**
	 * Declare (in)compatibility with a given feature for a given plugin.
	 *
	 * This method MUST be executed from inside a handler for the 'before_woocommerce_init' hook.
	 *
	 * The plugin name is expected to be in the form 'directory/file.php' and be one of the keys
	 * of the array returned by 'get_plugins', but this won't be checked. Plugins are expected to use
	 * FeaturesUtil::declare_compatibility instead, passing the full plugin file path instead of the plugin name.
	 *
	 * @param string $feature_id Unique feature id.
	 * @param string $plugin_name Plugin name, in the form 'directory/file.php'.
	 * @param bool   $positive_compatibility True if the plugin declares being compatible with the feature, false if it declares being incompatible.
	 * @return bool True on success, false on error (feature doesn't exist or not inside the required hook).
	 * @throws \Exception A plugin attempted to declare itself as compatible and incompatible with a given feature at the same time.
	 */
	public function declare_compatibility( string $feature_id, string $plugin_name, bool $positive_compatibility = true ): bool {
		if ( ! $this->proxy->call_function( 'doing_action', 'before_woocommerce_init' ) ) {
			$class_and_method = ( new \ReflectionClass( $this ) )->getShortName() . '::' . __FUNCTION__;
			/* translators: 1: class::method 2: before_woocommerce_init */
			$this->proxy->call_function( 'wc_doing_it_wrong', $class_and_method, sprintf( __( '%1$s should be called inside the %2$s action.', 'woocommerce' ), $class_and_method, 'before_woocommerce_init' ), '7.0' );
			return false;
		}

		if ( ! $this->feature_exists( $feature_id ) ) {
			return false;
		}

		// Register compatibility by plugin.

		ArrayUtil::ensure_key_is_array( $this->compatibility_info_by_plugin, $plugin_name );

		$key          = $positive_compatibility ? 'compatible' : 'incompatible';
		$opposite_key = $positive_compatibility ? 'incompatible' : 'compatible';
		ArrayUtil::ensure_key_is_array( $this->compatibility_info_by_plugin[ $plugin_name ], $key );
		ArrayUtil::ensure_key_is_array( $this->compatibility_info_by_plugin[ $plugin_name ], $opposite_key );

		if ( in_array( $feature_id, $this->compatibility_info_by_plugin[ $plugin_name ][ $opposite_key ], true ) ) {
			throw new \Exception( "Plugin $plugin_name is trying to declare itself as $key with the '$feature_id' feature, but it already declared itself as $opposite_key" );
		}

		if ( ! in_array( $feature_id, $this->compatibility_info_by_plugin[ $plugin_name ][ $key ], true ) ) {
			$this->compatibility_info_by_plugin[ $plugin_name ][ $key ][] = $feature_id;
		}

		// Register compatibility by feature.

		$key = $positive_compatibility ? 'compatible' : 'incompatible';

		if ( ! in_array( $plugin_name, $this->compatibility_info_by_feature[ $feature_id ][ $key ], true ) ) {
			$this->compatibility_info_by_feature[ $feature_id ][ $key ][] = $plugin_name;
		}

		return true;
	}

	/**
	 * Check whether a feature exists with a given id.
	 *
	 * @param string $feature_id The feature id to check.
	 * @return bool True if the feature exists.
	 */
	private function feature_exists( string $feature_id ): bool {
		return isset( $this->features[ $feature_id ] );
	}

	/**
	 * Get the ids of the features that a certain plugin has declared compatibility for.
	 *
	 * This method can't be called before the 'woocommerce_init' hook is fired.
	 *
	 * @param string $plugin_name Plugin name, in the form 'directory/file.php'.
	 * @return array An array having a 'compatible' and an 'incompatible' key, each holding an array of feature ids.
	 */
	public function get_compatible_features_for_plugin( string $plugin_name ) : array {
		$this->verify_did_woocommerce_init( __FUNCTION__ );

		if ( ! isset( $this->compatibility_info_by_plugin[ $plugin_name ] ) ) {
			return array(
				'compatible'   => array(),
				'incompatible' => array(),
			);
		}

		return $this->compatibility_info_by_plugin[ $plugin_name ];
	}

	/**
	 * Get the names of the plugins that have been declared compatible or incompatible with a given feature.
	 *
	 * @param string $feature_id Feature id.
	 * @return array An array having a 'compatible' and an 'incompatible' key, each holding an array of plugin names.
	 */
	public function get_compatible_plugins_for_feature( string $feature_id ) : array {
		$this->verify_did_woocommerce_init( __FUNCTION__ );

		if ( ! $this->feature_exists( $feature_id ) ) {
			return array(
				'compatible'   => array(),
				'incompatible' => array(),
			);
		}

		return $this->compatibility_info_by_feature[ $feature_id ];
	}

	/**
	 * Check if the 'woocommerce_init' has run or is running, do a 'wc_doing_it_wrong' if not.
	 *
	 * @param string $function Name of the invoking method.
	 */
	private function verify_did_woocommerce_init( string $function ) {
		if ( ! $this->proxy->call_function( 'did_action', 'woocommerce_init' ) &&
			! $this->proxy->call_function( 'doing_action', 'woocommerce_init' ) ) {
			$class_and_method = ( new \ReflectionClass( $this ) )->getShortName() . '::' . $function;
			/* translators: 1: class::method 2: plugins_loaded */
			$this->proxy->call_function( 'wc_doing_it_wrong', $class_and_method, sprintf( __( '%1$s should not be called before the %2$s action.', 'woocommerce' ), $class_and_method, 'woocommerce_init' ), '7.0' );
		}
	}

	/**
	 * Get the name of the option that enables/disables a given feature.
	 * Note that it doesn't check if the feature actually exists.
	 *
	 * @param string $feature_id The id of the feature.
	 * @return string The option that enables or disables the feature.
	 */
	public function feature_enable_option_name( string $feature_id ): string {
		if ( 'analytics' === $feature_id ) {
			return Analytics::TOGGLE_OPTION_NAME;
		} elseif ( 'new_navigation' === $feature_id ) {
			return Init::TOGGLE_OPTION_NAME;
		}

		return "woocommerce_feature_${feature_id}_enabled";
	}

	/**
	 * Handler for the 'added_option' hook.
	 *
	 * It fires FEATURE_ENABLED_CHANGED_ACTION when a feature is enabled or disabled.
	 *
	 * @param string $option The option that has been created.
	 * @param mixed  $value The value of the option.
	 */
	private function process_added_option( string $option, $value ) {
		$this->process_updated_option( $option, false, $value );
	}

	/**
	 * Handler for the 'updated_option' hook.
	 *
	 * It fires FEATURE_ENABLED_CHANGED_ACTION when a feature is enabled or disabled.
	 *
	 * @param string $option The option that has been modified.
	 * @param mixed  $old_value The old value of the option.
	 * @param mixed  $value The new value of the option.
	 */
	private function process_updated_option( string $option, $old_value, $value ) {
		$matches = array();
		$success = preg_match( '/^woocommerce_feature_([a-zA-Z0-9_]+)_enabled$/', $option, $matches );

		if ( ! $success ) {
			return;
		}

		if ( $value === $old_value ) {
			return;
		}

		$feature_id = $matches[1];

		/**
		 * Action triggered when a feature is enabled or disabled (the value of the corresponding setting option is changed).
		 *
		 * @param string $feature_id The id of the feature.
		 * @param bool $enabled True if the feature has been enabled, false if it has been disabled.
		 *
		 * @since 7.0.0
		 */
		do_action( self::FEATURE_ENABLED_CHANGED_ACTION, $feature_id, 'yes' === $value );
	}

	/**
	 * Handler for the 'woocommerce_get_sections_advanced' hook,
	 * it adds the "Features" section to the advanced settings page.
	 *
	 * @param array $sections The original sections array.
	 * @return array The updated sections array.
	 */
	private function add_features_section( $sections ) {
		if ( ! isset( $sections['features'] ) ) {
			$sections['features'] = __( 'Features', 'woocommerce' );
		}
		return $sections;
	}

	/**
	 * Handler for the 'woocommerce_get_settings_advanced' hook,
	 * it adds the settings UI for all the existing features.
	 *
	 * Note that the settings added via the 'woocommerce_settings_features' hook will be
	 * displayed in the non-experimental features section.
	 *
	 * @param array  $settings The existing settings for the corresponding settings section.
	 * @param string $current_section The section to get the settings for.
	 * @return array The updated settings array.
	 */
	private function add_feature_settings( $settings, $current_section ): array {
		if ( 'features' !== $current_section ) {
			return $settings;
		}

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/**
		 * Filter allowing WooCommerce Admin to be disabled.
		 *
		 * @param bool $disabled False.
		 */
		$admin_features_disabled = apply_filters( 'woocommerce_admin_disabled', false );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$feature_settings =
			array(
				array(
					'title' => __( 'Features', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Start using new features that are being progressively rolled out to improve the store management experience.', 'woocommerce' ),
					'id'    => 'features_options',
				),
			);

		$features = $this->get_features( true );

		$feature_ids              = array_keys( $features );
		$experimental_feature_ids = array_filter(
			$feature_ids,
			function( $feature_id ) use ( $features ) {
				return $features[ $feature_id ]['is_experimental'];
			}
		);
		$mature_feature_ids       = array_diff( $feature_ids, $experimental_feature_ids );
		$feature_ids              = array_merge( $mature_feature_ids, array( 'mature_features_end' ), $experimental_feature_ids );

		foreach ( $feature_ids as $id ) {
			if ( 'mature_features_end' === $id ) {
				// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
				/**
				 * Filter allowing to add additional settings to the WooCommerce Advanced - Features settings page.
				 *
				 * @param bool $disabled False.
				 */
				$additional_features = apply_filters( 'woocommerce_settings_features', $features );
				// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment

				$feature_settings = array_merge( $feature_settings, $additional_features );

				if ( ! empty( $experimental_feature_ids ) ) {
					$feature_settings[] = array(
						'type' => 'sectionend',
						'id'   => 'features_options',
					);

					$feature_settings[] = array(
						'title' => __( 'Experimental features', 'woocommerce' ),
						'type'  => 'title',
						'desc'  => __( 'These features are either experimental or incomplete, enable them at your own risk!', 'woocommerce' ),
						'id'    => 'experimental_features_options',
					);
				}
				continue;
			}

			$feature_settings[] = $this->get_setting_for_feature( $id, $features[ $id ], $admin_features_disabled );
		}

		$feature_settings[] = array(
			'type' => 'sectionend',
			'id'   => empty( $experimental_feature_ids ) ? 'features_options' : 'experimental_features_options',
		);

		return $feature_settings;
	}

	/**
	 * Get the parameters to display the setting enable/disable UI for a given feature.
	 *
	 * @param string $feature_id The feature id.
	 * @param array  $feature The feature parameters, as returned by get_features.
	 * @param bool   $admin_features_disabled True if admin features have been disabled via 'woocommerce_admin_disabled' filter.
	 * @return array The parameters to add to the settings array.
	 */
	private function get_setting_for_feature( string $feature_id, array $feature, bool $admin_features_disabled ): array {
		$description = $feature['description'];
		$disabled    = false;
		$desc_tip    = '';

		if ( ( 'analytics' === $feature_id || 'new_navigation' === $feature_id ) && $admin_features_disabled ) {
			$disabled = true;
			$desc_tip = __( 'WooCommerce Admin has been disabled', 'woocommerce' );
		} elseif ( 'new_navigation' === $feature_id ) {
			$needs_update = version_compare( get_bloginfo( 'version' ), '5.6', '<' );
			if ( $needs_update && current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
				$update_text = sprintf(
					// translators: 1: line break tag, 2: open link to WordPress update link, 3: close link tag.
					__( '%1$s %2$sUpdate WordPress to enable the new navigation%3$s', 'woocommerce' ),
					'<br/>',
					'<a href="' . self_admin_url( 'update-core.php' ) . '" target="_blank">',
					'</a>'
				);
				$description .= $update_text;
				$disabled     = true;
			}
		}

		return array(
			'title'    => $feature['name'],
			'desc'     => $description,
			'type'     => 'checkbox',
			'id'       => $this->feature_enable_option_name( $feature_id ),
			'class'    => $disabled ? 'disabled' : '',
			'desc_tip' => $desc_tip,
		);
	}
}
