<?php
/**
 * Testing WC_Widget functionality.
 *
 * @package WooCommerce\Tests\Widgets
 */

/**
 * Class for testing WC_Widget functionality.
 */
class WC_Tests_Widget extends WC_Unit_Test_Case {
	/**
	 * Test instance creation
	 *
	 * @return void
	 */
	public function test_instance() {
		require_once __DIR__ . '/class-dummy-widget.php';
		$dummy_widget = new Dummy_Widget();
		$this->assertTrue( property_exists( $dummy_widget, 'widget_id' ) );
	}

	/**
	 * Test widget caching.
	 *
	 * @return void
	 */
	public function test_caching() {
		global $wp_widget_factory;
		require_once __DIR__ . '/class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );

		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];

		// Uncached widget.
		ob_start();
		$cache_hit = $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) );
		$output    = ob_get_clean();
		$this->assertFalse( $cache_hit );
		$this->assertEmpty( $output );

		// Render widget to prime the cache.
		ob_start();
		$dummy_widget->widget( array( 'widget_id' => $dummy_widget->widget_id ), array() );
		ob_get_clean();

		// Cached widget.
		ob_start();
		$cache_hit = $dummy_widget->get_cached_widget( array( 'widget_id' => $dummy_widget->widget_id ) );
		$output    = ob_get_clean();
		$this->assertTrue( $cache_hit );
		$this->assertEquals( 'Dummy', $output );
	}

	/**
	 * Test widget form.
	 *
	 * @return void
	 */
	public function test_form() {
		global $wp_widget_factory;
		require_once __DIR__ . '/class-dummy-widget.php';
		register_widget( 'Dummy_Widget' );
		$dummy_widget = $wp_widget_factory->widgets['Dummy_Widget'];
		$this->assertEmpty( $dummy_widget->form( array( 'widget_id' => $dummy_widget->widget_id ) ) );
	}

	/**
	 * @testdox Should preserve extension-provided recently viewed cookie IDs in widget queries.
	 */
	public function test_recently_viewed_widget_query_preserves_extension_cookie_ids(): void {
		$viewed_product_ids = range( 1, 20 );
		$query_args         = array();
		$query_args_filter  = static function ( $args ) use ( &$query_args ) {
			$query_args = $args;

			return $args;
		};
		$cookies            = $_COOKIE; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserve global test cookie state.

		$_COOKIE['woocommerce_recently_viewed'] = implode( '|', $viewed_product_ids );
		add_filter( 'woocommerce_recently_viewed_products_widget_query_args', $query_args_filter );

		try {
			$sut = new WC_Widget_Recently_Viewed();

			ob_start();
			try {
				$sut->widget( array(), array() );
			} finally {
				ob_end_clean();
			}

			$this->assertSame(
				array_reverse( $viewed_product_ids ),
				$query_args['post__in'],
				'The recently viewed widget query should preserve every product ID provided through the shared cookie.'
			);
		} finally {
			remove_filter( 'woocommerce_recently_viewed_products_widget_query_args', $query_args_filter );

			$_COOKIE = $cookies;
		}
	}

	/**
	 * @testdox Should render widget form labels through wp_kses_post for every setting type.
	 */
	public function test_form_renders_setting_labels_through_kses(): void {
		$sut = new class() extends WC_Widget {
			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->widget_cssclass    = 'widget_label_dummy';
				$this->widget_description = 'Label rendering widget';
				$this->widget_id          = 'wc_label_dummy_widget';
				$this->widget_name        = 'Label Dummy Widget';
				$this->settings           = array(
					'text_field'     => array(
						'type'  => 'text',
						'std'   => '',
						'label' => 'Plain text label',
					),
					'number_field'   => array(
						'type'  => 'number',
						'std'   => 1,
						'step'  => 1,
						'min'   => 1,
						'max'   => 10,
						'label' => 'Number label <strong>emphasised</strong>',
					),
					'select_field'   => array(
						'type'    => 'select',
						'std'     => 'a',
						'options' => array( 'a' => 'A' ),
						'label'   => 'Select label',
					),
					'textarea_field' => array(
						'type'  => 'textarea',
						'std'   => '',
						'label' => 'Textarea label<script>alert(1)</script>',
					),
					'checkbox_field' => array(
						'type'  => 'checkbox',
						'std'   => 0,
						'label' => 'Checkbox label',
					),
				);
				parent::__construct();
			}

			/**
			 * Output widget.
			 *
			 * @param array $args     Arguments.
			 * @param array $instance Instance.
			 */
			public function widget( $args, $instance ) {}
		};

		ob_start();
		try {
			$sut->form( array() );
			$output = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		foreach ( array( 'Plain text label', 'Number label', 'Select label', 'Textarea label', 'Checkbox label' ) as $label ) {
			$this->assertStringContainsString( $label, $output, 'Every setting label should still be rendered in the widget form.' );
		}

		$this->assertStringContainsString(
			'<strong>emphasised</strong>',
			$output,
			'Markup a post KSES filter allows should survive in a setting label.'
		);
		$this->assertStringNotContainsString(
			'<script>',
			$output,
			'Markup a post KSES filter disallows should be stripped from a setting label.'
		);
	}
}
