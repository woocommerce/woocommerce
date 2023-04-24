<?php
/**
 * Template cache tests class.
 *
 * @package WooCommerce\Tests\Core
 */

/**
 * WC_Template_Cache class.
 */
class WC_Template_Cache extends WC_Unit_Test_Case {
	/**
	 * Test wc_get_template_part().
	 */
	public function test_wc_get_template_part() {
		// Clear cache to start.
		$this->clear_template_cache();

		// Prevent template being loaded.
		add_filter( 'wc_get_template_part', '__return_false' );
		// Use content-* templates.
		$templates = array();
		foreach ( glob( dirname( WC_PLUGIN_FILE ) . '/templates/content*.php' ) as $template ) {
			$name                    = preg_replace( '|content-(.*)\.php|', '$1', basename( $template ) );
			$cache_key               = sanitize_key(
				implode(
					'-',
					array(
						'template-part',
						'content',
						$name,
						WC_VERSION,
					)
				)
			);
			$templates[ $cache_key ] = $template;
			wc_get_template_part( 'content', $name );
		}
		remove_filter( 'wc_get_template_part', '__return_false' );

		// Check cached template list.
		$this->assertEquals( array_keys( $templates ), wp_cache_get( 'cached_templates', 'woocommerce' ) );

		// Check individual templates.
		foreach ( $templates as $cache_key => $template ) {
			// The cache contents may be tokenized and we need to untokenize it for the assertion.
			$from_cache = wc_untokenize_path( wp_cache_get( $cache_key, 'woocommerce' ), wc_get_path_define_tokens() );
			$this->assertEquals( $template, $from_cache );
		}

		// Clear cache.
		$this->clear_template_cache();
	}

	/**
	 * Clear the template cache.
	 */
	protected function clear_template_cache() {
		$cached_templates = wp_cache_get( 'cached_templates', 'woocommerce' );
		wc_clear_template_cache();

		if ( $cached_templates ) {
			foreach ( (array) $cached_templates as $template ) {
				$this->assertEmpty( wp_cache_get( $template, 'woocommerce' ) );
			}
		}
		$this->assertEmpty( wp_cache_get( 'cached_templates', 'woocommerce' ) );
	}
}
