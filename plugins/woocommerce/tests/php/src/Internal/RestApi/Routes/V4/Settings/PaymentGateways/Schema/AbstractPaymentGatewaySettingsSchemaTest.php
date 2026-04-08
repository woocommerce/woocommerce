<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema\AbstractPaymentGatewaySettingsSchema;
use WC_Payment_Gateway;
use WC_Unit_Test_Case;

/**
 * Tests for the AbstractPaymentGatewaySettingsSchema class.
 *
 * Focuses on the build_fields_from_form_fields() helper method which all
 * gateway schemas depend on.
 */
class AbstractPaymentGatewaySettingsSchemaTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AbstractPaymentGatewaySettingsSchema
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = $this->create_concrete_schema();
	}

	/**
	 * @testdox build_fields_from_form_fields should include synthetic order field even when not in form_fields.
	 */
	public function test_build_fields_includes_synthetic_order_field(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'enabled' => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable test gateway',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled' => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable test gateway',
			),
			'order'   => array(
				'label' => 'Order',
				'type'  => 'number',
				'desc'  => 'Display order.',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$order_field = $this->find_field_by_id( $fields, 'order' );
		$this->assertNotNull( $order_field, 'Synthetic order field should be present' );
		$this->assertSame( 'number', $order_field['type'] );
		$this->assertSame( 'Order', $order_field['label'] );
	}

	/**
	 * @testdox build_fields_from_form_fields should preserve extension-injected fields not in core overrides.
	 */
	public function test_build_fields_preserves_extension_injected_fields(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'enabled'          => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable',
				),
				'custom_ext_field' => array(
					'title'       => 'Extension Setting',
					'type'        => 'text',
					'description' => 'Added by an extension.',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled' => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$ext_field = $this->find_field_by_id( $fields, 'custom_ext_field' );
		$this->assertNotNull( $ext_field, 'Extension-injected field should be preserved' );
		$this->assertSame( 'Extension Setting', $ext_field['label'] );
		$this->assertSame( 'text', $ext_field['type'] );
	}

	/**
	 * @testdox build_fields_from_form_fields should skip fields in skip_field_ids.
	 */
	public function test_build_fields_skips_specified_field_ids(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'enabled'         => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable',
				),
				'account_details' => array(
					'title' => 'Account details',
					'type'  => 'array',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled' => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides, array( 'account_details' ) );

		$skipped_field = $this->find_field_by_id( $fields, 'account_details' );
		$this->assertNull( $skipped_field, 'Fields in skip_field_ids should be excluded' );
	}

	/**
	 * @testdox build_fields_from_form_fields should use override labels instead of gateway form_fields labels.
	 */
	public function test_build_fields_applies_override_labels(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'title' => array(
					'title'       => 'Original Title Label',
					'type'        => 'text',
					'description' => 'Original description.',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'title' => array(
				'label' => 'Checkout label',
				'type'  => 'text',
				'desc'  => 'Shown to customers.',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$title_field = $this->find_field_by_id( $fields, 'title' );
		$this->assertNotNull( $title_field, 'Title field should exist' );
		$this->assertSame( 'Checkout label', $title_field['label'], 'Override label should be used' );
		$this->assertSame( 'Shown to customers.', $title_field['desc'], 'Override description should be used' );
	}

	/**
	 * @testdox build_fields_from_form_fields should preserve options from form_fields for multiselect when override has no options.
	 */
	public function test_build_fields_preserves_form_field_options_for_multiselect(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'shipping_methods' => array(
					'title'   => 'Shipping Methods',
					'type'    => 'multiselect',
					'options' => array(
						'flat_rate' => 'Flat rate',
						'free'      => 'Free shipping',
					),
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'shipping_methods' => array(
				'label' => 'Available shipping methods',
				'type'  => 'multiselect',
				'desc'  => '',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$field = $this->find_field_by_id( $fields, 'shipping_methods' );
		$this->assertNotNull( $field, 'Shipping methods field should exist' );
		$this->assertArrayHasKey( 'options', $field, 'Options should be preserved from form_fields' );
		$this->assertSame( 'Flat rate', $field['options']['flat_rate'] );
	}

	/**
	 * @testdox build_fields_from_form_fields should use override options when explicitly provided.
	 */
	public function test_build_fields_uses_override_options_when_provided(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'shipping_methods' => array(
					'title'   => 'Shipping Methods',
					'type'    => 'multiselect',
					'options' => array(
						'old' => 'Old option',
					),
				),
			)
		);
		$gateway->init_form_fields();

		$custom_options = array(
			'new_flat_rate' => 'New Flat rate',
			'new_free'      => 'New Free shipping',
		);
		$overrides      = array(
			'shipping_methods' => array(
				'label'   => 'Shipping methods',
				'type'    => 'multiselect',
				'desc'    => '',
				'options' => $custom_options,
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$field = $this->find_field_by_id( $fields, 'shipping_methods' );
		$this->assertNotNull( $field, 'Shipping methods field should exist' );
		$this->assertSame( $custom_options, $field['options'], 'Override options should take precedence' );
	}

	/**
	 * @testdox build_fields_from_form_fields should skip non-data fields like title and sectionend from extension fields.
	 */
	public function test_build_fields_skips_non_data_extension_fields(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'enabled'       => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable',
				),
				'section_title' => array(
					'title' => 'Section Header',
					'type'  => 'title',
				),
				'section_end'   => array(
					'type' => 'sectionend',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled' => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$this->assertNull( $this->find_field_by_id( $fields, 'section_title' ), 'Title type fields should be skipped' );
		$this->assertNull( $this->find_field_by_id( $fields, 'section_end' ), 'Sectionend type fields should be skipped' );
	}

	/**
	 * @testdox build_fields_from_form_fields should skip override fields not present in form_fields (except order).
	 */
	public function test_build_fields_skips_overrides_missing_from_form_fields(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'enabled' => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled'      => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable',
			),
			'instructions' => array(
				'label' => 'Instructions',
				'type'  => 'text',
				'desc'  => 'Order confirmation instructions.',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$this->assertNull(
			$this->find_field_by_id( $fields, 'instructions' ),
			'Override fields not in form_fields should be skipped'
		);
	}

	/**
	 * @testdox build_fields_from_form_fields should maintain core field order from overrides, then extension fields.
	 */
	public function test_build_fields_maintains_override_order_then_extensions(): void {
		$gateway = $this->create_mock_gateway(
			array(
				'custom_ext' => array(
					'title' => 'Extension Field',
					'type'  => 'text',
				),
				'enabled'    => array(
					'title' => 'Enable/Disable',
					'type'  => 'checkbox',
					'label' => 'Enable',
				),
				'title'      => array(
					'title' => 'Title',
					'type'  => 'text',
				),
			)
		);
		$gateway->init_form_fields();

		$overrides = array(
			'enabled' => array(
				'label' => 'Enable/Disable',
				'type'  => 'checkbox',
				'desc'  => 'Enable',
			),
			'title'   => array(
				'label' => 'Title',
				'type'  => 'text',
				'desc'  => 'Title desc.',
			),
		);

		$fields = $this->invoke_build_fields( $gateway, $overrides );

		$field_ids = array_column( $fields, 'id' );
		$this->assertSame( 'enabled', $field_ids[0], 'First field should follow override order' );
		$this->assertSame( 'title', $field_ids[1], 'Second field should follow override order' );
		$this->assertSame( 'custom_ext', $field_ids[2], 'Extension field should come after core overrides' );
	}

	/**
	 * Create a concrete implementation of the abstract schema for testing.
	 *
	 * @return AbstractPaymentGatewaySettingsSchema
	 */
	private function create_concrete_schema(): AbstractPaymentGatewaySettingsSchema {
		return new class() extends AbstractPaymentGatewaySettingsSchema {
			/**
			 * Expose build_fields_from_form_fields for testing.
			 *
			 * @param WC_Payment_Gateway $gateway              Gateway instance.
			 * @param array              $core_field_overrides  Core field overrides.
			 * @param array              $skip_field_ids        Field IDs to skip.
			 * @return array
			 */
			public function public_build_fields( WC_Payment_Gateway $gateway, array $core_field_overrides, array $skip_field_ids = array() ): array {
				return $this->build_fields_from_form_fields( $gateway, $core_field_overrides, $skip_field_ids );
			}
		};
	}

	/**
	 * Create a mock gateway with specified form fields.
	 *
	 * @param array $form_fields Form fields definition.
	 * @return WC_Payment_Gateway
	 */
	private function create_mock_gateway( array $form_fields ): WC_Payment_Gateway {
		$gateway = new class() extends WC_Payment_Gateway {
			/**
			 * Fields to use in init_form_fields.
			 *
			 * @var array
			 */
			public array $test_form_fields = array();

			/**
			 * Initialize form fields from test data.
			 */
			public function init_form_fields() {
				$this->form_fields = $this->test_form_fields;
			}
		};

		$gateway->test_form_fields = $form_fields;

		return $gateway;
	}

	/**
	 * Invoke build_fields_from_form_fields on the SUT.
	 *
	 * @param WC_Payment_Gateway $gateway              Gateway instance.
	 * @param array              $core_field_overrides  Core field overrides.
	 * @param array              $skip_field_ids        Field IDs to skip.
	 * @return array
	 */
	private function invoke_build_fields( WC_Payment_Gateway $gateway, array $core_field_overrides, array $skip_field_ids = array() ): array {
		return $this->sut->public_build_fields( $gateway, $core_field_overrides, $skip_field_ids );
	}

	/**
	 * Find a field by ID in a fields array.
	 *
	 * @param array  $fields   Fields array.
	 * @param string $field_id Field ID to find.
	 * @return array|null The field or null if not found.
	 */
	private function find_field_by_id( array $fields, string $field_id ): ?array {
		foreach ( $fields as $field ) {
			if ( ( $field['id'] ?? '' ) === $field_id ) {
				return $field;
			}
		}
		return null;
	}
}
