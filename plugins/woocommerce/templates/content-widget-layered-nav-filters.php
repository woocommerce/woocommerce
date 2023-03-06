<?php
/**
 * The template for displaying product layered nav filters widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-widget-layered-nav-filters.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.5.0
 */

defined( 'ABSPATH' ) || exit;

?>
<?php
/**
 * Hook: woocommerce_widget_layered_nav_filters_start.
 *
 * @since 7.5.0
 *
 * @param array $filters List of all available filters
 */
do_action( 'woocommerce_widget_layered_nav_filters_start', $filters );
?>

<ul>
	<?php foreach ( $filters as $filter ) : ?>
		<li class="<?php echo esc_attr( $filter['class'] ); ?>">
			<a rel="nofollow" aria-label="<?php esc_attr_e( 'Remove filter', 'woocommerce' ); ?>" href="<?php echo esc_url( $filter['link'] ); ?>"><?php echo $filter['label']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		</li>
	<?php endforeach; ?>
</ul>

<?php
/**
 * Hook: woocommerce_widget_layered_nav_filters_end.
 *
 * @since 7.5.0
 *
 * @param array $filters List of all available filters
 */
do_action( 'woocommerce_widget_layered_nav_filters_end', $filters );

