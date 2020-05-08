<?php


namespace Automattic\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Class that handles the product loop.
 *
 * @package Automattic\WooCommerce
 */
class Loop {
	/**
	 * @var Loop Holds the only existing instance of the class.
	 */
	private static $instance = null;

	/**
	 * @return Loop Get the only existing instance of the class.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_before_shop_loop', array( $this, 'setup_loop' ) );
		add_action( 'woocommerce_after_shop_loop', array( $this, 'reset_loop' ), 999 );
	}

	/**
	 * Set the value of the only existing instance of the class. Intended for unit tests only.
	 *
	 * @param Loop $instance Instance to set.
	 *
	 * @throws \Exception Method invoked outside a unit testing session.
	 */
	public static function set_instance( $instance ) {
		if ( ! defined( 'RUNNING_UNIT_TESTS' ) ) {
			throw new \Exception( 'set_instance can be used only when running unit tests.' );
		}
		self::$instance = $instance;
	}

	/**
	 * Sets up the woocommerce_loop global from the passed args or from the main query.
	 *
	 * @since 3.3.0
	 * @param array $args Args to pass into the global.
	 */
	public function setup_loop( $args = array() ) {
		$default_args = array(
			'loop'         => 0,
			'columns'      => wc_get_default_products_per_row(),
			'name'         => '',
			'is_shortcode' => false,
			'is_paginated' => true,
			'is_search'    => false,
			'is_filtered'  => false,
			'total'        => 0,
			'total_pages'  => 0,
			'per_page'     => 0,
			'current_page' => 1,
		);

		// If this is a main WC query, use global args as defaults.
		if ( $GLOBALS['wp_query']->get( 'wc_query' ) ) {
			$default_args = array_merge(
				$default_args,
				array(
					'is_search'    => $GLOBALS['wp_query']->is_search(),
					'is_filtered'  => is_filtered(),
					'total'        => $GLOBALS['wp_query']->found_posts,
					'total_pages'  => $GLOBALS['wp_query']->max_num_pages,
					'per_page'     => $GLOBALS['wp_query']->get( 'posts_per_page' ),
					'current_page' => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
				)
			);
		}

		// Merge any existing values.
		if ( isset( $GLOBALS['woocommerce_loop'] ) ) {
			$default_args = array_merge( $default_args, $GLOBALS['woocommerce_loop'] );
		}

		$GLOBALS['woocommerce_loop'] = wp_parse_args( $args, $default_args );
	}

	/**
	 * Resets the woocommerce_loop global.
	 *
	 * @since 3.3.0
	 */
	public function reset_loop() {
		unset( $GLOBALS['woocommerce_loop'] );
	}

	/**
	 * Gets a property from the woocommerce_loop global.
	 *
	 * @since 3.3.0
	 * @param string $prop Prop to get.
	 * @param string $default Default if the prop does not exist.
	 * @return mixed
	 */
	public function get_loop_prop( $prop, $default = '' ) {
		$this->setup_loop(); // Ensure shop loop is setup.

		return isset( $GLOBALS['woocommerce_loop'], $GLOBALS['woocommerce_loop'][ $prop ] ) ? $GLOBALS['woocommerce_loop'][ $prop ] : $default;
	}

	/**
	 * Sets a property in the woocommerce_loop global.
	 *
	 * @since 3.3.0
	 * @param string $prop Prop to set.
	 * @param string $value Value to set.
	 */
	public function set_loop_prop( $prop, $value = '' ) {
		if ( ! isset( $GLOBALS['woocommerce_loop'] ) ) {
			$this->setup_loop();
		}
		$GLOBALS['woocommerce_loop'][ $prop ] = $value;
	}

	/**
	 * See what is going to display in the loop.
	 *
	 * @since 3.3.0
	 * @return string Either products, subcategories, or both, based on current page.
	 */
	public function get_loop_display_mode() {
		// Only return products when filtering things.
		if ( $this->get_loop_prop( 'is_search' ) || $this->get_loop_prop( 'is_filtered' ) ) {
			return 'products';
		}

		$parent_id    = 0;
		$display_type = '';

		if ( is_shop() ) {
			$display_type = get_option( 'woocommerce_shop_page_display', '' );
		} elseif ( is_product_category() ) {
			$parent_id    = get_queried_object_id();
			$display_type = get_term_meta( $parent_id, 'display_type', true );
			$display_type = '' === $display_type ? get_option( 'woocommerce_category_archive_display', '' ) : $display_type;
		}

		if ( ( ! is_shop() || 'subcategories' !== $display_type ) && 1 < wc_get_loop_prop( 'current_page' ) ) {
			return 'products';
		}

		// Ensure valid value.
		if ( '' === $display_type || ! in_array( $display_type, array( 'products', 'subcategories', 'both' ), true ) ) {
			$display_type = 'products';
		}

		// If we're showing categories, ensure we actually have something to show.
		if ( in_array( $display_type, array( 'subcategories', 'both' ), true ) ) {
			$subcategories = woocommerce_get_product_subcategories( $parent_id );

			if ( empty( $subcategories ) ) {
				$display_type = 'products';
			}
		}

		return $display_type;
	}

	/**
	 * Should the WooCommerce loop be displayed?
	 *
	 * This will return true if we have posts (products) or if we have subcats to display.
	 *
	 * @since 3.4.0
	 * @return bool
	 */
	public function in_product_loop() {
		return have_posts() || 'products' !== $this->get_loop_display_mode();
	}

	/**
	 * Get classname for woocommerce loops.
	 *
	 * @since 2.6.0
	 * @return string
	 * @deprecated Use get_loop_class in Loop class instead.
	 */
	public function get_loop_class() {
		$loop_index = $this->get_loop_prop( 'loop', 0 );
		$columns    = absint( max( 1, wc_get_loop_prop( 'columns', wc_get_default_products_per_row() ) ) );

		$loop_index ++;
		$this->set_loop_prop( 'loop', $loop_index );

		if ( 0 === ( $loop_index - 1 ) % $columns || 1 === $columns ) {
			return 'first';
		}

		if ( 0 === $loop_index % $columns ) {
			return 'last';
		}

		return '';
	}

	/**
	 * Output the start of a product loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	public function product_loop_start( $echo = true ) {
		ob_start();

		$this->set_loop_prop( 'loop', 0 );

		wc_get_template( 'loop/loop-start.php' );

		$loop_start = apply_filters( 'woocommerce_product_loop_start', ob_get_clean() );

		if ( $echo ) {
			echo $loop_start; // WPCS: XSS ok.
		} else {
			return $loop_start;
		}
	}

	/**
	 * Output the end of a product loop. By default this is a UL.
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	public function product_loop_end( $echo = true ) {
		ob_start();

		wc_get_template( 'loop/loop-end.php' );

		$loop_end = apply_filters( 'woocommerce_product_loop_end', ob_get_clean() );

		if ( $echo ) {
			echo $loop_end; // WPCS: XSS ok.
		} else {
			return $loop_end;
		}
	}
}
