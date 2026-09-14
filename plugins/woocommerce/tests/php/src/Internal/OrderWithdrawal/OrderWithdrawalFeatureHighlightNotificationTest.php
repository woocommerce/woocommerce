<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderWithdrawal;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFeatureHighlightNotification;
use WC_Unit_Test_Case;

/**
 * Tests for the order withdrawal inbox notification.
 */
class OrderWithdrawalFeatureHighlightNotificationTest extends WC_Unit_Test_Case {

	private const FEATURE_OPTION              = 'woocommerce_feature_order_withdrawal_enabled';
	private const COMING_SOON_OPTION          = 'woocommerce_coming_soon';
	private const ALLOWED_COUNTRIES_OPTION    = 'woocommerce_allowed_countries';
	private const ALL_EXCEPT_COUNTRIES_OPTION = 'woocommerce_all_except_countries';
	private const SPECIFIC_COUNTRIES_OPTION   = 'woocommerce_specific_allowed_countries';
	private const ENDPOINT_OPTION             = 'woocommerce_myaccount_order_withdrawal_endpoint';
	private const MISSING_OPTION_MARK         = '__woocommerce_order_withdrawal_missing_option__';

	private const OPTION_NAMES = array(
		self::FEATURE_OPTION,
		self::COMING_SOON_OPTION,
		self::ALLOWED_COUNTRIES_OPTION,
		self::ALL_EXCEPT_COUNTRIES_OPTION,
		self::SPECIFIC_COUNTRIES_OPTION,
		self::ENDPOINT_OPTION,
		OrderWithdrawalFeatureHighlightNotification::CREATED_OPTION,
		OrderWithdrawalFeatureHighlightNotification::ENABLED_CREATED_OPTION,
	);

	/**
	 * The System Under Test.
	 *
	 * @var OrderWithdrawalFeatureHighlightNotification
	 */
	private $sut;

	/**
	 * Original option values.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_options = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new OrderWithdrawalFeatureHighlightNotification();
		$this->store_original_options();
		$this->delete_notification_state();
		$this->set_live_eu_store_defaults();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->delete_notification_state();
		$this->restore_original_options();

		parent::tearDown();
	}

	/**
	 * @testdox Should add the notification for existing live stores.
	 */
	public function test_possibly_add_note_adds_notification_for_existing_live_stores(): void {
		$this->sut->possibly_add_note();

		$note_ids = $this->get_notification_note_ids();

		$this->assertCount(
			1,
			$note_ids,
			'An eligible existing live store should receive the notification.'
		);
		$this->assertSame(
			'yes',
			get_option( OrderWithdrawalFeatureHighlightNotification::CREATED_OPTION ),
			'Creating the note should persist the one-time creation flag.'
		);
	}

	/**
	 * @testdox Should add the notification when a coming soon store goes live.
	 */
	public function test_maybe_add_note_when_store_goes_live_adds_notification(): void {
		update_option( self::COMING_SOON_OPTION, 'no' );

		$this->sut->maybe_add_note_when_store_goes_live( 'yes', 'no' );

		$this->assertCount(
			1,
			$this->get_notification_note_ids(),
			'The notification should be created when coming soon changes to live.'
		);
	}

	/**
	 * @testdox Should only create the notification once.
	 */
	public function test_possibly_add_note_prevents_duplicates(): void {
		$this->sut->possibly_add_note();
		$this->sut->possibly_add_note();

		$this->assertCount(
			1,
			$this->get_notification_note_ids(),
			'Repeated attempts should not create duplicate notifications.'
		);
	}

	/**
	 * @testdox Should add a notification with the configured withdrawal page when the feature is enabled.
	 */
	public function test_possibly_add_enabled_note_adds_notification_with_configured_page_url(): void {
		update_option( self::ENDPOINT_OPTION, 'request-withdrawal' );

		$this->sut->possibly_add_enabled_note( 'order_withdrawal', true );

		$note = Notes::get_note_by_name( OrderWithdrawalFeatureHighlightNotification::ENABLED_NOTE_NAME );

		$this->assertInstanceOf( Note::class, $note, 'Enabling order withdrawal should create an inbox notification.' );
		$this->assertSame( 'The order withdrawal feature is enabled', $note->get_title(), 'The notification should have the expected title.' );
		$this->assertSame( 'This gives customers a simple, self-serve way to request a withdrawal during the applicable period, generally 14 days from delivery for goods or from when a service contract is agreed.', $note->get_content(), 'The notification should explain the enabled feature.' );

		$actions = $note->get_actions();

		$this->assertCount( 2, $actions, 'The notification should include view page and learn more actions.' );
		$this->assertSame( 'view-page', $actions[0]->name, 'The primary action should view the withdrawal page.' );
		$this->assertSame( wc_get_endpoint_url( 'request-withdrawal', '', wc_get_page_permalink( 'myaccount' ) ), $actions[0]->query, 'The view page action should use the configured endpoint.' );
		$this->assertSame( 'learn-more', $actions[1]->name, 'The secondary action should link to documentation.' );
		$this->assertSame( 'https://woocommerce.com/document/customer-order-withdrawal/', $actions[1]->query, 'The learn more action should use the order withdrawal documentation URL.' );
	}

	/**
	 * @testdox Should only create the enabled notification once.
	 */
	public function test_possibly_add_enabled_note_prevents_duplicates(): void {
		$this->sut->possibly_add_enabled_note( 'order_withdrawal', true );
		$this->sut->possibly_add_enabled_note( 'order_withdrawal', true );

		$this->assertCount(
			1,
			$this->get_enabled_notification_note_ids(),
			'Repeated enable events should not create duplicate notifications.'
		);
	}

	/**
	 * @testdox Should not add the enabled notification for unrelated or disabled features.
	 * @dataProvider provide_ignored_feature_changes
	 *
	 * @param string $feature_id Feature being toggled.
	 * @param bool   $enabled    Whether the feature was enabled.
	 */
	public function test_possibly_add_enabled_note_ignores_other_feature_changes( string $feature_id, bool $enabled ): void {
		$this->sut->possibly_add_enabled_note( $feature_id, $enabled );

		$this->assertFalse( Notes::get_note_by_name( OrderWithdrawalFeatureHighlightNotification::ENABLED_NOTE_NAME ), 'An irrelevant feature change should not create the notification.' );
	}

	/**
	 * Data provider for ignored feature changes.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function provide_ignored_feature_changes(): array {
		return array(
			'order withdrawal disabled' => array( 'order_withdrawal', false ),
			'unrelated feature enabled' => array( 'other_feature', true ),
		);
	}

	/**
	 * @testdox Should match stores selling to EU countries.
	 * @dataProvider provide_country_settings
	 *
	 * @param string   $allowed_countries Allowed countries setting.
	 * @param string[] $specific_countries Specific allowed countries.
	 * @param string[] $excluded_countries Excluded countries.
	 * @param bool     $expected           Expected applicability.
	 */
	public function test_possibly_add_note_checks_country_settings(
		string $allowed_countries,
		array $specific_countries,
		array $excluded_countries,
		bool $expected
	): void {
		update_option( self::ALLOWED_COUNTRIES_OPTION, $allowed_countries );
		update_option( self::SPECIFIC_COUNTRIES_OPTION, $specific_countries );
		update_option( self::ALL_EXCEPT_COUNTRIES_OPTION, $excluded_countries );

		$this->sut->possibly_add_note();

		$this->assertCount(
			$expected ? 1 : 0,
			$this->get_notification_note_ids(),
			'Applicability should match the configured selling countries.'
		);
	}

	/**
	 * @testdox Should identify stores that only sell to the US as not selling to EU countries.
	 */
	public function test_store_sells_to_eu_or_all_countries_returns_false_for_us_only_store(): void {
		update_option( self::ALLOWED_COUNTRIES_OPTION, 'specific' );
		update_option( self::SPECIFIC_COUNTRIES_OPTION, array( 'US' ) );

		$this->sut->possibly_add_note();

		$this->assertCount(
			0,
			$this->get_notification_note_ids(),
			'A store that only sells to the US should not receive the notification.'
		);
	}

	/**
	 * Data provider for {@see test_possibly_add_note_checks_country_settings()}.
	 *
	 * @return array<string,array{0:string,1:string[],2:string[],3:bool}>
	 */
	public function provide_country_settings(): array {
		return array(
			'all countries'             => array( 'all', array(), array(), true ),
			'specific EU country'       => array( 'specific', array( 'DE' ), array(), true ),
			'specific non-EU country'   => array( 'specific', array( 'US' ), array(), false ),
			'all except non-EU country' => array( 'all_except', array(), array( 'US' ), true ),
		);
	}

	/**
	 * Store original option values.
	 */
	private function store_original_options(): void {
		foreach ( self::OPTION_NAMES as $option ) {
			$this->original_options[ $option ] = get_option( $option, self::MISSING_OPTION_MARK );
		}
	}

	/**
	 * Restore original option values.
	 */
	private function restore_original_options(): void {
		foreach ( $this->original_options as $option => $value ) {
			$this->restore_option( $option, $value );
		}
	}

	/**
	 * Set baseline options for a live EU store with the feature disabled.
	 */
	private function set_live_eu_store_defaults(): void {
		update_option( self::COMING_SOON_OPTION, 'no' );
		update_option( self::FEATURE_OPTION, 'no' );
		update_option( self::ALLOWED_COUNTRIES_OPTION, 'specific' );
		update_option( self::SPECIFIC_COUNTRIES_OPTION, array( 'DE' ) );
		delete_option( self::ALL_EXCEPT_COUNTRIES_OPTION );
	}

	/**
	 * Delete notification state created by tests.
	 */
	private function delete_notification_state(): void {
		delete_option( OrderWithdrawalFeatureHighlightNotification::CREATED_OPTION );
		delete_option( OrderWithdrawalFeatureHighlightNotification::ENABLED_CREATED_OPTION );

		$note_ids = array_merge(
			$this->get_notification_note_ids(),
			$this->get_enabled_notification_note_ids()
		);

		foreach ( $note_ids as $note_id ) {
			$note = Notes::get_note( $note_id );

			if ( $note instanceof Note ) {
				$note->delete();
			}
		}
	}

	/**
	 * Get test notification note IDs.
	 *
	 * @return int[]
	 */
	private function get_notification_note_ids(): array {
		$data_store = Notes::load_data_store();

		return array_map(
			'absint',
			$data_store->get_notes_with_name( OrderWithdrawalFeatureHighlightNotification::NOTE_NAME )
		);
	}

	/**
	 * Get enabled notification note IDs.
	 *
	 * @return int[]
	 */
	private function get_enabled_notification_note_ids(): array {
		$data_store = Notes::load_data_store();

		return array_map(
			'absint',
			$data_store->get_notes_with_name( OrderWithdrawalFeatureHighlightNotification::ENABLED_NOTE_NAME )
		);
	}

	/**
	 * Restore an option to its original state.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Original value.
	 */
	private function restore_option( string $option, $value ): void {
		if ( self::MISSING_OPTION_MARK === $value ) {
			delete_option( $option );
			return;
		}

		update_option( $option, $value );
	}
}
