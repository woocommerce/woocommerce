<?php
/**
 * Sorting
 */
?>
<form class="woocommerce_ordering" method="post">
	<select name="catalog_orderby" class="orderby">
		<?php
			$catalog_orderby = apply_filters('woocommerce_catalog_orderby', array(
				'title' 	=> __('Alphabetically', 'woothemes'),
				'date' 		=> __('Most Recent', 'woothemes'),
				'price' 	=> __('Price', 'woothemes')
			));

			foreach ($catalog_orderby as $id => $name) echo '<option value="'.$id.'" '.selected( $_SESSION['orderby'], $id, false ).'>'.$name.'</option>';
		?>
	</select>
</form>