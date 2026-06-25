<?php
/**
 * This template displays the recent product review widget on the WordPress dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/dashboard-widget-reviews.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * For this template, the following variables are available
 *
 * @var $product \WC_Product
 * @var $comment \WP_Comment
 */

?>

<li>
	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	?>

	<?php echo get_avatar( $comment->comment_author_email, '32' ); ?>

	<?php echo wc_get_rating_html( (int) get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?>

	<h4 class="meta">
		<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>"><?php echo wp_kses_post( $product->get_name() ); ?></a>
		<?php
		/* translators: %s: review author */
		printf( esc_html__( 'reviewed by %s', 'woocommerce' ), esc_html( get_comment_author( $comment->comment_ID ) ) );
		?>
	</h4>

	<blockquote><?php echo wp_kses_data( $comment->comment_content ); ?></blockquote>

	<?php
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</li>
