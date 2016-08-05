<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @class     WC_Structured_Data
 * @version   2.7.0
 * @package   WooCommerce/Classes
 * @category  Class
 * @author    Clement Cazaud
 */
class WC_Structured_Data {
  
  /**
   * @var array Partially structured data
   */
  private $data;

  /**
   * Checks if the passed $json variable is an array and stores it into $this->data...
   *
   * @param  array $json Partially structured data
   * @return bool Returns false If the param $json is not an array
   */
  private function set_data( $json ) {
    if ( ! is_array( $json ) ) {
      return false;
    }
    
    $this->data[] = $json;
  }
  
  /**
   * Structures and returns the data...
   *
   * @return array If data is set, returns the structured data, otherwise returns empty array
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
      } elseif ( 'WebSite' === $type || 'BreadcrumbList' === $type ) {
        $data[] = $value;
      }
    }
    
    if ( isset( $products ) ) {
      if ( count( $products ) > 1 ) {
        $data[] = array( '@graph' => $products );
      } else {
        $data[] = isset( $reviews ) ? $products[0] + array( 'review' => $reviews ) : $products[0];
      }
    }
 
    if ( ! isset( $data ) ) {
      return array();
    }
    
    $context['@context'] = apply_filters( 'woocommerce_structured_data_context', 'http://schema.org/' );

    foreach( $data as $key => $value ) {
      $data[ $key ] = $context + $value;
    }

    if ( count( $data ) > 1 ) {
      return $data = array( '@graph' => $data );
    } else {
      return $data[0];
    }
  }

  /**
   * Contructor
   */
  public function __construct() {
    add_action( 'woocommerce_before_main_content', array( $this, 'generate_shop_data' ) );
    add_action( 'woocommerce_breadcrumb', array( $this, 'generate_breadcrumb_data' ), 10, 1 );
    add_action( 'woocommerce_before_shop_loop_item', array( $this, 'generate_product_category_data' ) );
    add_action( 'woocommerce_single_product_summary', array( $this, 'generate_product_data' ) );
    add_action( 'woocommerce_review_meta', array( $this, 'generate_product_review_data' ), 10, 1 );
    add_action( 'wp_footer', array( $this, 'enqueue_data' ) );
  }

  /**
   * Sanitizes, encodes and echoes the structured data into `wp_footer` action hook.
   */
  public function enqueue_data() {
    if ( $data = $this->get_data() ) {
      $data = $this->sanitize_data( $data );

      echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>';
    }
  }

  /**
   * Sanitizes the structured data.
   *
   * @param  array $data
   * @return array $sanitized_data
   */
  private function sanitize_data( $data ) {
    foreach ( $data as $key => $value ) {
      $sanitized_data[ sanitize_text_field( $key ) ] = is_array( $value ) ? $this->sanitize_data( $value ) : sanitize_text_field( $value );
    }

    return $sanitized_data;
  }

  /**
   * Generates the product category structured data...
   * Hooked into the `woocommerce_before_shop_loop_item` action hook...
   */
  public function generate_product_category_data() {
    if ( ! is_product_category() && ! is_shop() ) {
      return;
    }
    
    $this->generate_product_data();
  }
  
  /**
   * Generates the product structured data...
   * Hooked into the `woocommerce_single_product_summary` action hook...
   * Applies the `woocommerce_structured_data_product` filter hook for clean structured data customization...
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
    $json['description']       = get_the_excerpt();
    $json['url']               = get_the_permalink();
 
    if ( $product->get_rating_count() ) {
      $json['aggregateRating'] = array(
        '@type'                => 'AggregateRating',
        'ratingValue'          => $product->get_average_rating(),
        'ratingCount'          => $product->get_rating_count(),
        'reviewCount'          => $product->get_review_count()
      );
    }

    if ( $multi_variation = count( $product->get_children() ) > 1 ? true : false ) {
      $variations = $product->get_available_variations();
    } else {
      $variations[0] = array( 'variation_id' => $product->get_id() );
    }
      
    foreach ( $variations as $key => $variation ) {
      $product_variation = wc_get_product( $variation['variation_id'] );
      
      $json_offers[ $key ]     = array(
        '@type'                => 'Offer',
        'priceCurrency'        => get_woocommerce_currency(),
        'price'                => $product_variation->get_price(),
        'availability'         => 'http://schema.org/' . $stock = ( $product_variation->is_in_stock() ? 'InStock' : 'OutOfStock' ),
        'sku'                  => $product_variation->get_sku(),
        'image'                => wp_get_attachment_url( $product_variation->get_image_id() ),
        'description'          => $multi_variation ? $product_variation->get_variation_description() : ''
      );  
    }

    $json['offers'] = $json_offers;
    
    $this->set_data( apply_filters( 'woocommerce_structured_data_product', $json, $product ) );
  }

  /**
   * Generates the product review structured data...
   * Hooked into the `woocommerce_review_meta` action hook...
   * Applies the `woocommerce_structured_data_product_review` filter hook for clean structured data customization...
   *
   * @param object $comment From `woocommerce_review_meta` action hook
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

  /**
   * Generates the breadcrumbs structured data...
   * Hooked into the `woocommerce_breadcrumb` action hook...
   * Applies the `woocommerce_structured_data_breadcrumb` filter hook for clean structured data customization...
   *
   * @param array $args From `woocommerce_breadcrumb` action hook
   */
  public function generate_breadcrumb_data( $args ) {
    if ( empty( $args['breadcrumb'] ) ) {
      return;
    }

    $breadcrumb = $args['breadcrumb'];
    $position   = 1;

    foreach ( $breadcrumb as $key => $value ) {
      if ( ! empty( $value[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
        $json_crumbs_item      = array(
          '@id'                => $value[1],
          'name'               => $value[0]
        );
      } else {
        $json_crumbs_item      = array(
          'name'               => $value[0]
        );
      }

      $json_crumbs[]           = array(
        '@type'                => 'ListItem',
        'position'             => $position ++,
        'item'                 => $json_crumbs_item
      );
    }

    $json['@type']             = 'BreadcrumbList';
    $json['itemListElement']   = $json_crumbs;

    $this->set_data( apply_filters( 'woocommerce_structured_data_breadcrumb', $json, $breadcrumb ) );
  }

  /**
   * Generates the shop related structured data...
   * Hooked into the `woocommerce_before_main_content` action hook...
   * Applies the `woocommerce_structured_data_shop` filter hook for clean structured data customization...
   */
  public function generate_shop_data() {
    if ( ! is_shop() || ! is_front_page() ) {
      return;
    }

    $json['@type']             = 'WebSite';
    $json['name']              = get_bloginfo( 'name' );
    $json['url']               = get_bloginfo( 'url' );
    $json['potentialAction']   = array(
      '@type'                  => 'SearchAction',
      'target'                 => get_bloginfo( 'url' ) . '/?s={search_term_string}&post_type=product',
      'query-input'            => 'required name=search_term_string'
    );

    $this->set_data( apply_filters( 'woocommerce_structured_data_shop', $json ) );
  }
}
