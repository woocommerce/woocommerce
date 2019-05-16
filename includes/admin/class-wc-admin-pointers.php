<?php
/**
 * Adds and controls pointers for contextual help/tutorials
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Pointers Class.
 */
class WC_Admin_Pointers {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_pointers_for_screen' ) );
	}

	/**
	 * Setup pointers for screen.
	 */
	public function setup_pointers_for_screen() {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		switch ( $screen->id ) {
			case 'product':
				$this->create_product_tutorial();
				break;
		}
	}

	/**
	 * Pointers for creating a product.
	 */
	public function create_product_tutorial() {
		if ( ! isset( $_GET['tutorial'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// These pointers will chain - they will not be shown at once.
		$pointers = array(
			'pointers' => array(
				'title'          => array(
					'target'       => '#title',
					'next'         => 'content',
					'next_trigger' => array(
						'target' => '#title',
						'event'  => 'input',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Product name', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Give your new product a name here. This is a required field and will be what your customers will see in your store.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'top',
							'align' => 'left',
						),
					),
				),
				'content'        => array(
					'target'       => '#wp-content-editor-container',
					'next'         => 'product-type',
					'next_trigger' => array(),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Product description', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'This is your products main body of content. Here you should describe your product in detail.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'product-type'   => array(
					'target'       => '#product-type',
					'next'         => 'virtual',
					'next_trigger' => array(
						'target' => '#product-type',
						'event'  => 'change blur click',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Choose product type', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Choose a type for this product. Simple is suitable for most physical goods and services (we recommend setting up a simple product for now).', 'woocommerce' ) . '</p>' .
										'<p>' . esc_html__( 'Variable is for more complex products such as t-shirts with multiple sizes.', 'woocommerce' ) . '</p>' .
										'<p>' . esc_html__( 'Grouped products are for grouping several simple products into one.', 'woocommerce' ) . '</p>' .
										'<p>' . esc_html__( 'Finally, external products are for linking off-site.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'virtual'        => array(
					'target'       => '#_virtual',
					'next'         => 'downloadable',
					'next_trigger' => array(
						'target' => '#_virtual',
						'event'  => 'change',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Virtual products', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Check the "Virtual" box if this is a non-physical item, for example a service, which does not need shipping.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'downloadable'   => array(
					'target'       => '#_downloadable',
					'next'         => 'regular_price',
					'next_trigger' => array(
						'target' => '#_downloadable',
						'event'  => 'change',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Downloadable products', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'If purchasing this product gives a customer access to a downloadable file, e.g. software, check this box.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'regular_price'  => array(
					'target'       => '#_regular_price',
					'next'         => 'postexcerpt',
					'next_trigger' => array(
						'target' => '#_regular_price',
						'event'  => 'input',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Prices', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Next you need to give your product a price.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'postexcerpt'    => array(
					'target'       => '#postexcerpt',
					'next'         => 'postimagediv',
					'next_trigger' => array(
						'target' => '#postexcerpt',
						'event'  => 'input',
					),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Product short description', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Add a quick summary for your product here. This will appear on the product page under the product name.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'middle',
						),
					),
				),
				'postimagediv'   => array(
					'target'  => '#postimagediv',
					'next'    => 'product_tag',
					'options' => array(
						'content'  => '<h3>' . esc_html__( 'Product images', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( "Upload or assign an image to your product here. This image will be shown in your store's catalog.", 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'right',
							'align' => 'middle',
						),
					),
				),
				'product_tag'    => array(
					'target'  => '#tagsdiv-product_tag',
					'next'    => 'product_catdiv',
					'options' => array(
						'content'  => '<h3>' . esc_html__( 'Product tags', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'You can optionally "tag" your products here. Tags are a method of labeling your products to make them easier for customers to find.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'right',
							'align' => 'middle',
						),
					),
				),
				'product_catdiv' => array(
					'target'  => '#product_catdiv',
					'next'    => 'submitdiv',
					'options' => array(
						'content'  => '<h3>' . esc_html__( 'Product categories', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'Optionally assign categories to your products to make them easier to browse through and find in your store.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'right',
							'align' => 'middle',
						),
					),
				),
				'submitdiv'      => array(
					'target'  => '#submitdiv',
					'next'    => '',
					'options' => array(
						'content'  => '<h3>' . esc_html__( 'Publish your product!', 'woocommerce' ) . '</h3>' .
										'<p>' . esc_html__( 'When you are finished editing your product, hit the "Publish" button to publish your product to your store.', 'woocommerce' ) . '</p>',
						'position' => array(
							'edge'  => 'right',
							'align' => 'middle',
						),
					),
				),
			),
		);

		$this->enqueue_pointers( $pointers );
	}

	/**
	 * Enqueue pointers and add script to page.
	 *
	 * @param array $pointers
	 */
	public function enqueue_pointers( $pointers ) {
		$pointers = rawurlencode( wp_json_encode( $pointers ) );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wc_enqueue_js(
			"jQuery( function( $ ) {
				var wc_pointers = JSON.parse( decodeURIComponent( '{$pointers}' ) );

				setTimeout( init_wc_pointers, 800 );

				function init_wc_pointers() {
					$.each( wc_pointers.pointers, function( i ) {
						show_wc_pointer( i );
						return false;
					});
				}

				function show_wc_pointer( id ) {
					var pointer = wc_pointers.pointers[ id ];
					var options = $.extend( pointer.options, {
						pointerClass: 'wp-pointer wc-pointer',
						close: function() {
							if ( pointer.next ) {
								show_wc_pointer( pointer.next );
							}
						},
						buttons: function( event, t ) {
							var close   = '" . esc_js( __( 'Dismiss', 'woocommerce' ) ) . "',
								next    = '" . esc_js( __( 'Next', 'woocommerce' ) ) . "',
								button  = $( '<a class=\"close\" href=\"#\">' + close + '</a>' ),
								button2 = $( '<a class=\"button button-primary\" href=\"#\">' + next + '</a>' ),
								wrapper = $( '<div class=\"wc-pointer-buttons\" />' );

							button.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('destroy');
							});

							button2.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('close');
							});

							wrapper.append( button );
							wrapper.append( button2 );

							return wrapper;
						},
					} );
					var this_pointer = $( pointer.target ).pointer( options );
					this_pointer.pointer( 'open' );

					if ( pointer.next_trigger ) {
						$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
							setTimeout( function() { this_pointer.pointer( 'close' ); }, 400 );
						});
					}
				}
			});"
		);
	}
}

new WC_Admin_Pointers();
