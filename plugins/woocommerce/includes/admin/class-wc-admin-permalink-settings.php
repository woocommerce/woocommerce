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
	 * Resolve the Shop page URI that serves as the Shop base, or the default slug.
	 *
	 * Shared by the render and the save paths so both resolve the base the same way.
	 *
	 * wc_get_page_id() returns -1 when no page is set, so the ID is tested against zero rather
	 * than for truthiness -- and the page it names can still be gone, which get_post() catches.
	 *
	 * @param int $shop_page_id Shop page ID, as resolved by wc_get_page_id( 'shop' ).
	 * @return string Shop base slug.
	 */
	private function get_shop_base_slug( int $shop_page_id ): string {
		return (string) ( ( $shop_page_id > 0 && get_post( $shop_page_id ) ) ? get_page_uri( $shop_page_id ) : _x( 'shop', 'default-slug', 'woocommerce' ) );
	}

	/**
	 * Resolve a posted product permalink choice to the value that gets persisted for it.
	 *
	 * The render path checks a radio by comparing the stored base against this, and the save path
	 * stores what this returns, so both derive the base from one set of rules instead of restating
	 * them separately -- which is what made every predefined structure revert to "Custom base".
	 * See https://github.com/woocommerce/woocommerce/issues/29050.
	 *
	 * Must run inside a wc_switch_to_site_locale() window: the Default base is a translated slug,
	 * and an administrator whose profile language differs from the site language would otherwise
	 * store one translation and compare against another.
	 *
	 * Sharing the rules is not the same as receiving identical input: the save path runs the
	 * posted value through sanitize_text_field() first, which strips percent-encoded octets, so a
	 * Shop page whose slug carries a literal percent-escape can still resolve differently on the
	 * two sides.
	 *
	 * @param string      $posted_base      Posted `product_permalink` radio value.
	 * @param string|null $posted_structure Posted `product_permalink_structure` value, or null when that field is absent or not a string.
	 * @return string The base as it is stored.
	 */
	private function get_stored_product_base( string $posted_base, ?string $posted_structure = null ): string {
		$default_base = wc_sanitize_permalink( _x( 'product', 'slug', 'woocommerce' ) );

		if ( 'custom' === $posted_base ) {
			if ( null === $posted_structure ) {
				// A missing or non-string field resolves to the default base, so the stored slug
				// stays deterministic and in the site locale.
				$base = $default_base;
			} else {
				// Remove every `#` so the base cannot open a URL fragment, prepend the leading
				// slash, then collapse each run of slashes into one.
				$base = (string) preg_replace( '~/+~', '/', '/' . str_replace( '#', '', trim( $posted_structure ) ) );
			}

			// A base of nothing but the category token gives products the same URL shape as the
			// category archives they sit under, so the two collide. Prefix the default base.
			if ( '/%product_cat%/' === trailingslashit( $base ) ) {
				$base = '/' . $default_base . $base;
			}
		} else {
			// The Default radio posts an empty value; store the site-locale slug.
			$base = '' === $posted_base ? $default_base : $posted_base;
		}

		$base = wc_sanitize_permalink( $base );

		/*
		 * Resolve an empty base to the default one. A custom base that is blank, whitespace, or
		 * nothing but hashes and slashes sanitizes down to empty, and an empty base does not
		 * survive: wc_get_permalink_structure() drops it and refills the option from the request
		 * locale, outside any locale window, persisting a slug the site locale never chose -- the
		 * same divergence this resolver exists to prevent.
		 *
		 * This narrows that window rather than closing it. The default base is itself a translated
		 * slug run through wc_sanitize_permalink(), so a translation or a gettext filter that
		 * resolves it to something sanitizing away -- `/` untrailingslashits to nothing -- leaves
		 * this assigning empty to empty.
		 */
		if ( '' === $base ) {
			$base = $default_base;
		}

		/*
		 * A base equal to the Default structure is reported in Default's bare form. The Custom
		 * base field is the next tab stop after the radio group and focusing it selects Custom
		 * base, so a single Tab from Default posts the field's prefilled `/product/` through the
		 * custom branch, which prepends a slash. The two forms are not interchangeable: under
		 * index.php (PATHINFO) permalinks the leading slash reaches register_post_type() and
		 * produces an `index.php//product/%product%` permastruct whose URLs do not resolve, while
		 * the bare slug works everywhere. Converging on the bare form keeps Default checked after
		 * that keystroke and never persists the broken shape.
		 */
		return '/' . $default_base === $base ? $default_base : $base;
	}

	/**
	 * Show the settings.
	 */
	public function settings() {
		/* translators: %s: Home URL */
		echo wp_kses_post( wpautop( sprintf( __( 'If you like, you may enter custom structures for your product URLs here. For example, using <code>shop</code> would make your product links like <code>%sshop/sample-product/</code>. This setting affects product URLs only, not things such as product categories.', 'woocommerce' ), esc_url( home_url( '/' ) ) ) ) );

		/*
		 * Resolve the Shop page and the translated slugs inside the same window settings_save()
		 * opens, so the values compared below are the values a save would store.
		 *
		 * wc_get_page_id() is resolved here too because settings_save() resolves it inside its own
		 * window, and the woocommerce_get_shop_page_id filter multilingual plugins attach to can
		 * return a different page per locale.
		 *
		 * This holds only for a persisted product_base: wc_get_permalink_structure() initializes a
		 * missing one in the request locale, outside any window, before this screen renders.
		 * See https://github.com/woocommerce/woocommerce/issues/67507.
		 */
		wc_switch_to_site_locale();
		$shop_page_id = wc_get_page_id( 'shop' );
		$base_slug    = urldecode( $this->get_shop_base_slug( $shop_page_id ) );

		// The value each radio posts. The Shop entries exist only when their rows render below, so
		// a stored base matching a hidden row reports as Custom instead of checking nothing.
		$structures = array( 0 => '' );
		if ( $shop_page_id ) {
			$structures[1] = '/' . trailingslashit( $base_slug );
			$structures[2] = '/' . trailingslashit( $base_slug ) . trailingslashit( '%product_cat%' );
		}

		// What a save would store for each of them, so the comparison below cannot drift from
		// settings_save().
		$stored_forms = array_map( array( $this, 'get_stored_product_base' ), $structures );
		wc_restore_locale();

		$default_product_base      = $stored_forms[0];
		$stored_product_base       = $this->permalinks['product_base'];
		$default_product_structure = trailingslashit( '/' . ltrim( $default_product_base, '/' ) );

		// The index of the predefined structure the stored base corresponds to, or false for a custom one.
		$selected_structure = array_search( $stored_product_base, $stored_forms, true );

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
						<input name="product_permalink_structure" id="woocommerce_permalink_structure" type="text" value="<?php echo esc_attr( $product_permalink_structure ); ?>" class="regular-text code" aria-label="<?php esc_attr_e( 'Custom product permalink base', 'woocommerce' ); ?>" aria-describedby="woocommerce_permalink_structure_description"> <span class="description" id="woocommerce_permalink_structure_description"><?php esc_html_e( 'Enter a custom base to use. A base must be set or WordPress will use default instead.', 'woocommerce' ); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field( 'wc-permalinks', 'wc-permalinks-nonce' ); ?>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('input.wctog').on( 'change', function() {
					jQuery('#woocommerce_permalink_structure').val( jQuery( this ).attr( 'data-permalink-structure' ) );
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
				// Selecting Custom base takes a click or a typed character, the pair core binds to
				// its own structure field. Focus alone must not: the radios share one tab stop, so
				// tabbing forward lands here, and flipping on focus would move the checked radio
				// off the structure the store actually uses.
				jQuery('#woocommerce_permalink_structure').on( 'click input', function(){
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
			 * The form only ever posts strings for these two fields, but nothing enforces that,
			 * and the resolver requires one. A non-string radio value resolves to the default
			 * product base; a non-string structure is passed as null, which resolves to the
			 * default base only when the radio selects the custom branch that reads it.
			 */
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_text_field().
			$product_base = sanitize_text_field( isset( $_POST['product_permalink'] ) && is_string( $_POST['product_permalink'] ) ? wp_unslash( $_POST['product_permalink'] ) : '' );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by wc_sanitize_permalink() inside the resolver.
			$posted_structure = isset( $_POST['product_permalink_structure'] ) && is_string( $_POST['product_permalink_structure'] ) ? wp_unslash( $_POST['product_permalink_structure'] ) : null;

			// Resolved inside the wc_switch_to_site_locale() window opened above, so the stored
			// value is the same site-locale form settings() compares against.
			$permalinks['product_base'] = $this->get_stored_product_base( $product_base, $posted_structure );

			// Shop base may require verbose page rules if nesting pages.
			$shop_page_id   = wc_get_page_id( 'shop' );
			$shop_permalink = $this->get_shop_base_slug( $shop_page_id );

			if ( $shop_page_id && stristr( trim( $permalinks['product_base'], '/' ), $shop_permalink ) ) {
				$permalinks['use_verbose_page_rules'] = true;
			}

			update_option( 'woocommerce_permalinks', $permalinks );
			wc_restore_locale();
		}
	}
}

return new WC_Admin_Permalink_Settings();
