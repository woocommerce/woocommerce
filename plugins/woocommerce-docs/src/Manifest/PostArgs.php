<?php

namespace WooCommerceDocs\Manifest;

/**
 * Generate post args from manifest.
 *
 * @package WooCommerceDocs\Manifest
 */
class PostArgs {
	/**
	 * Post args
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Constructor
	 *
	 * @param mixed  $manifest_post - The manifest representation of a post.
	 * @param string $post_content - The post content.
	 * @return void
	 */
	public function __construct( $manifest_post, $post_content ) {

		$possible_attributes = array(
			'post_title',
			'post_author',
			'post_date',
			'comment_status',
			'post_status',
		);

		$args = array();

		foreach ( $possible_attributes as $attribute ) {
			if ( isset( $manifest_post[ $attribute ] ) ) {
				$args[ $attribute ] = $manifest_post[ $attribute ];
			}
		}

		$args['post_content'] = $post_content;

		if ( ! isset( $args['post_status'] ) ) {
			$args['post_status'] = 'publish';
		}

		$this->args = $args;
	}

	/**
	 * Get the post args.
	 *
	 * @return array
	 */
	public function get_args() {
		return $this->args;
	}
}
