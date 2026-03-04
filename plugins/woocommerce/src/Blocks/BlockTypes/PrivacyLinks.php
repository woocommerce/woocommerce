<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * PrivacyLinks class.
 *
 * @since 10.7.0
 */
class PrivacyLinks extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'privacy-links';

	/**
	 * Register the REST API endpoint for the editor.
	 *
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/**
	 * Register the privacy-links REST route.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			'wc/v3',
			'/privacy-links',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_privacy_links_response' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST API callback that returns privacy links including drafts.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_privacy_links_response() {
		$default_links = $this->get_default_privacy_links();

		/** This filter is documented in src/Blocks/BlockTypes/PrivacyLinks.php */
		$links = apply_filters( 'woocommerce_privacy_links', $default_links );

		// Include id and status so the editor can show draft badges.
		$index = 0;
		$links = array_values(
			array_filter(
				array_map(
					function ( $link ) use ( &$index ) {
						$link['id'] = ++$index;

						if ( empty( $link['page_id'] ) ) {
							$link['status'] = 'publish';
							return $link;
						}

						$post = get_post( $link['page_id'] );
						if ( ! $post ) {
							return null;
						}

						$link['status']  = $post->post_status;
						$link['edit_url'] = get_edit_post_link( $link['page_id'], 'raw' );

						return $link;
					},
					$links
				)
			)
		);

		return rest_ensure_response( $links );
	}

	/**
	 * Get the default privacy links from WooCommerce settings.
	 *
	 * @return array<int, array{label: string, url: string, page_id: int}> Array of privacy link data.
	 */
	private function get_default_privacy_links() {
		$links    = array();
		$page_ids = array(
			wc_privacy_policy_page_id(),
			wc_terms_and_conditions_page_id(),
			absint( get_option( 'woocommerce_refund_returns_page_id', 0 ) ),
		);

		foreach ( $page_ids as $page_id ) {
			if ( ! $page_id ) {
				continue;
			}

			$url = get_permalink( $page_id );
			if ( ! $url ) {
				continue;
			}

			$links[] = array(
				'label'   => html_entity_decode( get_the_title( $page_id ), ENT_QUOTES, 'UTF-8' ),
				'url'     => $url,
				'page_id' => $page_id,
			);
		}

		return $links;
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$default_links = $this->get_default_privacy_links();

		/**
		 * Filters the list of privacy links displayed by the Privacy Links block.
		 *
		 * Each link is an associative array with the following keys:
		 * - 'label'   (string) The link text.
		 * - 'url'     (string) The link URL.
		 * - 'page_id' (int)    Optional. The page ID. If provided, the link is only
		 *                      rendered when the page is published.
		 *
		 * @param array<int, array{label: string, url: string, page_id?: int}> $links Default privacy links.
		 *
		 * @since 10.7.0
		 */
		$links = apply_filters( 'woocommerce_privacy_links', $default_links );

		// Filter out links whose pages are not published.
		$links = array_filter(
			$links,
			function ( $link ) {
				if ( empty( $link['page_id'] ) ) {
					return true;
				}

				$post = get_post( $link['page_id'] );

				return $post && 'publish' === $post->post_status;
			}
		);

		if ( empty( $links ) ) {
			return '';
		}

		$links = $this->apply_inner_block_order( $links, $block );

		$list_items = '';
		foreach ( $links as $link ) {
			$list_items .= sprintf(
				'<li class="wp-block-woocommerce-privacy-links__item"><a href="%s">%s</a></li>',
				esc_url( $link['url'] ),
				esc_html( $link['label'] )
			);
		}

		return sprintf(
			'<ul %s>%s</ul>',
			get_block_wrapper_attributes(),
			$list_items
		);
	}

	/**
	 * Reorder links to match the inner block order saved in post content.
	 * Any new links not present in inner blocks are appended at the end.
	 *
	 * @param array     $links Filtered privacy links.
	 * @param \WP_Block $block Block instance.
	 * @return array Reordered links.
	 */
	private function apply_inner_block_order( $links, $block ) {
		if ( empty( $block->inner_blocks ) ) {
			return $links;
		}

		// Build a lookup of links keyed by page_id.
		$links_by_page_id = array();
		$links_without_id = array();
		foreach ( $links as $link ) {
			if ( ! empty( $link['page_id'] ) ) {
				$links_by_page_id[ $link['page_id'] ] = $link;
			} else {
				$links_without_id[] = $link;
			}
		}

		$ordered = array();
		$used_page_ids = array();

		// Follow inner block order.
		foreach ( $block->inner_blocks as $inner_block ) {
			$page_id = $inner_block->attributes['pageId'] ?? 0;
			if ( $page_id && isset( $links_by_page_id[ $page_id ] ) ) {
				$ordered[]       = $links_by_page_id[ $page_id ];
				$used_page_ids[] = $page_id;
			}
		}

		// Append any links not covered by inner blocks (e.g. newly added via filter).
		foreach ( $links_by_page_id as $page_id => $link ) {
			if ( ! in_array( $page_id, $used_page_ids, true ) ) {
				$ordered[] = $link;
			}
		}

		// Append links without page_id (custom filter links).
		foreach ( $links_without_id as $link ) {
			$ordered[] = $link;
		}

		return $ordered;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
