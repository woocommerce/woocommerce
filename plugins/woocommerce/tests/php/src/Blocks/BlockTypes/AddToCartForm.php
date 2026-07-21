<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\Package;
use WC_Unit_Test_Case;

/**
 * Tests for the AddToCartForm block type.
 */
class AddToCartForm extends WC_Unit_Test_Case {

	/**
	 * Tests that add_stepper_classes_to_add_to_cart_form_input adds wrapper and input classes to inputs.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartForm::add_stepper_classes_to_add_to_cart_form_input
	 */
	public function test_add_stepper_classes_to_add_to_cart_form_input(): void {
		$quantity_html = '<div class="quantity"><input type="number" class="input-text qty text" name="custom_name" value="1" /></div>';

		$block = new class(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		) extends \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartForm {
			/**
			 * Skip block registration; woocommerce/add-to-cart-form is already registered when WooCommerce loads.
			 */
			protected function initialize() {
			}
		};

		$reflection = new \ReflectionClass( $block );
		$method     = $reflection->getMethod( 'add_stepper_classes_to_add_to_cart_form_input' );
		$method->setAccessible( true );

		$result = $method->invoke( $block, $quantity_html );

		$this->assertStringContainsString( 'wc-block-components-quantity-selector', $result, 'The quantity wrapper should receive the stepper wrapper class.' );
		$this->assertStringContainsString( 'wc-block-components-quantity-selector__input', $result, 'The input should receive the stepper input class.' );
		$this->assertStringContainsString( 'custom_name', $result, 'The original input name value should be preserved.' );
	}

	/**
	 * Tests that add_steppers injects the buttons in visual DOM order (− input +),
	 * so keyboard focus and screen-reader reading order are logical.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartForm::add_steppers
	 */
	public function test_add_steppers_injects_buttons_in_visual_dom_order(): void {
		$quantity_html = '<div class="quantity"><input type="number" id="quantity_123" class="input-text qty text" name="quantity" value="1" /></div>';

		$block = new class(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		) extends \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartForm {
			/**
			 * Skip block registration; woocommerce/add-to-cart-form is already registered when WooCommerce loads.
			 */
			protected function initialize() {
			}
		};

		$reflection = new \ReflectionClass( $block );
		$method     = $reflection->getMethod( 'add_steppers' );
		$method->setAccessible( true );

		$result = $method->invoke( $block, $quantity_html, 'Test Product' );

		// The minus button must precede the input, which must precede the plus button.
		$this->assertMatchesRegularExpression(
			'/quantity-selector__button--minus.*id="quantity_.*quantity-selector__button--plus/s',
			$result,
			'add_steppers should inject buttons in − input + DOM order.'
		);
	}
}
