<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStatus;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Provides product link previews and fills missing Open Graph metadata.
 */
class OpenGraph {

	/**
	 * Metadata candidates keyed by property.
	 *
	 * @var array<string, string>
	 */
	private $tags = array();

	/**
	 * Default Twitter card before filtering, or null when omitted.
	 *
	 * @var string|null
	 */
	private $default_card = null;

	/**
	 * Related tags and their coexistence rules.
	 *
	 * @var array<array{properties: string[], matches: callable(array): bool}>
	 */
	private $groups = array();

	/**
	 * Register the frontend callback.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_template_redirect' ) );
	}

	/**
	 * Prepare metadata for a public, unprotected product before rendering the template.
	 *
	 * @internal
	 */
	public function handle_template_redirect(): void {
		/**
		 * Filters whether WooCommerce may output Open Graph metadata.
		 *
		 * @since 11.4.0
		 *
		 * @param bool $enabled Whether metadata is enabled.
		 */
		if ( ! apply_filters( 'woocommerce_enable_open_graph', true ) || is_preview() ) {
			return;
		}

		$page = $this->get_page_data();
		if ( null === $page ) {
			return;
		}

		$this->tags   = $this->prepare_tags( $page['tags'], $page['context'] );
		$this->groups = array( wc_get_container()->get( ProductOpenGraphPrices::class )->get_group( $this->tags ) );
		if ( $this->tags ) {
			add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'handle_wp_template_enhancement_output_buffer' ), PHP_INT_MAX );
		}
	}

	/**
	 * Filter and validate metadata before rendering starts.
	 *
	 * @param array<string, string>                           $tags   Metadata candidates.
	 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
	 * @return array<string, string>
	 */
	private function prepare_tags( array $tags, $context ): array {
		$this->default_card = $tags['twitter:card'] ?? null;
		$defaults           = $tags;

		/**
		 * Filters Open Graph metadata before output. Currently applied only to product pages.
		 * Other page types may be supported later; callbacks must check the object type.
		 * Removing og:type from $tags also preserves an existing provider's type.
		 *
		 * @since 11.4.0
		 *
		 * @param array<string, string> $tags Metadata keyed by og:, twitter:, or product: property name. Remove a key to omit a tag.
		 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
		 */
		$tags = apply_filters( 'woocommerce_open_graph_tags', $tags, $context );
		if ( ! is_array( $tags ) ) {
			return array();
		}

		foreach ( $tags as $property => $value ) {
			if ( ! is_string( $property ) || ! is_string( $value ) || '' === trim( $value ) || ! preg_match( '/^(og|twitter|product):[a-z_][a-z_0-9:]*$/D', $property ) ) {
				unset( $tags[ $property ] );
				continue;
			}
			if ( in_array( $property, array( 'og:url', 'og:image' ), true ) ) {
				$tags[ $property ] = esc_url_raw( $value );
				if ( '' === $tags[ $property ] ) {
					unset( $tags[ $property ] );
				}
			}
		}

		return $context instanceof WC_Product ? wc_get_container()->get( ProductOpenGraphPrices::class )->synchronize_aliases( $tags, $defaults ) : $tags;
	}

	/**
	 * Select metadata and its source object for the current page.
	 *
	 * @return array{tags: array<string, string>, context: WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null}|null
	 */
	private function get_page_data(): ?array {
		if ( is_product() ) {
			return $this->get_product_page_data();
		}

		return null;
	}

	/**
	 * Build metadata for a published, unprotected product page.
	 *
	 * @return array{tags: array<string, string>, context: WC_Product}|null
	 */
	private function get_product_page_data(): ?array {
		$product = wc_get_product( get_queried_object_id() );
		if (
			! $product instanceof WC_Product
			|| ProductStatus::PUBLISH !== $product->get_status()
			|| '' !== $product->get_post_password()
		) {
			return null;
		}

		return array(
			'tags'    => wc_get_container()->get( ProductOpenGraphTags::class )->get_tags( $product ),
			'context' => $product,
		);
	}

	/**
	 * Add metadata after the complete document is available.
	 *
	 * @internal
	 *
	 * @param mixed $html Filtered template output.
	 * @return mixed
	 */
	public function handle_wp_template_enhancement_output_buffer( $html ) {
		if ( ! is_string( $html ) || ! $this->tags ) {
			return $html;
		}

		return wc_get_container()->get( OpenGraphMarkup::class )->merge( $html, $this->tags, $this->default_card, $this->groups );
	}
}
