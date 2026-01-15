<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

use Automattic\WooCommerce\Blocks\DependencyDetection;
use WC_Unit_Test_Case;

/**
 * Unit tests for the DependencyDetection class.
 */
class DependencyDetectionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var DependencyDetection
	 */
	private DependencyDetection $sut;

	/**
	 * Reflection class for accessing private methods.
	 *
	 * @var \ReflectionClass
	 */
	private \ReflectionClass $reflection;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut        = new DependencyDetection();
		$this->reflection = new \ReflectionClass( DependencyDetection::class );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		// Clean up any registered test scripts.
		wp_deregister_script( 'test-plugin-script' );
		wp_deregister_script( 'test-plugin-with-deps' );
		wp_deregister_script( 'test-nested-dep' );

		parent::tearDown();
	}

	/**
	 * Helper to invoke a private method.
	 *
	 * @param string $method_name The method name.
	 * @param array  $args        The arguments to pass.
	 * @return mixed The method result.
	 */
	private function invoke_private_method( string $method_name, array $args = [] ) {
		$method = $this->reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->sut, $args );
	}

	/**
	 * @testdox is_woocommerce_script returns true for WooCommerce core client path.
	 */
	public function test_is_woocommerce_script_returns_true_for_client_path(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		$url           = $wc_plugin_url . 'client/blocks/index.js';
		$result        = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns true for WooCommerce core assets path.
	 */
	public function test_is_woocommerce_script_returns_true_for_assets_path(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		$url           = $wc_plugin_url . 'assets/js/frontend.js';
		$result        = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns true for WooCommerce core build path.
	 */
	public function test_is_woocommerce_script_returns_true_for_build_path(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		$url           = $wc_plugin_url . 'build/bundle.js';
		$result        = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns true for WooCommerce core vendor path.
	 */
	public function test_is_woocommerce_script_returns_true_for_vendor_path(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		$url           = $wc_plugin_url . 'vendor/some-lib.js';
		$result        = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertTrue( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns false for scripts in root directory (not in asset dirs).
	 */
	public function test_is_woocommerce_script_returns_false_for_root_scripts(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		$url           = $wc_plugin_url . 'readme.js';
		$result        = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns false for WooCommerce extensions.
	 */
	public function test_is_woocommerce_script_returns_false_for_subscriptions(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		// Replace /woocommerce/ with /woocommerce-subscriptions/ in the URL.
		$url    = str_replace( '/woocommerce/', '/woocommerce-subscriptions/', $wc_plugin_url ) . 'assets/js/index.js';
		$result = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns false for WooCommerce Payments.
	 */
	public function test_is_woocommerce_script_returns_false_for_payments(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		// Replace /woocommerce/ with /woocommerce-payments/ in the URL.
		$url    = str_replace( '/woocommerce/', '/woocommerce-payments/', $wc_plugin_url ) . 'build/index.js';
		$result = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox is_woocommerce_script returns false for third-party plugins.
	 */
	public function test_is_woocommerce_script_returns_false_for_third_party(): void {
		$wc_plugin_url = plugins_url( '/', WC_PLUGIN_FILE );
		// Replace /woocommerce/ with /my-plugin/ in the URL.
		$url    = str_replace( '/woocommerce/', '/my-plugin/', $wc_plugin_url ) . 'assets/js/index.js';
		$result = $this->invoke_private_method( 'is_woocommerce_script', array( $url ) );
		$this->assertFalse( $result );
	}

	/**
	 * @testdox get_all_dependencies returns empty array for empty input.
	 */
	public function test_get_all_dependencies_returns_empty_for_empty_input(): void {
		$result = $this->invoke_private_method( 'get_all_dependencies', array( array() ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox get_all_dependencies filters to WC handles only.
	 */
	public function test_get_all_dependencies_filters_to_wc_handles(): void {
		// Register a script with mixed dependencies.
		wp_register_script( 'test-nested-dep', '', array( 'wc-blocks-checkout', 'jquery' ), '1.0.0', true );

		$result = $this->invoke_private_method( 'get_all_dependencies', array( array( 'test-nested-dep' ) ) );

		// Should only include WC handles, not jquery.
		$this->assertContains( 'wc-blocks-checkout', $result );
		$this->assertNotContains( 'jquery', $result );
		$this->assertNotContains( 'test-nested-dep', $result );
	}

	/**
	 * @testdox get_all_dependencies handles circular dependencies.
	 */
	public function test_get_all_dependencies_handles_circular_deps(): void {
		// This test verifies the method doesn't infinite loop.
		// The WC handles filtering means we won't see the circular dep in output.
		$result = $this->invoke_private_method( 'get_all_dependencies', array( array( 'wc-blocks-checkout' ) ) );

		// Should complete without error and return the WC handle.
		$this->assertContains( 'wc-blocks-checkout', $result );
	}

	/**
	 * @testdox build_script_registry includes third-party plugin scripts.
	 */
	public function test_build_script_registry_includes_plugin_scripts(): void {
		// Register a third-party plugin script.
		wp_register_script(
			'test-plugin-script',
			'https://example.com/wp-content/plugins/my-plugin/script.js',
			array( 'wc-blocks-checkout' ),
			'1.0.0',
			true
		);

		$result = $this->invoke_private_method( 'build_script_registry', array() );

		// Find the script in the registry by checking for normalized URL.
		$found = false;
		foreach ( $result as $url => $info ) {
			if ( strpos( $url, 'my-plugin/script.js' ) !== false ) {
				$found = true;
				$this->assertSame( 'test-plugin-script', $info['handle'] );
				$this->assertContains( 'wc-blocks-checkout', $info['deps'] );
				break;
			}
		}
		$this->assertTrue( $found, 'Third-party plugin script should be in registry' );
	}

	/**
	 * @testdox build_script_registry excludes WooCommerce core scripts.
	 */
	public function test_build_script_registry_excludes_woocommerce_scripts(): void {
		$result = $this->invoke_private_method( 'build_script_registry', array() );

		// Check that no WooCommerce core scripts are in the registry.
		foreach ( $result as $url => $info ) {
			$this->assertStringNotContainsString(
				'/plugins/woocommerce/client/',
				$url,
				'WooCommerce client scripts should be excluded'
			);
			$this->assertStringNotContainsString(
				'/plugins/woocommerce/assets/',
				$url,
				'WooCommerce assets scripts should be excluded'
			);
			$this->assertStringNotContainsString(
				'/plugins/woocommerce/build/',
				$url,
				'WooCommerce build scripts should be excluded'
			);
		}
	}

	/**
	 * @testdox build_script_registry excludes WordPress core scripts.
	 */
	public function test_build_script_registry_excludes_wordpress_scripts(): void {
		$result = $this->invoke_private_method( 'build_script_registry', array() );

		// Check that no WordPress core scripts are in the registry.
		foreach ( $result as $url => $info ) {
			$this->assertStringNotContainsString(
				'/wp-includes/',
				$url,
				'WordPress wp-includes scripts should be excluded'
			);
			$this->assertStringNotContainsString(
				'/wp-admin/',
				$url,
				'WordPress wp-admin scripts should be excluded'
			);
		}
	}

	/**
	 * @testdox build_script_registry normalizes URLs by removing query strings.
	 */
	public function test_build_script_registry_normalizes_urls(): void {
		// Register a script (WordPress adds version query string automatically).
		wp_register_script(
			'test-plugin-script',
			'https://example.com/wp-content/plugins/my-plugin/script.js',
			array(),
			'1.0.0',
			true
		);

		$result = $this->invoke_private_method( 'build_script_registry', array() );

		// Check that URLs don't have query strings.
		foreach ( $result as $url => $info ) {
			$this->assertStringNotContainsString(
				'?',
				$url,
				'Registry URLs should not contain query strings'
			);
		}
	}

	/**
	 * @testdox output_early_proxy_setup outputs nothing when no tracked blocks are present.
	 */
	public function test_output_early_proxy_setup_outputs_nothing_without_tracked_blocks(): void {
		// Create a post without any WooCommerce blocks.
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->',
			)
		);
		$this->go_to( get_permalink( $post_id ) );

		ob_start();
		$this->sut->output_early_proxy_setup();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @testdox output_script_registry outputs nothing when proxy was not output.
	 */
	public function test_output_script_registry_outputs_nothing_without_proxy(): void {
		// Don't call output_early_proxy_setup first.
		ob_start();
		$this->sut->output_script_registry();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}
}
