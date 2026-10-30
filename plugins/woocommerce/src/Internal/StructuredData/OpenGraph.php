<?php
/**
 * Product Open Graph metadata.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Provides product link previews and fills missing Open Graph metadata.
 */
class OpenGraph {

	/**
	 * Ordered metadata groups, each containing related tags.
	 *
	 * @var array<array<string, string>>
	 */
	private $tags = array();

	/**
	 * Default Twitter card before filtering, or null when omitted.
	 *
	 * @var string|null
	 */
	private $default_card = null;

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

		if ( ComingSoonRequestHandler::is_showing_coming_soon() ) {
			return;
		}

		$page = $this->get_page_data();
		if ( null === $page ) {
			return;
		}

		$this->tags = $this->prepare_tags( $page['tags'], $page['context'] );
		if ( $this->tags ) {
			add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'handle_wp_template_enhancement_output_buffer' ), PHP_INT_MAX );
		}
	}

	/**
	 * Filter and validate metadata before rendering starts.
	 *
	 * @param array<array<string, string>>                    $tags   Metadata candidates.
	 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
	 * @return array<array<string, string>>
	 */
	private function prepare_tags( array $tags, $context ): array {
		$this->default_card = array_column( $tags, 'twitter:card' )[0] ?? null;

		/**
		 * Filters Open Graph metadata before output. Applied to product and shop pages.
		 * Other page types may be supported later; callbacks must check the object type.
		 * The first tag in each group is its root; details follow it. Remove or replace an entire group to omit or replace its details.
		 *
		 * @since 11.4.0
		 *
		 * @param array<array<string, string>> $tags Ordered groups of og:, twitter:, or product: tags with string values. Removing the og:type group preserves an existing provider's type.
		 * @param WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null $context Product or queried object; null when no object is available.
		 */
		$tags = apply_filters( 'woocommerce_open_graph_tags', $tags, $context );
		if ( ! is_array( $tags ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $tags as $group ) {
			if ( ! is_array( $group ) || ! $group ) {
				continue;
			}
			$root = array_key_first( $group );
			// Media details cannot start a group; the :url alias is also a valid root.
			if ( ! is_string( $root ) || preg_match( '/^og:(image|video|audio):(?!url$)/D', $root ) ) {
				continue;
			}
			foreach ( $group as $property => $value ) {
				if ( ! is_string( $property ) || ! is_string( $value ) || '' === trim( $value ) || ! preg_match( '/^(og|twitter|product):[a-z_:]+$/D', $property ) ) {
					unset( $group[ $property ] );
					continue;
				}
				if ( 'og:url' === $property || preg_match( '/^og:(image|video|audio)(:(url|secure_url))?$/D', $property ) ) {
					$group[ $property ] = esc_url_raw( $value, array( 'http', 'https' ) );
					if ( '' === $group[ $property ] ) {
						unset( $group[ $property ] );
					}
				}
			}
			if ( isset( $group[ $root ] ) ) {
				$prepared[] = $group;
			}
		}

		return $prepared;
	}

	/**
	 * Select metadata and its source object for the current page.
	 *
	 * @return array{tags: array<array<string, string>>, context: WC_Product|\WP_Post|\WP_Term|\WP_Post_Type|null}|null
	 */
	private function get_page_data(): ?array {
		if ( is_product() ) {
			return $this->get_product_page_data();
		}

		if ( is_shop() && ! is_search() && ! is_paged() ) {
			return $this->get_shop_page_data();
		}

		return null;
	}

	/**
	 * Build metadata for a published, unprotected product page.
	 *
	 * @return array{tags: array<array<string, string>>, context: WC_Product}|null
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
	 * Build metadata for the configured shop page.
	 *
	 * @return array{tags: array<array<string, string>>, context: \WP_Post}|null
	 */
	private function get_shop_page_data(): ?array {
		$page = get_post( wc_get_page_id( 'shop' ) );
		if ( ! $page instanceof \WP_Post || 'publish' !== $page->post_status || '' !== $page->post_password ) {
			return null;
		}

		return array(
			'tags'    => wc_get_container()->get( ShopOpenGraphTags::class )->get_tags( $page ),
			'context' => $page,
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
		if ( ! is_string( $html ) || ! $this->tags || ComingSoonRequestHandler::is_showing_coming_soon() ) {
			return $html;
		}

		return wc_get_container()->get( OpenGraphMarkup::class )->merge( $html, $this->tags, $this->default_card );
	}
}
