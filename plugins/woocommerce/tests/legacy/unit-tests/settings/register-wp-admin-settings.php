<?php
/**
 * WC_Tests_Register_WP_Admin_Settings class file.
 *
 * @package @package WooCommerce\Tests\Settings
 */

/**
 * Settings API: WP-Admin Helper Tests
 * Tests the helper class that makes settings (currently present in wp-admin)
 * available to the REST API.
 *
 * @package WooCommerce\Tests\Settings
 * @since 3.0.0
 */
class WC_Tests_Register_WP_Admin_Settings extends WC_Unit_Test_Case {

	/**
	 * @var WC_Settings_Page $page
	 */
	protected $page;

	/**
	 * Initialize a WC_Settings_Page for testing
	 */
	public function setUp() {
		parent::setUp();

		$mock_page = $this->getMockBuilder( 'WC_Settings_General' )->getMock();

		$mock_page
			->expects( $this->any() )
			->method( 'get_id' )
			->will( $this->returnValue( 'page-id' ) );

		$mock_page
			->expects( $this->any() )
			->method( 'get_label' )
			->will( $this->returnValue( 'Page Label' ) );

		$this->page = $mock_page;
	}

	/**
	 * @since 3.0.0
	 * @covers WC_Register_WP_Admin_Settings::__construct
	 */
	public function test_constructor() {
		$settings = new WC_Register_WP_Admin_Settings( $this->page, 'page' );

		$this->assertEquals( has_filter( 'woocommerce_settings_groups', array( $settings, 'register_page_group' ) ), 10 );
		$this->assertEquals( has_filter( 'woocommerce_settings-' . $this->page->get_id(), array( $settings, 'register_page_settings' ) ), 10 );
	}

	/**
	 * @since 3.0.0
	 * @covers WC_Register_WP_Admin_Settings::register_page_group
	 */
	public function test_register_group() {
		$settings = new WC_Register_WP_Admin_Settings( $this->page, 'page' );

		$existing = array(
			'id'    => 'existing-id',
			'label' => 'Existing Group',
		);
		$initial  = array( $existing );
		$expected = array(
			$existing,
			array(
				'id'    => $this->page->get_id(),
				'label' => $this->page->get_label(),
			),
		);
		$actual   = $settings->register_page_group( $initial );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 3.0.0
	 */
	public function register_setting_provider() {
		return array(
			// No "id" case.
			array(
				array(
					'type'       => 'some-type-with-no-id',
					'option_key' => '',
				),
				false,
			),
			// All optional properties except 'desc_tip'.
			array(
				array(
					'id'         => 'setting-id',
					'type'       => 'select',
					'title'      => 'Setting Name',
					'desc'       => 'Setting Description',
					'default'    => 'one',
					'options'    => array( 'one', 'two' ),
					'option_key' => '',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'default'     => 'one',
					'options'     => array( 'one', 'two' ),
					'option_key'  => '',
				),
			),
			// Boolean 'desc_tip' defaulting to 'desc' value.
			array(
				array(
					'id'         => 'setting-id',
					'type'       => 'select',
					'title'      => 'Setting Name',
					'desc'       => 'Setting Description',
					'desc_tip'   => true,
					'option_key' => '',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'tip'         => 'Setting Description',
					'option_key'  => '',
				),
			),
			// String 'desc_tip'.
			array(
				array(
					'id'         => 'setting-id',
					'type'       => 'select',
					'title'      => 'Setting Name',
					'desc'       => 'Setting Description',
					'desc_tip'   => 'Setting Tip',
					'option_key' => '',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => 'Setting Name',
					'description' => 'Setting Description',
					'tip'         => 'Setting Tip',
					'option_key'  => '',
				),
			),
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// Empty 'title' and empty 'desc'.
			array(
				array(
					'id'         => 'setting-id',
					'type'       => 'select',
					'option_key' => '',
				),
				array(
					'id'          => 'setting-id',
					'type'        => 'select',
					'label'       => '',
					'description' => '',
					'option_key'  => '',
				),
			),
		);
	}

	/**
	 * @since 3.0.0
	 * @dataProvider register_setting_provider
	 * @covers WC_Register_WP_Admin_Settings::register_setting
	 * @param array $input Array of settings.
	 * @param bool  $expected Expected result of the setting registering operation.
	 */
	public function test_register_setting( $input, $expected ) {
		$settings = new WC_Register_WP_Admin_Settings( $this->page, 'page' );

		$actual = $settings->register_setting( $input );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 3.0.0
	 * @covers WC_Register_WP_Admin_Settings::register_page_settings
	 */
	public function test_register_settings_default_section_no_settings() {
		$this->page
			->expects( $this->any() )
			->method( 'get_sections' )
			->will( $this->returnValue( array() ) );

		$this->page
			->expects( $this->once() )
			->method( 'get_settings' )
			->with( $this->equalTo( '' ) )
			->will( $this->returnValue( array() ) );

		$settings = new WC_Register_WP_Admin_Settings( $this->page, 'page' );

		$expected = array();
		$actual   = $settings->register_page_settings( array() );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @since 3.0.0
	 * @covers WC_Register_WP_Admin_Settings::register_page_settings
	 */
	public function test_register_settings_default_section_with_settings() {
		$this->page
			->expects( $this->any() )
			->method( 'get_sections' )
			->will( $this->returnValue( array() ) );

		$settings = array(
			array(
				'id'         => 'setting-1',
				'type'       => 'text',
				'option_key' => '',
			),
			array(
				'type'       => 'no-id',
				'option_key' => '',
			),
			array(
				'id'         => 'setting-2',
				'type'       => 'textarea',
				'option_key' => '',
			),
		);

		$this->page
			->expects( $this->any() )
			->method( 'get_settings' )
			->will( $this->returnValue( $settings ) );

		$settings = new WC_Register_WP_Admin_Settings( $this->page, 'page' );

		$expected = array(
			array(
				'id'          => 'setting-1',
				'type'        => 'text',
				'label'       => '',
				'description' => '',
				'option_key'  => 'setting-1',
			),
			array(
				'id'          => 'setting-2',
				'type'        => 'textarea',
				'label'       => '',
				'description' => '',
				'option_key'  => 'setting-2',
			),
		);
		$actual   = $settings->register_page_settings( array() );

		$this->assertEquals( $expected, $actual );
	}
}
