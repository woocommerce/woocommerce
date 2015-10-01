<?php
/**
 * The opening anchor tag for products in the loop
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<a href="<?php the_permalink(); ?>">
