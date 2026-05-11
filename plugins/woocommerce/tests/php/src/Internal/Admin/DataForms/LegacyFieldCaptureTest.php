<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\DataForms;

use Automattic\WooCommerce\Internal\Admin\DataForms\LegacyFieldCapture;
use WC_Unit_Test_Case;

/**
 * Tests for LegacyFieldCapture.
 */
class LegacyFieldCaptureTest extends WC_Unit_Test_Case {

	/**
	 * Enable capture mode on the static class so collect() accepts fields.
	 */
	private function enable_capture(): void {
		$ref = new \ReflectionClass( LegacyFieldCapture::class );

		$capturing = $ref->getProperty( 'capturing' );
		$capturing->setAccessible( true );
		$capturing->setValue( null, true );

		$hook = $ref->getProperty( 'current_hook' );
		$hook->setAccessible( true );
		$hook->setValue( null, 'woocommerce_variation_options' );

		$fields = $ref->getProperty( 'fields' );
		$fields->setAccessible( true );
		$fields->setValue( null, array( 'woocommerce_variation_options' => array() ) );
	}

	/**
	 * Read the collected fields from the static class.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_collected_fields(): array {
		$ref    = new \ReflectionClass( LegacyFieldCapture::class );
		$fields = $ref->getProperty( 'fields' );
		$fields->setAccessible( true );
		$all = $fields->getValue( null );
		return $all['woocommerce_variation_options'] ?? array();
	}

	public function tearDown(): void {
		LegacyFieldCapture::reset();
		parent::tearDown();
	}

	public function test_collect_uses_base_id_as_meta_key_without_prefix(): void {
		$this->enable_capture();

		LegacyFieldCapture::collect( 'text_input', array(
			'id'    => 'my_custom_field[0]',
			'label' => 'Custom Field',
		) );

		$fields = $this->get_collected_fields();
		$this->assertCount( 1, $fields );
		$this->assertSame( 'my_custom_field', $fields[0]['id'] );
		$this->assertSame( 'my_custom_field', $fields[0]['meta_key'] );
	}

	public function test_collect_preserves_underscore_prefix_without_doubling(): void {
		$this->enable_capture();

		LegacyFieldCapture::collect( 'text_input', array(
			'id'    => '_my_custom_field[0]',
			'label' => 'Custom Field',
		) );

		$fields = $this->get_collected_fields();
		$this->assertCount( 1, $fields );
		$this->assertSame( '_my_custom_field', $fields[0]['id'] );
		$this->assertSame( '_my_custom_field', $fields[0]['meta_key'] );
	}

	public function test_collect_strips_loop_index_for_meta_key(): void {
		$this->enable_capture();

		LegacyFieldCapture::collect( 'text_input', array(
			'id'    => 'variable_weight[3]',
			'label' => 'Weight',
		) );

		$fields = $this->get_collected_fields();
		$this->assertCount( 1, $fields );
		$this->assertSame( 'variable_weight', $fields[0]['id'] );
		$this->assertSame( 'variable_weight', $fields[0]['meta_key'] );
	}

	public function test_collect_handles_id_without_loop_index(): void {
		$this->enable_capture();

		LegacyFieldCapture::collect( 'text_input', array(
			'id'    => 'field_name',
			'label' => 'Field',
		) );

		$fields = $this->get_collected_fields();
		$this->assertCount( 1, $fields );
		$this->assertSame( 'field_name', $fields[0]['meta_key'] );
	}
}
