<?php
declare( strict_types = 1 );

/**
 * Tests for the woocommerce_template_args filter.
 *
 * @package WooCommerce\\Tests\\Includes
 */
class WC_Template_Args_Filter_Test extends WC_Unit_Test_Case {

	/**
	 * Filtered arguments reach the template and lifecycle hooks.
	 */
	public function test_filter_updates_arguments_before_rendering(): void {
		$seen        = array();
		$action_args = array();
		$filter      = static function ( $args, $name, $path ) use ( &$seen ) {
			$seen                = array( $name, $path );
			$args['user_login'] = $args['user_email'];
			return $args;
		};
		$action = static function ( $name, $path, $located, $args ) use ( &$action_args ) {
			$action_args = array( $name, $path, $located, $args );
		};

		add_filter( 'woocommerce_template_args', $filter, 10, 3 );
		add_action( 'woocommerce_before_template_part', $action, 10, 4 );

		$file = tempnam( sys_get_temp_dir(), 'wc-template-' );
		try {
			file_put_contents( $file, '<?php echo esc_html( $user_login );' );
			$template_name = basename( $file );
			$template_path = 'sample-path/';
			$output        = wc_get_template_html(
				$template_name,
				array(
					'user_login' => 'external-id-123',
					'user_email' => 'customer@example.com',
				),
				$template_path,
				trailingslashit( dirname( $file ) )
			);

			$this->assertSame( 'customer@example.com', $output );
			$this->assertSame( array( $template_name, $template_path ), $seen );
			$this->assertSame( 'customer@example.com', $action_args[3]['user_login'] );
		} finally {
			unlink( $file );
			remove_filter( 'woocommerce_template_args', $filter, 10 );
			remove_action( 'woocommerce_before_template_part', $action, 10 );
		}
	}

	/**
	 * Templates still render unchanged arguments when no filter is present.
	 */
	public function test_unfiltered_template_arguments_are_unchanged(): void {
		$file = tempnam( sys_get_temp_dir(), 'wc-template-' );
		try {
			file_put_contents( $file, '<?php echo esc_html( $value );' );
			$this->assertSame(
				'original',
				wc_get_template_html(
					basename( $file ),
					array( 'value' => 'original' ),
					'',
					trailingslashit( dirname( $file ) )
				)
			);
		} finally {
			unlink( $file );
		}
	}
}
