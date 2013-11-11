<?php
/**
 * Cart item data (when outputting non-flat)
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<dl class="variation">
	<?php foreach ( $item_data as $data ) : ?>
		<dt><?php echo wp_kses_post( $data['key'] ); ?>:</dt>
		<dd><?php echo wp_kses_post( wpautop( $data['value'] ) ); ?></dd>
	<?php endforeach; ?>
</dl>