<?php
/**
 * Adds settings to the permalinks admin settings page
 *
 * @class       WC_Admin_Permalink_Settings
 * @package     WooCommerce\Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_Permalink_Settings', false ) ) {
	return new WC_Admin_Permalink_Settings();
}

/**
 * WC_Admin_Permalink_Settings Class.
 */
class WC_Admin_Permalink_Settings {

	/**
	 * Permalink settings.
	 *
	 * @var array
	 */
	private $permalinks = array();

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		$this->settings_init();
		$this->settings_save();
	}

	/**
	 * Init our settings.
	 */
	public function settings_init() {
		add_settings_section( 'woocommerce-permalink', __( 'Product permalinks', 'woocommerce' ), array( $this, 'settings' ), 'permalink' );

		add_settings_field(
			'woocommerce_product_category_slug',
			__( 'Product category base', 'woocommerce' ),
			array( $this, 'product_category_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'woocommerce_product_tag_slug',
			__( 'Product tag base', 'woocommerce' ),
			array( $this, 'product_tag_slug_input' ),
			'permalink',
			'optional'
		);
		add_settings_field(
			'woocommerce_product_attribute_slug',
			__( 'Product attribute base', 'woocommerce' ),
			array( $this, 'product_attribute_slug_input' ),
			'permalink',
			'optional'
		);

		$this->permalinks = wc_get_permalink_structure();
	}

	/**
	 * Show a slug input box.
	 */
	public function product_category_slug_input() {
		?>
		<input name="woocommerce_product_category_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['category_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'product-category', 'slug', 'woocommerce' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function product_tag_slug_input() {
		?>
		<input name="woocommerce_product_tag_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['tag_base'] ); ?>" placeholder="<?php echo esc_attr_x( 'product-tag', 'slug', 'woocommerce' ); ?>" />
		<?php
	}

	/**
	 * Show a slug input box.
	 */
	public function product_attribute_slug_input() {
		?>
		<input name="woocommerce_product_attribute_slug" type="text" class="regular-text code" value="<?php echo esc_attr( $this->permalinks['attribute_base'] ); ?>" /><code>/attribute-name/attribute/</code>
		<?php
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your product URLs here. For example, using <code>shop</code> would make your product links like <code>%sshop/sample-product/</code>. This setting affects product URLs only, not things such as product categories.', 'woocommerce' ), esc_url( home_url( '/' ) ) ) ) );

		/*
		 * Resolve the Shop page and the translated slugs inside the same window settings_save()
		 * opens, so the values compared below are the values a save would store. Without it, an
		 * administrator whose profile language differs from the site language stores one
		 * translation and compares against another, and no radio ever matches.
		 *
		 * wc_get_page_id() is resolved here too because settings_save() resolves it inside its own
		 * window, and the woocommerce_get_shop_page_id filter multilingual plugins attach to can
		 * return a different page per locale.
		 *
		 * This aligns the two paths for a product_base that is already persisted. It cannot align
		 * one that is not: wc_get_permalink_structure() initializes a missing default in the
		 * request locale, outside any window, before this screen ever renders. That is pre-existing
		 * behavior, tracked separately.
		 */
		wc_switch_to_site_locale();
		$shop_page_id         = wc_get_page_id( 'shop' );
		$base_slug            = urldecode( ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' ) );
		$default_product_base = wc_sanitize_permalink( _x( 'product', 'slug', 'woocommerce' ) );
		wc_restore_locale();

		$structures = array(
			0 => '',
			1 => '/' . trailingslashit( $base_slug ),
			2 => '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' ),
		);

		/*
		 * Must match what settings_save() stores rather than what the radios post:
		 * wc_sanitize_permalink() strips the trailing slash, and Default is stored as the
		 * translated product slug, not as the empty value its radio carries. Otherwise no radio
		 * matches and the screen falls through to Custom base.
		 * See https://github.com/woocommerce/woocommerce/issues/29050.
		 */
		$structures_for_comparison = array(
			0 => $default_product_base,
			1 => wc_sanitize_permalink( $structures[1] ),
			2 => wc_sanitize_permalink( $structures[2] ),
		);

		$stored_product_base       = $this->permalinks['product_base'];
		$default_product_structure = trailingslashit( '/' . ltrim( $default_product_base, '/' ) );

		// The index of the predefined structure the stored base corresponds to, or false for a custom one.
		$selected_structure = array_search( $stored_product_base, $structures_for_comparison, true );

		$product_permalink_structure = 0 === $selected_structure
			? $default_product_structure
			: ( $stored_product_base ? trailingslashit( $stored_product_base ) : '' );
		?>
		<table class="form-table wc-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" data-permalink-structure="<?php echo esc_attr( $default_product_structure ); ?>" class="wctog" <?php checked( 0 === $selected_structure ); ?> /> <?php esc_html_e( 'Default', 'woocommerce' ); ?></label></th>
					<td><code class="default-example"><?php echo esc_html( home_url() ); ?>/?product=sample-product</code> <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $default_product_base ); ?>/sample-product/</code></td>
				</tr>
				<?php if ( $shop_page_id ) : ?>
					<tr>
						<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" data-permalink-structure="<?php echo esc_attr( $structures[1] ); ?>" class="wctog" <?php checked( 1 === $selected_structure ); ?> /> <?php esc_html_e( 'Shop base', 'woocommerce' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-product/</code></td>
					</tr>
					<tr>
						<th><label><input name="product_permalink" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" data-permalink-structure="<?php echo esc_attr( $structures[2] ); ?>" class="wctog" <?php checked( 2 === $selected_structure ); ?> /> <?php esc_html_e( 'Shop base with category', 'woocommerce' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/product-category/sample-product/</code></td>
					</tr>
				<?php endif; ?>
				<tr>
					<th><label><input name="product_permalink" id="woocommerce_custom_selection" type="radio" value="custom" class="tog" <?php checked( false === $selected_structure ); ?> />
						<?php esc_html_e( 'Custom base', 'woocommerce' ); ?></label></th>
					<td>
						<input name="product_permalink_structure" id="woocommerce_permalink_structure" type="text" value="<?php echo esc_attr( $product_permalink_structure ); ?>" class="regular-text code"> <span class="description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'woocommerce' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'wc-permalinks', 'wc-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.wctog').on( 'change', function() {
					// Fall back to the radio value for markup that copies the .wctog class
					// without carrying the attribute.
					jQuery('#woocommerce_permalink_structure').val( jQuery( this ).attr( 'data-permalink-structure' ) || jQuery( this ).val() );
				});
				jQuery('.permalink-structure input').on( 'change', function() {
					jQuery('.wc-permalink-structure').find('code.non-default-example, code.default-example').hide();
					if ( jQuery(this).val() ) {
						jQuery('.wc-permalink-structure code.non-default-example').show();
						jQuery('.wc-permalink-structure input').prop('disabled', false);
					} else {
						jQuery('.wc-permalink-structure code.default-example').show();
						jQuery('.wc-permalink-structure input:eq(0)').trigger( 'click' );
						jQuery('.wc-permalink-structure input').attr('disabled', 'disabled');
					}
				});
				jQuery('.permalink-structure input:checked').trigger( 'change' );
				jQuery('#woocommerce_permalink_structure').on( 'focus', function(){
					jQuery('#woocommerce_custom_selection').trigger( 'click' );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Save the settings.
	 */
	public function settings_save() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['permalink_structure'], $_POST['wc-permalinks-nonce'], $_POST['woocommerce_product_category_slug'], $_POST['woocommerce_product_tag_slug'], $_POST['woocommerce_product_attribute_slug'] ) && wp_verify_nonce( wp_unslash( $_POST['wc-permalinks-nonce'] ), 'wc-permalinks' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified; permalink tokens require domain-specific cleaning.
			wc_switch_to_site_locale();

			$permalinks                   = (array) get_option( 'woocommerce_permalinks', array() );
			$permalinks['category_base']  = wc_sanitize_permalink( wp_unslash( $_POST['woocommerce_product_category_slug'] ) );
			$permalinks['tag_base']       = wc_sanitize_permalink( wp_unslash( $_POST['woocommerce_product_tag_slug'] ) );
			$permalinks['attribute_base'] = wc_sanitize_permalink( wp_unslash( $_POST['woocommerce_product_attribute_slug'] ) );

			/*
			 * Generate product base. The form only ever posts scalars for these two fields, but
			 * nothing enforces that, and unguarded an array reaches trim() and
			 * wc_sanitize_permalink(), which both expect a string. Each field falls back
			 * differently: a non-scalar product_permalink becomes the empty string and is resolved
			 * by the Default branch further down, while a non-scalar product_permalink_structure is
			 * treated as an absent field and resolved to '/' inside the custom branch.
			 */
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line.
			$posted_product_base = isset( $_POST['product_permalink'] ) && is_scalar( $_POST['product_permalink'] ) ? wp_unslash( $_POST['product_permalink'] ) : '';
			$product_base        = sanitize_text_field( (string) $posted_product_base );

			// Resolved inside the wc_switch_to_site_locale() window opened above, so every branch
			// below stores the same site-locale slug settings() compares against.
			$default_product_base = _x( 'product', 'slug', 'woocommerce' );

			if ( 'custom' === $product_base ) {
				if ( isset( $_POST['product_permalink_structure'] ) && is_scalar( $_POST['product_permalink_structure'] ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by wc_sanitize_permalink() below.
					$posted_structure = trim( (string) wp_unslash( $_POST['product_permalink_structure'] ) );
					$product_base     = (string) preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $posted_structure ) );
				} else {
					$product_base = '/';
				}

				// This is an invalid base structure and breaks pages.
				if ( '/%product_cat%/' === trailingslashit( $product_base ) ) {
					$product_base = '/' . $default_product_base . $product_base;
				}
			} elseif ( empty( $product_base ) ) {
				// The Default radio posts an empty value; store the site-locale slug.
				$product_base = $default_product_base;
			}

			$permalinks['product_base'] = wc_sanitize_permalink( $product_base );

			/*
			 * A custom base describing the Default structure is stored in Default's bare form. The
			 * Custom base field is the next tab stop after the radio group and focusing it selects
			 * Custom base, so a single Tab from Default posts the field's prefilled `/product/`
			 * through the custom branch, which prepends a slash. The two stored forms are not
			 * interchangeable: under index.php (PATHINFO) permalinks the leading slash reaches
			 * register_post_type() and produces an `index.php//product/%product%` permastruct whose
			 * URLs do not resolve, while the bare slug works everywhere. Converging on the bare
			 * form keeps Default checked after that keystroke and never persists the broken shape.
			 */
			$sanitized_default_base = wc_sanitize_permalink( $default_product_base );
			if ( '/' . $sanitized_default_base === $permalinks['product_base'] ) {
				$permalinks['product_base'] = $sanitized_default_base;
			}

			// Shop base may require verbose page rules if nesting pages.
			$shop_page_id   = wc_get_page_id( 'shop' );
			$shop_permalink = ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' );

			if ( $shop_page_id && stristr( trim( $permalinks['product_base'], '/' ), $shop_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}

			update_option( 'woocommerce_permalinks', $permalinks );
			wc_restore_locale();
		}
	}
}

return new WC_Admin_Permalink_Settings();
