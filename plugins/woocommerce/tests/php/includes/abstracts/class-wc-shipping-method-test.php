<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Tests for WC_Shipping_Method abstract class.
 */
class WC_Shipping_Method_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Shipping_Flat_Rate
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                       = new WC_Shipping_Flat_Rate();
		$this->sut->instance_form_fields = array(
			'title' => array(
				'title'   => 'Method title',
				'type'    => 'text',
				'default' => 'Flat rate',
			),
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_shipping_instance_form_fields' );
		remove_all_filters( 'woocommerce_shipping_instance_form_fields_flat_rate' );
		parent::tearDown();
	}

	/**
	 * @testdox Should fire the per-method woocommerce_shipping_instance_form_fields_{id} filter.
	 */
	public function test_get_instance_form_fields_fires_per_method_filter(): void {
		add_filter(
			'woocommerce_shipping_instance_form_fields_flat_rate',
			function ( $fields ) {
				$fields['per_method_field'] = array(
					'title' => 'Per-method field',
					'type'  => 'text',
				);
				return $fields;
			}
		);

		$fields = $this->sut->get_instance_form_fields();

		$this->assertArrayHasKey( 'per_method_field', $fields, 'Per-method filter should add fields.' );
	}

	/**
	 * @testdox Should fire the generic woocommerce_shipping_instance_form_fields filter for every shipping method.
	 */
	public function test_get_instance_form_fields_fires_generic_filter(): void {
		$received_method = null;

		add_filter(
			'woocommerce_shipping_instance_form_fields',
			function ( $fields, $method ) use ( &$received_method ) {
				$received_method            = $method;
				$fields['custom_test_field'] = array(
					'title'   => 'Custom Hook Test Field',
					'type'    => 'text',
					'default' => 'hook_fired',
				);
				return $fields;
			},
			10,
			2
		);

		$fields = $this->sut->get_instance_form_fields();

		$this->assertArrayHasKey( 'custom_test_field', $fields, 'Generic filter should add custom fields to every shipping method.' );
		$this->assertSame( $this->sut, $received_method, 'Generic filter should pass the shipping method instance.' );
	}

	/**
	 * @testdox Should fire the generic filter after the per-method filter so generic callbacks can see per-method additions.
	 */
	public function test_get_instance_form_fields_filter_ordering(): void {
		add_filter(
			'woocommerce_shipping_instance_form_fields_flat_rate',
			function ( $fields ) {
				$fields['per_method_field'] = array(
					'title' => 'Per-method field',
					'type'  => 'text',
				);
				return $fields;
			}
		);

		$seen_keys = array();
		add_filter(
			'woocommerce_shipping_instance_form_fields',
			function ( $fields ) use ( &$seen_keys ) {
				$seen_keys = array_keys( $fields );
				return $fields;
			}
		);

		$this->sut->get_instance_form_fields();

		$this->assertContains( 'per_method_field', $seen_keys, 'Generic filter should run after per-method filter.' );
	}
}
