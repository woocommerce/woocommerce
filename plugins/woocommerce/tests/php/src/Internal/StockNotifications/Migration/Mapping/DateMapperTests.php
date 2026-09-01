<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\DateMapper;
use WC_Unit_Test_Case;

/**
 * Tests for DateMapper.
 */
class DateMapperTests extends WC_Unit_Test_Case {

	/**
	 * Migration run timestamp shared by every test: 2024-01-01 00:00:00 UTC.
	 *
	 * @var int
	 */
	private const MIGRATION_TIMESTAMP = 1704067200;

	/**
	 * The System Under Test.
	 *
	 * @var DateMapper
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DateMapper( self::MIGRATION_TIMESTAMP );
	}

	/**
	 * @testdox date_created_gmt should use create_date when it is plausible.
	 */
	public function test_date_created_gmt_uses_plausible_create_date(): void {
		$create_date = self::MIGRATION_TIMESTAMP - DAY_IN_SECONDS;

		$result = $this->sut->date_created_gmt( array( 'create_date' => $create_date ) );

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $create_date ), $result );
	}

	/**
	 * @testdox date_created_gmt should fall back to subscribe_date when create_date is corrupt.
	 * @dataProvider provider_corrupt_create_dates
	 *
	 * @param int $corrupt_create_date Test case value.
	 */
	public function test_date_created_gmt_falls_back_to_subscribe_date_when_corrupt( int $corrupt_create_date ): void {
		$subscribe_date = self::MIGRATION_TIMESTAMP - 1000;

		$result = $this->sut->date_created_gmt(
			array(
				'create_date'    => $corrupt_create_date,
				'subscribe_date' => $subscribe_date,
			)
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $subscribe_date ), $result );
	}

	/**
	 * @testdox date_created_gmt should fall back to the migration timestamp when both create_date and subscribe_date are unusable.
	 * @dataProvider provider_corrupt_create_dates
	 *
	 * @param int $corrupt_create_date Test case value.
	 */
	public function test_date_created_gmt_falls_back_to_migration_timestamp( int $corrupt_create_date ): void {
		$result = $this->sut->date_created_gmt(
			array(
				'create_date'    => $corrupt_create_date,
				'subscribe_date' => 0,
			)
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $result );
	}

	/**
	 * Corrupt/rejected create_date values: 0, before the 2015 floor, and past now+1day.
	 *
	 * @return array
	 */
	public function provider_corrupt_create_dates(): array {
		return array(
			'zero'                     => array( 0 ),
			'before 2015 floor'        => array( 1420070400 - 1 ),
			'past now + 1 day ceiling' => array( self::MIGRATION_TIMESTAMP + DAY_IN_SECONDS + 1 ),
		);
	}

	/**
	 * @testdox date_created_gmt should accept create_date exactly on the plausibility boundaries.
	 * @dataProvider provider_boundary_create_dates
	 *
	 * @param int $boundary_create_date Test case value.
	 */
	public function test_date_created_gmt_accepts_boundary_values( int $boundary_create_date ): void {
		$result = $this->sut->date_created_gmt( array( 'create_date' => $boundary_create_date ) );

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $boundary_create_date ), $result );
	}

	/**
	 * Boundary values that must still be accepted: exactly the 2015 floor, and exactly now+1day.
	 *
	 * @return array
	 */
	public function provider_boundary_create_dates(): array {
		return array(
			'exactly the 2015 floor' => array( 1420070400 ),
			'exactly now + 1 day'    => array( self::MIGRATION_TIMESTAMP + DAY_IN_SECONDS ),
		);
	}

	/**
	 * @testdox date_modified_gmt should always be the migration timestamp.
	 */
	public function test_date_modified_gmt(): void {
		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $this->sut->date_modified_gmt() );
	}

	/**
	 * @testdox date_confirmed_gmt should use subscribe_date when it is set.
	 */
	public function test_date_confirmed_gmt_uses_subscribe_date(): void {
		$subscribe_date = self::MIGRATION_TIMESTAMP - 500;

		$result = $this->sut->date_confirmed_gmt(
			array(
				'subscribe_date' => $subscribe_date,
				'create_date'    => self::MIGRATION_TIMESTAMP - 9000,
			),
			NotificationStatus::ACTIVE
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $subscribe_date ), $result );
	}

	/**
	 * @testdox date_confirmed_gmt should be null for a pending row, which was never confirmed.
	 */
	public function test_date_confirmed_gmt_is_null_for_pending(): void {
		$result = $this->sut->date_confirmed_gmt(
			array(
				'subscribe_date' => self::MIGRATION_TIMESTAMP - 500,
				'create_date'    => self::MIGRATION_TIMESTAMP - 9000,
			),
			NotificationStatus::PENDING
		);

		$this->assertNull( $result );
	}

	/**
	 * @testdox date_confirmed_gmt should fall back to create_date when subscribe_date is 0.
	 */
	public function test_date_confirmed_gmt_falls_back_to_create_date(): void {
		$create_date = self::MIGRATION_TIMESTAMP - 9000;

		$result = $this->sut->date_confirmed_gmt(
			array(
				'subscribe_date' => 0,
				'create_date'    => $create_date,
			),
			NotificationStatus::ACTIVE
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $create_date ), $result );
	}

	/**
	 * @testdox date_last_attempt_gmt should be null when last_notified_date is 0.
	 */
	public function test_date_last_attempt_gmt_is_null_when_zero(): void {
		$this->assertNull( $this->sut->date_last_attempt_gmt( array( 'last_notified_date' => 0 ) ) );
	}

	/**
	 * @testdox date_last_attempt_gmt should format last_notified_date when set.
	 */
	public function test_date_last_attempt_gmt_formats_when_set(): void {
		$last_notified_date = self::MIGRATION_TIMESTAMP - 200;

		$result = $this->sut->date_last_attempt_gmt( array( 'last_notified_date' => $last_notified_date ) );

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $last_notified_date ), $result );
	}

	/**
	 * @testdox date_notified_gmt should be null for every status except sent.
	 * @dataProvider provider_non_sent_statuses
	 *
	 * @param string $status Test case value.
	 */
	public function test_date_notified_gmt_is_null_for_non_sent_statuses( string $status ): void {
		$result = $this->sut->date_notified_gmt( array( 'last_notified_date' => self::MIGRATION_TIMESTAMP - 100 ), $status );

		$this->assertNull( $result );
	}

	/**
	 * Every NotificationStatus value except sent.
	 *
	 * @return array
	 */
	public function provider_non_sent_statuses(): array {
		return array(
			'pending'   => array( NotificationStatus::PENDING ),
			'active'    => array( NotificationStatus::ACTIVE ),
			'cancelled' => array( NotificationStatus::CANCELLED ),
		);
	}

	/**
	 * @testdox date_notified_gmt should be null for status sent when last_notified_date is 0.
	 */
	public function test_date_notified_gmt_is_null_when_sent_but_never_notified(): void {
		$result = $this->sut->date_notified_gmt( array( 'last_notified_date' => 0 ), NotificationStatus::SENT );

		$this->assertNull( $result );
	}

	/**
	 * @testdox date_notified_gmt should format last_notified_date for status sent.
	 */
	public function test_date_notified_gmt_formats_when_sent(): void {
		$last_notified_date = self::MIGRATION_TIMESTAMP - 100;

		$result = $this->sut->date_notified_gmt( array( 'last_notified_date' => $last_notified_date ), NotificationStatus::SENT );

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $last_notified_date ), $result );
	}

	/**
	 * @testdox date_cancelled_gmt should be null for every status except cancelled.
	 * @dataProvider provider_non_cancelled_statuses
	 *
	 * @param string $status Test case value.
	 */
	public function test_date_cancelled_gmt_is_null_for_non_cancelled_statuses( string $status ): void {
		$result = $this->sut->date_cancelled_gmt( array(), $status, self::MIGRATION_TIMESTAMP - 10 );

		$this->assertNull( $result );
	}

	/**
	 * Every NotificationStatus value except cancelled.
	 *
	 * @return array
	 */
	public function provider_non_cancelled_statuses(): array {
		return array(
			'pending' => array( NotificationStatus::PENDING ),
			'active'  => array( NotificationStatus::ACTIVE ),
			'sent'    => array( NotificationStatus::SENT ),
		);
	}

	/**
	 * @testdox date_cancelled_gmt should prefer the mined activity date when present.
	 */
	public function test_date_cancelled_gmt_prefers_activity_date(): void {
		$activity_date = self::MIGRATION_TIMESTAMP - 10;

		$result = $this->sut->date_cancelled_gmt(
			array(
				'last_notified_date' => self::MIGRATION_TIMESTAMP - 500,
				'create_date'        => self::MIGRATION_TIMESTAMP - 9000,
			),
			NotificationStatus::CANCELLED,
			$activity_date
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $activity_date ), $result );
	}

	/**
	 * @testdox date_cancelled_gmt should fall back to last_notified_date when there is no activity date.
	 */
	public function test_date_cancelled_gmt_falls_back_to_last_notified_date(): void {
		$last_notified_date = self::MIGRATION_TIMESTAMP - 500;

		$result = $this->sut->date_cancelled_gmt(
			array(
				'last_notified_date' => $last_notified_date,
				'create_date'        => self::MIGRATION_TIMESTAMP - 9000,
			),
			NotificationStatus::CANCELLED,
			null
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $last_notified_date ), $result );
	}

	/**
	 * @testdox date_cancelled_gmt should fall back to create_date when there is no activity date or last_notified_date.
	 */
	public function test_date_cancelled_gmt_falls_back_to_create_date(): void {
		$create_date = self::MIGRATION_TIMESTAMP - 9000;

		$result = $this->sut->date_cancelled_gmt(
			array(
				'last_notified_date' => 0,
				'create_date'        => $create_date,
			),
			NotificationStatus::CANCELLED,
			null
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', $create_date ), $result );
	}

	/**
	 * @testdox date_confirmed_gmt should not confirm a row in 1970 when both dates are unset.
	 */
	public function test_date_confirmed_gmt_rejects_a_zero_create_date(): void {
		$result = $this->sut->date_confirmed_gmt(
			array(
				'subscribe_date' => 0,
				'create_date'    => 0,
			),
			NotificationStatus::ACTIVE
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $result );
	}

	/**
	 * @testdox date_confirmed_gmt should reject a pre-2015 create_date the same way date_created_gmt does.
	 */
	public function test_date_confirmed_gmt_rejects_a_pre_2015_create_date(): void {
		$result = $this->sut->date_confirmed_gmt(
			array(
				'subscribe_date' => 0,
				'create_date'    => 1000000000,
			),
			NotificationStatus::ACTIVE
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $result );
	}

	/**
	 * @testdox date_cancelled_gmt should not cancel a row in 1970 when every date is unset.
	 */
	public function test_date_cancelled_gmt_rejects_a_zero_create_date(): void {
		$result = $this->sut->date_cancelled_gmt(
			array(
				'last_notified_date' => 0,
				'create_date'        => 0,
			),
			NotificationStatus::CANCELLED,
			null
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $result );
	}

	/**
	 * @testdox date_cancelled_gmt should reject a pre-2015 create_date the same way date_created_gmt does.
	 */
	public function test_date_cancelled_gmt_rejects_a_pre_2015_create_date(): void {
		$result = $this->sut->date_cancelled_gmt(
			array(
				'last_notified_date' => 0,
				'create_date'        => 1000000000,
			),
			NotificationStatus::CANCELLED,
			null
		);

		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::MIGRATION_TIMESTAMP ), $result );
	}
}
