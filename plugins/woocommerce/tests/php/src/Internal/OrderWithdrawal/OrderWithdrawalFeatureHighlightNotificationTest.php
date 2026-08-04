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
	private const MISSING_OPTION_MARK         = '__woocommerce_order_withdrawal_missing_option__';

	private const OPTION_NAMES = array(
		self::FEATURE_OPTION,
		self::COMING_SOON_OPTION,
		self::ALLOWED_COUNTRIES_OPTION,
		self::ALL_EXCEPT_COUNTRIES_OPTION,
		self::SPECIFIC_COUNTRIES_OPTION,
		OrderWithdrawalFeatureHighlightNotification::CREATED_OPTION,
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
	 * @testdox Should prepare the settings and learn more actions.
	 */
	public function test_get_note_prepares_settings_and_learn_more_actions(): void {
		$note    = $this->sut->get_note();
		$actions = $note->get_actions();

		$this->assertSame(
			OrderWithdrawalFeatureHighlightNotification::NOTE_NAME,
			$note->get_name(),
			'The note should use the expected stable name.'
		);
		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_WARNING,
			$note->get_type(),
			'The note should be a warning because the feature relates to regulatory requirements.'
		);
		$this->assertCount(
			2,
			$actions,
			'The note should include the settings action and learn more action.'
		);
		$this->assertSame( 'review-feature-settings', $actions[0]->name, 'The settings action should be first.' );
		$this->assertSame(
			'Review feature settings',
			$actions[0]->label,
			'The first action should direct merchants to settings.'
		);
		$this->assertSame(
			admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ),
			$actions[0]->query,
			'The first action should link to advanced feature settings.'
		);
		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			$actions[0]->status,
			'The settings action should action the note.'
		);
		$this->assertSame( 'learn-more', $actions[1]->name, 'The second action should be learn more.' );
		$this->assertSame(
			'https://woocommerce.com/',
			$actions[1]->query,
			'The learn more action should link to WooCommerce.com for now.'
		);
		$this->assertSame(
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			$actions[1]->status,
			'The learn more action should not action the note.'
		);
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
	 * @testdox Should not add the notification for non-live transitions.
	 */
	public function test_maybe_add_note_when_store_goes_live_ignores_other_transitions(): void {
		$this->sut->maybe_add_note_when_store_goes_live( 'no', 'yes' );

		$this->assertCount(
			0,
			$this->get_notification_note_ids(),
			'Only the coming soon to live transition should create the notification.'
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
	 * @testdox Should not add the notification while the store is coming soon.
	 */
	public function test_possibly_add_note_does_not_add_notification_while_coming_soon(): void {
		update_option( self::COMING_SOON_OPTION, 'yes' );

		$this->sut->possibly_add_note();

		$this->assertCount(
			0,
			$this->get_notification_note_ids(),
			'Coming soon stores should not receive the notification.'
		);
	}

	/**
	 * @testdox Should not add the notification when order withdrawal is enabled.
	 */
	public function test_possibly_add_note_does_not_add_notification_when_feature_is_enabled(): void {
		update_option( self::FEATURE_OPTION, 'yes' );

		$this->sut->possibly_add_note();

		$this->assertCount(
			0,
			$this->get_notification_note_ids(),
			'Stores that already enabled order withdrawal should not receive the notification.'
		);
	}

	/**
	 * @testdox Should consider missing country settings as selling to all countries.
	 */
	public function test_is_applicable_defaults_missing_country_settings_to_all_countries(): void {
		delete_option( self::ALLOWED_COUNTRIES_OPTION );

		$this->assertTrue(
			$this->sut->is_applicable(),
			'Missing selling location settings should be treated as selling to all countries.'
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
	public function test_is_applicable_checks_country_settings(
		string $allowed_countries,
		array $specific_countries,
		array $excluded_countries,
		bool $expected
	): void {
		update_option( self::ALLOWED_COUNTRIES_OPTION, $allowed_countries );
		update_option( self::SPECIFIC_COUNTRIES_OPTION, $specific_countries );
		update_option( self::ALL_EXCEPT_COUNTRIES_OPTION, $excluded_countries );

		$this->assertSame(
			$expected,
			$this->sut->is_applicable(),
			'Applicability should match the configured selling countries.'
		);
	}

	/**
	 * Data provider for {@see test_is_applicable_checks_country_settings()}.
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
	 * @testdox Should not match stores that exclude every EU country.
	 */
	public function test_is_applicable_returns_false_when_all_eu_countries_are_excluded(): void {
		update_option( self::ALLOWED_COUNTRIES_OPTION, 'all_except' );
		update_option(
			self::ALL_EXCEPT_COUNTRIES_OPTION,
			WC()->countries->get_european_union_countries()
		);

		$this->assertFalse(
			$this->sut->is_applicable(),
			'Stores that do not sell to any EU countries should not receive the notification.'
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

		foreach ( $this->get_notification_note_ids() as $note_id ) {
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
