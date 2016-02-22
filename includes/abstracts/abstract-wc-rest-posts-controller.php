<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Rest Posts Controler Class
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/Abstracts
 * @version  2.6.0
 */
abstract class WC_REST_Posts_Controller extends WP_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Controls visibility on frontend.
	 *
	 * @var string
	 */
	protected $public = false;

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( sprintf( 'woocommerce_rest_api_invalid_%s_id', $this->post_type ), __( 'Invalid id.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$data = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', get_permalink( $id ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Check if a given request has access to read a post.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( 'edit' === $request['context'] && $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error( 'rest_forbidden_context', sprintf( __( 'Sorry, you are not allowed to edit %s', 'woocommerce' ), $this->rest_base ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( $post ) {
			return $this->check_read_permission( $post );
		}

		return true;
	}

	/**
	 * Check if we can read a post.
	 *
	 * Correctly handles posts with the inherit status.
	 *
	 * @param object $post Post object.
	 * @return boolean Can we read it?
	 */
	public function check_read_permission( $post ) {
		return $this->check_permission( $post, 'read' );
	}

	/**
	 * Check if we can edit a post.
	 *
	 * @param object $post Post object.
	 * @return boolean Can we edit it?
	 */
	protected function check_update_permission( $post ) {
		return $this->check_permission( $post, 'edit' );
	}

	/**
	 * Check if we can create a post.
	 *
	 * @param object $post Post object.
	 * @return boolean Can we create it?.
	 */
	protected function check_create_permission( $post ) {
		return $this->check_permission( $post, 'edit' );
	}

	/**
	 * Check if we can delete a post.
	 *
	 * @param object $post Post object.
	 * @return boolean Can we delete it?
	 */
	protected function check_delete_permission( $post ) {
		return $this->check_permission( $post, 'delete' );
	}

	/**
	 * Checks the permissions for the current user given a post and context.
	 *
	 * @param WP_Post|int $post
	 * @param string $context the type of permission to check, either `read`, `write`, or `delete`
	 * @return bool true if the current user has the permissions to perform the context on the post
	 */
	private function check_permission( $post, $context ) {
		$permission = false;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			$post = get_post( $post );
		}

		if ( is_null( $post ) ) {
			return $permission;
		}

		$post_type = get_post_type_object( $post->post_type );

		if ( 'read' === $context ) {
			$permission = 'revision' !== $post->post_type && current_user_can( $post_type->cap->read_private_posts, $post->ID );
		} elseif ( 'edit' === $context ) {
			$permission = current_user_can( $post_type->cap->edit_post, $post->ID );
		} elseif ( 'delete' === $context ) {
			$permission = current_user_can( $post_type->cap->delete_post, $post->ID );
		}

		return apply_filters( 'woocommerce_api_check_permission', $permission, $context, $post, $post_type );
	}

	/**
	 * Check the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @param string       $date_gmt
	 * @param string|null  $date
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}
		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}
		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt );
	}
}
