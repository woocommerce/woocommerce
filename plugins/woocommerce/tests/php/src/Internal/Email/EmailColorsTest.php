<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\EmailColors;
use WC_Unit_Test_Case;

/**
 * EmailColors test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\EmailColors
 */
class EmailColorsTest extends WC_Unit_Test_Case {

	/**
	 * Test get_default_colors when email improvements are disabled.
	 */
	public function test_get_default_colors_email_improvements_disabled() {
		$result = EmailColors::get_default_colors( false );

		$expected = array(
			'base'        => '#720eec',
			'bg'          => '#f7f7f7',
			'body_bg'     => '#ffffff',
			'body_text'   => '#3c3c3c',
			'footer_text' => '#3c3c3c',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_default_colors when email improvements are enabled but global styles are not available.
	 */
	public function test_get_default_colors_email_improvements_enabled_no_global_styles() {
		// Create a test class that overrides the get_colors_from_global_styles method.
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_colors_from_global_styles to return null.
			 *
			 * @return null
			 */
			public static function get_colors_from_global_styles() {
				return null;
			}
		};

		$result = $test_class::get_default_colors( true );

		$expected = array(
			'base'        => '#8526ff',
			'bg'          => '#ffffff',
			'body_bg'     => '#ffffff',
			'body_text'   => '#1e1e1e',
			'footer_text' => '#787c82',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_default_colors when email improvements are enabled and global styles are available.
	 */
	public function test_get_default_colors_email_improvements_enabled_with_global_styles() {
		$global_colors = array(
			'base'        => '#ff0000',
			'bg'          => '#f0f0f0',
			'body_bg'     => '#ffffff',
			'body_text'   => '#000000',
			'footer_text' => '#666666',
		);

		$test_class = new class() extends EmailColors {
			/**
			 * Override get_colors_from_global_styles to return test colors.
			 *
			 * @return array
			 */
			public static function get_colors_from_global_styles() {
				return array(
					'base'        => '#ff0000',
					'bg'          => '#f0f0f0',
					'body_bg'     => '#ffffff',
					'body_text'   => '#000000',
					'footer_text' => '#666666',
				);
			}
		};

		$result = $test_class::get_default_colors( true );

		$this->assertEquals( $global_colors, $result );
	}

	/**
	 * Test get_colors_from_global_styles when not a block theme or wp_get_global_styles function doesn't exist.
	 */
	public function test_get_colors_from_global_styles_not_block_theme() {
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_global_styles_data to return null.
			 *
			 * @return null
			 */
			public static function get_global_styles_data() {
				return null;
			}
		};

		$result = $test_class::get_colors_from_global_styles();

		$this->assertEquals( null, $result );
	}

	/**
	 * Test get_colors_from_global_styles with complete global styles.
	 */
	public function test_get_colors_from_global_styles_complete_styles() {
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_global_styles_data to return test styles.
			 *
			 * @return array
			 */
			public static function get_global_styles_data() {
				return array(
					'color'    => array(
						'background' => '#ffffff',
						'text'       => '#000000',
					),
					'elements' => array(
						'button'  => array(
							'color' => array(
								'background' => '#8526ff',
							),
						),
						'caption' => array(
							'color' => array(
								'text' => '#666666',
							),
						),
					),
				);
			}
		};

		$result = $test_class::get_colors_from_global_styles();

		$expected = array(
			'base'        => '#8526ff',
			'bg'          => '#ffffff',
			'body_bg'     => '#ffffff',
			'body_text'   => '#000000',
			'footer_text' => '#666666',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_colors_from_global_styles with incomplete global styles (missing button color).
	 */
	public function test_get_colors_from_global_styles_incomplete_styles_missing_button() {
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_global_styles_data to return incomplete styles.
			 *
			 * @return array
			 */
			public static function get_global_styles_data() {
				return array(
					'color'    => array(
						'background' => '#ffffff',
						'text'       => '#000000',
					),
					'elements' => array(
						'caption' => array(
							'color' => array(
								'text' => '#666666',
							),
						),
					),
				);
			}
		};

		$result = $test_class::get_colors_from_global_styles();

		$expected = array(
			'base'        => '#000000', // Should fallback to body_text.
			'bg'          => '#ffffff',
			'body_bg'     => '#ffffff',
			'body_text'   => '#000000',
			'footer_text' => '#666666',
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_colors_from_global_styles with incomplete global styles (missing footer text).
	 */
	public function test_get_colors_from_global_styles_incomplete_styles_missing_footer() {
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_global_styles_data to return incomplete styles.
			 *
			 * @return array
			 */
			public static function get_global_styles_data() {
				return array(
					'color'    => array(
						'background' => '#ffffff',
						'text'       => '#000000',
					),
					'elements' => array(
						'button' => array(
							'color' => array(
								'background' => '#8526ff',
							),
						),
					),
				);
			}
		};

		$result = $test_class::get_colors_from_global_styles();

		$expected = array(
			'base'        => '#8526ff',
			'bg'          => '#ffffff',
			'body_bg'     => '#ffffff',
			'body_text'   => '#000000',
			'footer_text' => '#000000', // Should fallback to body_text.
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_colors_from_global_styles returns null when required colors are missing.
	 */
	public function test_get_colors_from_global_styles_returns_null_when_colors_missing() {
		$test_class = new class() extends EmailColors {
			/**
			 * Override get_global_styles_data to return incomplete styles.
			 *
			 * @return array
			 */
			public static function get_global_styles_data() {
				return array(
					'color'    => array(
						'background' => '#ffffff',
						// Missing text color.
					),
					'elements' => array(),
				);
			}
		};

		$result = $test_class::get_colors_from_global_styles();

		$this->assertEquals( null, $result );
	}
}
