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
	 * Tone of voice select options.
	 *
	 * @var array
	 */
	private $tone_of_voice_select_options;

	/**
	 * Constants used for naming of saved options in the database.
	 */
	private const WOO_AI_OPTIONS_PREFIX        = 'woo_ai_';
	private const STORE_DESCRIPTION_OPTION_KEY = self::WOO_AI_OPTIONS_PREFIX . 'describe_store_description';
	private const TONE_OF_VOICE_OPTION_KEY     = self::WOO_AI_OPTIONS_PREFIX . 'tone_of_voice_select';
	private const WOO_AI_TITLE_OPTION_KEY      = self::WOO_AI_OPTIONS_PREFIX . 'title';

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
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_woo_ai_settings' ), 10, 2 );
		add_filter( 'woocommerce_settings_groups', array( $this, 'add_woo_ai_settings_group' ) );
		add_filter( 'woocommerce_settings-woo-ai', array( $this, 'add_woo_ai_settings_group_settings' ) );

		$this->tone_of_voice_select_options = array(
			'humorous'     => __( 'Light-hearted and fun.', 'woocommerce' ),
			'youthful'     => __( 'Friendly and cheeky tone.', 'woocommerce' ),
			'informal'     => __( 'Relaxed and friendly.', 'woocommerce' ),
			'neutral'      => __( 'Default: Neutral tone with casual expressions.', 'woocommerce' ),
			'motivational' => __( 'Passionate and inspiring.', 'woocommerce' ),
			'formal'       => __( 'Direct yet respectful formal tone.', 'woocommerce' ),
		);

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
	 * Adds settings which can be retrieved via the WooCommerce Settings API.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/Settings-API
	 *
	 * @param array $settings The original settings array.
	 * @return array The modified settings array.
	 */
	public function add_woo_ai_settings_group_settings( $settings ) {
		$settings[] = array(
			'id'          => 'tone-of-voice',
			'option_key'  => self::TONE_OF_VOICE_OPTION_KEY,
			'label'       => __( 'Storewide Tone of Voice', 'woocommerce' ),
			'description' => __( 'This controls the conversational tone that will be used when generating content.', 'woocommerce' ),
			'default'     => 'neutral',
			'type'        => 'select',
			'options'     => $this->tone_of_voice_select_options,
		);
		$settings[] = array(
			'id'          => 'store-description',
			'option_key'  => self::STORE_DESCRIPTION_OPTION_KEY,
			'label'       => __( 'Store Description', 'woocommerce' ),
			'description' => __( 'This is a short description of your store which could be used to help generate content.', 'woocommerce' ),
			'type'        => 'textarea',
		);
		return $settings;
	}

	/**
	 * Register our Woo AI plugin group to the WooCommerce Settings API.
	 *
	 * @param array $locations The original settings array.
	 * @return array The modified settings array.
	 */
	public function add_woo_ai_settings_group( $locations ) {
		$locations[] = array(
			'id'          => 'woo-ai',
			'label'       => __( 'Woo AI', 'woocommerce' ),
			'description' => __( 'Settings for the Woo AI plugin.', 'woocommerce' ),
		);
		return $locations;
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
			'id'    => self::WOO_AI_TITLE_OPTION_KEY,
			'title' => __( 'Woo AI Personalization', 'woocommerce' ),
			'desc'  => __( "Empower your store with AI-driven content that truly reflects your brand.\nBy providing additional context, like your store's tone of voice and a brief business description, our AI adapts its output to fit seamlessly with your brand's identity.", 'woocommerce' ),
			'type'  => 'title',
		);

		$settings_ai[] = array(
			'id'      => self::TONE_OF_VOICE_OPTION_KEY,
			'name'    => __( 'Tone of voice', 'woocommerce' ),
			'desc'    => __( 'Select the tone of voice for the AI', 'woocommerce' ),
			'type'    => 'select',
			'options' => $this->tone_of_voice_select_options,
			'css'     => 'min-width:300px;',

		);

		$settings_ai[] = array(
			'id'          => self::STORE_DESCRIPTION_OPTION_KEY,
			'title'       => __( 'Describe your business', 'woocommerce' ),
			'type'        => 'textarea',
			'desc_tip'    => __( 'Tell us what makes your business unique to further improve accuracy of the AI-generated content. This will not be shown to customers.', 'woocommerce' ),
			/* translators: Short paragraph describing the store. */
			'placeholder' => __( 'e.g. Marianne Renoir is a greengrocery taken over by a ten generations Parisian family who wants to keep quality and tradition in the quarter of Montmartre', 'woocommerce' ),
			'css'         => 'min-width:300px;min-height: 130px;',
		);

		$settings_ai[] = array(
			'id'   => self::WOO_AI_TITLE_OPTION_KEY,
			'type' => 'sectionend',
		);

		return $settings_ai;
	}
}
