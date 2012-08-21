<?php
/**
 * Loop Products
 */

global $products;

?>

<ul class="products">

	<?php while ( $products->have_posts() ) : $products->the_post(); ?>

		<?php woocommerce_get_template_part( 'content', 'product' ); ?>

	<?php endwhile; // end of the loop. ?>

</ul>
