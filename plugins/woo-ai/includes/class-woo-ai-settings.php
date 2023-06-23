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
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'action_save_woo_ai_settings_tab' ) );
		add_action( 'woocommerce_settings_page_init', array( $this, 'add_tabs' ) );
	}

	/**
	 * Initialize hooks.
	 */
	public function add_tabs() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_woo_ai_settings_tab' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'action_woocommerce_settings_ai_tab' ), 10 );
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
	 * Include any classes we need within admin.
	 */
	public function action_woocommerce_settings_ai_tab() {
		$settings = $this->get_woo_ai_settings();

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function get_woo_ai_settings() {
		return array(
			array(
				'id'    => 'ai_describe_store',
				'type'  => 'title',
				'title' => __( 'About your store', 'woocommerce' ),
				'desc'  => __( "Tell us a bit more about your business, and our AI will get to work on generating your content, images, and look and feel of your store. And if it's not quite right you can always customize it later.", 'woocommerce' ),
			),

			array(
				'id'    => 'ai_describe_store_sell',
				'type'  => 'text',
				'title' => __( 'What does your store sell?', 'woocommerce' ),
				'desc'  => __( 'e.g. organic fruit and vegetables', 'woocommerce' ),
				'css'   => 'min-width:300px;',
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
					'informal'     => __( 'Informal', 'woocommerce' ),
					'humorous'     => __( 'Humorous', 'woocommerce' ),
					'neutral'      => __( 'Neutral', 'woocommerce' ),
					'youthful'     => __( 'Youthful', 'woocommerce' ),
					'formal'       => __( 'Formal', 'woocommerce' ),
					'motivational' => __( 'Motivational', 'woocommerce' ),
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
					'modern'    => __( 'Modern', 'woocommerce' ),
					'bold'      => __( 'Bold', 'woocommerce' ),
					'classic'   => __( 'Classic', 'woocommerce' ),
					'energetic' => __( 'Energetic', 'woocommerce' ),
					'retro'     => __( 'Retro', 'woocommerce' ),
					'warm'      => __( 'Warm', 'woocommerce' ),
				),
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'ai_store_style',
			),
		);
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $settings_tabs Existing pages.
	 * @return array|mixed
	 */
	public function add_woo_ai_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->id ] = __( 'AI', 'woocommerce' );

		return $settings_tabs;
	}

}
