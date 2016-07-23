<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @class 		WC_Structured_Data
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		Clement Cazaud
 */
class WC_Structured_Data {
  
  /**
   * @var array Partially formatted structured data
   */
  private $data;

  /**
   * Checks if the passed $json variable is an array and stores it into $this->data...
   *
   * @param array $json Partially formatted JSON-LD
   * @return false If the param $json is not an array
   */
  private function set_data( $json ) {
    if ( ! is_array( $json ) ) {
      return false;
    }
    
    $this->data[] = $json;
  }
  
  /**
   * Formats and returns the structured data...
   *
   * @return array If data is set, returns the fully formatted JSON-LD array, otherwise returns empty array
   */
  private function get_data() {
    if ( ! $this->data ) {
      return array();
    }
    
    foreach ( $this->data as $value ) {
      $type = isset( $value['@type'] ) ? $value['@type'] : false;
        
      if ( 'Product' === $type || 'SoftwareApplication' === $type || 'MusicAlbum' === $type ) {
        $products[] = $value;
      } elseif ( 'Review' === $type ) {
        $reviews[] = $value;
      }
    }

    $product_count = isset( $products ) ? count( $products ) : 0;

    if ( $product_count === 1 ) {
      $data = isset( $reviews ) ? $products[0] + array( 'review' => $reviews ) : $products[0];
    } elseif ( $product_count > 1 ) {
      $data = array( '@graph' => $products );
    }
    
    if ( ! isset( $data ) ) {
      return array();
    }
    
    $context['@context'] = apply_filters( 'woocommerce_structured_data_context', 'http://schema.org/' );

    return $context + $data;
  }

  /**
   * Contructor
   */
  public function __construct() {
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'generate_product_category_data' ) );
    add_action( 'woocommerce_single_product_summary', array( $this, 'generate_product_data' ) );
    add_action( 'woocommerce_review_meta', array( $this, 'generate_product_review_data' ) );
    add_action( 'wp_footer', array( $this, 'enqueue_data' ) );
  }

  /**
   * Sanitizes, encodes and echoes the structured data into `wp_footer` action hook.
   */
  public function enqueue_data() {
    if ( $structured_data = $this->get_data() ) {

      array_walk_recursive( $structured_data, array( $this, 'sanitize_data' ) );

      echo '<script type="application/ld+json">' . wp_json_encode( $structured_data ) . '</script>';
    }
  }

  /**
   * Callback function for sanitizing the structured data.
   *
   * @param ref
   */
  private function sanitize_data( &$value ) {
    $value = sanitize_text_field( $value );
  }

  /**
   * Generates the product category structured data...
   * Hooked into the `woocommerce_before_shop_loop_item` action hook...
   */
  public function generate_product_category_data() {
    if ( ! is_product_category() ) {
      return;
    }
    
    $this->generate_product_data();
  }
  
  /**
   * Generates the product structured data...
   * Hooked into the `woocommerce_single_product_summary` action hook...
   * Applies the `woocommerce_product_structured_data` filter hook for clean structured data customization...
   */
  public function generate_product_data() {
    global $product;

    if ( $product->is_downloadable() ) {
      switch ( $product->download_type ) {
        case 'application' :
          $type = "SoftwareApplication";
        break;
        case 'music' :
          $type = "MusicAlbum";
        break;
        default :
          $type = "Product";
        break;
      }
    } else {
      $type = "Product";
    }

    $json['@type']             = $type;
    $json['@id']               = get_the_permalink();
    $json['name']              = get_the_title();
    $json['image']             = wp_get_attachment_url( $product->get_image_id() );
    $json['description']       = get_the_excerpt();
    $json['url']               = get_the_permalink();
    $json['sku']               = $product->get_sku();
 
    if ( $product->get_rating_count() ) {
      $json['aggregateRating'] = array(
        '@type'                => 'AggregateRating',
        'ratingValue'          => $product->get_average_rating(),
        'ratingCount'          => $product->get_rating_count(),
        'reviewCount'          => $product->get_review_count()
      );
    }
    
    $json['offers']            = array(
      '@type'                  => 'Offer',
      'priceCurrency'          => get_woocommerce_currency(),
      'price'                  => $product->get_price(),
      'availability'           => 'http://schema.org/' . $stock = ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' )
    );
    
    $this->set_data( apply_filters( 'woocommerce_structured_data_product', $json, $product ) );
  }

  /**
   * Generates the product review structured data...
   * Hooked into the `woocommerce_review_meta` action hook...
   * Applies the `woocommerce_product_review_structured_data` filter hook for clean structured data customization...
   *
   * @param object $comment
   */
  public function generate_product_review_data( $comment ) {

    $json['@type']             = 'Review';
    $json['@id']               = get_the_permalink() . '#li-comment-' . get_comment_ID();
    $json['datePublished']     = get_comment_date( 'c' );
    $json['description']       = get_comment_text();
    $json['reviewRating']      = array(
      '@type'                  => 'rating',
      'ratingValue'            => intval( get_comment_meta( $comment->comment_ID, 'rating', true ) )
    );
    $json['author']            = array(
      '@type'                  => 'Person',
      'name'                   => get_comment_author()
    );
    
    $this->set_data( apply_filters( 'woocommerce_structured_data_product_review', $json, $comment ) );
  }
}
