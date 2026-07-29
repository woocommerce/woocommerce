<?php
/**
 * Minimal classic theme template.
 *
 * @package Unsupported_Classic_Test_Theme
 */

declare( strict_types = 1 );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php wp_footer(); ?>
</body>
</html>
