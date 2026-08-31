<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\CancellationSourceMiner;
use WC_Unit_Test_Case;

/**
 * Tests for CancellationSourceMiner.
 *
 * Exercises the real `woocommerce_bis_activity` table via $wpdb, since the miner's whole
 * job is a batched SQL lookup against it. The table is created in setUp() and dropped in
 * tearDown() because it belongs to the legacy extension schema, not Core's.
 */
class CancellationSourceMinerTests extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CancellationSourceMiner
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new CancellationSourceMiner();

		global $wpdb;
		$table = $wpdb->prefix . 'woocommerce_bis_activity';
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and cannot be a prepared placeholder.
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				notification_id BIGINT UNSIGNED NOT NULL,
				product_id BIGINT UNSIGNED NOT NULL,
				type VARCHAR(20) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				user_email VARCHAR(255) NOT NULL,
				object_id BIGINT UNSIGNED DEFAULT 0 NOT NULL,
				date INT UNSIGNED NOT NULL,
				note text NULL,
				PRIMARY KEY (id)
			) {$wpdb->get_charset_collate()}"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed DDL with no user input, test-only.
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * @after
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'woocommerce_bis_activity' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed DDL, test-only.
		parent::tearDown();
	}

	/**
	 * Insert one activity row for the given notification.
	 *
	 * @param int    $notification_id Legacy notification id.
	 * @param string $type            Activity type.
	 * @param int    $user_id         Activity actor user id.
	 * @param int    $date            Activity epoch date.
	 */
	private function insert_activity( int $notification_id, string $type, int $user_id, int $date ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_bis_activity',
			array(
				'notification_id' => $notification_id,
				'product_id'      => 1,
				'type'            => $type,
				'user_id'         => $user_id,
				'user_email'      => 'customer@example.com',
				'object_id'       => 0,
				'date'            => $date,
				'note'            => '',
			),
			array( '%d', '%d', '%s', '%d', '%s', '%d', '%d', '%s' )
		);
	}

	/**
	 * @testdox unsubscribed activity should always resolve to USER, even when its actor differs from the notification's own user.
	 */
	public function test_unsubscribed_resolves_to_user(): void {
		$this->insert_activity( 101, 'unsubscribed', 999, 1000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 101,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::USER, $result[101]['source'] );
		$this->assertSame( 1000, $result[101]['date'] );
	}

	/**
	 * @testdox deactivated activity with a differing non-zero user_id should resolve to ADMIN.
	 */
	public function test_deactivated_with_differing_user_resolves_to_admin(): void {
		$this->insert_activity( 102, 'deactivated', 7, 2000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 102,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::ADMIN, $result[102]['source'] );
	}

	/**
	 * @testdox deactivated activity with user_id 0 should resolve to SYSTEM.
	 */
	public function test_deactivated_with_zero_user_resolves_to_system(): void {
		$this->insert_activity( 103, 'deactivated', 0, 3000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 103,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::SYSTEM, $result[103]['source'] );
	}

	/**
	 * @testdox deactivated activity whose user_id matches the notification's own user should resolve to USER.
	 */
	public function test_deactivated_with_matching_user_resolves_to_user(): void {
		$this->insert_activity( 104, 'deactivated', 5, 4000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 104,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::USER, $result[104]['source'] );
	}

	/**
	 * @testdox no matching activity row should resolve to SYSTEM with a null date.
	 */
	public function test_no_activity_resolves_to_system(): void {
		$result = $this->sut->mine(
			array(
				array(
					'id'      => 105,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::SYSTEM, $result[105]['source'] );
		$this->assertNull( $result[105]['date'] );
	}

	/**
	 * @testdox mine() should take the latest cancelling event when a notification has more than one.
	 */
	public function test_takes_latest_event_per_notification(): void {
		$this->insert_activity( 106, 'deactivated', 0, 1000 );
		$this->insert_activity( 106, 'unsubscribed', 5, 2000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 106,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::USER, $result[106]['source'] );
		$this->assertSame( 2000, $result[106]['date'] );
	}

	/**
	 * @testdox mine() should ignore non-cancelling activity types such as subscribed.
	 */
	public function test_ignores_non_cancelling_activity_types(): void {
		$this->insert_activity( 107, 'subscribed', 5, 1000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 107,
					'user_id' => 5,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::SYSTEM, $result[107]['source'] );
		$this->assertNull( $result[107]['date'] );
	}

	/**
	 * @testdox mine() should resolve a batch of several notifications in one call.
	 */
	public function test_resolves_a_batch_of_notifications(): void {
		$this->insert_activity( 108, 'unsubscribed', 5, 1000 );
		$this->insert_activity( 109, 'deactivated', 0, 2000 );

		$result = $this->sut->mine(
			array(
				array(
					'id'      => 108,
					'user_id' => 5,
				),
				array(
					'id'      => 109,
					'user_id' => 6,
				),
				array(
					'id'      => 110,
					'user_id' => 7,
				),
			)
		);

		$this->assertSame( NotificationCancellationSource::USER, $result[108]['source'] );
		$this->assertSame( NotificationCancellationSource::SYSTEM, $result[109]['source'] );
		$this->assertSame( NotificationCancellationSource::SYSTEM, $result[110]['source'] );
		$this->assertNull( $result[110]['date'] );
	}

	/**
	 * @testdox mine() should return an empty array for an empty batch.
	 */
	public function test_returns_empty_array_for_empty_batch(): void {
		$this->assertSame( array(), $this->sut->mine( array() ) );
	}

	/**
	 * @testdox has_cancelling_event() should tell a real event apart from the seeded fallback.
	 */
	public function test_has_cancelling_event_distinguishes_the_seeded_fallback(): void {
		$this->assertFalse( CancellationSourceMiner::has_cancelling_event( null ) );
		$this->assertFalse(
			CancellationSourceMiner::has_cancelling_event(
				array(
					'source' => NotificationCancellationSource::SYSTEM,
					'date'   => null,
				)
			)
		);
		$this->assertTrue(
			CancellationSourceMiner::has_cancelling_event(
				array(
					'source' => NotificationCancellationSource::USER,
					'date'   => 1600009999,
				)
			)
		);
	}
}
