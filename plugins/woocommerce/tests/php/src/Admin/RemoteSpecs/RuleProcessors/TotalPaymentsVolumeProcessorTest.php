<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteSpecs\RuleProcessors;

use Automattic\WooCommerce\Admin\DateTimeProvider\DateTimeProviderInterface;
use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\TotalPaymentsVolumeProcessor;
use DateTime;
use DateTimeZone;
use WC_Unit_Test_Case;

/**
 * Tests for the TotalPaymentsVolumeProcessor class.
 */
class TotalPaymentsVolumeProcessorTest extends WC_Unit_Test_Case {

	/**
	 * Builds the processor with a frozen clock and a stubbed report query that captures its args.
	 *
	 * The frozen "now" is 2020-08-31 15:00 UTC, which is 2020-09-01 05:00 in Pacific/Kiritimati
	 * (UTC+14). A past instant is used so a regression that falls back to the real clock can
	 * never reproduce the expected bounds.
	 *
	 * @return TotalPaymentsVolumeProcessor Processor exposing a public $captured_args property.
	 */
	private function get_processor_with_frozen_clock() {
		$frozen_provider = new class() implements DateTimeProviderInterface {
			/**
			 * The cached frozen instance.
			 *
			 * @var DateTime|null
			 */
			private $now;

			/**
			 * Returns the frozen current DateTime. Deliberately returns the same cached
			 * instance on every call, like the legacy MockDateTimeProvider: the processor
			 * must not leak its date mutations back into the provider's object.
			 *
			 * @return DateTime
			 */
			public function get_now() {
				if ( null === $this->now ) {
					$this->now = new DateTime( '2020-08-31 15:00:00', new DateTimeZone( 'UTC' ) );
				}
				return $this->now;
			}
		};

		return new class( $frozen_provider ) extends TotalPaymentsVolumeProcessor {
			/**
			 * Args the report query was built with.
			 *
			 * @var array
			 */
			public $captured_args;

			/**
			 * Captures the query args and returns a stubbed query.
			 *
			 * @param array $args The query args.
			 * @return object Stub with a get_data() method.
			 */
			protected function get_reports_query( $args ) {
				$this->captured_args = $args;

				return new class() {
					/**
					 * Returns stubbed report data.
					 *
					 * @return object
					 */
					public function get_data() {
						return (object) array( 'totals' => (object) array( 'total_sales' => 100 ) );
					}
				};
			}
		};
	}

	/**
	 * @testdox Should evaluate the timeframe against the site's calendar date, not the UTC date.
	 */
	public function test_process_resolves_timeframe_in_site_timezone(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Pacific/Kiritimati' );

		try {
			$processor = $this->get_processor_with_frozen_clock();
			$rule      = (object) array(
				'timeframe' => 'last_month',
				'value'     => 50,
				'operation' => '>',
			);

			$result = $processor->process( $rule, (object) array() );

			$this->assertTrue( $result, 'total_sales of 100 should satisfy "> 50"' );
			// On the frozen instant the site-local date is 2020-09-01 while the UTC date is still
			// 2020-08-31, so last_month must resolve to August 2020, not July 2020.
			$this->assertSame( '2020-08-01 00:00:00', $processor->captured_args['after'] );
			$this->assertSame( '2020-08-31 23:59:59', $processor->captured_args['before'] );

			// A second evaluation must produce the same window: the date calculations mutate the
			// reference date in place, and that must not leak into the provider's cached instance.
			$processor->process( $rule, (object) array() );

			$this->assertSame( '2020-08-01 00:00:00', $processor->captured_args['after'], 'Repeated process() calls should not drift the timeframe' );
			$this->assertSame( '2020-08-31 23:59:59', $processor->captured_args['before'], 'Repeated process() calls should not drift the timeframe' );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @testdox Should fail the rule without querying reports when the timeframe is unknown.
	 */
	public function test_process_returns_false_for_unknown_timeframe(): void {
		$processor = $this->get_processor_with_frozen_clock();
		$rule      = (object) array(
			'timeframe' => 'last_century',
			'value'     => 50,
			'operation' => '>',
		);

		$result = $processor->process( $rule, (object) array() );

		$this->assertFalse( $result );
		$this->assertNull( $processor->captured_args, 'No report query should be built for an unknown timeframe' );
	}
}
