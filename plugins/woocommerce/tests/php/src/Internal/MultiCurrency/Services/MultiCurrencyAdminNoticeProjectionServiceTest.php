<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAdminNoticeProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAdminNoticeProjectionService class.
 */
class MultiCurrencyAdminNoticeProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project admin notice hook manifest.
	 */
	public function test_projects_admin_notice_hook_manifest(): void {
		$manifest = MultiCurrencyAdminNoticeProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				array(
					'hook'     => 'admin_notices',
					'callback' => 'admin_notices',
					'priority' => 10,
				),
				array(
					'hook'     => 'wp_loaded',
					'callback' => 'hide_notices',
					'priority' => 10,
				),
			),
			$manifest['actions']
		);
	}

	/**
	 * @testdox Should project manual rate currency changed notice.
	 */
	public function test_projects_manual_rate_currency_changed_notice(): void {
		$notice = MultiCurrencyAdminNoticeProjectionService::get_manual_rate_notice(
			array( 'Canadian dollar', 'Euro', 'Monopoly money' )
		);

		$this->assertSame( 'currency_changed', $notice['key'] );
		$this->assertSame( 'notice notice-warning', $notice['class'] );
		$this->assertTrue( $notice['dismissible'] );
		$this->assertSame(
			'The store currency was recently changed. The following currencies are set to manual rates and may need updates: Canadian dollar, Euro, Monopoly money',
			$notice['message']
		);
		$this->assertNull( MultiCurrencyAdminNoticeProjectionService::get_manual_rate_notice( false ) );
		$this->assertNull( MultiCurrencyAdminNoticeProjectionService::get_manual_rate_notice( array() ) );
	}

	/**
	 * @testdox Should project notices only for users who can manage WooCommerce.
	 */
	public function test_projects_notices_only_for_users_who_can_manage_woocommerce(): void {
		$this->assertSame(
			array(),
			MultiCurrencyAdminNoticeProjectionService::get_notices_for_user(
				false,
				array( 'Canadian dollar' )
			)
		);

		$notices = MultiCurrencyAdminNoticeProjectionService::get_notices_for_user(
			true,
			array( 'Canadian dollar' )
		);

		$this->assertCount( 1, $notices );
		$this->assertSame( 'currency_changed', $notices[0]['key'] );
	}

	/**
	 * @testdox Should project notice markup with optional dismiss link.
	 */
	public function test_projects_notice_markup_with_optional_dismiss_link(): void {
		$notice = MultiCurrencyAdminNoticeProjectionService::get_manual_rate_notice(
			array( 'Canadian dollar' )
		);

		$markup = MultiCurrencyAdminNoticeProjectionService::get_notice_markup(
			$notice,
			'https://example.test/wp-admin/?wcpay-multi-currency-hide-notice=currency_changed'
		);

		$this->assertStringContainsString(
			'<div class="notice notice-warning" style="position:relative;">',
			$markup
		);
		$this->assertStringContainsString(
			'class="woocommerce-message-close notice-dismiss"',
			$markup
		);
		$this->assertStringContainsString(
			'style="position:relative;float:right;padding:9px 0 9px 9px;text-decoration:none;"',
			$markup
		);
		$this->assertStringContainsString(
			'<p>The store currency was recently changed. The following currencies are set to manual rates and may need updates: Canadian dollar</p>',
			$markup
		);

		$unsafe_markup = MultiCurrencyAdminNoticeProjectionService::get_notice_markup(
			array(
				'key'         => 'currency_changed',
				'class'       => 'notice notice-warning',
				'message'     => '<a href="https://example.test" target="_blank">Allowed</a><script>Blocked</script>',
				'dismissible' => false,
			)
		);

		$this->assertStringContainsString( '<a href="https://example.test" target="_blank">Allowed</a>', $unsafe_markup );
		$this->assertStringNotContainsString( '<script>', $unsafe_markup );
		$this->assertStringNotContainsString( 'notice-dismiss', $unsafe_markup );
	}

	/**
	 * @testdox Should project no-op hide intent when query arguments are missing.
	 */
	public function test_projects_noop_hide_intent_when_query_arguments_are_missing(): void {
		$this->assertSame(
			array(
				'should_hide'  => false,
				'option_name'  => null,
				'option_value' => null,
				'error'        => null,
			),
			MultiCurrencyAdminNoticeProjectionService::get_hide_notice_intent( array(), true, true )
		);
	}

	/**
	 * @testdox Should project hide intent errors for invalid requests.
	 */
	public function test_projects_hide_intent_errors_for_invalid_requests(): void {
		$query = array(
			'_wcpay_multi_currency_notice_nonce' => 'nonce',
			'wcpay-multi-currency-hide-notice'   => 'currency_changed',
		);

		$this->assertSame(
			array(
				'should_hide'  => false,
				'option_name'  => null,
				'option_value' => null,
				'error'        => 'invalid_nonce',
			),
			MultiCurrencyAdminNoticeProjectionService::get_hide_notice_intent( $query, false, true )
		);
		$this->assertSame(
			array(
				'should_hide'  => false,
				'option_name'  => null,
				'option_value' => null,
				'error'        => 'forbidden',
			),
			MultiCurrencyAdminNoticeProjectionService::get_hide_notice_intent( $query, true, false )
		);

		$query['wcpay-multi-currency-hide-notice'] = 'unknown';

		$this->assertSame(
			array(
				'should_hide'  => false,
				'option_name'  => null,
				'option_value' => null,
				'error'        => 'unsupported_notice',
			),
			MultiCurrencyAdminNoticeProjectionService::get_hide_notice_intent( $query, true, true )
		);
	}

	/**
	 * @testdox Should project hide intent for valid currency changed dismissal.
	 */
	public function test_projects_hide_intent_for_valid_currency_changed_dismissal(): void {
		$this->assertSame(
			array(
				'should_hide'  => true,
				'option_name'  => 'wcpay_multi_currency_show_store_currency_changed_notice',
				'option_value' => 'no',
				'error'        => null,
			),
			MultiCurrencyAdminNoticeProjectionService::get_hide_notice_intent(
				array(
					'_wcpay_multi_currency_notice_nonce' => 'nonce',
					'wcpay-multi-currency-hide-notice'   => 'currency_changed',
				),
				true,
				true
			)
		);
	}
}
