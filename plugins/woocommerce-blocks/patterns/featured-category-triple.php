<?php
/**
 * Title: Featured Category Triple
 * Slug: woocommerce-blocks/featured-category-triple
 * Categories: WooCommerce
 */
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"0"},"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column -->
	<div class="wp-block-column">
		<?php echo '<!-- wp:cover {"url":"' . esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-3.png', dirname( __FILE__ ) ) ) . '","id":1,"dimRatio":0,"contentPosition":"bottom center","style":{"spacing":{"blockGap":"0","padding":{"bottom":"1.8em"}}},"layout":{"type":"constrained"}} -->'; ?>
		<div class="wp-block-cover has-custom-content-position is-position-bottom-center" style="padding-bottom:1.8em">
			<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
			<img class="wp-block-cover__image-background wp-image-1" alt="" src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-3.png', dirname( __FILE__ ) ) ); ?>" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph {"align":"center","placeholder":"Write title…","style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"},"margin":{"top":"0","right":"0","bottom":"0","left":"0"}}},"textColor":"background","fontSize":"large"} -->
				<p class="has-text-align-center has-background-color has-text-color has-large-font-size" style="margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><strong>Aztec clay masks</strong></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"small"} -->
				<p class="has-text-align-center has-background-color has-text-color has-small-font-size"><strong><span style="text-decoration: underline;">Shop Now</span></strong></p>
				<!-- /wp:paragraph --></div>
			</div>
		<!-- /wp:cover --></div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<?php echo '<!-- wp:cover {"url":"' . esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-2.png', dirname( __FILE__ ) ) ) . '","id":1,"dimRatio":0,"contentPosition":"bottom center","isDark":false,"style":{"spacing":{"padding":{"bottom":"1.8em"},"blockGap":"0"}},"textColor":"foreground","layout":{"type":"constrained"}} -->'; ?>
		<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center has-foreground-color has-text-color" style="padding-bottom:1.8em">
			<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
			<img class="wp-block-cover__image-background wp-image-1" alt="" src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-2.png', dirname( __FILE__ ) ) ); ?>" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
				<p class="has-text-align-center has-large-font-size"><strong>Moisturizing toners</strong></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","textColor":"foreground","fontSize":"small"} -->
				<p class="has-text-align-center has-foreground-color has-text-color has-small-font-size"><strong><span style="text-decoration: underline;">Shop Now</span></strong></p>
				<!-- /wp:paragraph --></div>
			</div>
		<!-- /wp:cover --></div>
	<!-- /wp:column -->

	<!-- wp:column -->
	<div class="wp-block-column">
		<?php echo '<!-- wp:cover {"url":"' . esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-1.png', dirname( __FILE__ ) ) ) . '","id":1,"dimRatio":0,"contentPosition":"bottom center","isDark":false,"style":{"spacing":{"padding":{"bottom":"1.8em"},"blockGap":"0"}},"textColor":"background","layout":{"type":"constrained"}} -->'; ?>
		<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center has-background-color has-text-color" style="padding-bottom:1.8em">
			<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>
			<img class="wp-block-cover__image-background wp-image-1" alt="" src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/product-beauty-1.png', dirname( __FILE__ ) ) ); ?>" data-object-fit="cover"/>
			<div class="wp-block-cover__inner-container">
				<!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
				<p class="has-text-align-center has-large-font-size"><strong>Natural body lotions</strong></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"small"} -->
				<p class="has-text-align-center has-background-color has-text-color has-small-font-size"><strong><span style="text-decoration: underline;">Shop Now</span></strong></p>
				<!-- /wp:paragraph --></div>
			</div>
		<!-- /wp:cover --></div>
	<!-- /wp:column --></div>
<!-- /wp:columns -->
