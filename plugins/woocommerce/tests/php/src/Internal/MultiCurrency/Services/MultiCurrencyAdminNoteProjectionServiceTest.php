<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAdminNoteProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAdminNoteProjectionService class.
 */
class MultiCurrencyAdminNoteProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project admin note hook manifest.
	 */
	public function test_projects_admin_note_hook_manifest(): void {
		$this->assertSame(
			array(
				'actions' => array(
					array(
						'hook'     => 'admin_init',
						'callback' => 'add_woo_admin_notes',
						'priority' => 10,
					),
				),
			),
			MultiCurrencyAdminNoteProjectionService::get_hook_manifest( true )
		);
		$this->assertSame(
			array( 'actions' => array() ),
			MultiCurrencyAdminNoteProjectionService::get_hook_manifest( false )
		);
	}

	/**
	 * @testdox Should project multi-currency availability note manifest.
	 */
	public function test_projects_multi_currency_availability_note_manifest(): void {
		$manifest  = MultiCurrencyAdminNoteProjectionService::get_note_manifest();
		$setup_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/settings#advanced' );

		$this->assertSame( 'wc-payments-notes-multi-currency-available', $manifest['name'] );
		$this->assertSame( 'Sell worldwide in multiple currencies', $manifest['title'] );
		$this->assertSame(
			'Boost your international sales by allowing your customers to shop and pay in their local currency.',
			$manifest['content']
		);
		$this->assertSame( array(), $manifest['content_data'] );
		$this->assertSame( 'info', $manifest['type'] );
		$this->assertSame( 'woocommerce-payments', $manifest['source'] );
		$this->assertSame(
			array(
				array(
					'name'    => 'wc-payments-notes-multi-currency-available',
					'label'   => 'Set up now',
					'query'   => $setup_url,
					'status'  => 'unactioned',
					'primary' => true,
				),
			),
			$manifest['actions']
		);
	}

	/**
	 * @testdox Should project the native multi-currency setup URL.
	 */
	public function test_projects_native_multi_currency_setup_url(): void {
		$manifest = MultiCurrencyAdminNoteProjectionService::get_note_manifest();
		$action   = $manifest['actions'][0];

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout', $action['query'] );
		$this->assertStringContainsString( 'path=/woopayments/settings', $action['query'] );
		$this->assertStringContainsString( '#advanced', $action['query'] );
		$this->assertStringNotContainsString( 'section=', $action['query'] );
	}

	/**
	 * @testdox Should project WC Admin note version support.
	 */
	public function test_projects_wc_admin_note_version_support(): void {
		$this->assertFalse( MultiCurrencyAdminNoteProjectionService::supports_wc_admin_notes( '' ) );
		$this->assertFalse( MultiCurrencyAdminNoteProjectionService::supports_wc_admin_notes( '4.3.9' ) );
		$this->assertTrue( MultiCurrencyAdminNoteProjectionService::supports_wc_admin_notes( '4.4.0' ) );
		$this->assertTrue( MultiCurrencyAdminNoteProjectionService::supports_wc_admin_notes( '11.0.0' ) );
	}

	/**
	 * @testdox Should project add note blockers.
	 */
	public function test_projects_add_note_blockers(): void {
		$this->assertSame(
			array(
				'should_add' => false,
				'note'       => null,
				'blockers'   => array( 'ajax_request' ),
			),
			MultiCurrencyAdminNoteProjectionService::get_add_note_manifest( true, '11.0.0', true, true )
		);
		$this->assertSame(
			array(
				'should_add' => false,
				'note'       => null,
				'blockers'   => array( 'unsupported_wc_version' ),
			),
			MultiCurrencyAdminNoteProjectionService::get_add_note_manifest( false, '4.3.9', true, true )
		);
		$this->assertSame(
			array(
				'should_add' => false,
				'note'       => null,
				'blockers'   => array( 'provider_not_connected' ),
			),
			MultiCurrencyAdminNoteProjectionService::get_add_note_manifest( false, '11.0.0', false, true )
		);
		$this->assertSame(
			array(
				'should_add' => false,
				'note'       => null,
				'blockers'   => array( 'note_cannot_be_added' ),
			),
			MultiCurrencyAdminNoteProjectionService::get_add_note_manifest( false, '11.0.0', true, false )
		);
	}

	/**
	 * @testdox Should project add note manifest when eligible.
	 */
	public function test_projects_add_note_manifest_when_eligible(): void {
		$manifest = MultiCurrencyAdminNoteProjectionService::get_add_note_manifest( false, '11.0.0', true, true );

		$this->assertTrue( $manifest['should_add'] );
		$this->assertSame( array(), $manifest['blockers'] );
		$this->assertSame( MultiCurrencyAdminNoteProjectionService::get_note_manifest(), $manifest['note'] );
	}

	/**
	 * @testdox Should project delete note manifest.
	 */
	public function test_projects_delete_note_manifest(): void {
		$this->assertSame(
			array(
				'should_delete' => false,
				'note_name'     => null,
				'blockers'      => array( 'unsupported_wc_version' ),
			),
			MultiCurrencyAdminNoteProjectionService::get_delete_note_manifest( '4.3.9' )
		);
		$this->assertSame(
			array(
				'should_delete' => true,
				'note_name'     => 'wc-payments-notes-multi-currency-available',
				'blockers'      => array(),
			),
			MultiCurrencyAdminNoteProjectionService::get_delete_note_manifest( '11.0.0' )
		);
	}
}
