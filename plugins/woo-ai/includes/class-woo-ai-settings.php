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
		add_action( 'woocommerce_settings_save_advanced', array( $this, 'action_save_woo_ai_settings_tab' ) );
		add_action( 'woocommerce_settings_page_init', array( $this, 'add_ui' ) );

		$this->add_sanitization_hooks();
	}

	/**
	 * Add UI related hooks.
	 */
	public function add_ui() {
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_woo_ai_settings' ), 10, 2 );
	}

	/**
	 * Save settings.
	 */
	public function action_save_woo_ai_settings_tab() {
		WC_Admin_Settings::save_fields(
			$this->get_woo_ai_settings()
		);
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
			$settings = array_merge(
				$this->get_woo_ai_settings(),
				$settings
			);
		}

		return $settings;
	}

	/**
	 * Return array describing new settings.
	 */
	public function get_woo_ai_settings() {

		// These are made available in the DOM and actually toggled client-side.
		$tone_desc = array(
			'informal'     => __( 'Relaxed and friendly, as if you were having a conversation with a friend.', 'woocommerce' ),
			'humorous'     => __( 'Engage customers with a light-hearted and fun communication style.', 'woocommerce' ),
			'neutral'      => __( 'A balanced and impartial tone that uses casual expressions without slang.', 'woocommerce' ),
			'youthful'     => __( 'Bring energy and enthusiasm to your store with a friendly and cheeky tone.', 'woocommerce' ),
			'formal'       => __( 'Direct yet respectful, a formal tone sounds serious and professional.', 'woocommerce' ),
			'motivational' => __( 'Passionate and inspiring, engage users with this upbeat tone.', 'woocommerce' ),
		);

		$markup_str = array_reduce(
			array_keys( $tone_desc ),
			function( $acc, $key ) use ( $tone_desc ) {
				$value = $tone_desc[ $key ];
				return $acc . '<span class="woo-ai-settings-tone-option" data-option="' . htmlspecialchars( $key ) . '">' . htmlspecialchars( $value ) . '</span>';
			},
			''
		);

		return array(
			array(
				'id'    => 'woo_ai_title',
				'type'  => 'title',
				'title' => __( 'Artificial Intelligence', 'woocommerce' ),
				'desc'  => __( "Save time by automating mundane parts of store management. This information will make AI-generated content, visuals, and setting more aligned with your store's goals and identity.", 'woocommerce' ),
			),

			array(
				'title'   => __( 'Enable AI', 'woocommerce' ),
				'desc'    => __( 'Enable AI features in your store', 'woocommerce' ),
				'id'      => 'woo_ai_enable_checkbox',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'             => __( 'Tone of voice', 'woocommerce' ),
				'id'                => 'woo_ai_tone_of_voice_select',
				'css'               => 'min-width:300px;',
				'default'           => 'informal',
				'type'              => 'select',
				'desc'              => $markup_str,
				'desc_tip'          => __( 'Choose the language style that best resonates with your customers. It\'ll be used in text-based content, like product descriptions.', 'woocommerce' ),
				'custom_attributes' => array( 'disabled' => 'true' ),
				'options'           => array(
					'informal'     => esc_html__( 'Informal', 'woocommerce' ),
					'humorous'     => esc_html__( 'Humorous', 'woocommerce' ),
					'neutral'      => esc_html__( 'Neutral', 'woocommerce' ),
					'youthful'     => esc_html__( 'Youthful', 'woocommerce' ),
					'formal'       => esc_html__( 'Formal', 'woocommerce' ),
					'motivational' => esc_html__( 'Motivational', 'woocommerce' ),
				),
			),

			array(
				'id'                => 'ai_describe_store_description',
				'type'              => 'textarea',
				'custom_attributes' => array( 'disabled' => 'true' ),
				'title'             => __( 'Describe your business', 'woocommerce' ),
				'desc_tip'          => __( 'Tell us what makes your business unique to further improve accuracy of the AI-generated content. This will not be shown to customers.', 'woocommerce' ),
				'placeholder'       => __( 'e.g. Marianne Renoir is a greengrocery taken over by a ten generations Parisian family who wants to keep quality and tradition in the quarter of Montmartre', 'woocommerce' ),
				'css'               => 'min-width:300px;min-height: 130px;',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'woo_ai_title',
			),
		);
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_settings_script() {
		global $pagenow;

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
