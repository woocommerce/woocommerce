<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates;

use WC_Unit_Test_Case;

/**
 * Tests that templates which print their own notices do so inside the shared notices wrapper.
 */
class NoticesWrapperTemplatesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Templates that print notices should render them inside the shared notices wrapper.
	 * @dataProvider templates_provider
	 *
	 * @param string $template Template path relative to the templates directory.
	 * @param array  $args     Arguments passed to the template.
	 */
	public function test_template_renders_notices_inside_wrapper( string $template, array $args ): void {
		wc_add_notice( 'Template notice.', 'error' );

		$html = wc_get_template_html( $template, $args );

		$wrapper_start = strpos( $html, '<div class="woocommerce-notices-wrapper">' );
		$notice_start  = strpos( $html, 'Template notice.' );
		$wrapper_end   = false === $wrapper_start ? false : strpos( $html, '</div>', $wrapper_start );

		$this->assertNotFalse( $wrapper_start, "{$template} should print the notices wrapper." );
		$this->assertNotFalse( $notice_start, "{$template} should print the queued notice." );
		$this->assertTrue(
			$wrapper_start < $notice_start && $notice_start < $wrapper_end,
			"{$template} should print the queued notice inside the notices wrapper."
		);
		$this->assertSame( 0, wc_notice_count(), "Rendering {$template} should clear the notice queue." );
	}

	/**
	 * Templates that print notices, with the arguments they need to render.
	 *
	 * @return array<string, array{string, array<string, mixed>}>
	 */
	public function templates_provider(): array {
		$user = wp_get_current_user();

		return array(
			'auth/form-login.php'                 => array(
				'auth/form-login.php',
				array(
					'app_name'     => 'Test App',
					'return_url'   => 'https://example.com/return',
					'redirect_url' => 'https://example.com/authorize',
				),
			),
			'auth/form-grant-access.php'          => array(
				'auth/form-grant-access.php',
				array(
					'app_name'     => 'Test App',
					'callback_url' => 'https://example.com/callback',
					'return_url'   => 'https://example.com/return',
					'scope'        => 'read',
					'permissions'  => array( 'View coupons' ),
					'granted_url'  => 'https://example.com/granted',
					'logout_url'   => 'https://example.com/logout',
					'user'         => $user,
				),
			),
			'myaccount/form-order-withdrawal.php' => array(
				'myaccount/form-order-withdrawal.php',
				array(
					'screen' => 'confirmation',
					'data'   => array( 'email' => 'customer@example.com' ),
				),
			),
		);
	}
}
