<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product;
$columns = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );

$post_thumbnail_id = get_post_thumbnail_id( $post->ID );

$full_size_image  = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );

$thumbnail_post   = get_post( $post_thumbnail_id );
$image_title      = $thumbnail_post->post_content;

?>
<div class="woocommerce-product-gallery <?php echo 'woocommerce-product-gallery--columns-' . sanitize_html_class( $columns ) . ' columns-' . sanitize_html_class( $columns ); ?> images">
	<figure class="woocommerce-product-gallery__wrapper">
		<?php
		$attributes = array(
			'title'                   => $image_title,
			'data-large-image'        => $full_size_image[0],
			'data-large-image-width'  => $full_size_image[1],
			'data-large-image-height' => $full_size_image[2],
		);
		if ( has_post_thumbnail() ) {
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<figure data-thumb="' . get_the_post_thumbnail_url( $post->ID, 'shop_thumbnail' ) . '" class="woocommerce-product-gallery__image">' . get_the_post_thumbnail( $post->ID, 'shop_single', $attributes ) . '</figure>' );
		} else {
			echo sprintf( '<figure><img src="%s" alt="%s" /></figure>', esc_url( wc_placeholder_img_src() ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
		}

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</figure>
</div>

<script type="text/javascript">
function get_gallery_items() {
	var $slides = jQuery( '.woocommerce-product-gallery__wrapper' ).children(),
		items = [],
		index = $slides.filter( '.' + 'flex-active-slide' ).index();

		if ( $slides.length > 0 ) {
			$slides.each( function( i, el ) {
				var img = jQuery( el ).find( 'img' ),
					large_image_src = img.attr( 'data-large-image' ),
					large_image_w   = img.attr( 'data-large-image-width' ),
					large_image_h   = img.attr( 'data-large-image-height' ),
					item            = {
										src: large_image_src,
										w:   large_image_w,
										h:   large_image_h
									};

				var title = img.attr('title');

				item.title = title;

				items.push( item );

			});
		}

	return {
		index: index,
		items: items
	};
}

function trigger_photoswipe( last_slide ) {
	var pswpElement = jQuery( '.pswp' )[0];

	// build items array
	var items = get_gallery_items();

	// define options (if needed)
	var options = {
		index:         typeof last_slide === "undefined" ? items.index : items.items.length-1, // start at first slide
		shareEl:       false,
		closeOnScroll: false,
		history:       false,
	};

	// Initializes and opens PhotoSwipe
	var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items.items, options );
		gallery.init();
}

</script>

<?php if ( wp_script_is( 'photoswipe', 'enqueued' ) ) { ?>
<script type="text/javascript">
jQuery( '.woocommerce-product-gallery' ).prepend( '<a href="#" class="woocommerce-product-gallery__trigger">🔍</a>' );
jQuery( document ).on( 'click', '.woocommerce-product-gallery__trigger', function() {
	trigger_photoswipe();
});
</script>
<?php } ?>
