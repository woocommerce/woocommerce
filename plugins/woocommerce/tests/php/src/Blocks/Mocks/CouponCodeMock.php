<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

use Automattic\WooCommerce\Blocks\BlockTypes\CouponCode;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

/**
 * CouponCodeMock used to test CouponCode block functions.
 */
class CouponCodeMock extends CouponCode {

	/**
	 * Initialize our mock class.
	 */
	public function __construct() {
		parent::__construct(
			Package::container()->get( Api::class ),
			Package::container()->get( AssetDataRegistry::class ),
			new IntegrationRegistry()
		);
	}

	/**
	 * Public wrapper for the render method.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content Block content.
	 * @param \WP_Block|null $block Block instance.
	 * @return string
	 */
	public function call_render( array $attributes, string $content = '', $block = null ): string {
		return $this->render( $attributes, $content, $block );
	}

	/**
	 * Public wrapper for the build_coupon_html method via reflection.
	 *
	 * @param string            $coupon_code Coupon code text.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	public function call_build_coupon_html( string $coupon_code, array $attributes, Rendering_Context $rendering_context ): string {
		$reflection = new \ReflectionClass( $this );
		$method     = $reflection->getMethod( 'build_coupon_html' );
		$method->setAccessible( true );

		return $method->invoke( $this, $coupon_code, $attributes, $rendering_context );
	}

	/**
	 * Public wrapper for the is_css_color_value method via reflection.
	 *
	 * @param string $value Value to check.
	 * @return bool
	 */
	public function call_is_css_color_value( string $value ): bool {
		$reflection = new \ReflectionClass( $this );
		$method     = $reflection->getMethod( 'is_css_color_value' );
		$method->setAccessible( true );

		return $method->invoke( $this, $value );
	}

	/**
	 * Public wrapper for the get_alignment method via reflection.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return string
	 */
	public function call_get_alignment( array $parsed_block ): string {
		$reflection = new \ReflectionClass( $this );
		$method     = $reflection->getMethod( 'get_alignment' );
		$method->setAccessible( true );

		return $method->invoke( $this, $parsed_block );
	}
}
