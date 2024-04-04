<?php
/**
 * Marketing Specs Handler
 *
 * Fetches the specifications for the marketing feature from WooCommerce.com API.
 */

namespace Automattic\WooCommerce\Internal\Admin\Marketing;

/**
 * Marketing Specifications Class.
 *
 * @internal
 * @since x.x.x
 */
class MarketingSpecs {
	/**
	 * Name of knowledge base post transient.
	 *
	 * @var string
	 */
	const KNOWLEDGE_BASE_TRANSIENT = 'wc_marketing_knowledge_base';

	/**
	 * Load knowledge base posts from Woo.com
	 *
	 * @param string|null $topic The topic of marketing knowledgebase to retrieve.
	 * @return array
	 */
	public function get_knowledge_base_posts( ?string $topic ): array {
		// Default to the marketing topic (if no topic is set on the kb component).
		if ( empty( $topic ) ) {
			$topic = 'marketing';
		}

		$kb_transient = self::KNOWLEDGE_BASE_TRANSIENT . '_' . strtolower( $topic );

		$posts = get_transient( $kb_transient );

		if ( false === $posts ) {
			$request_url = add_query_arg(
				array(
					'page'     => 1,
					'per_page' => 8,
					'_embed'   => 1,
				),
				'https://woocommerce.com/wp-json/wccom/marketing-knowledgebase/v1/posts/' . $topic
			);

			$request = wp_remote_get(
				$request_url,
				array(
					'user-agent' => 'WooCommerce/' . WC()->version . '; ' . get_bloginfo( 'url' ),
				)
			);
			$posts   = array();

			if ( ! is_wp_error( $request ) && 200 === $request['response']['code'] ) {
				$raw_posts = json_decode( $request['body'], true );

				foreach ( $raw_posts as $raw_post ) {
					$post = array(
						'title'         => html_entity_decode( $raw_post['title']['rendered'] ),
						'date'          => $raw_post['date_gmt'],
						'link'          => $raw_post['link'],
						'author_name'   => isset( $raw_post['author_name'] ) ? html_entity_decode( $raw_post['author_name'] ) : '',
						'author_avatar' => isset( $raw_post['author_avatar_url'] ) ? $raw_post['author_avatar_url'] : '',
					);

					$featured_media = isset( $raw_post['_embedded']['wp:featuredmedia'] ) && is_array( $raw_post['_embedded']['wp:featuredmedia'] ) ? $raw_post['_embedded']['wp:featuredmedia'] : array();
					if ( count( $featured_media ) > 0 ) {
						$image         = current( $featured_media );
						$post['image'] = add_query_arg(
							array(
								'resize' => '650,340',
								'crop'   => 1,
							),
							$image['source_url']
						);
					}

					$posts[] = $post;
				}
			}

			set_transient(
				$kb_transient,
				$posts,
				// Expire transient in 15 minutes if remote get failed.
				empty( $posts ) ? 900 : DAY_IN_SECONDS
			);
		}

		return $posts;
	}
}
