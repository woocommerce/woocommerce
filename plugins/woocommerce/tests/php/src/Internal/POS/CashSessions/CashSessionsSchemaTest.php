<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Enums\CashMovementDirection;
use Automattic\WooCommerce\Enums\CashMovementType;
use Automattic\WooCommerce\Enums\CashSessionStatus;
use Automattic\WooCommerce\Enums\DrawerEventReason;
use Automattic\WooCommerce\Enums\DrawerEventType;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionsSchema;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Tests for the cash sessions response schemas and vocabularies.
 */
class CashSessionsSchemaTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should keep the wire values in behavior-free enum classes and expose them in response schemas.
	 */
	public function test_enum_values_match_response_schemas(): void {
		$sut   = wc_get_container()->get( CashSessionsSchema::class );
		$cases = array(
			array( CashSessionStatus::class, array( 'open', 'closed' ), $sut->get_session_schema()['properties']['status'] ),
			array( CashMovementType::class, array( 'opening_float', 'cash_sale', 'cash_refund', 'paid_in', 'paid_out' ), $sut->get_movement_schema()['properties']['type'] ),
			array( CashMovementDirection::class, array( 'in', 'out' ), $sut->get_movement_schema()['properties']['direction'] ),
			array( DrawerEventType::class, array( 'open_requested', 'opened', 'open_failed' ), $sut->get_drawer_event_schema()['properties']['type'] ),
			array( DrawerEventReason::class, array( 'cash_sale', 'cash_refund', 'no_sale', 'test', 'paid_in', 'paid_out', 'count', 'unknown' ), $sut->get_drawer_event_schema()['properties']['reason'] ),
		);

		foreach ( $cases as list( $class, $values, $property ) ) {
			$reflection = new ReflectionClass( $class );
			$this->assertTrue( $reflection->isFinal() );
			$this->assertSame( array(), $reflection->getMethods(), 'Enums must contain constants only.' );
			$this->assertSame( $values, array_values( $reflection->getConstants() ), $class );
			$this->assertSame( $values, $property['enum'], $class );
		}
	}

	/**
	 * @testdox Should retain supplied drawer references and reject null references in the response schema.
	 */
	public function test_drawer_event_optional_references(): void {
		$sut  = wc_get_container()->get( CashSessionsSchema::class );
		$row  = array(
			'id'               => 1,
			'session_id'       => 2,
			'request_id'       => 'aaaaaaaa-0000-4000-8000-000000000001',
			'type'             => DrawerEventType::OPENED,
			'reason'           => DrawerEventReason::CASH_REFUND,
			'drawer_name'      => 'Front counter',
			'order_id'         => '3',
			'refund_id'        => '4',
			'movement_id'      => '5',
			'correlation_id'   => 'bbbbbbbb-0000-4000-8000-000000000001',
			'occurred_at_gmt'  => '2026-09-25 12:00:00',
			'created_by'       => 6,
			'created_by_name'  => 'Cashier',
			'date_created_gmt' => '2026-09-25 12:00:00',
		);
		$data = $sut->format_drawer_event( $row );
		$this->assertSame( 3, $data['order_id'] );
		$this->assertSame( 4, $data['refund_id'] );
		$this->assertSame( 5, $data['movement_id'] );
		$this->assertSame( $row['correlation_id'], $data['correlation_id'] );
		$this->assertTrue( rest_validate_value_from_schema( $data, $sut->get_drawer_event_schema() ) );

		foreach ( array( 'order_id', 'refund_id', 'movement_id', 'correlation_id' ) as $field ) {
			$invalid           = $data;
			$invalid[ $field ] = null;
			$this->assertWPError( rest_validate_value_from_schema( $invalid, $sut->get_drawer_event_schema() ) );
			$row[ $field ] = null;
		}
		$this->assertTrue( rest_validate_value_from_schema( $sut->format_drawer_event( $row ), $sut->get_drawer_event_schema() ) );
	}
}
