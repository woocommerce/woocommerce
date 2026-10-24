<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Templates\AbstractTemplateCompatibility;
use WP_UnitTestCase;

/**
 * Tests for AbstractTemplateCompatibility::set_compatibility_layer_flag().
 */
class AbstractTemplateCompatibilityTest extends WP_UnitTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var AbstractTemplateCompatibility
	 */
	private $sut;

	/**
	 * @inheritdoc
	 */
	public function tearDown(): void {
		$this->remove_compatibility_layer_filters();
		parent::tearDown();
	}

	/**
	 * @testdox Disables the compatibility layer when the template has a legacy template block.
	 */
	public function test_disables_compatibility_layer_when_template_has_legacy_block(): void {
		$this->sut = $this->create_sut( true );

		$this->sut->set_compatibility_layer_flag();

		$this->assertTrue(
			$this->is_compatibility_layer_disabled(),
			'Legacy templates should disable the compatibility layer for subsequent checks.'
		);
	}

	/**
	 * @testdox Keeps the compatibility layer enabled when the template is blockified.
	 */
	public function test_keeps_compatibility_layer_enabled_when_template_is_blockified(): void {
		$this->sut = $this->create_sut( false );

		$this->sut->set_compatibility_layer_flag();

		$this->assertFalse(
			$this->is_compatibility_layer_disabled(),
			'Blockified templates should keep the compatibility layer enabled by default.'
		);
	}

	/**
	 * @testdox Passes legacy detection as the filter default value.
	 */
	public function test_filter_receives_legacy_detection_as_default(): void {
		$received_default = null;

		add_filter(
			'woocommerce_disable_compatibility_layer',
			function ( $should_disable ) use ( &$received_default ) {
				$received_default = $should_disable;

				return $should_disable;
			},
			10,
			1
		);

		$this->sut = $this->create_sut( true );
		$this->sut->set_compatibility_layer_flag();

		$this->assertTrue( $received_default, 'Filter default should reflect legacy template detection.' );
	}

	/**
	 * @testdox Keeps the compatibility layer enabled when an extension overrides a legacy template default to false.
	 */
	public function test_keeps_compatibility_layer_enabled_when_extension_overrides_legacy_default_to_false(): void {
		add_filter( 'woocommerce_disable_compatibility_layer', '__return_false' );

		$this->sut = $this->create_sut( true );
		$this->sut->set_compatibility_layer_flag();

		$this->assertFalse(
			$this->is_compatibility_layer_disabled(),
			'Extensions should be able to keep the compatibility layer enabled on legacy templates.'
		);
	}

	/**
	 * @testdox Disables the compatibility layer when an extension overrides a blockified template default to true.
	 */
	public function test_disables_compatibility_layer_when_extension_overrides_blockified_default_to_true(): void {
		add_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );

		$this->sut = $this->create_sut( false );
		$this->sut->set_compatibility_layer_flag();

		$this->assertTrue(
			$this->is_compatibility_layer_disabled(),
			'Extensions should be able to disable the compatibility layer on blockified templates.'
		);
	}

	/**
	 * Applies the compatibility layer filter the same way render callbacks do.
	 *
	 * @return bool
	 */
	private function is_compatibility_layer_disabled(): bool {
		/**
		 * Filter to disable the compatibility layer for the blockified templates.
		 *
		 * @since 7.6.0
		 * @param bool $is_disabled_compatibility_layer Whether the compatibility layer should be disabled.
		 */
		return apply_filters( 'woocommerce_disable_compatibility_layer', false );
	}

	/**
	 * Creates a test double with a fixed legacy-template detection result.
	 *
	 * @param bool $has_legacy_template_block Legacy template detection result to return.
	 * @return AbstractTemplateCompatibility
	 */
	private function create_sut( bool $has_legacy_template_block ): AbstractTemplateCompatibility {
		return new class( $has_legacy_template_block ) extends AbstractTemplateCompatibility {

			/**
			 * Whether the current template is detected as having a legacy template block.
			 *
			 * @var bool
			 */
			private $has_legacy_template_block;

			/**
			 * @param bool $has_legacy_template_block Legacy template detection result to return.
			 */
			public function __construct( bool $has_legacy_template_block ) {
				$this->has_legacy_template_block = $has_legacy_template_block;
			}

			/**
			 * @inheritdoc
			 */
			public function current_template_has_legacy_template_block() {
				return $this->has_legacy_template_block;
			}

			/**
			 * @param array       $parsed_block  The block being rendered.
			 * @param array       $source_block  An un-modified copy of the parsed block.
			 * @param object|null $parent_block  Parent block, if any.
			 * @return array
			 */
			public function update_render_block_data( $parsed_block, $source_block, $parent_block ) {
				unset( $source_block, $parent_block );

				return $parsed_block;
			}

			/**
			 * @param mixed $block_content The rendered block content.
			 * @param mixed $block         The parsed block data.
			 * @return string
			 */
			public function inject_hooks( $block_content, $block ) {
				unset( $block );

				return $block_content;
			}

			/**
			 * @inheritdoc
			 */
			protected function set_hook_data() {
				$this->hook_data = array();
			}
		};
	}

	/**
	 * Removes filters registered during tests.
	 */
	private function remove_compatibility_layer_filters(): void {
		remove_filter( 'woocommerce_disable_compatibility_layer', '__return_true' );
		remove_filter( 'woocommerce_disable_compatibility_layer', '__return_false' );
		remove_all_filters( 'woocommerce_disable_compatibility_layer' );
	}
}
