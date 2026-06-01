<?php
/**
 * Visual attribute term admin fields.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Admin UI for wc-visual attribute term metadata.
 *
 * @internal
 *
 * @since 10.9.0
 */
class VisualAttributeTermAdmin implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() ) {
			return;
		}
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

		self::render_div_visual_attribute_fields( 'term-' );
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

		self::render_table_visual_attribute_fields( $term );
	}

	/**
	 * Render visual fields for the add attribute term modal.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public static function render_add_attribute_term_modal_fields(): void {
		self::render_div_visual_attribute_fields( 'wc-modal-add-attribute-term-' );
	}

	/**
	 * Render visual attribute fields for add forms.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @return void
	 */
	private static function render_div_visual_attribute_fields( string $field_id_prefix ): void {
		?>
		<div class="form-field wc-admin-visual-attribute-type">
			<label><?php esc_html_e( 'Swatch type', 'woocommerce' ); ?></label>
			<?php self::render_visual_type_inputs( $field_id_prefix, VisualAttributeTermMeta::TYPE_COLOR ); ?>
		</div>
		<div class="form-field wc-admin-visual-attribute-color">
			<?php self::render_color_input( $field_id_prefix, '' ); ?>
		</div>
		<div class="form-field wc-admin-visual-attribute-image">
			<?php self::render_image_input( $field_id_prefix, 0 ); ?>
		</div>
		<?php
	}

	/**
	 * Render visual attribute fields for edit forms.
	 *
	 * @param \WP_Term $term Current term.
	 * @return void
	 */
	private static function render_table_visual_attribute_fields( \WP_Term $term ): void {
		$color_value  = get_term_meta( $term->term_id, 'color', true );
		$color_value  = is_string( $color_value ) ? $color_value : '';
		$image_value  = absint( get_term_meta( $term->term_id, 'image', true ) );
		$visual_type  = $image_value > 0 ? VisualAttributeTermMeta::TYPE_IMAGE : VisualAttributeTermMeta::TYPE_COLOR;
		$field_prefix = 'term-';
		?>
		<tr class="form-field wc-admin-visual-attribute-type">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Swatch type', 'woocommerce' ); ?></label>
			</th>
			<td><?php self::render_visual_type_inputs( $field_prefix, $visual_type ); ?></td>
		</tr>
		<tr class="form-field wc-admin-visual-attribute-color">
			<th scope="row" valign="top">
				<label for="<?php echo esc_attr( self::get_color_input_id( $field_prefix ) ); ?>"><?php esc_html_e( 'Color value', 'woocommerce' ); ?></label>
			</th>
			<td><?php self::render_color_input_control( $field_prefix, $color_value ); ?></td>
		</tr>
		<tr class="form-field wc-admin-visual-attribute-image">
			<th scope="row" valign="top">
				<label for="<?php echo esc_attr( self::get_image_input_id( $field_prefix ) ); ?>"><?php esc_html_e( 'Image value', 'woocommerce' ); ?></label>
			</th>
			<td><?php self::render_image_input_control( $field_prefix, $image_value ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Render visual type radio inputs.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @param string $selected_type Selected visual type.
	 * @return void
	 */
	private static function render_visual_type_inputs( string $field_id_prefix, string $selected_type ): void {
		$color_id = $field_id_prefix . 'visual-type-color';
		$image_id = $field_id_prefix . 'visual-type-image';
		?>
		<fieldset>
			<label for="<?php echo esc_attr( $color_id ); ?>">
				<input
					type="radio"
					id="<?php echo esc_attr( $color_id ); ?>"
					name="wc_visual_attribute_type"
					value="<?php echo esc_attr( VisualAttributeTermMeta::TYPE_COLOR ); ?>"
					<?php checked( VisualAttributeTermMeta::TYPE_COLOR, $selected_type ); ?>
				/>
				<?php esc_html_e( 'Color', 'woocommerce' ); ?>
			</label>
			<label for="<?php echo esc_attr( $image_id ); ?>">
				<input
					type="radio"
					id="<?php echo esc_attr( $image_id ); ?>"
					name="wc_visual_attribute_type"
					value="<?php echo esc_attr( VisualAttributeTermMeta::TYPE_IMAGE ); ?>"
					<?php checked( VisualAttributeTermMeta::TYPE_IMAGE, $selected_type ); ?>
				/>
				<?php esc_html_e( 'Image', 'woocommerce' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render the color input and label.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @param string $color_value Color value.
	 * @return void
	 */
	private static function render_color_input( string $field_id_prefix, string $color_value ): void {
		?>
		<label for="<?php echo esc_attr( self::get_color_input_id( $field_id_prefix ) ); ?>"><?php esc_html_e( 'Color value', 'woocommerce' ); ?></label>
		<?php self::render_color_input_control( $field_id_prefix, $color_value ); ?>
		<?php
	}

	/**
	 * Render the color input control.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @param string $color_value Color value.
	 * @return void
	 */
	private static function render_color_input_control( string $field_id_prefix, string $color_value ): void {
		?>
		<input name="term_color" id="<?php echo esc_attr( self::get_color_input_id( $field_id_prefix ) ); ?>" class="wc-admin-visual-attribute-color-input" type="text" value="<?php echo esc_attr( $color_value ); ?>" />
		<?php
	}

	/**
	 * Render the image input and label.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @param int    $image_value Image attachment ID.
	 * @return void
	 */
	private static function render_image_input( string $field_id_prefix, int $image_value ): void {
		?>
		<label for="<?php echo esc_attr( self::get_image_input_id( $field_id_prefix ) ); ?>"><?php esc_html_e( 'Image value', 'woocommerce' ); ?></label>
		<?php self::render_image_input_control( $field_id_prefix, $image_value ); ?>
		<?php
	}

	/**
	 * Render the image input control.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @param int    $image_value Image attachment ID.
	 * @return void
	 */
	private static function render_image_input_control( string $field_id_prefix, int $image_value ): void {
		?>
		<input name="term_image" id="<?php echo esc_attr( self::get_image_input_id( $field_id_prefix ) ); ?>" class="wc-admin-visual-attribute-image-input" type="hidden" value="<?php echo absint( $image_value ); ?>" />
		<?php
	}

	/**
	 * Get color input ID.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @return string
	 */
	private static function get_color_input_id( string $field_id_prefix ): string {
		return $field_id_prefix . 'color';
	}

	/**
	 * Get image input ID.
	 *
	 * @param string $field_id_prefix Field ID prefix.
	 * @return string
	 */
	private static function get_image_input_id( string $field_id_prefix ): string {
		return $field_id_prefix . 'image';
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
		if ( $this->is_ajax_add_attribute_request() ) {
			return;
		}

		VisualAttributeTermMeta::save_term_visual_from_request( (int) $term_id, $taxonomy, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
	 * Check whether the current request is the add attribute AJAX action.
	 *
	 * @return bool
	 */
	private function is_ajax_add_attribute_request(): bool {
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return wp_doing_ajax() && 'woocommerce_add_new_attribute' === $action;
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
