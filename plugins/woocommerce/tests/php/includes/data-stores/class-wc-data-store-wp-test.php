<?php declare( strict_types = 1 );

/**
 * Class WC_Data_Store_WP_Test
 */
final class WC_Data_Store_WP_Test extends WC_Unit_Test_Case {

	/**
	 * Timestamp of 2026-07-19 00:00:00 -04:00, in America/New_York.
	 */
	private const NY_JUL_19_MIDNIGHT = 1784433600;

	/**
	 * Timestamp of 2026-07-20 00:00:00 -04:00, in America/New_York.
	 */
	private const NY_JUL_20_MIDNIGHT = 1784520000;

	/**
	 * Timestamp of 2026-07-21 00:00:00 -04:00, in America/New_York.
	 */
	private const NY_JUL_21_MIDNIGHT = 1784606400;

	/**
	 * Timestamp of 2026-07-26 00:00:00 -04:00, in America/New_York.
	 */
	private const NY_JUL_26_MIDNIGHT = 1785038400;

	/**
	 * Timestamp of 2026-11-01 00:00:00 -04:00, the last day of DST in America/New_York.
	 */
	private const NY_NOV_01_MIDNIGHT = 1793505600;

	/**
	 * Timestamp of 2026-11-02 00:00:00 -05:00, 25 hours after the start of 2026-11-01.
	 */
	private const NY_NOV_02_MIDNIGHT = 1793595600;

	/**
	 * Timestamp of 2026-03-08 00:00:00 -05:00, the day DST starts in America/New_York.
	 */
	private const NY_MAR_08_MIDNIGHT = 1772946000;

	/**
	 * Timestamp of 2026-03-09 00:00:00 -04:00, 23 hours after the start of 2026-03-08.
	 */
	private const NY_MAR_09_MIDNIGHT = 1773028800;

	/**
	 * Timestamp of the first instant of 2026-03-29 in Asia/Beirut. DST starts at 00:00 that day,
	 * so local midnight never happens and the day starts at 01:00:00 +03:00 instead.
	 */
	private const BEIRUT_MAR_29_START = 1774735200;

	/**
	 * Timestamp of 2026-03-30 00:00:00 +03:00, 23 hours after the start of 2026-03-29.
	 */
	private const BEIRUT_MAR_30_MIDNIGHT = 1774818000;

	/**
	 * The System Under Test.
	 *
	 * @var WC_Data_Store_WP
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Data_Store_WP();
	}

	/**
	 * Restore the default timezone settings.
	 */
	public function tearDown(): void {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 0 );

		parent::tearDown();
	}

	/**
	 * @return array
	 */
	public function provider_update_or_delete_post_meta(): array {
		return array(
			'empty string — key absent'  => array( static fn() => null, '', false, '' ),
			'empty string — key present' => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), '', true, '' ),
			'empty array — key absent'   => array( static fn() => null, array(), false, '' ),
			'empty array — key present'  => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), array(), true, '' ),
			'non-empty — key absent'     => array( static fn() => null, 'new-sku', true, 'new-sku' ),
			'non-empty — key present'    => array( static fn( $id ) => add_post_meta( $id, '_sku', 'old' ), 'new-sku', true, 'new-sku' ),
		);
	}

	/**
	 * @dataProvider provider_update_or_delete_post_meta
	 * @testdox update_or_delete_post_meta writes, deletes, or skips based on value and key presence.
	 *
	 * @param Closure $setup           Receives the product ID; sets up pre-existing meta if needed.
	 * @param mixed   $meta_value      Value passed to update_or_delete_post_meta.
	 * @param bool    $expected_result Expected boolean return value.
	 * @param mixed   $expected_stored Expected get_post_meta result after the call.
	 */
	public function test_update_or_delete_post_meta( Closure $setup, $meta_value, bool $expected_result, $expected_stored ): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_or_delete_post_meta( $product, $meta_key, $meta_value ): bool { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				return parent::update_or_delete_post_meta( $product, $meta_key, $meta_value );
			}
		};

		$product = new WC_Product();
		$product->save();
		$product_id = $product->get_id();
		$setup( $product_id );

		$result = $store->update_or_delete_post_meta( $product, '_sku', $meta_value );

		$this->assertSame( $expected_result, $result );
		$this->assertSame( $expected_stored, get_post_meta( $product_id, '_sku', true ) );

		$product->delete();
	}

	/**
	 * Day-precision meta date boundaries per operator, for a site in America/New_York.
	 *
	 * The bounds are half-open, so a day is matched in full and exactly once. For a range the
	 * upper bound is the midnight *after* the final day, so that the whole final day is covered.
	 *
	 * @return array
	 */
	public function day_precision_meta_boundary_provider(): array {
		return array(
			'equals'                  => array(
				'2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'greater than'            => array(
				'>2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '>=',
					),
				),
			),
			'greater than or equals'  => array(
				'>=2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
				),
			),
			'less than'               => array(
				'<2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'less than or equals'     => array(
				'<=2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'range'                   => array(
				'2026-07-20...2026-07-25',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_26_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'explicit UTC instant'    => array(
				'2026-07-20T02:00:00Z',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'explicit offset instant' => array(
				'2026-07-20T02:00:00+05:00',
				array(
					array(
						'value'   => self::NY_JUL_19_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'naive datetime'          => array(
				'2026-07-20 22:00:00',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
			'single-day range'        => array(
				'2026-07-20...2026-07-20',
				array(
					array(
						'value'   => self::NY_JUL_20_MIDNIGHT,
						'compare' => '>=',
					),
					array(
						'value'   => self::NY_JUL_21_MIDNIGHT,
						'compare' => '<',
					),
				),
			),
		);
	}

	/**
	 * @testdox Day-precision meta date queries should anchor on the site's local midnight for every operator.
	 *
	 * @dataProvider day_precision_meta_boundary_provider
	 *
	 * @param string $query_var The date query var.
	 * @param array  $expected  Expected value/compare pairs, in order.
	 */
	public function test_day_precision_meta_boundaries_use_site_timezone( string $query_var, array $expected ): void {
		update_option( 'timezone_string', 'America/New_York' );

		$result = $this->sut->parse_date_for_wp_query( $query_var, '_date_paid' );

		$this->assertCount(
			count( $expected ),
			$result['meta_query'],
			'Wrong number of meta query clauses for ' . $query_var
		);

		foreach ( $expected as $index => $clause ) {
			$this->assertSame(
				$clause['value'],
				$result['meta_query'][ $index ]['value'],
				"Wrong boundary timestamp in clause {$index} for {$query_var}"
			);
			$this->assertSame(
				$clause['compare'],
				$result['meta_query'][ $index ]['compare'],
				"Wrong comparison operator in clause {$index} for {$query_var}"
			);
		}
	}

	/**
	 * @testdox A day-precision date the calendar cannot represent should drop the date constraint rather than throw.
	 */
	public function test_day_precision_meta_boundaries_survive_an_unrepresentable_date(): void {
		update_option( 'timezone_string', 'America/New_York' );

		// Resolves to the year 10026, which DateTime refuses to parse.
		$result = $this->sut->parse_date_for_wp_query( '+8000 years', '_date_paid' );

		$this->assertSame(
			array(
				array(
					'key'     => '_date_paid',
					'value'   => array( 1, 0 ),
					'type'    => 'NUMERIC',
					'compare' => 'BETWEEN',
				),
			),
			$result['meta_query'],
			'An unrepresentable date should constrain the query to an empty range rather than dropping the date filter'
		);
	}

	/**
	 * @testdox Day-precision meta date queries should anchor on local midnight on sites using a manual UTC offset.
	 */
	public function test_day_precision_meta_boundaries_use_manual_utc_offset(): void {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', -4 );

		$result = $this->sut->parse_date_for_wp_query( '2026-07-20', '_date_paid' );

		$this->assertSame( self::NY_JUL_20_MIDNIGHT, $result['meta_query'][0]['value'], 'Start should be local midnight, not UTC midnight' );
		$this->assertSame( self::NY_JUL_21_MIDNIGHT, $result['meta_query'][1]['value'], 'End should be the next local midnight' );
	}

	/**
	 * Days whose local length is not 24 hours.
	 *
	 * @return array
	 */
	public function dst_transition_provider(): array {
		return array(
			'DST ends, 25-hour day'               => array(
				'America/New_York',
				'2026-11-01',
				self::NY_NOV_01_MIDNIGHT,
				self::NY_NOV_02_MIDNIGHT,
			),
			'DST starts, 23-hour day'             => array(
				'America/New_York',
				'2026-03-08',
				self::NY_MAR_08_MIDNIGHT,
				self::NY_MAR_09_MIDNIGHT,
			),
			'DST starts at midnight, 23-hour day' => array(
				'Asia/Beirut',
				'2026-03-29',
				self::BEIRUT_MAR_29_START,
				self::BEIRUT_MAR_30_MIDNIGHT,
			),
		);
	}

	/**
	 * @testdox Day-precision meta date queries should span the real length of a day across DST transitions.
	 *
	 * @dataProvider dst_transition_provider
	 *
	 * @param string $timezone       Site timezone.
	 * @param string $date           The queried day.
	 * @param int    $expected_start Expected start-of-day timestamp.
	 * @param int    $expected_end   Expected next-midnight timestamp.
	 */
	public function test_day_precision_meta_boundaries_follow_dst_transitions( string $timezone, string $date, int $expected_start, int $expected_end ): void {
		$this->skip_if_day_has_no_dst_transition( $timezone, $date );

		update_option( 'timezone_string', $timezone );

		$result = $this->sut->parse_date_for_wp_query( $date, '_date_paid' );

		$this->assertSame( $expected_start, $result['meta_query'][0]['value'], "Wrong start of day for {$date} in {$timezone}" );
		$this->assertSame( $expected_end, $result['meta_query'][1]['value'], "Wrong end of day for {$date} in {$timezone}" );
	}

	/**
	 * Skip a DST case when the runtime's timezone database puts no transition on that day.
	 *
	 * PHP resolves timezones against the tz database compiled into the binary, not the one the OS
	 * ships, so a case is only meaningful on a runtime whose database still places a transition
	 * where the case expects one. The zones above were picked because their rules hold across the
	 * tzdata versions this suite runs under, but DST policy is set by governments and does change:
	 * Egypt, for one, has reinstated and repealed it repeatedly. This is the backstop that turns a
	 * future policy change into a skip rather than a failure that reads like a real regression.
	 *
	 * The expected timestamps stay hard-coded on purpose. Recomputing them through the same
	 * DateTime API the production code uses would make the assertion tautological; this guard only
	 * reads the environment, never the code under test.
	 *
	 * @param string $timezone Site timezone.
	 * @param string $date     The queried day.
	 */
	private function skip_if_day_has_no_dst_transition( string $timezone, string $date ): void {
		$zone  = new DateTimeZone( $timezone );
		$start = new DateTime( $date . ' 00:00:00', $zone );
		$end   = new DateTime( $date . ' 00:00:00', $zone );
		$end->modify( 'tomorrow' );

		if ( DAY_IN_SECONDS === $end->getTimestamp() - $start->getTimestamp() ) {
			$this->markTestSkipped(
				'Timezone database ' . timezone_version_get() . " puts no DST transition on {$date} in {$timezone}."
			);
		}
	}
}
