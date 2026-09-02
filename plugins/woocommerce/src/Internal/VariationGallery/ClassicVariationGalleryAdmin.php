<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\VariationGallery;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Product_Variation;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a classic-editor authoring UI for variation galleries.
 *
 * The editor unifies the variation's featured image and gallery into a single
 * ordered list. The legacy single-image slot is hidden visually, and kept
 * in sync with the first gallery image via JS.
 *
 * This preserves the existing variation save path while giving merchants
 * one control to manage.
 */
class ClassicVariationGalleryAdmin implements RegisterHooksInterface {

	private const SCRIPT_HANDLE = 'wc-admin-variation-gallery';

	private const STYLE_HANDLE = 'wc-admin-variation-gallery-styles';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_action( 'woocommerce_variation_after_upload_image', array( $this, 'render_variation_gallery_field' ), 10, 3 );
		add_action( 'woocommerce_admin_process_variation_object', array( $this, 'persist_variation_gallery_field' ), 10, 2 );
	}

	/**
	 * Enqueue admin assets for the classic variation gallery editor.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_product_edit_screen() ) {
			return;
		}

		$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			\WC()->plugin_url() . '/assets/js/admin/variation-gallery' . $suffix . '.js',
			array( 'wc-admin-variation-meta-boxes', 'wp-a11y' ),
			Constants::get_constant( 'WC_VERSION' ),
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcVariationGalleryL10n',
			array(
				'manageTitle'      => __( 'Manage variation gallery', 'woocommerce' ),
				'manageButton'     => __( 'Update gallery', 'woocommerce' ),
				'replaceTitle'     => __( 'Replace image', 'woocommerce' ),
				'replaceButton'    => __( 'Use this image', 'woocommerce' ),
				'replaceLabel'     => __( 'Replace', 'woocommerce' ),
				'addTitle'         => __( 'Add images to variation gallery', 'woocommerce' ),
				'addButton'        => __( 'Add to gallery', 'woocommerce' ),
				'emptyCtaLabel'    => __( 'Add variation images', 'woocommerce' ),
				'announceUpdated'  => __( 'Variation gallery updated.', 'woocommerce' ),
				'announceReplaced' => __( 'Image replaced.', 'woocommerce' ),
				'announceRemoved'  => __( 'Image removed from variation gallery.', 'woocommerce' ),
				'announceReorder'  => __( 'Variation gallery order updated.', 'woocommerce' ),
				'announcePrimary'  => __( 'New primary image set.', 'woocommerce' ),
				'removeLabel'      => __( 'Remove image', 'woocommerce' ),
				'countZero'        => __( 'No images yet', 'woocommerce' ),
				/* translators: %d: number of variation gallery images */
				'countSingular'    => __( '%d image', 'woocommerce' ),
				/* translators: %d: number of variation gallery images */
				'countPlural'      => __( '%d images', 'woocommerce' ),
				'primaryLabel'     => __( 'Primary', 'woocommerce' ),
				/* translators: %d: gallery image position */
				'thumbLabel'       => __( 'Show gallery image %d', 'woocommerce' ),
				'missingFileLabel' => __( 'Attachment file missing', 'woocommerce' ),
			)
		);

		wp_enqueue_style(
			self::STYLE_HANDLE,
			\WC()->plugin_url() . '/assets/css/variation-gallery-admin.css',
			array(),
			Constants::get_constant( 'WC_VERSION' )
		);
	}

	/**
	 * Render the variation gallery field.
	 *
	 * @param int     $loop           Variation row index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post object.
	 * @return void
	 */
	public function render_variation_gallery_field( int $loop, array $variation_data, WP_Post $variation ): void {
		$variation_object = wc_get_product( $variation->ID );

		if ( ! $variation_object instanceof WC_Product_Variation ) {
			return;
		}

		$image_ids = $this->get_display_image_ids( $variation_object );
		$count     = count( $image_ids );
		$field_id  = 'variable_gallery_image_ids_' . $loop;
		$hero_id   = $count > 0 ? $image_ids[0] : 0;
		?>
		<div
			class="wc-variation-gallery-field<?php echo 0 === $count ? ' is-empty' : ''; ?>"
			data-variation-id="<?php echo esc_attr( (string) $variation->ID ); ?>"
		>
			<div class="wc-variation-gallery-field__header">
				<div class="wc-variation-gallery-field__title-block">
					<strong class="wc-variation-gallery-field__title">
						<?php esc_html_e( 'Variation gallery', 'woocommerce' ); ?>
					</strong>
					<span class="wc-variation-gallery-field__count" aria-live="polite">
						<?php echo esc_html( $this->get_count_text( $count ) ); ?>
					</span>
				</div>
				<button
					type="button"
					class="button-link wc-variation-gallery-manage"
					aria-label="<?php esc_attr_e( 'Manage variation gallery images', 'woocommerce' ); ?>"
				>
					<?php esc_html_e( 'Manage', 'woocommerce' ); ?>
				</button>
			</div>

			<div class="wc-variation-gallery-field__hero" data-active-index="0">
				<?php if ( $hero_id > 0 ) : ?>
					<?php $this->render_hero_image( $hero_id ); ?>
					<span class="wc-variation-gallery-field__badge" data-primary-badge aria-hidden="true">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Primary', 'woocommerce' ); ?>
					</span>
					<button type="button" class="button wc-variation-gallery-replace">
						<?php esc_html_e( 'Replace', 'woocommerce' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="wc-variation-gallery-field__empty-cta wc-variation-gallery-manage">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Add variation images', 'woocommerce' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<ul class="wc-variation-gallery-field__thumbs">
				<?php foreach ( $image_ids as $index => $image_id ) : ?>
					<?php $this->render_thumbnail( $image_id, 0 === $index ); ?>
				<?php endforeach; ?>
			</ul>

			<p class="wc-variation-gallery-field__hint"<?php echo 0 === $count ? ' hidden' : ''; ?>>
				<?php esc_html_e( 'First image is used as the primary. Drag to reorder.', 'woocommerce' ); ?>
			</p>

			<input
				type="hidden"
				id="<?php echo esc_attr( $field_id ); ?>"
				name="variable_gallery_image_ids[<?php echo esc_attr( (string) $loop ); ?>]"
				class="wc-variation-gallery-image-ids"
				value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>"
			/>
		</div>
		<?php
	}

	/**
	 * Persist the variation gallery field.
	 *
	 * The merchant-facing UI presents featured + gallery as a single ordered
	 * list.
	 *
	 * @param WC_Product_Variation $variation Variation being saved.
	 * @param int                  $index     Variation row index.
	 * @return void
	 * @throws \Throwable When setting the variation image or gallery fails.
	 */
	public function persist_variation_gallery_field( WC_Product_Variation $variation, int $index ): void {
		// We verify the variation save nonce before firing `woocommerce_admin_process_variation_object`.
		if ( ! isset( $_POST['variable_gallery_image_ids'][ $index ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$unified_ids = wp_parse_id_list( wp_unslash( $_POST['variable_gallery_image_ids'][ $index ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$featured_id = (int) ( $unified_ids[0] ?? 0 );
		$gallery_ids = array_values( array_slice( $unified_ids, 1 ) );

		try {
			$variation->set_image_id( $featured_id );
			$variation->set_gallery_image_ids( $gallery_ids );
			LegacyVariationGalleryCompatibility::mark_core_managed( $variation );
		} catch ( \Throwable $e ) {
			Telemetry::record_event(
				Telemetry::EVENT_SAVE_FAILED,
				array(
					'context' => 'classic_admin',
					'reason'  => get_class( $e ),
				)
			);
			throw $e;
		}

		Telemetry::record_event(
			Telemetry::EVENT_SAVE_SUCCEEDED,
			array(
				'context'     => 'classic_admin',
				'image_count' => count( $unified_ids ),
				'is_multi'    => count( $unified_ids ) > 1 ? 'yes' : 'no',
			)
		);
	}

	/**
	 * Display-time image list (render-only).
	 *
	 * Prepends the variation's featured image to the gallery when it's not
	 * already present, so the meta-box UI shows a single ordered set instead
	 * of two separate fields. This synthesis is **not persisted** — storage
	 * only changes when the merchant saves the variation, at which point
	 * `gallery[0]` becomes the canonical primary image.
	 *
	 * @param WC_Product_Variation $variation Variation object.
	 * @return array<int>
	 */
	private function get_display_image_ids( WC_Product_Variation $variation ): array {
		$image_ids   = array_values( array_map( 'intval', $variation->get_gallery_image_ids() ) );
		$featured_id = (int) $variation->get_image_id();

		if ( $featured_id > 0 && ! in_array( $featured_id, $image_ids, true ) ) {
			array_unshift( $image_ids, $featured_id );
		}

		if ( ! empty( $image_ids ) ) {
			_prime_post_caches( $image_ids );
		}

		return $image_ids;
	}

	/**
	 * Render the hero image.
	 *
	 * @param int $image_id Attachment ID.
	 * @return void
	 */
	private function render_hero_image( int $image_id ): void {
		$html = wp_get_attachment_image(
			$image_id,
			'woocommerce_single',
			false,
			array(
				'class'    => 'wc-variation-gallery-field__hero-img',
				'data-id'  => (string) $image_id,
				'decoding' => 'async',
				'loading'  => 'lazy',
			)
		);

		if ( '' === $html ) {
			?>
			<span class="wc-variation-gallery-field__hero-broken" aria-hidden="true">
				<span class="dashicons dashicons-format-image"></span>
			</span>
			<span class="screen-reader-text">
				<?php esc_html_e( 'Attachment file missing', 'woocommerce' ); ?>
			</span>
			<?php
			return;
		}

		echo wp_kses_post( $html );
	}

	/**
	 * Render a single thumbnail list item.
	 *
	 * @param int  $image_id  Attachment ID.
	 * @param bool $is_active Whether this thumbnail is the active/primary one.
	 * @return void
	 */
	private function render_thumbnail( int $image_id, bool $is_active ): void {
		$thumbnail = wp_get_attachment_image( $image_id, 'thumbnail' );
		$is_broken = '' === $thumbnail;
		$classes   = array( 'wc-variation-gallery-thumb' );

		if ( $is_active ) {
			$classes[] = 'is-active';
		}

		if ( $is_broken ) {
			$classes[] = 'is-broken';
		}

		/* translators: %d attachment ID */
		$thumb_label = sprintf( __( 'Show gallery image %d', 'woocommerce' ), $image_id );
		?>
		<li
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-attachment_id="<?php echo esc_attr( (string) $image_id ); ?>"
		>
			<button
				type="button"
				class="wc-variation-gallery-thumb__button"
				aria-label="<?php echo esc_attr( $thumb_label ); ?>"
			>
				<?php if ( $is_broken ) : ?>
					<span class="wc-variation-gallery-thumb__broken" aria-hidden="true">
						<span class="dashicons dashicons-format-image"></span>
					</span>
					<span class="screen-reader-text">
						<?php esc_html_e( 'Attachment file missing', 'woocommerce' ); ?>
					</span>
				<?php else : ?>
					<?php echo wp_kses_post( $thumbnail ); ?>
				<?php endif; ?>
			</button>
			<button
				type="button"
				class="wc-variation-gallery-thumb__remove"
				aria-label="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>"
			>
				<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
			</button>
		</li>
		<?php
	}

	/**
	 * Get the image count label shown beside the field title.
	 *
	 * @param int $count Number of images.
	 * @return string
	 */
	private function get_count_text( int $count ): string {
		if ( 0 === $count ) {
			return __( 'No images yet', 'woocommerce' );
		}

		return sprintf(
			/* translators: %d number of variation gallery images */
			_n( '%d image', '%d images', $count, 'woocommerce' ),
			$count
		);
	}

	/**
	 * Determine if the current screen is the classic product editor.
	 *
	 * @return bool
	 */
	private function is_product_edit_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && 'product' === $screen->id;
	}
}
