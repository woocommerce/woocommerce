<?php

/**
 * Settings API: WP-Admin Helper Tests
 * Tests the helper class that makes settings (currently present in wp-admin)
 * avaiable to the REST API.
 *
 * @package WooCommerce\Tests\Settings
 * @backupGlobals disabled
 * @since 3.0.0
 */
class WC_Tests_WC_Settings_Products extends WC_Unit_Test_Case{
	/**
	 * @var WC_Settings_Page $page
	 */
	protected $page;

	/**
	 * Initialize a WC_Settings_Page for testing
	 */
	public function setUp() {
		parent::setUp();

		$mock_page = $this->getMockBuilder( 'WC_Settings_Products' )->getMock();

		$mock_page
			->expects( $this->any() )
			->method( 'get_id' )
			->will( $this->returnValue( 'products' ) );

		$mock_page
			->expects( $this->any() )
			->method( 'get_label' )
			->will( $this->returnValue( 'Products' ) );

		$this->page = $mock_page;
	}
	/**
	 * @since 3.0.0
	 * @covers WC_Register_WP_Admin_Settings::__construct
	 */
	public function test_constructor(){
		$settings = new WC_Settings_Products();
		$this->assertEquals( has_filter( 'woocommerce_settings_tabs_array', array( $settings, 'add_settings_page' ) ), 20 );
		$this->assertEquals( has_action( 'woocommerce_settings_' . $settings->get_id(), array( $settings, 'output' ) ), true );
		$this->assertEquals( has_action( 'woocommerce_settings_save_' . $settings->get_id(), array( $settings, 'save') ), true );
		$this->assertEquals( has_action( 'woocommerce_sections_' . $settings->get_id(), array( $settings, 'output_sections') ), true );
	}
	/**
	 * @since 3.0.0
	 * @covers WC_Settings_Products::get_sections
	 */
	public function test_get_sections(){
		$settings = new WC_Settings_Products();
		$existing = array(
			''          	=> __( 'General', 'woocommerce' ),
			'display'       => __( 'Display', 'woocommerce' ),
			'inventory' 	=> __( 'Inventory', 'woocommerce' ),
			'downloadable' 	=> __( 'Downloadable products', 'woocommerce' ),
		);
		$initial  = array( $existing );
		$expected = $existing;
		$actual = $settings->get_sections( $initial );
		$this->assertEquals( $expected, $actual );
	}
	/**
	 * @since 3.0.0
	 * @covers WC_Settings_Products::output
	 */
	public function test_output(){
		$settings = new WC_Settings_Products();
		//$current_section = 'display';
		
		$current_section = array('display', '', 'inventory', 'downloadable');
		
		for( $i = 0; $i < count($current_section); $i++){
			$setting = $settings->get_settings( $current_section[$i] );
			foreach ( $setting as $value ) {
				$actual = array();
				switch ( $value['type'] ) {

					// Section Titles
					case 'title':
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc'] 		= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						break;

					// Section Ends
					case 'sectionend':
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						break;

					// Standard text inputs and subtypes like 'number'
					case 'text':
						if (isset($value['title'])) {
							$actual['title'] 	= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc'] 	= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 		= $value['id'];
						}
						if (isset($value['type'])) {
							$actual['type'] 	= $value['type'];
						}
						if (isset($value['default'])) {
							$actual['default'] 	= $value['default'];
						}
						if (isset($value['css'])) {
							$actual['css'] 		= $value['css'];
						}
						if (isset($value['autoload'])) {
							$actual['autoload'] = $value['autoload'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip'] = $value['desc_tip'];
						}
						if (isset($value['class'])) {
							$actual['class'] 	= $value['class'];
						}
						break;
					case 'email':
						break;
					case 'number':
						if (isset($value['title'])) {
							$actual['title'] 				= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc'] 				= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 					= $value['id'];
						}
						if (isset($value['type'])) {
							$actual['type'] 				= $value['type'];
						}
						if (isset($value['custom_attributes'])) {
							$actual['custom_attributes'] 	= $value['custom_attributes'];
						}
						if (isset($value['css'])) {
							$actual['css'] 					= $value['css'];
						}
						if (isset($value['default'])) {
							$actual['default'] 				= $value['default'];
						}
						if (isset($value['autoload'])) {
							$actual['autoload'] 			= $value['autoload'];
						}
						if (isset($value['class'])) {
							$actual['class'] 				= $value['class'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip'] 			= $value['desc_tip'];
						}
						break;
					case 'password' :
						if (isset($value['id']) && isset($value['default'] )) {
							$actual['option_value'] = self::get_option( $value['id'], $value['default'] );
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						if (isset($value['css'])) {
							$actual['css'] 			= $value['css'];
						}
						if (isset($value['class'])) {
							$actual['class'] 		= $value['class'];	
						}
						if (isset($value['placeholder'])) {
							$actual['placeholder'] 	= $value['placeholder'];	
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						break;

					// Color picker.
					case 'color' :
						if (isset($value['id']) && isset($value['default'])) {
							$actual['option_value'] = self::get_option( $value['id'], $value['default'] );
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						if (isset($value['css'])) {
							$actual['css'] 			= $value['css'];
						}
						if (isset($value['class'])) {
							$actual['class'] 		= $value['class'];
						}
						if (isset($value['placeholder'])) {
							$actual['placeholder'] 	= $value['placeholder'];
						}
						break;

					// Textarea
					case 'textarea':
						if (isset($value['id']) && isset($value['default'])) {
							$actual['option_value'] = self::get_option( $value['id'], $value['default'] );
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						if (isset($value['css'])) {
							$actual['css'] 			= $value['css'];
						}
						if (isset($value['class'])) {
							$actual['class'] 		= $value['class'];
						}
						if (isset($value['placeholder'])) {
							$actual['placeholder'] 	= $value['placeholder'];
						}
						if (isset($value['type'])) {
							$actual ['type'] 		= $value['type'];
						}
						break;

					// Select boxes
					case 'select' :
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc'] 		= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['class'])) {
							$actual['class'] 		= $value['class'];
						}
						if (isset($value['css'])) {
							$actual['css'] 			= $value['css'];
						}
						if (isset($value['default'])) {
							$actual['default'] 		= $value['default'];
						}
						if (isset($value['type'])) {
							$actual['type']			= $value['type'];
						}
						if (isset($value['options'])) {
							$actual['options'] 		= $value['options'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip'] 	= $value['desc_tip'];
						}
						if (isset($value['autoload'])) {
							$actual['autoload'] 	= $value['autoload'];
						}
						break;

					//Multiselect boxes
					case 'multiselect' :
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc'] 		= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['class'])) {
							$actual['class'] 		= $value['class'];
						}
						if (isset($value['css'])) {
							$actual['css'] 			= $value['css'];
						}
						if (isset($value['default'])) {
							$actual['default'] 		= $value['default'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						if (isset($value['options'])) {
							$actual['options'] 		= $value['options'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip'] 	= $value['desc_tip'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						break;

					// Radio inputs
					case 'radio' :
						if (isset($value['id']) && isset($value['default'])) {
							$actual['option_value'] = self::get_option( $value['id'], $value['default'] );
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						break;

					// Checkbox input
					case 'checkbox' :
						if (isset($value['desc'])) {
							$actual['desc'] 		= $value['desc'];
						}
						if (isset($value['id'])) {
							$actual['id'] 			= $value['id'];
						}
						if (isset($value['default'])) {
							$actual['default'] 		= $value['default'];
						}
						if (isset($value['type'])) {
							$actual['type'] 		= $value['type'];
						}
						if (isset($value['checkboxgroup'])) {
							$actual['checkboxgroup']= $value['checkboxgroup'];
						}
						if (isset($value['title'])) {
							$actual['title'] 		= $value['title'];
						}
						if (isset($value['show_if_checked'])) {
							$actual['show_if_checked'] = $value['show_if_checked'];
						}
						if (isset($value['autoload'])) {
							$actual['autoload']		= $value['autoload'];
						}
						if (isset($value['class'])) {
							$actual['class']		= $value['class'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip']		= $value['desc_tip'];
						}
						break;

					// Image width settings
					case 'image_width' :
						if (isset($value['id'])) {
							$actual['id']     		= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title']     	= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc']     	= $value['desc'];
						}
						if (isset($value['css'])) {
							$actual['css']     		= $value['css'];
						}
						if (isset($value['default'])) {
							$actual['default']		= $value['default'];
						}
						if (isset($value['desc'])) {
							$actual['desc']			= $value['desc'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip']		= $value['desc_tip'];
						}
						if (isset($value['type'])) {
							$actual['type']			= $value['type'];
						}
						break;

					// Single page selects
					case 'single_select_page' :
						if (isset($value['id'])) {
							$actual['id']			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title']		= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc']			= $value['desc'];
						}
						if (isset($value['default'])) {
							$actual['default']		= $value['default'];
						}
						if (isset($value['class'])) {
							$actual['class']		= $value['class'];
						}
						if (isset($value['css'])) {
							$actual['css']			= $value['css'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip']		= $value['desc_tip'];
						}
						if (isset($value['type'])) {
							$actual['type']			= $value['type'];
						}
						break;

					// Single country selects
					case 'single_select_country' :
						if (isset($value['id'])) {
							$actual['id']			= $value['id'];
						}
						if (isset($value['title'])) {
							$actual['title']		= $value['title'];
						}
						if (isset($value['desc'])) {
							$actual['desc']			= $value['desc'];
						}
						if (isset($value['default'])) {
							$actual['default']		= $value['default'];
						}
						if (isset($value['class'])) {
							$actual['class']		= $value['class'];
						}
						if (isset($value['css'])) {
							$actual['css']			= $value['css'];
						}
						if (isset($value['desc_tip'])) {
							$actual['desc_tip']		= $value['desc_tip'];
						}
						if (isset($value['type'])) {
							$actual['type']			= $value['type'];
						}
						break;

					// Country multiselects
					case 'multi_select_countries' :
						if (isset($value['id'])) {
							$actual['id']			= $value['id'];
						}
						if (isset($value['options'])) {
							$actual['options']		= $value['options'];
						}
						if (isset($value['title'])) {
							$actual['title']		= $value['title'];
						}
						if (isset($value['type'])) {
							$actual['type']			= $value['type'];
						}
						break;

					// Default: run an action
					default:
						$actual = do_action( 'woocommerce_admin_field_' . $value['type'], $value );
						break;
				}
				$this->assertEquals($value, $actual);
			}
		}
	}
}