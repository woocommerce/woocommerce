<?php
/**
 * Woo AI settings page module.
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Constants;

/**
 * Woo_AI_Settings Class.
 */
class Woo_AI_Settings {

	/**
	 * Plugin instance.
	 *
	 * @var Woo_AI_Settings
	 */
	protected static $instance = null;


	/**
	 * Settings ID.
	 *
	 * @var id
	 */
	protected $id = 'woo-ai-settings-tab';

	/**
	 * Main Instance.
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_woo_ai_settings_script' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_woo_ai_settings' ), 10, 2 );

		$this->add_sanitization_hooks();
	}

	/**
	 * Add sanitization hooks.
	 */
	public function add_sanitization_hooks() {
		$settings = $this->get_woo_ai_settings();

		foreach ( $settings as $setting ) {
			if ( in_array( $setting['type'], array( 'text', 'textarea' ), true ) ) {
				add_filter( 'woocommerce_admin_settings_sanitize_option_' . $setting['id'], array( $this, 'strip_tags_field_value' ) );
			}
		}
	}

	/**
	 * Sanitize field value.
	 *
	 * @param string $raw_value The current section.
	 */
	public function strip_tags_field_value( $raw_value ) {
		return wp_strip_all_tags( $raw_value ?? '' );
	}

	/**
	 * Add settings to the AI section.
	 *
	 * @param array  $settings The original settings array.
	 * @param string $current_section The current section.
	 */
	public function add_woo_ai_settings( $settings = array(), $current_section = null ) {
		if ( 'features' === $current_section ) {
			return array_merge(
				$this->get_woo_ai_settings(),
				$settings
			);
		}

		return $settings;
	}

	/**
	 * Add Woo AI Settings to section.
	 */
	public function get_woo_ai_settings() {
		$settings_ai = array();

		$settings_ai[] = array(
			'id'    => 'woo_ai_title',
			'type'  => 'title',
			'title' => __( 'Artificial Intelligence', 'woocommerce' ),
			'desc'  => __( "Save time by automating mundane parts of store management. This information will make AI-generated content, visuals, and settings more aligned with your store's goals and identity.", 'woocommerce' ),
		);

		$settings_ai[] = array(
			'title'   => __( 'Enable AI', 'woocommerce' ),
			'desc'    => __( 'Enable AI features in your store', 'woocommerce' ),
			'id'      => 'woo_ai_enable_checkbox',
			'default' => 'yes',
			'type'    => 'checkbox',
		);

		$settings_ai[] = array(
			'name'    => __( 'Tone of voice', 'woocommerce' ),
			'id'      => 'woo_ai_tone_of_voice_select',
			'type'    => 'select',
			'options' => array(
				'informal'     => __( 'Relaxed and friendly.', 'woocommerce' ),
				'humorous'     => __( 'Light-hearted and fun.', 'woocommerce' ),
				'neutral'      => __( 'A balanced tone that uses casual expressions.', 'woocommerce' ),
				'youthful'     => __( 'Friendly and cheeky tone.', 'woocommerce' ),
				'formal'       => __( 'Direct yet respectful formal tone.', 'woocommerce' ),
				'motivational' => __( 'Passionate and inspiring.', 'woocommerce' ),
			),
			'css'     => 'min-width:300px;',
			'desc'    => __( 'Select the tone of voice for the AI', 'woocommerce' ),
		);

		$settings_ai[] = array(
			'id'          => 'woo_ai_describe_store_description',
			'type'        => 'textarea',
			'title'       => __( 'Describe your business', 'woocommerce' ),
			'desc_tip'    => __( 'Tell us what makes your business unique to further improve accuracy of the AI-generated content. This will not be shown to customers.', 'woocommerce' ),
			'placeholder' => __( 'e.g. Marianne Renoir is a greengrocery taken over by a ten generations Parisian family who wants to keep quality and tradition in the quarter of Montmartre', 'woocommerce' ),
			'css'         => 'min-width:300px;min-height: 130px;',
		);

		$settings_ai[] = array(
			'type' => 'sectionend',
			'id'   => 'woo_ai_title',
		);

		return $settings_ai;
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_settings_script() {
		global $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'admin.php' !== $pagenow || ( isset( $_GET['page'] ) && 'wc-settings' !== $_GET['page'] ) ) {
			return;
		}

		$script_path = '/../build/settings.js';
		$script_url  = plugins_url( $script_path, __FILE__ );
		$version     = Constants::get_constant( 'WC_VERSION' );

		wp_register_script(
			'woo-ai-settings',
			$script_url,
			array( 'jquery' ),
			$version,
			true
		);

		wp_enqueue_script( 'woo-ai-settings' );

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/settings.css' );

		wp_register_style(
			'woo-ai-settings',
			plugins_url( '/../build/settings.css', __FILE__ ),
			array(),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai-settings' );
	}
}
