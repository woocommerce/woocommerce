<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Embed Class which handles any WooCommerce Products that are embedded on this site or another site
 *
 * @class       WC_Embed
 * @version     2.4.11
 * @package     WooCommerce/Classes/Embed
 * @category    Class
 * @author      WooThemes
 */
class WC_Embed {

	/**
	 * Init embed class.
	 *
     * @since 2.4.11
	 */
	public static function init() {

        // filter all of the content that's going to be embedded
        add_filter( 'the_title', array( __CLASS__, 'the_title' ), 10 );
        add_filter( 'the_excerpt_embed', array( __CLASS__, 'the_excerpt' ), 10 );

        // make sure no comments display. Doesn't make sense for products
        remove_action( 'embed_content_meta', 'print_embed_comments_button' );

        // in the comments place let's display the product rating
        add_action( 'embed_content_meta', array( __CLASS__, 'get_ratings' ), 5 );

		// Add some basic styles
		add_action( 'embed_head', array( __CLASS__, 'print_embed_styles' ) );
	}

    /**
     * Create the title for embedded products - we want to add the price to it
     *
     * @return string
     * @since 2.4.11
     */
    public static function the_title( $title ) {
        // make sure we're only affecting embedded products
        if ( self::is_embedded_product() ) {

            // get product
            $_product = wc_get_product( get_the_ID() );

            // add the price
            $title = $title . '<span class="wc-embed-price">' . $_product->get_price_html() . '</span>';
        }
        return $title;
    }

    /**
     * Check if this is an embedded product - to make sure we don't mess up regular posts
     *
     * @return bool
     * @since 2.4.11
     */
    public static function is_embedded_product() {
        if ( function_exists( 'is_embed' ) && is_embed() && is_product() ) {
            return true;
        }
        return false;
    }

    /**
     * Create the excerpt for embedded products - we want to add the buy button to it
     *
     * @return string
     * @since 2.4.11
     */
    public static function the_excerpt( $excerpt ) {
		global $post;

        //  make sure we're only affecting embedded products
        if ( self::is_embedded_product() ) {
			if ( ! empty( $post->post_excerpt ) ) {
				ob_start();
	            woocommerce_template_single_excerpt();
	            $excerpt = ob_get_clean();
			}

			// add the button
			$excerpt.= self::product_buttons();
        }
        return $excerpt;
    }

    /**
     * Create the button to go to the product page for embedded products.
     *
     * @return string
     * @since 2.4.11
     */
    public static function product_buttons() {
		$_product = wc_get_product( get_the_ID() );
		$buttons  = array();
		$button   = '<a href="%s" class="wp-embed-more wc-embed-button">%s</a>';

		if ( $_product->is_type( 'simple' ) && $_product->is_purchasable() && $_product->is_in_stock() ) {
			$buttons[] = sprintf( $button, add_query_arg( 'add-to-cart', get_the_ID(), wc_get_cart_url() ), __( 'Buy Now', 'woocommerce' ) );
		}

        $buttons[] = sprintf( $button, get_the_permalink(), __( 'Read More', 'woocommerce' ) );

		return '<p>' . implode( ' ', $buttons ) . '</p>';
    }

    /**
     * Prints the markup for the rating stars
     *
     * @return string
     * @since 2.4.11
     */
    public static function get_ratings( $comments ) {
        //  make sure we're only affecting embedded products
        if ( self::is_embedded_product() && ( $_product = wc_get_product( get_the_ID() ) ) && $_product->get_average_rating() > 0 ) {
            ?>
            <div class="wc-embed-rating">
                <?php printf( __( 'Rated %s out of 5', 'woocommerce' ), $_product->get_average_rating() ); ?>
            </div>
            <?php
        }
    }

	/**
	 * Basic styling
	 */
	public static function print_embed_styles() {
		if ( ! self::is_embedded_product() ) {
			return;
		}
		?>
		<style type="text/css">
			a.wc-embed-button {
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: .5em;
				display:inline-block;
				box-shadow: 0px 1px 0 0px rgba(0,0,0,0.05);
			}
			a.wc-embed-button:hover, a.wc-embed-button:focus {
				border: 1px solid #ccc;
				box-shadow: 0px 1px 0 0px rgba(0,0,0,0.1);
				text-decoration: none;
				color: #999;
			}
			.wp-embed-excerpt p {
				margin: 0 0 1em;
			}
			.wc-embed-price {
				float:right;
			}
			.wc-embed-rating {
				display: inline-block;
			}
		</style>
		<?php
	}
}

WC_Embed::init();
