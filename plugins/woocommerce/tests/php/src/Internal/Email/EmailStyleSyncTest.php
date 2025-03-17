<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailStyleSync;
use Automattic\WooCommerce\Internal\Email\EmailColors;
use WC_Unit_Test_Case;

/**
 * EmailStyleSync test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\EmailStyleSync
 */
class EmailStyleSyncTest extends WC_Unit_Test_Case {
    /**
     * "System Under Test", an instance of the class to be tested.
     *
     * @var EmailStyleSync
     */
    private $sut;

    /**
     * Original option values to restore after tests.
     *
     * @var array
     */
    private $original_options;

    /**
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->sut = new EmailStyleSync();
        
        // Store original option values
        $this->original_options = array(
            EmailStyleSync::AUTO_SYNC_OPTION => get_option( EmailStyleSync::AUTO_SYNC_OPTION, false ),
            'woocommerce_email_base_color' => get_option( 'woocommerce_email_base_color', '' ),
            'woocommerce_email_background_color' => get_option( 'woocommerce_email_background_color', '' ),
            'woocommerce_email_body_background_color' => get_option( 'woocommerce_email_body_background_color', '' ),
            'woocommerce_email_text_color' => get_option( 'woocommerce_email_text_color', '' ),
            'woocommerce_email_footer_text_color' => get_option( 'woocommerce_email_footer_text_color', '' ),
        );
        
        // Ensure we have a clean state for each test
        delete_option( EmailStyleSync::AUTO_SYNC_OPTION );
    }

    /**
     * Tear down.
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Restore original option values
        foreach ( $this->original_options as $option_name => $option_value ) {
            if ( $option_value ) {
                update_option( $option_name, $option_value );
            } else {
                delete_option( $option_name );
            }
        }
    }

    /**
     * Test auto-sync is disabled by default.
     */
    public function test_auto_sync_disabled_by_default() {
        $this->assertFalse( $this->sut->is_auto_sync_enabled() );
    }

    /**
     * Test setting auto-sync option.
     */
    public function test_set_auto_sync() {
        $this->sut->set_auto_sync( false );
        $this->assertFalse( $this->sut->is_auto_sync_enabled() );
        
        $this->sut->set_auto_sync( true );
        $this->assertTrue( $this->sut->is_auto_sync_enabled() );
    }

    /**
     * Test sync doesn't run when auto-sync is disabled.
     */
    public function test_sync_doesnt_run_when_disabled() {
        $this->sut->set_auto_sync( false );

        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['update_email_colors'] )
            ->getMock();
            
        $mock->expects( $this->never() )
            ->method( 'update_email_colors' );
            
        $mock->set_auto_sync( false );
        $mock->sync_email_styles_with_theme();
    }

    /**
     * Test sync doesn't run when theme doesn't have theme.json.
     */
    public function test_sync_doesnt_run_without_theme_json() {
        add_filter( 'wp_theme_has_theme_json', array( $this, 'return_false' ) );

        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['update_email_colors'] )
            ->getMock();
            
        $mock->expects( $this->never() )
            ->method( 'update_email_colors' );
            
        $mock->sync_email_styles_with_theme();

        remove_filter( 'wp_theme_has_theme_json', array( $this, 'return_false' ) );
    }

    /**
     * Test update_email_colors updates options correctly.
     */
    public function test_update_email_colors() {
        // Create a reflection to access the private method
        $reflection = new \ReflectionClass( $this->sut );
        $method = $reflection->getMethod( 'update_email_colors' );
        $method->setAccessible( true );
        
        // Create a mock for get_theme_colors
        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['get_theme_colors'] )
            ->getMock();
            
        $test_colors = array(
            'base_color' => '#ff0000',
            'bg_color' => '#eeeeee',
            'body_bg_color' => '#ffffff',
            'body_text_color' => '#333333',
            'footer_text_color' => '#999999',
        );
        
        $mock->expects( $this->once() )
            ->method( 'get_theme_colors' )
            ->will( $this->returnValue( $test_colors ) );
            
        // Call the method directly using reflection
        $method->invoke( $mock );
        
        // Verify options were updated
        $this->assertEquals( '#ff0000', get_option( 'woocommerce_email_base_color' ) );
        $this->assertEquals( '#eeeeee', get_option( 'woocommerce_email_background_color' ) );
        $this->assertEquals( '#ffffff', get_option( 'woocommerce_email_body_background_color' ) );
        $this->assertEquals( '#333333', get_option( 'woocommerce_email_text_color' ) );
        $this->assertEquals( '#999999', get_option( 'woocommerce_email_footer_text_color' ) );
    }

    /**
     * Test get_theme_colors returns expected values.
     */
    public function test_get_theme_colors() {
        // Create a reflection to access the private method
        $reflection = new \ReflectionClass( $this->sut );
        $method = $reflection->getMethod( 'get_theme_colors' );
        $method->setAccessible( true );
        
        // Define test global styles
        $test_global_styles = [
            'elements' => [
                'button' => [
                    'color' => [
                        'background' => '#ff0000'
                    ]
                ],
                'caption' => [
                    'color' => [
                        'text' => '#999999'
                    ]
                ]
            ],
            'color' => [
                'background' => '#eeeeee',
                'text' => '#333333'
            ]
        ];
        
        // Call the method with override_styles parameter
        $colors = $method->invoke( $this->sut, $test_global_styles );
        
        // Verify the colors
        $this->assertEquals( '#ff0000', $colors['base_color'] );
        $this->assertEquals( '#eeeeee', $colors['bg_color'] );
        $this->assertEquals( '#eeeeee', $colors['body_bg_color'] );
        $this->assertEquals( '#333333', $colors['body_text_color'] );
        $this->assertEquals( '#999999', $colors['footer_text_color'] );
    }

    /**
     * Test maybe_sync_on_option_update triggers sync when auto-sync is enabled.
     */
    public function test_maybe_sync_on_option_update_when_enabled() {
        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['update_email_colors'] )
            ->getMock();
            
        $mock->expects( $this->once() )
            ->method( 'update_email_colors' );
            
        $mock->maybe_sync_on_option_update( 'no', 'yes', EmailStyleSync::AUTO_SYNC_OPTION );
    }

    /**
     * Test maybe_sync_on_option_update doesn't trigger sync when auto-sync is disabled.
     */
    public function test_maybe_sync_on_option_update_when_disabled() {
        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['update_email_colors'] )
            ->getMock();
            
        $mock->expects( $this->never() )
            ->method( 'update_email_colors' );
            
        $mock->maybe_sync_on_option_update( 'yes', 'no', EmailStyleSync::AUTO_SYNC_OPTION );
    }

    /**
     * Test maybe_sync_on_option_update doesn't trigger sync when auto-sync value doesn't change.
     */
    public function test_maybe_sync_on_option_update_when_unchanged() {
        $mock = $this->getMockBuilder( EmailStyleSync::class )
            ->setMethods( ['update_email_colors'] )
            ->getMock();
            
        $mock->expects( $this->never() )
            ->method( 'update_email_colors' );
            
        // Test when already enabled
        $mock->maybe_sync_on_option_update( 'yes', 'yes', EmailStyleSync::AUTO_SYNC_OPTION );
        
        // Test when already disabled
        $mock->maybe_sync_on_option_update( 'no', 'no', EmailStyleSync::AUTO_SYNC_OPTION );
    }

    /**
     * A hook for returning false in filters, safe to add and remove in tests.
     */
    public function return_false() {
        return false;
    }
}
