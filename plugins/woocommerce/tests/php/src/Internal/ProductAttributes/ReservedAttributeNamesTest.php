<?php
/**
 * Reserved attribute names tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\ReservedAttributeNames;
use WC_Logger;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Unit_Test_Case;

/**
 * Tests for the reserved attribute names utility.
 */
class ReservedAttributeNamesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_blocked_reserved_names() blocks newly added reserved custom attribute names.
	 */
	public function test_blocks_new_reserved_names(): void {
		$attributes = array(
			$this->create_custom_attribute( 'Variation' ),
			$this->create_custom_attribute( 'Color' ),
		);

		$blocked = ReservedAttributeNames::get_blocked_reserved_names( $attributes );

		$this->assertSame( array( 'Variation' ), $blocked, 'Only the reserved attribute name should be blocked.' );
	}

	/**
	 * @testdox get_blocked_reserved_names() grandfathers reserved names already stored on the product.
	 */
	public function test_grandfathers_existing_reserved_names(): void {
		$existing_product = new WC_Product_Variable();
		$existing_product->set_attributes( array( $this->create_custom_attribute( 'Variation' ) ) );

		$blocked = ReservedAttributeNames::get_blocked_reserved_names(
			array( $this->create_custom_attribute( 'Variation' ) ),
			$existing_product
		);

		$this->assertSame( array(), $blocked, 'A reserved name already on the product should be grandfathered.' );
	}

	/**
	 * @testdox get_blocked_reserved_names() ignores global (taxonomy) attributes.
	 */
	public function test_ignores_taxonomy_attributes(): void {
		$taxonomy_attribute = new WC_Product_Attribute();
		$taxonomy_attribute->set_id( 123 );
		$taxonomy_attribute->set_name( 'pa_variation' );

		$blocked = ReservedAttributeNames::get_blocked_reserved_names( array( $taxonomy_attribute ) );

		$this->assertSame( array(), $blocked, 'Global attributes are namespaced and should never be blocked.' );
	}

	/**
	 * @testdox get_blocked_reserved_names() logs a breadcrumb under 'attribute-collision' for a grandfathered collision, with the correct product ID.
	 */
	public function test_logs_grandfathered_collision(): void {
		$existing_product = new WC_Product_Variable();
		$existing_product->set_attributes( array( $this->create_custom_attribute( 'Variation' ) ) );
		$existing_product->save();

		$fake_logger   = $this->create_fake_logger();
		$logger_filter = static function () use ( $fake_logger ) {
			return $fake_logger;
		};
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			ReservedAttributeNames::get_blocked_reserved_names(
				array( $this->create_custom_attribute( 'Variation' ) ),
				$existing_product
			);

			$this->assertCount( 1, $fake_logger->warning_calls, 'A grandfathered collision should be logged once.' );
			$this->assertSame( 'attribute-collision', $fake_logger->warning_calls[0]['context']['source'], 'The collision should be logged under the attribute-collision source.' );
			$this->assertSame( $existing_product->get_id(), $fake_logger->warning_calls[0]['context']['product_id'], 'The correct product ID should be logged.' );
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
			$existing_product->delete( true );
		}
	}

	/**
	 * @testdox get_blocked_reserved_names() does not log when a reserved name is newly blocked (not grandfathered).
	 */
	public function test_does_not_log_when_newly_blocked(): void {
		$fake_logger   = $this->create_fake_logger();
		$logger_filter = static function () use ( $fake_logger ) {
			return $fake_logger;
		};
		add_filter( 'woocommerce_logging_class', $logger_filter );

		try {
			ReservedAttributeNames::get_blocked_reserved_names( array( $this->create_custom_attribute( 'Variation' ) ) );

			$this->assertCount( 0, $fake_logger->warning_calls, 'A newly blocked name is rejected, not grandfathered, so nothing should be logged.' );
		} finally {
			remove_filter( 'woocommerce_logging_class', $logger_filter );
		}
	}

	/**
	 * Create a logger that records the warning calls made to it, for assertions.
	 *
	 * @return WC_Logger An anonymous WC_Logger subclass exposing a public $warning_calls array.
	 */
	private function create_fake_logger(): WC_Logger {
		return new class() extends WC_Logger {
			/**
			 * The recorded warning calls, each as an array with 'message' and 'context' keys.
			 *
			 * @var array
			 */
			public array $warning_calls = array();

			/**
			 * Record a warning call instead of logging it.
			 *
			 * @param string $message The log message.
			 * @param array  $context The log context.
			 */
			public function warning( $message, $context = array() ) {
				$this->warning_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}

	/**
	 * Build a custom (non-taxonomy) product attribute with the given name.
	 *
	 * @param string $name Attribute name.
	 * @return WC_Product_Attribute
	 */
	private function create_custom_attribute( string $name ): WC_Product_Attribute {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( $name );
		$attribute->set_options( array( 'Value' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		return $attribute;
	}
}
