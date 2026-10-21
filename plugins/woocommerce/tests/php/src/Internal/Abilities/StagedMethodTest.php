<?php
/**
 * StagedMethodTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\StagedMethod;
use Automattic\WooCommerce\Tests\Internal\AbilitiesApi\TestStagingOrder;

/**
 * Tests for StagedMethod with update_status() and stage_status().
 */
class StagedMethodTest extends \WC_Unit_Test_Case {

	/**
	 * The pair under test.
	 *
	 * @var StagedMethod
	 */
	private StagedMethod $sut;

	/**
	 * Set up the pair.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new StagedMethod( 'update_status', 'stage_status' );
	}

	/**
	 * @testdox A plain WC_Order can stage the change.
	 */
	public function test_plain_order_is_supported(): void {
		$this->assertTrue( $this->sut->supports( new \WC_Order() ) );
	}

	/**
	 * @testdox A class that overrides only update_status() cannot stage the change.
	 */
	public function test_class_overriding_only_the_method_is_unsupported(): void {
		$order = new class() extends \WC_Order {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
			public function update_status( $new_status, $note = '', $manual = false ) {
				return parent::update_status( $new_status, $note, $manual );
			}
		};

		$this->assertFalse( $this->sut->supports( $order ) );
	}

	/**
	 * @testdox A class that overrides update_status() and stage_status() can stage the change.
	 */
	public function test_class_overriding_both_methods_is_supported(): void {
		$order = new class() extends \WC_Order {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
			public function update_status( $new_status, $note = '', $manual = false ) {
				return parent::update_status( $new_status, $note, $manual );
			}

			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
			public function stage_status( $new_status, $note = '', $manual_update = false ) {
				return parent::stage_status( $new_status, $note, $manual_update );
			}
		};

		$this->assertTrue( $this->sut->supports( $order ) );
	}

	/**
	 * @testdox A subclass of an adopter that overrides update_status() again cannot stage the change.
	 */
	public function test_subclass_of_adopter_overriding_the_method_again_is_unsupported(): void {
		$order = new class() extends TestStagingOrder {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
			public function update_status( $new_status, $note = '', $manual_update = false ) {
				return parent::update_status( $new_status, $note, $manual_update );
			}
		};

		$this->assertTrue( $this->sut->supports( new TestStagingOrder() ) );
		$this->assertFalse( $this->sut->supports( $order ) );
	}

	/**
	 * @testdox An object without the stage method cannot stage the change.
	 */
	public function test_object_without_the_stage_method_is_unsupported(): void {
		$subject = new class() {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function update_status(): bool {
				return true;
			}
		};

		$this->assertFalse( $this->sut->supports( $subject ) );
	}

	/**
	 * @testdox The unsupported error carries the code and message the ability passes, with status 400.
	 */
	public function test_unsupported_error(): void {
		$error = $this->sut->unsupported_error( 'test_code', 'Test message.' );

		$this->assertSame( 'test_code', $error->get_error_code() );
		$this->assertSame( 'Test message.', $error->get_error_message() );
		$this->assertSame( array( 'status' => 400 ), $error->get_error_data() );
	}
}
