<?php
/**
 * Entire-site Coming Soon document for classic themes.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/coming-soon/coming-soon-entire-site.php.
 *
 * @package WooCommerce\Templates
 * @version 11.3.0
 */

defined( 'ABSPATH' ) || exit;

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'woocommerce-coming-soon-classic-page' ); ?>>
<?php wp_body_open(); ?>
<div class="woocommerce-coming-soon-classic woocommerce-coming-soon-classic--entire-site">
	<header class="woocommerce-coming-soon-classic__header">
		<div class="woocommerce-coming-soon-classic__identity">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
		</div>
		<a class="woocommerce-coming-soon-classic__login" href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></a>
	</header>
	<main class="woocommerce-coming-soon-classic__content">
		<h1>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s is the site name. */
					__( '%s is coming soon', 'woocommerce' ),
					get_bloginfo( 'name' )
				)
			);
			?>
		</h1>
		<?php if ( get_bloginfo( 'description' ) ) : ?>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
		<?php endif; ?>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
