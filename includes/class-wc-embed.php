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
        add_filter( 'the_title', array( 'WC_Embed', 'the_title' ), 10 );
        add_filter( 'the_excerpt_embed', array( 'WC_Embed', 'the_excerpt' ), 10 );

        // make sure no comments display. Doesn't make sense for products
        remove_action( 'embed_content_meta', 'print_embed_comments_button' );

        // in the comments place let's display the product rating
        add_action( 'embed_content_meta', array( 'WC_Embed', 'get_ratings' ), 5 );
	}

    /**
     * Create the title for embedded products - we want to add the price to it
     *
     * @return string
     * @since 2.4.11
     */
    public static function the_title( $title ) {
        // make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {

            // get product
            $_pf = new WC_Product_Factory();
            $_product = $_pf->get_product( get_the_ID() );

            // add the price
            $title = $title . '<span class="price" style="float: right;">' . $_product->get_price_html() . '</span>';
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
        //  make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {

            // add the exerpt
            $excerpt = wpautop( $excerpt );

            // add the button
            $excerpt .= WC_Embed::product_button();
        }
        return $excerpt;
    }

    /**
     * Create the button to go to the product page for embedded products.
     *
     * @return string
     * @since 2.4.11
     */
    public static function product_button( ) {
        $button = '<a href="%s" class="wp-embed-more">%s &rarr;</a>';
        return sprintf( $button, get_the_permalink(), __( 'View The Product', 'woocommerce' ) );
    }

    /**
     * Prints the markup for the rating stars
     *
     * @return string
     * @since 2.4.11
     */
    public static function get_ratings( $comments ) {
        //  make sure we're only affecting embedded products
        if ( WC_Embed::is_embedded_product() ) {
            ?>
            <div style="display:inline-block;">
                <?php
                	$_product = wc_get_product( get_the_ID() );
                	printf( __( 'Rated %s out of 5', 'woocommerce' ), $_product->get_average_rating() );
                ?>
            </div>
            <?php
        }
    }

}

WC_Embed::init();
