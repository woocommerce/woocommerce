<?php

/**
 * @group timezone
 */
class ActionScheduler_TimezoneHelper_Test extends ActionScheduler_UnitTestCase {

	/**
	 * Ensure that the timezone string we expect works properly.
	 *
	 * @dataProvider local_timezone_provider
	 *
	 * @param $timezone_string
	 */
	public function test_local_timezone_strings( $timezone_string ) {
		$timezone_filter = function ( $tz ) use ( $timezone_string ) {
			return $timezone_string;
		};

		add_filter( 'option_timezone_string', $timezone_filter );

		$date     = new ActionScheduler_DateTime();
		$timezone = ActionScheduler_TimezoneHelper::set_local_timezone( $date )->getTimezone();
		$this->assertInstanceOf( 'DateTimeZone', $timezone );
		$this->assertEquals( $timezone_string, $timezone->getName() );

		remove_filter( 'option_timezone_string', $timezone_filter );
	}

	public function local_timezone_provider() {
		return array(
			array( 'America/New_York' ),
			array( 'Australia/Melbourne' ),
			array( 'UTC' ),
		);
	}

	/**
	 * Ensure that most GMT offsets don't return UTC as the timezone.
	 *
	 * @dataProvider local_timezone_offsets_provider
	 *
	 * @param $gmt_offset
	 */
	public function test_local_timezone_offsets( $gmt_offset ) {
		$gmt_filter = function ( $gmt ) use ( $gmt_offset ) {
			return $gmt_offset;
		};

		$date = new ActionScheduler_DateTime();

		add_filter( 'option_gmt_offset', $gmt_filter );
		ActionScheduler_TimezoneHelper::set_local_timezone( $date );
		remove_filter( 'option_gmt_offset', $gmt_filter );

		$offset_in_seconds = $gmt_offset * HOUR_IN_SECONDS;

		$this->assertEquals( $offset_in_seconds, $date->getOffset() );
		$this->assertEquals( $offset_in_seconds, $date->getOffsetTimestamp() - $date->getTimestamp() );
	}

	public function local_timezone_offsets_provider() {
		return array(
			array( '-11' ),
			array( '-10.5' ),
			array( '-10' ),
			array( '-9' ),
			array( '-8' ),
			array( '-7' ),
			array( '-6' ),
			array( '-5' ),
			array( '-4.5' ),
			array( '-4' ),
			array( '-3.5' ),
			array( '-3' ),
			array( '-2' ),
			array( '-1' ),
			array( '1' ),
			array( '1.5' ),
			array( '2' ),
			array( '3' ),
			array( '4' ),
			array( '5' ),
			array( '5.5' ),
			array( '5.75' ),
			array( '6' ),
			array( '7' ),
			array( '8' ),
			array( '8.5' ),
			array( '9' ),
			array( '9.5' ),
			array( '10' ),
			array( '10.5' ),
			array( '11' ),
			array( '11.5' ),
			array( '12' ),
			array( '13' ),
		);
	}
}
