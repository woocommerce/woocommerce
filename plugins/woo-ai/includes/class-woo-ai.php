<?php
/**
 * Woo AI plugin main class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Woo_AI Main Class.
 */
class Woo_AI {

	/**
	 * Config
	 *
	 * @var array
	 */
	private $plugin_config;

	/**
	 * Plugin instance.
	 *
	 * @var Woo_AI
	 */
	protected static $instance = null;

	/**
	 * Main Instance.
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;

		return self::$instance;
	}

	/**
	 * Ran on activation to flush update cache
	 */
	public static function activate() {
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'woocommerce_latest_tag' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WOO_AI_FILE ) );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_name   = plugin_basename( WOO_AI_FILE );
		$this->plugin_config = array(
			'plugin_file'        => 'woocommerce/woocommerce.php',
			'slug'               => 'woocommerce',
			'proper_folder_name' => 'woocommerce',
			'api_url'            => 'https://K.wordpress.org/plugins/info/1.0/woocommerce.json',
			'repo_url'           => 'https://wordpress.org/plugins/woocommerce/',
		);

		add_filter( 'jetpack_offline_mode', '__return_false' );
		add_action( 'current_screen', array( $this, 'includes' ) );

		add_action(
			'woocommerce_block_template_area_product-form_after_add_block_product-name',
			array( $this, 'add_product_title_with_ai_assistant' )
		);
	}

	public function is_woo_product_editor_available() {
		$current_screen = get_current_screen();
		return
			'woocommerce_page_wc-admin' === $current_screen->base &&
			$current_screen->is_block_editor() &&
			\Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		$current_screen = get_current_screen();

		if ( 'post' === $current_screen->base && 'product' === $current_screen->post_type ) {
			include_once dirname( __FILE__ ) . '/class-woo-ai-product-text-generation.php';
		}

		if ( $this->is_woo_product_editor_available() ) {
			$this->includes_product_editor();
		}
	}

	/**
	 * Include any classes we need within admin / product editor app.
	 */
	public function includes_product_editor() {
		include_once dirname( __FILE__ ) . '/class-woo-ai-new-product-ai-assistant.php';
	}

	/**
	 * Add product title with AI assistant.
	 *
	 * @param object $product_name_field The product name field.
	 */
	public function add_product_title_with_ai_assistant( $product_name_field ) {
		if ( ! \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			return;
		}

		// @todo to extend the block from here
		$parent = $product_name_field->get_parent();

		if ( ! method_exists( $parent, 'add_block' ) ) {
			return;
		}
	
		$parent->add_block(
			[
				'id'         => 'product-title-with-ai-assistance',
				'order'      => $product_name_field->get_order() + 10,
				'blockName'  => 'woocommerce/product-title-ai-field',
				'attributes' => [
					'property' => 'meta_data.onsale_label',
					'label'    => __( 'Onsale Label', 'woo-product-editor-ai-workshop' ),
					'help'     => __( 'The label to display when the product is on sale.', 'woo-product-editor-ai-workshop' ),
					'placeholder' => __( 'Onsale Label', 'woo-product-editor-ai-workshop' ),
				],
			]
		);
	}

	/**
	 * Get plugin download URL.
	 *
	 * @since 1.0
	 * @param string $version The version.
	 * @return string
	 */
	public function get_download_url( $version ) {
		$data = $this->get_wporg_data();

		if ( empty( $data->versions->$version ) ) {
			return false;
		}

		return $data->versions->$version;
	}

	/**
	 * Get Plugin data.
	 *
	 * @since 1.0
	 * @return object $data The data.
	 */
	public function get_plugin_data() {
		return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_config['plugin_file'] );
	}

	/**
	 * Return true if version string is a beta version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_beta_version( $version_str ) {
		return strpos( $version_str, 'beta' ) !== false;
	}

	/**
	 * Return true if version string is a Release Candidate.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_rc_version( $version_str ) {
		return strpos( $version_str, 'rc' ) !== false;
	}

	/**
	 * Return true if version string is a stable version.
	 *
	 * @param string $version_str Version string.
	 * @return bool
	 */
	protected static function is_stable_version( $version_str ) {
		return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to beta channel, i.e.
	 * if it's beta, rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_beta_channel( $version_str ) {
		return self::is_beta_version( $version_str ) || self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to release candidate channel, i.e.
	 * if it's rc or stable release.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_rc_channel( $version_str ) {
		return self::is_rc_version( $version_str ) || self::is_stable_version( $version_str );
	}

	/**
	 * Return true if release's version string belongs to stable channel, i.e.
	 * if it's stable release and not a beta or rc.
	 *
	 * @param string $version_str Version string of the release.
	 * @return bool
	 */
	protected static function is_in_stable_channel( $version_str ) {
		return self::is_stable_version( $version_str );
	}

	/**
	 * Return available versions from wp.org tags belonging to selected channel.
	 *
	 * @param string $channel Filter versions by channel: all|beta|rc|stable.
	 * @return array(string)
	 */
	public function get_tags( $channel = 'all' ) {
		$data     = $this->get_wporg_data();
		$releases = (array) $data->versions;

		unset( $releases['trunk'] );

		$releases = array_keys( $releases );
		foreach ( $releases as $index => $version ) {
			if ( version_compare( $version, '3.6', '<' ) ) {
				unset( $releases[ $index ] );
			}
		}

		if ( 'beta' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_beta_channel' ) );
		} elseif ( 'rc' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_rc_channel' ) );
		} elseif ( 'stable' === $channel ) {
			$releases = array_filter( $releases, array( __CLASS__, 'is_in_stable_channel' ) );
		}

		return $releases;
	}

}
