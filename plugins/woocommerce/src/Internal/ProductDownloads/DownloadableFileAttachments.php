<?php
/**
 * DownloadableFileAttachments class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductDownloads;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Attachments_Controller;

/**
 * Keeps downloadable product file URLs out of public attachment views.
 *
 * Files added with the product editor's "Choose file" button become unattached attachments in the woocommerce_uploads
 * directory, and WordPress treats unattached attachments as public. Without this, anyone could find their URLs through
 * the REST API media endpoint, attachment pages and feeds, or oEmbed. Users who can upload files or manage WooCommerce
 * still see them, since the media library already lists these files for them.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class DownloadableFileAttachments implements RegisterHooksInterface {

	/**
	 * Query var that marks a REST API media query as needing downloadable files excluded.
	 */
	private const EXCLUDE_QUERY_VAR = 'wc_exclude_downloadable_files';

	/**
	 * Start of the `_wp_attached_file` path, relative to the uploads directory, for downloadable product files.
	 */
	private const DIRECTORY = 'woocommerce_uploads/';

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_filter( 'rest_attachment_query', array( $this, 'handle_rest_attachment_query' ) );
		add_filter( 'posts_where', array( $this, 'handle_posts_where' ), PHP_INT_MAX, 2 );
		add_filter( 'rest_request_before_callbacks', array( $this, 'handle_rest_request_before_callbacks' ), 10, 3 );
		add_filter( 'posts_results', array( $this, 'handle_posts_results' ), 10, 2 );
		add_filter( 'oembed_response_data', array( $this, 'handle_oembed_response_data' ), 10, 2 );
		add_filter( 'attachment_link', array( $this, 'handle_attachment_link' ), 10, 2 );
		add_filter( 'get_the_guid', array( $this, 'handle_get_the_guid' ), 10, 2 );
		add_filter( 'comments_open', array( $this, 'handle_comments_open' ), 10, 2 );
		add_filter( 'pings_open', array( $this, 'handle_comments_open' ), 10, 2 );
		add_filter( 'redirect_canonical', array( $this, 'handle_redirect_canonical' ) );
		add_filter( 'previous_image_link', array( $this, 'handle_adjacent_image_link' ), 10, 2 );
		add_filter( 'next_image_link', array( $this, 'handle_adjacent_image_link' ), 10, 2 );
	}

	/**
	 * Mark REST API media collection queries to exclude downloadable product files.
	 *
	 * @internal
	 *
	 * @param array|mixed $args WP_Query arguments.
	 * @return array|mixed
	 */
	public function handle_rest_attachment_query( $args ) {
		if ( is_array( $args ) && ! $this->current_user_can_see_files() ) {
			$args[ self::EXCLUDE_QUERY_VAR ] = true;
		}

		return $args;
	}

	/**
	 * Exclude attachments stored in the woocommerce_uploads directory from marked queries.
	 *
	 * @internal
	 *
	 * @param string|mixed   $where WHERE clause of the query.
	 * @param WP_Query|mixed $query The query.
	 * @return string|mixed
	 */
	public function handle_posts_where( $where, $query ) {
		if ( ! is_string( $where ) || ! $query instanceof WP_Query || ! $query->get( self::EXCLUDE_QUERY_VAR ) ) {
			return $where;
		}

		global $wpdb;

		// Grouping the existing conditions keeps an OR added by another posts_where filter from escaping the exclusion.
		return " AND ( 1=1 {$where} )" . $wpdb->prepare(
			" AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS wc_attached_file WHERE wc_attached_file.post_id = {$wpdb->posts}.ID AND wc_attached_file.meta_key = '_wp_attached_file' AND wc_attached_file.meta_value LIKE %s )",
			$wpdb->esc_like( self::DIRECTORY ) . '%'
		);
	}

	/**
	 * Respond with "not found" when a single downloadable product file is requested from the REST API media endpoint.
	 *
	 * @internal
	 *
	 * @param mixed                                  $response Response to replace the requested version with, or null.
	 * @param array|mixed                            $handler  Route handler used for the request.
	 * @param \WP_REST_Request<array<string, mixed>> $request  Request used to generate the response.
	 * @return mixed
	 */
	public function handle_rest_request_before_callbacks( $response, $handler, $request ) {
		$callback = is_array( $handler ) ? ( $handler['callback'] ?? null ) : null;

		if (
			is_wp_error( $response )
			|| ! is_array( $callback )
			|| ! ( $callback[0] ?? null ) instanceof WP_REST_Attachments_Controller
			|| 'get_item' !== ( $callback[1] ?? null )
			|| ! $this->is_hidden_attachment( absint( $request['id'] ?? 0 ) )
		) {
			return $response;
		}

		return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'woocommerce' ), array( 'status' => 404 ) );
	}

	/**
	 * Drop a downloadable product file from the main query when it is requested as a single post.
	 *
	 * With nothing found, WordPress shows a 404 instead of the attachment page or its redirect to the file, and feeds,
	 * embeds, and theme templates have no attachment to render.
	 *
	 * @internal
	 *
	 * @param WP_Post[]|mixed $posts Posts found by the query.
	 * @param WP_Query|mixed  $query The query.
	 * @return WP_Post[]|mixed
	 */
	public function handle_posts_results( $posts, $query ) {
		if ( ! is_array( $posts ) || 1 !== count( $posts ) || ! $query instanceof WP_Query || ! $query->is_main_query() || ! $query->is_singular() ) {
			return $posts;
		}

		$post = reset( $posts );

		if ( ! $post instanceof WP_Post || 'attachment' !== $post->post_type || ! $this->is_hidden_attachment( $post->ID ) ) {
			return $posts;
		}

		// Pretty permalinks set the queried object before posts are fetched, and feed titles print its title.
		unset( $query->queried_object, $query->queried_object_id );

		return array();
	}

	/**
	 * Skip canonical redirects for downloadable product file attachment requests.
	 *
	 * Feed requests aren't turned into 404s, so with attachment pages disabled WordPress would redirect them to the file.
	 *
	 * @internal
	 *
	 * @param string|false|mixed $redirect_url Redirect URL.
	 * @return string|false|mixed
	 */
	public function handle_redirect_canonical( $redirect_url ) {
		return ( is_attachment() && $this->is_hidden_attachment( absint( get_query_var( 'attachment_id' ) ) ) ) ? false : $redirect_url;
	}

	/**
	 * Leave out previous and next image links that point to downloadable product files.
	 *
	 * Attachment pages for unattached images link to other unattached images, and the link can show the image itself.
	 *
	 * @internal
	 *
	 * @param string|mixed $output        Link HTML.
	 * @param int|mixed    $attachment_id ID of the linked attachment.
	 * @return string|mixed
	 */
	public function handle_adjacent_image_link( $output, $attachment_id ) {
		return $this->is_hidden_attachment( absint( $attachment_id ) ) ? '' : $output;
	}

	/**
	 * Use an ID-only link for downloadable product files.
	 *
	 * The usual link includes the attachment's slug, which is based on the original file name. Canonical and other
	 * redirects that point to the attachment then land on a URL that reveals nothing and shows a 404.
	 *
	 * @internal
	 *
	 * @param string|mixed $link          Attachment link.
	 * @param int|mixed    $attachment_id Attachment ID.
	 * @return string|mixed
	 */
	public function handle_attachment_link( $link, $attachment_id ) {
		$attachment_id = absint( $attachment_id );

		return $this->is_hidden_attachment( $attachment_id ) ? add_query_arg( 'attachment_id', $attachment_id, home_url( '/' ) ) : $link;
	}

	/**
	 * Use the ID-only link as the GUID of downloadable product files, which is otherwise the file's URL.
	 *
	 * Comment feeds print the GUID of the post each comment belongs to.
	 *
	 * @internal
	 *
	 * @param string|mixed $guid    Post GUID.
	 * @param int|mixed    $post_id Post ID.
	 * @return string|mixed
	 */
	public function handle_get_the_guid( $guid, $post_id ) {
		$post_id = absint( $post_id );

		return $this->is_hidden_attachment( $post_id ) ? add_query_arg( 'attachment_id', $post_id, home_url( '/' ) ) : $guid;
	}

	/**
	 * Close comments and pings on downloadable product files, which would otherwise list them in comment feeds.
	 *
	 * @internal
	 *
	 * @param bool|mixed $open    Whether comments or pings are open.
	 * @param int|mixed  $post_id Post ID.
	 * @return bool|mixed
	 */
	public function handle_comments_open( $open, $post_id ) {
		return $this->is_downloadable_file( absint( $post_id ) ) ? false : $open;
	}

	/**
	 * Return no oEmbed data for downloadable product files, which would otherwise include the URL of an image file.
	 *
	 * @internal
	 *
	 * @param array|false|mixed  $data oEmbed response data.
	 * @param WP_Post|null|mixed $post Post the data was generated for.
	 * @return array|false|mixed
	 */
	public function handle_oembed_response_data( $data, $post ) {
		return ( $post instanceof WP_Post && $this->is_hidden_attachment( $post->ID ) ) ? false : $data;
	}

	/**
	 * Check whether an attachment is a downloadable product file the current user should not see.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private function is_hidden_attachment( int $attachment_id ): bool {
		return $this->is_downloadable_file( $attachment_id ) && ! $this->current_user_can_see_files();
	}

	/**
	 * Check whether an attachment is stored in the woocommerce_uploads directory.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	private function is_downloadable_file( int $attachment_id ): bool {
		// Checking the post type first skips a meta lookup for the posts that comment and GUID hooks run on.
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		// Case-insensitive to match the SQL LIKE used for REST API collections.
		return 0 === stripos( (string) get_post_meta( $attachment_id, '_wp_attached_file', true ), self::DIRECTORY );
	}

	/**
	 * Check whether the current user may see downloadable product file URLs.
	 *
	 * @return bool
	 */
	private function current_user_can_see_files(): bool {
		return current_user_can( 'upload_files' ) || current_user_can( 'manage_woocommerce' );
	}
}
