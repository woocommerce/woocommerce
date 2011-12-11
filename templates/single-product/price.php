<?php
/**
 * Single Product Price
 */

global $post, $_product;
?>
<p itemprop="price" class="price"><?php echo $_product->get_price_html(); ?></p>