<?php
/**
 * Adds and controls pointers for contextual help/tutorials
 *
 * @package WooCommerce\Admin\Pointers
 * @version 2.4.0
 */

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Admin\Features\Features;

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
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'product':
				$this->create_product_tutorial();
				break;
		}
	}

	/**
	 * Check if product tour experiment is treatment.
	 *
	 * @return bool
	 */
	public static function is_experiment_product_tour() {
		$anon_id        = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : '';
		$allow_tracking = 'yes' === get_option( 'woocommerce_allow_tracking' );
		$abtest         = new \WooCommerce\Admin\Experimental_Abtest(
			$anon_id,
			'woocommerce',
			$allow_tracking
		);
		return $abtest->get_variation( 'woocommerce_products_tour' ) === 'treatment';
	}

	/**
	 * Pointers for creating a product.
	 */
	public function create_product_tutorial() {
		if ( ! isset( $_GET['tutorial'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		global $wp_post_types;

		if (
			Features::is_enabled( 'experimental-product-tour' ) &&
			isset( $_GET['spotlight'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			isset( $wp_post_types ) &&
			self::is_experiment_product_tour()
		) {
			$labels          = $wp_post_types['product']->labels;
			$labels->add_new = __( 'Enable guided mode', 'woocommerce' );

			WCAdminAssets::register_script( 'wp-admin-scripts', 'product-tour', true );
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
						'step_name' => 'old-product-name',
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
						'step_name' => 'old-product-description',
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
						'step_name' => 'old-product-type',
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
						'step_name' => 'old-virtual-product',
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
						'step_name' => 'old-downloadable-product',
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
						'step_name' => 'old-product-price',
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
						'step_name' => 'old-product-short-description',
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
						'step_name' => 'old-product-image',
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
						'step_name' => 'old-product-tags',
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
						'step_name' => 'old-product-categories',
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
						'step_name' => 'old-publish',
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
	 * @param array $pointers Pointers data.
	 */
	public function enqueue_pointers( $pointers ) {
		$pointers = rawurlencode( wp_json_encode( $pointers ) );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wc_enqueue_js(
			"jQuery( function( $ ) {
				var wc_pointers = JSON.parse( decodeURIComponent( '{$pointers}' ) );
				var current_pointer;
				const recordEvent =
					window.wc.tracks.recordEvent || window.wcTracks.recordEvent || function() {};
				const publishButton = $( '#publish' );

				setTimeout( init_wc_pointers, 800 );

				// Records completion or dismiss if publish button is clicked.
				function onPublish() {
					if ( current_pointer && current_pointer.options.step_name === 'old-publish' ) {
						recordEvent( 'walkthrough_product_completed' );
					} else if ( current_pointer ) {
						recordEvent( 'walkthrough_product_dismissed', { step_name: current_pointer.options.step_name } );
					}
				}

				function init_wc_pointers() {
					$.each( wc_pointers.pointers, function( i ) {
						show_wc_pointer( i );
						return false;
					});

					recordEvent( 'walkthrough_product_view', {
						spotlight: 'no',
						product_template: 'physical',
					} );

					publishButton.on( 'click', onPublish );
				}

				function show_wc_pointer( id ) {
					var pointer = wc_pointers.pointers[ id ];
					current_pointer = pointer;
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

							button.on( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('destroy');
								publishButton.off( 'click', onPublish );
								// Tracks completion if it's the last step, otherwise track as dismiss.
								if ( pointer.next ) {
									recordEvent( 'walkthrough_product_dismissed', { step_name: pointer.options.step_name } );
								} else {
									recordEvent( 'walkthrough_product_completed' );
								}
							});

							button2.on( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('close');

								if ( !pointer.next ) {
									recordEvent( 'walkthrough_product_completed' );
								}
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
