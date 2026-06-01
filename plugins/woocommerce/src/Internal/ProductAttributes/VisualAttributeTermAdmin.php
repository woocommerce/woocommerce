<?php
/**
 * Visual attribute term admin fields.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Admin UI for wc-visual attribute term metadata.
 *
 * @internal
 *
 * @since 10.9.0
 */
class VisualAttributeTermAdmin {

	/**
	 * Hook visual attribute term admin behavior.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		$this->init_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'created_term', array( $this, 'save_product_attribute_term_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_product_attribute_term_fields' ), 10, 3 );

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = 'pa_' . $attribute->attribute_name;

			add_action( $taxonomy . '_add_form_fields', array( $this, 'add_product_attribute_term_fields' ) );
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'edit_product_attribute_term_fields' ), 10, 1 );
			add_filter(
				"manage_edit-{$taxonomy}_columns",
				function ( $columns ) use ( $taxonomy ) {
					return $this->add_term_visual_column( $columns, $taxonomy );
				}
			);
			add_filter(
				"manage_{$taxonomy}_custom_column",
				function ( $content, $column, $term_id ) use ( $taxonomy ) {
					return $this->render_term_visual_column( $content, $column, $term_id, $taxonomy );
				},
				10,
				3
			);
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_visual_attribute_script' ) );
		add_action( 'admin_footer', array( $this, 'move_visual_attribute_fields' ) );
	}

	/**
	 * Add custom fields for product attribute terms.
	 *
	 * @internal
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function add_product_attribute_term_fields( $taxonomy ): void {
		if ( ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			return;
		}
		?>
		<div class="form-field wc-admin-visual-attribute-type">
			<label><?php esc_html_e( 'Swatch type', 'woocommerce' ); ?></label>
			<fieldset>
				<label for="term_visual_type_color">
					<input
						type="radio"
						id="term_visual_type_color"
						name="wc_visual_attribute_type"
						value="color"
						checked
					/>
					<?php esc_html_e( 'Color', 'woocommerce' ); ?>
				</label>
				<label for="term_visual_type_image">
					<input
						type="radio"
						id="term_visual_type_image"
						name="wc_visual_attribute_type"
						value="image"
					/>
					<?php esc_html_e( 'Image', 'woocommerce' ); ?>
				</label>
			</fieldset>
		</div>
		<div class="form-field wc-admin-visual-attribute-color">
			<label for="term_color"><?php esc_html_e( 'Color value', 'woocommerce' ); ?></label>
			<input name="term_color" id="term_color" class="wc-admin-visual-attribute-color-input" type="text" value="" />
		</div>
		<div class="form-field wc-admin-visual-attribute-image">
			<label for="term_image"><?php esc_html_e( 'Image value', 'woocommerce' ); ?></label>
			<input name="term_image" id="term_image" class="wc-admin-visual-attribute-image-input" type="hidden" value="" />
		</div>
		<?php
	}

	/**
	 * Edit custom fields for product attribute terms.
	 *
	 * @internal
	 *
	 * @param \WP_Term $term Current term.
	 * @return void
	 */
	public function edit_product_attribute_term_fields( $term ): void {
		if ( ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $term->taxonomy ) ) {
			return;
		}

		$color_value = get_term_meta( $term->term_id, 'color', true );
		$image_value = get_term_meta( $term->term_id, 'image', true );
		$has_image   = absint( $image_value ) > 0;
		?>
		<tr class="form-field wc-admin-visual-attribute-type">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Swatch type', 'woocommerce' ); ?></label>
			</th>
			<td>
				<fieldset>
					<label for="term_visual_type_color">
						<input
							type="radio"
							id="term_visual_type_color"
							name="wc_visual_attribute_type"
							value="color"
							<?php checked( ! $has_image ); ?>
						/>
						<?php esc_html_e( 'Color', 'woocommerce' ); ?>
					</label>
					<label for="term_visual_type_image">
						<input
							type="radio"
							id="term_visual_type_image"
							name="wc_visual_attribute_type"
							value="image"
							<?php checked( $has_image ); ?>
						/>
						<?php esc_html_e( 'Image', 'woocommerce' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr class="form-field wc-admin-visual-attribute-color">
			<th scope="row" valign="top"><label for="term_color"><?php esc_html_e( 'Color value', 'woocommerce' ); ?></label></th>
			<td>
				<input name="term_color" id="term_color" class="wc-admin-visual-attribute-color-input" type="text" value="<?php echo esc_attr( $color_value ); ?>" />
			</td>
		</tr>
		<tr class="form-field wc-admin-visual-attribute-image">
			<th scope="row" valign="top"><label for="term_image"><?php esc_html_e( 'Image value', 'woocommerce' ); ?></label></th>
			<td>
				<input name="term_image" id="term_image" class="wc-admin-visual-attribute-image-input" type="hidden" value="<?php echo absint( $image_value ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueue the visual attribute script.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function enqueue_visual_attribute_script(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_product_editor_screen = 'product' === $screen->id;

		if ( $is_product_editor_screen && array_key_exists( 'wc-visual', wc_get_attribute_types() ) ) {
			wp_enqueue_media();
			WCAdminAssets::register_script( 'wp-admin-scripts', 'visual-attribute-color-picker', true, array( 'wp-components' ) );
			return;
		}

		$is_attribute_term_screen = 0 === strpos( $screen->id, 'edit-pa_' );
		$taxonomy                 = $this->get_current_taxonomy();

		if ( $is_attribute_term_screen && VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			wp_enqueue_media();
			WCAdminAssets::register_script( 'wp-admin-scripts', 'visual-attribute-color-picker', true, array( 'wp-components' ) );
		}
	}

	/**
	 * Save product attribute term fields.
	 *
	 * @internal
	 *
	 * @param mixed  $term_id Term ID being saved.
	 * @param mixed  $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function save_product_attribute_term_fields( $term_id, $tt_id = '', $taxonomy = '' ): void {
		if ( ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			return;
		}

		$visual_type = VisualAttributeTermMeta::TYPE_COLOR;
		if ( isset( $_POST['wc_visual_attribute_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted_visual_type = wc_clean( wp_unslash( $_POST['wc_visual_attribute_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$visual_type        = is_string( $posted_visual_type ) ? $posted_visual_type : VisualAttributeTermMeta::TYPE_COLOR;
		}

		$color_value = isset( $_POST['term_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['term_color'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$image_id    = isset( $_POST['term_image'] ) ? absint( wp_unslash( $_POST['term_image'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		VisualAttributeTermMeta::save_term_visual_by_type( (int) $term_id, $visual_type, $color_value ?? '', $image_id );
	}

	/**
	 * Add visual column for product attribute terms.
	 *
	 * @internal
	 *
	 * @param array  $columns  Existing columns.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public function add_term_visual_column( $columns, $taxonomy ): array {
		if ( ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			return $columns;
		}

		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			if ( 'slug' === $key ) {
				$new_columns['visual'] = __( 'Visual', 'woocommerce' );
			}
			$new_columns[ $key ] = $label;
		}

		if ( ! isset( $new_columns['visual'] ) ) {
			$new_columns['visual'] = __( 'Visual', 'woocommerce' );
		}

		return $new_columns;
	}

	/**
	 * Render visual column for product attribute terms.
	 *
	 * @internal
	 *
	 * @param string $content  Column output so far.
	 * @param string $column   Current column key.
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public function render_term_visual_column( $content, $column, $term_id, $taxonomy ): string {
		if ( 'visual' !== $column || ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			return $content;
		}

		$image_id = absint( get_term_meta( $term_id, 'image', true ) );

		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			$thumbnail = wp_get_attachment_image( $image_id, array( 32, 32 ) );

			return $thumbnail ? $thumbnail : '&ndash;';
		}

		$color_value = sanitize_hex_color( get_term_meta( $term_id, 'color', true ) );

		if ( ! $color_value ) {
			return '&ndash;';
		}

		$swatch = sprintf(
			'<span class="wc-admin-color-swatch" style="background-color:%s;" aria-hidden="true"></span>',
			esc_attr( $color_value )
		);

		return $swatch . esc_html( strtoupper( $color_value ) );
	}

	/**
	 * Move visual fields near taxonomy name fields.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function move_visual_attribute_fields(): void {
		$taxonomy = $this->get_current_taxonomy();
		if ( ! VisualAttributeTermMeta::is_visual_attribute_taxonomy( $taxonomy ) ) {
			return;
		}

		$handle = 'wc-admin-visual-attribute';
		wp_register_script( $handle, '', array(), WC_VERSION, array( 'in_footer' => true ) );
		wp_enqueue_script( $handle );
		wp_add_inline_script(
			$handle,
			"(function() {
				'use strict';
				const addFormVisualType = document.querySelector('.form-field.wc-admin-visual-attribute-type');
				const addFormColor = document.querySelector('.form-field.wc-admin-visual-attribute-color');
				const addFormImage = document.querySelector('.form-field.wc-admin-visual-attribute-image');
				const addFormSlug = document.querySelector('.form-field.term-slug-wrap');
				if (addFormVisualType && addFormSlug) {
					addFormSlug.parentNode.insertBefore(addFormVisualType, addFormSlug);
				}
				if (addFormColor && addFormSlug) {
					addFormSlug.parentNode.insertBefore(addFormColor, addFormSlug);
				}
				if (addFormImage && addFormSlug) {
					addFormSlug.parentNode.insertBefore(addFormImage, addFormSlug);
				}

				const editFormVisualType = document.querySelector('tr.form-field.wc-admin-visual-attribute-type');
				const editFormColor = document.querySelector('tr.form-field.wc-admin-visual-attribute-color');
				const editFormImage = document.querySelector('tr.form-field.wc-admin-visual-attribute-image');
				const editFormSlug = document.querySelector('tr.form-field.term-slug-wrap');
				if (editFormVisualType && editFormSlug) {
					editFormSlug.parentNode.insertBefore(editFormVisualType, editFormSlug);
				}
				if (editFormColor && editFormSlug) {
					editFormSlug.parentNode.insertBefore(editFormColor, editFormSlug);
				}
				if (editFormImage && editFormSlug) {
					editFormSlug.parentNode.insertBefore(editFormImage, editFormSlug);
				}
			})();"
		);
	}

	/**
	 * Get current taxonomy from request.
	 *
	 * @return string
	 */
	private function get_current_taxonomy(): string {
		return isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
