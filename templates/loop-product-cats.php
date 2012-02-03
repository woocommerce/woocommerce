<?php 

global $woocommerce, $woocommerce_loop, $product_category_found; 

$product_category_found = false;

?>

<?php 
foreach ($product_categories as $category) :

	if ($category->parent != $product_category_parent) continue;

	$product_category_found = true;

	$woocommerce_loop['loop']++;
	?>
	<li class="product sub-category <?php if ($woocommerce_loop['loop']%$woocommerce_loop['columns']==0) echo 'last'; if (($woocommerce_loop['loop']-1)%$woocommerce_loop['columns']==0) echo 'first'; ?>">

		<?php do_action('woocommerce_before_subcategory', $category); ?>

		<a href="<?php echo get_term_link($category->slug, 'product_cat'); ?>">

			<?php do_action('woocommerce_before_subcategory_title', $category); ?>

			<h3><?php echo $category->name; ?> <?php if ($category->count>0) : ?><mark class="count">(<?php echo $category->count; ?>)</mark><?php endif; ?></h3>

			<?php do_action('woocommerce_after_subcategory_title', $category); ?>

		</a>

		<?php do_action('woocommerce_after_subcategory', $category); ?>

	</li><?php

endforeach;
?>