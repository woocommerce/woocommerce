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
		add_action( 'woocommerce_settings_save_advanced', array( $this, 'action_save_woo_ai_settings_tab' ) );
		add_action( 'woocommerce_settings_page_init', array( $this, 'add_ui' ) );

		$this->add_sanitization_hooks();
	}

	/**
	 * Add UI related hooks.
	 */
	public function add_ui() {
		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_ai_section' ), 10, 1 );
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
	 * Handler for the 'woocommerce_get_sections_advanced' hook,
	 * it adds the "AI" section to the advanced settings page.
	 *
	 * @param array $sections The original sections array.
	 * @return array The updated sections array.
	 */
	public function add_ai_section( $sections ) {
		if ( ! isset( $sections['ai'] ) ) {
			$sections['ai'] = __( 'AI', 'woocommerce' );
		}
		return $sections;
	}

	/**
	 * Add settings to the AI section.
	 *
	 * @param array  $settings The original settings array.
	 * @param string $current_section The current section.
	 */
	public function add_woo_ai_settings( $settings = array(), $current_section = null ) {
		if ( 'ai' !== $current_section ) {
			return $settings;
		}

		return $this->get_woo_ai_settings();
	}

	/**
	 * Return array describing new settings.
	 */
	public function get_woo_ai_settings() {

		return array(
			array(
				'id'    => 'ai_describe_store',
				'type'  => 'title',
				'title' => __( 'About your store', 'woocommerce' ),
				'desc'  => __( "This is the core of the Woo AI plugin where your inputs help shape your WooCommerce experience. Share details about your business and your brand's unique style, and our advanced AI can use this to generate cohesive content and visuals that align with your brand's identity. The information you provide here helps to craft a unified and tailored experience for your customers. And remember, you always have full control to refine and adjust the settings as per your evolving needs.", 'woocommerce' ),
			),

			array(
				'id'          => 'ai_describe_store_sell',
				'type'        => 'text',
				'title'       => __( 'What does your store sell?', 'woocommerce' ),
				'placeholder' => __( 'e.g. organic fruit and vegetables', 'woocommerce' ),
				'css'         => 'min-width:300px;',
			),

			array(
				'id'          => 'ai_describe_store_description',
				'type'        => 'textarea',
				'title'       => __( 'Describe your store', 'woocommerce' ),
				'desc_tip'    => __( 'The more detail you provide, the better job our AI can do! Try to include what makes your business unique, who your audience is, and how many products you plan to display.', 'woocommerce' ),
				'placeholder' => __( 'e.g. Marianne Renoir is a greengrocery taken over by a ten generations Parisian family who wants to keep quality and tradition in the quarter of Montmartre', 'woocommerce' ),
				'css'         => 'min-width:300px;min-height: 130px;',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'ai_describe_store',
			),

			array(
				'title' => __( 'Tone of voice', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( "Choose the tone of voice you'd like to use to communicate with your customers.", 'woocommerce' ),
				'id'    => 'ai_tone_of_voice',
			),

			array(
				'title'    => __( 'Tone', 'woocommerce' ),
				'id'       => 'ai_tone_of_voice_select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width:300px;',
				'default'  => 'informal',
				'type'     => 'select',
				'options'  => array(
					'informal'     => esc_html__( 'Informal', 'woocommerce' ),
					'humorous'     => esc_html__( 'Humorous', 'woocommerce' ),
					'neutral'      => esc_html__( 'Neutral', 'woocommerce' ),
					'youthful'     => esc_html__( 'Youthful', 'woocommerce' ),
					'formal'       => esc_html__( 'Formal', 'woocommerce' ),
					'motivational' => esc_html__( 'Motivational', 'woocommerce' ),
				),
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'ai_tone_of_voice',
			),

			array(
				'title' => __( 'Store style', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => __( "Select the look and feel that best fits your brand - we'll base the layout, color palette, and fonts used on your store around this.", 'woocommerce' ),
				'id'    => 'ai_store_style',
			),

			array(
				'title'    => __( 'Style', 'woocommerce' ),
				'id'       => 'ai_store_style_select',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width:300px;',
				'default'  => 'aa',
				'type'     => 'select',
				'options'  => array(
					'modern'    => esc_html__( 'Modern', 'woocommerce' ),
					'bold'      => esc_html__( 'Bold', 'woocommerce' ),
					'classic'   => esc_html__( 'Classic', 'woocommerce' ),
					'energetic' => esc_html__( 'Energetic', 'woocommerce' ),
					'retro'     => esc_html__( 'Retro', 'woocommerce' ),
					'warm'      => esc_html__( 'Warm', 'woocommerce' ),
				),
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'ai_store_style',
			),
		);
	}
}
