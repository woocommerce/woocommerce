<?php

class WP_JSON_Posts {
	/**
	 * Server object
	 *
	 * @var WP_JSON_ResponseHandler
	 */
	protected $server;

	/**
	 * Constructor
	 *
	 * @param WP_JSON_ResponseHandler $server Server object
	 */
	public function __construct(WP_JSON_ResponseHandler $server) {
		$this->server = $server;
	}

	/**
	 * Register the post-related routes
	 *
	 * @param array $routes Existing routes
	 * @return array Modified routes
	 */
	public function registerRoutes( $routes ) {
		$post_routes = array(
			// Post endpoints
			'/posts'             => array(
				array( array( $this, 'getPosts' ), WP_JSON_Server::READABLE ),
				array( array( $this, 'newPost' ),  WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
			),

			'/posts/(?P<id>\d+)' => array(
				array( array( $this, 'getPost' ),    WP_JSON_Server::READABLE ),
				array( array( $this, 'editPost' ),   WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
				array( array( $this, 'deletePost' ), WP_JSON_Server::DELETABLE ),
			),
			'/posts/(?P<id>\d+)/revisions' => array( '__return_null', WP_JSON_Server::READABLE ),

			// Comments
			'/posts/(?P<id>\d+)/comments'                  => array(
				array( array( $this, 'getComments' ), WP_JSON_Server::READABLE ),
				array( '__return_null', WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
			),
			'/posts/(?P<id>\d+)/comments/(?P<comment>\d+)' => array(
				array( array( $this, 'getComment' ), WP_JSON_Server::READABLE ),
				array( '__return_null', WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
				array( '__return_null', WP_JSON_Server::DELETABLE ),
			),

			// Meta-post endpoints
			'/posts/types'               => array( array( $this, 'getPostTypes' ), WP_JSON_Server::READABLE ),
			'/posts/types/(?P<type>\w+)' => array( array( $this, 'getPostType' ), WP_JSON_Server::READABLE ),
			'/posts/statuses'            => array( array( $this, 'getPostStatuses' ), WP_JSON_Server::READABLE ),
		);
		return array_merge( $routes, $post_routes );
	}

	/**
	 * Retrieve posts.
	 *
	 * @since 3.4.0
	 *
	 * The optional $filter parameter modifies the query used to retrieve posts.
	 * Accepted keys are 'post_type', 'post_status', 'number', 'offset',
	 * 'orderby', and 'order'.
	 *
	 * The optional $fields parameter specifies what fields will be included
	 * in the response array.
	 *
	 * @uses wp_get_recent_posts()
	 * @see WP_JSON_Posts::getPost() for more on $fields
	 * @see get_posts() for more on $filter values
	 *
	 * @param array $filter optional
	 * @param array $fields optional
	 * @return array contains a collection of Post entities.
	 */
	public function getPosts( $filter = array(), $context = 'view', $type = 'post', $page = 1 ) {
		$query = array();

		$post_type = get_post_type_object( $type );
		if ( ! ( (bool) $post_type ) )
			return new WP_Error( 'json_invalid_post_type', __( 'The post type specified is not valid' ), array( 'status' => 403 ) );

		$query['post_type'] = $post_type->name;

		global $wp;
		// Allow the same as normal WP
		$valid_vars = apply_filters('query_vars', $wp->public_query_vars);

		// If the user has the correct permissions, also allow use of internal
		// query parameters, which are only undesirable on the frontend
		//
		// To disable anyway, use `add_filter('json_private_query_vars', '__return_empty_array');`

		if ( current_user_can( $post_type->cap->edit_posts ) ) {
			$private = apply_filters('json_private_query_vars', $wp->private_query_vars);
			$valid_vars = array_merge($valid_vars, $private);
		}

		// Define our own in addition to WP's normal vars
		$json_valid = array('posts_per_page');
		$valid_vars = array_merge($valid_vars, $json_valid);

		// Filter and flip for querying
		$valid_vars = apply_filters('json_query_vars', $valid_vars);
		$valid_vars = array_flip($valid_vars);

		// Exclude the post_type query var to avoid dodging the permission
		// check above
		unset($valid_vars['post_type']);

		foreach ($valid_vars as $var => $index) {
			if ( isset( $filter[ $var ] ) ) {
				$query[ $var ] = apply_filters( 'json_query_var-' . $var, $filter[ $var ] );
			}
		}

		// Special parameter handling
		$query['paged'] = absint( $page );

		$post_query = new WP_Query();
		$posts_list = $post_query->query( $query );
		$this->server->query_navigation_headers( $post_query );

		if ( ! $posts_list )
			return array();

		// holds all the posts data
		$struct = array();

		$this->server->header( 'Last-Modified', mysql2date( 'D, d M Y H:i:s', get_lastpostmodified( 'GMT' ), 0 ).' GMT' );

		foreach ( $posts_list as $post ) {
			$post = get_object_vars( $post );

			// Do we have permission to read this post?
			if ( ! $this->checkReadPermission( $post ) )
				continue;

			$this->server->link_header( 'item', json_url( '/posts/' . $post['ID'] ), array( 'title' => $post['post_title'] ) );
			$struct[] = $this->prepare_post( $post, $context );
		}

		return $struct;
	}

	/**
	 * Check if we can read a post
	 *
	 * Correctly handles posts with the inherit status.
	 * @param array $post Post data
	 * @return boolean Can we read it?
	 */
	protected function checkReadPermission( $post ) {
		// Can we read the post?
		$post_type = get_post_type_object( $post['post_type'] );
		if ( 'publish' === $post['post_status'] || current_user_can( $post_type->cap->read_post, $post['ID'] ) ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post['post_status'] && $post['post_parent'] > 0 ) {
			$parent = get_post( $post['post_parent'], ARRAY_A );

			if ( $this->checkReadPermission( $parent ) ) {
				return true;
			}
		}

		// If we don't have a parent, but the status is set to inherit, assume
		// it's published (as per get_post_status())
		if ( 'inherit' === $post['post_status'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Create a new post for any registered post type.
	 *
	 * @since 3.4.0
	 * @internal 'data' is used here rather than 'content', as get_default_post_to_edit uses $_REQUEST['content']
	 *
	 * @param array $content Content data. Can contain:
	 *  - post_type (default: 'post')
	 *  - post_status (default: 'draft')
	 *  - post_title
	 *  - post_author
	 *  - post_excerpt
	 *  - post_content
	 *  - post_date_gmt | post_date
	 *  - post_format
	 *  - post_password
	 *  - comment_status - can be 'open' | 'closed'
	 *  - ping_status - can be 'open' | 'closed'
	 *  - sticky
	 *  - post_thumbnail - ID of a media item to use as the post thumbnail/featured image
	 *  - custom_fields - array, with each element containing 'key' and 'value'
	 *  - terms - array, with taxonomy names as keys and arrays of term IDs as values
	 *  - terms_names - array, with taxonomy names as keys and arrays of term names as values
	 *  - enclosure
	 *  - any other fields supported by wp_insert_post()
	 * @return array Post data (see {@see WP_JSON_Posts::getPost})
	 */
	function newPost( $data ) {
		unset( $data['ID'] );

		$result = $this->insert_post( $data );
		if ( is_string( $result ) || is_int( $result ) ) {
			$this->server->send_status( 201 );
			$this->server->header( 'Location', json_url( '/posts/' . $result ) );

			return $this->getPost( $result );
		}
		elseif ( $result instanceof IXR_Error ) {
			return new WP_Error( 'json_insert_error', $result->message, array( 'status' => $result->code ) );
		}
		else {
			return new WP_Error( 'json_insert_error', __( 'An unknown error occurred while creating the post' ), array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieve a post.
	 *
	 * @uses get_post()
	 * @param int $id Post ID
	 * @param array $fields Post fields to return (optional)
	 * @return array Post entity
	 */
	public function getPost( $id, $context = 'view' ) {
		$id = (int) $id;

		if ( empty( $id ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		$post = get_post( $id, ARRAY_A );

		if ( empty( $post['ID'] ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! $this->checkReadPermission( $post ) )
			return new WP_Error( 'json_user_cannot_read', __( 'Sorry, you cannot read this post.' ), array( 'status' => 401 ) );

		// Link headers (see RFC 5988)

		$this->server->header( 'Last-Modified', mysql2date( 'D, d M Y H:i:s', $post['post_modified_gmt'] ) . 'GMT' );

		$post = $this->prepare_post( $post, $context );
		if ( is_wp_error( $post ) )
			return $post;

		foreach ( $post['meta']['links'] as $rel => $url ) {
			$this->server->link_header( $rel, $url );
		}
		$this->server->link_header( 'alternate',  get_permalink( $id ), array( 'type' => 'text/html' ) );

		return $post;
	}

	/**
	 * Edit a post for any registered post type.
	 *
	 * The $data parameter only needs to contain fields that should be changed.
	 * All other fields will retain their existing values.
	 *
	 * @since 3.4.0
	 * @internal 'data' is used here rather than 'content', as get_default_post_to_edit uses $_REQUEST['content']
	 *
	 * @param int $id Post ID to edit
	 * @param array $data Data construct, see {@see WP_JSON_Posts::newPost}
	 * @param array $_headers Header data
	 * @return true on success
	 */
	function editPost( $id, $data, $_headers = array() ) {
		$id = (int) $id;

		if ( empty( $id ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		$post = get_post( $id, ARRAY_A );

		if ( empty( $post['ID'] ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		if ( isset( $_headers['IF_UNMODIFIED_SINCE'] ) ) {
			// As mandated by RFC2616, we have to check all of RFC1123, RFC1036
			// and C's asctime() format (and ignore invalid headers)
			$formats = array( DateTime::RFC1123, DateTime::RFC1036, 'D M j H:i:s Y' );
			foreach ( $formats as $format ) {
				$check = DateTime::createFromFormat( $format, $_headers['IF_UNMODIFIED_SINCE'] );

				if ( $check !== false )
					break;
			}

			// If the post has been modified since the date provided, return an error.
			if ( $check && mysql2date( 'U', $post['post_modified_gmt'] ) > $check->format('U') ) {
				return new WP_Error( 'json_old_revision', __( 'There is a revision of this post that is more recent.' ), array( 'status' => 412 ) );
			}
		}

		$data['ID'] = $id;

		$retval = $this->insert_post( $data );
		if ( is_wp_error( $retval ) ) {
			return $retval;
		}

		return $this->getPost( $id );
	}

	/**
	 * Delete a post for any registered post type
	 *
	 * @uses wp_delete_post()
	 * @param int $id
	 * @return true on success
	 */
	public function deletePost( $id, $force = false ) {
		$id = (int) $id;

		if ( empty( $id ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		$post = get_post( $id, ARRAY_A );

		if ( empty( $post['ID'] ) )
			return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );

		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! current_user_can( $post_type->cap->delete_post, $id ) )
			return new WP_Error( 'json_user_cannot_delete_post', __( 'Sorry, you are not allowed to delete this post.' ), array( 'status' => 401 ) );

		$result = wp_delete_post( $id, $force );

		if ( ! $result )
			return new WP_Error( 'json_cannot_delete', __( 'The post cannot be deleted.' ), array( 'status' => 500 ) );

		if ( $force ) {
			return array( 'message' => __( 'Permanently deleted post' ) );
		}
		else {
			// TODO: return a HTTP 202 here instead
			return array( 'message' => __( 'Deleted post' ) );
		}
	}

	/**
	 * Retrieve comments
	 *
	 * @param int $id Post ID to retrieve comments for
	 * @return array List of Comment entities
	 */
	public function getComments( $id ) {
		//$args = array('status' => $status, 'post_id' => $id, 'offset' => $offset, 'number' => $number )l
		$comments = get_comments( array('post_id' => $id) );

		$struct = array();
		foreach ( $comments as $comment ) {
			$struct[] = $this->prepare_comment( $comment, array( 'comment', 'meta' ), 'collection' );
		}
		return $struct;
	}

	/**
	 * Retrieve a single comment
	 *
	 * @param int $comment Comment ID
	 * @return array Comment entity
	 */
	public function getComment( $comment ) {
		$comment = get_comment( $comment );
		$data = $this->prepare_comment( $comment );
		return $data;
	}

	/**
	 * Get all public post types
	 *
	 * @uses self::getPostType()
	 * @return array List of post type data
	 */
	public function getPostTypes() {
		$data = get_post_types( array(), 'objects' );

		$types = array();
		foreach ($data as $name => $type) {
			$type = $this->getPostType( $type, true );
			if ( is_wp_error( $type ) )
				continue;

			$types[ $name ] = $type;
		}

		return $types;
	}

	/**
	 * Get a post type
	 *
	 * @param string|object $type Type name, or type object (internal use)
	 * @param boolean $_in_collection Is this in a collection? (internal use)
	 * @return array Post type data
	 */
	public function getPostType( $type, $_in_collection = false ) {
		if ( ! is_object( $type ) )
			$type = get_post_type_object($type);

		if ( $type->public === false )
			return new WP_Error( 'json_cannot_read_type', __( 'Cannot view post type' ), array( 'status' => 403 ) );

		$data = array(
			'name' => $type->label,
			'slug' => $type->name,
			'description' => $type->description,
			'labels' => $type->labels,
			'queryable' => $type->publicly_queryable,
			'searchable' => ! $type->exclude_from_search,
			'hierarchical' => $type->hierarchical,
			'meta' => array(
				'links' => array()
			),
		);

		if ( $_in_collection )
			$data['meta']['links']['self'] = json_url( '/posts/types/' . $type->name );
		else
			$data['meta']['links']['collection'] = json_url( '/posts/types' );

		if ( $type->publicly_queryable ) {
			if ($type->name === 'post')
				$data['meta']['links']['archives'] = json_url( '/posts' );
			else
				$data['meta']['links']['archives'] = json_url( add_query_arg( 'type', $type->name, '/posts' ) );
		}

		return apply_filters( 'json_post_type_data', $data, $type );
	}

	/**
	 * Get the registered post statuses
	 *
	 * @return array List of post status data
	 */
	public function getPostStatuses() {
		$statuses = get_post_stati(array(), 'objects');

		$data = array();
		foreach ($statuses as $status) {
			if ( $status->internal === true || ! $status->show_in_admin_status_list )
				continue;

			$data[ $status->name ] = array(
				'name' => $status->label,
				'slug' => $status->name,
				'public' => $status->public,
				'protected' => $status->protected,
				'private' => $status->private,
				'queryable' => $status->publicly_queryable,
				'show_in_list' => $status->show_in_admin_all_list,
				'meta' => array(
					'links' => array()
				),
			);
			if ( $status->publicly_queryable ) {
				if ($status->name === 'publish')
					$data[ $status->name ]['meta']['links']['archives'] = json_url( '/posts' );
				else
					$data[ $status->name ]['meta']['links']['archives'] = json_url( add_query_arg( 'status', $status->name, '/posts' ) );
			}
		}

		return apply_filters( 'json_post_statuses', $data, $statuses );
	}

	/**
	 * Prepares post data for return in an XML-RPC object.
	 *
	 * @access protected
	 *
	 * @param array $post The unprepared post data
	 * @param array $fields The subset of post type fields to return
	 * @return array The prepared post data
	 */
	protected function prepare_post( $post, $context = 'view' ) {
		// holds the data for this post. built up based on $fields
		$_post = array(
			'ID' => (int) $post['ID'],
		);

		$post_type = get_post_type_object( $post['post_type'] );
		if ( ! $this->checkReadPermission( $post ) )
			return new WP_Error( 'json_user_cannot_read', __( 'Sorry, you cannot read this post.' ), array( 'status' => 401 ) );

		// prepare common post fields
		$post_fields = array(
			'title'        => get_the_title( $post['ID'] ), // $post['post_title'],
			'status'       => $post['post_status'],
			'type'         => $post['post_type'],
			'author'       => (int) $post['post_author'],
			'content'      => apply_filters( 'the_content', $post['post_content'] ),
			'parent'       => (int) $post['post_parent'],
			#'post_mime_type'    => $post['post_mime_type'],
			'link'          => get_permalink( $post['ID'] ),
		);
		$post_fields_extended = array(
			'slug'           => $post['post_name'],
			'guid'           => apply_filters( 'get_the_guid', $post['guid'] ),
			'excerpt'        => $this->prepare_excerpt( $post['post_excerpt'] ),
			'menu_order'     => (int) $post['menu_order'],
			'comment_status' => $post['comment_status'],
			'ping_status'    => $post['ping_status'],
			'sticky'         => ( $post['post_type'] === 'post' && is_sticky( $post['ID'] ) ),
		);
		$post_fields_raw = array(
			'title_raw'   => $post['post_title'],
			'content_raw' => $post['post_content'],
			'guid_raw'    => $post['guid'],
		);

		// Dates
		$timezone = $this->server->get_timezone();

		$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $post['post_date'], $timezone );
		$post_fields['date'] = $date->format( 'c' );
		$post_fields_extended['date_tz'] = $date->format( 'e' );
		$post_fields_extended['date_gmt'] = date( 'c', strtotime( $post['post_date_gmt'] ) );

		$modified = DateTime::createFromFormat( 'Y-m-d H:i:s', $post['post_modified'], $timezone );
		$post_fields['modified'] = $modified->format( 'c' );
		$post_fields_extended['modified_tz'] = $modified->format( 'e' );
		$post_fields_extended['modified_gmt'] = date( 'c', strtotime( $post['post_modified_gmt'] ) );

		// Authorized fields
		// TODO: Send `Vary: Authorization` to clarify that the data can be
		// changed by the user's auth status
		if ( current_user_can( $post_type->cap->edit_post, $post['ID'] ) ) {
			$post_fields_extended['password'] = $post['post_password'];
		}

		// Consider future posts as published
		if ( $post_fields['status'] === 'future' )
			$post_fields['status'] = 'publish';

		// Fill in blank post format
		$post_fields['format'] = get_post_format( $post['ID'] );
		if ( empty( $post_fields['format'] ) )
			$post_fields['format'] = 'standard';

		$post_fields['author'] = $this->prepare_author( $post['post_author'] );

		if ( 'view' === $context && 0 !== $post['post_parent'] ) {
			// Avoid nesting too deeply
			// This gives post + post-extended + meta for the main post,
			// post + meta for the parent and just meta for the grandparent
			$parent = get_post( $post['post_parent'], ARRAY_A );
			$post_fields['parent'] = $this->prepare_post( $parent, 'parent' );
		}

		// Merge requested $post_fields fields into $_post
		$_post = array_merge( $_post, $post_fields );

		// Include extended fields. We might come back to this.
		$_post = array_merge( $_post, $post_fields_extended );

		if ( 'edit' === $context && current_user_can( $post_type->cap->edit_post, $post['ID'] ) )
			$_post = array_merge( $_post, $post_fields_raw );
		elseif ( 'edit' === $context )
			return new WP_Error( 'json_cannot_edit', __( 'Sorry, you cannot edit this post' ), array( 'status' => 403 ) );

		// Post meta
		$_post['post_meta'] = $this->prepare_meta( $post['ID'] );

		// Entity meta
		$_post['meta'] = array(
			'links' => array(
				'self'            => json_url( '/posts/' . $post['ID'] ),
				'author'          => json_url( '/users/' . $post['post_author'] ),
				'collection'      => json_url( '/posts' ),
				'replies'         => json_url( '/posts/' . $post['ID'] . '/comments' ),
				'version-history' => json_url( '/posts/' . $post['ID'] . '/revisions' ),
			),
		);

		if ( ! empty( $post['post_parent'] ) )
			$_post['meta']['links']['up'] = json_url( '/posts/' . (int) $post['post_parent'] );

		return apply_filters( 'json_prepare_post', $_post, $post, $context );
	}

	/**
	 * Retrieve the post excerpt.
	 *
	 * @return string
	 */
	protected function prepare_excerpt( $excerpt ) {
		if ( post_password_required() ) {
			return __( 'There is no excerpt because this is a protected post.' );
		}

		return apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $excerpt ) );
	}

	/**
	 * Retrieve custom fields for post.
	 *
	 * @since 2.5.0
	 *
	 * @param int $post_id Post ID.
	 * @return array Custom fields, if exist.
	 */
	protected function prepare_meta( $post_id ) {
		$post_id = (int) $post_id;

		$custom_fields = array();

		foreach ( (array) has_meta( $post_id ) as $meta ) {
			// Don't expose protected fields.
			if ( is_protected_meta( $meta['meta_key'] ) )
				continue;

			$custom_fields[] = array(
				'id'    => $meta['meta_id'],
				'key'   => $meta['meta_key'],
				'value' => $meta['meta_value'],
			);
		}

		return apply_filters( 'json_prepare_meta', $custom_fields );
	}

	protected function prepare_author( $author ) {
		$user = get_user_by( 'id', $author );

		if (!$author)
			return null;

		$author = array(
			'ID' => $user->ID,
			'name' => $user->display_name,
			'slug' => $user->user_nicename,
			'URL' => $user->user_url,
			'avatar' => $this->server->get_avatar( $user->user_email ),
			'meta' => array(
				'links' => array(
					'self' => json_url( '/users/' . $user->ID ),
					'archives' => json_url( '/users/' . $user->ID . '/posts' ),
				),
			),
		);

		if ( current_user_can( 'edit_user', $user->ID ) ) {
			$author['first_name'] = $user->first_name;
			$author['last_name'] = $user->last_name;
		}
		return $author;
	}

	/**
	 * Helper method for wp_newPost and wp_editPost, containing shared logic.
	 *
	 * @since 3.4.0
	 * @uses wp_insert_post()
	 *
	 * @param WP_User $user The post author if post_author isn't set in $content_struct.
	 * @param array $content_struct Post data to insert.
	 */
	protected function insert_post( $data ) {
		$post = array();
		$update = ! empty( $data['ID'] );

		if ( $update ) {
			$current_post = get_post( absint( $data['ID'] ) );
			if ( ! $current_post )
				return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 400 ) );
			$post['ID'] = absint( $data['ID'] );
		}
		else {
			// Defaults
			$post['post_author'] = 0;
			$post['post_password'] = '';
			$post['post_excerpt'] = '';
			$post['post_content'] = '';
			$post['post_title'] = '';
		}

		// Post type
		if ( ! empty( $data['type'] ) ) {
			// Changing post type
			$post_type = get_post_type_object( $data['type'] );
			if ( ! $post_type )
				return new WP_Error( 'json_invalid_post_type', __( 'Invalid post type' ), array( 'status' => 400 ) );

			$post['post_type'] = $data['type'];
		}
		elseif ( $update ) {
			// Updating post, use existing post type
			$current_post = get_post( $data['ID'] );
			if ( ! $current_post )
				return new WP_Error( 'json_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 400 ) );

			$post_type = get_post_type_object( $current_post->post_type );
		}
		else {
			// Creating new post, use default type
			$post['post_type'] = apply_filters( 'json_insert_default_post_type', 'post' );
			$post_type = get_post_type_object( $post['post_type'] );
			if ( ! $post_type )
				return new WP_Error( 'json_invalid_post_type', __( 'Invalid post type' ), array( 'status' => 400 ) );
		}

		// Permissions check
		if ( $update ) {
			if ( ! current_user_can( $post_type->cap->edit_post, $data['ID'] ) )
				return new WP_Error( 'json_cannot_edit', __( 'Sorry, you are not allowed to edit this post.' ), array( 'status' => 401 ) );
			if ( $post_type->name != get_post_type( $data['ID'] ) )
				return new WP_Error( 'json_cannot_change_post_type', __( 'The post type may not be changed.' ), array( 'status' => 400 ) );
		} else {
			if ( ! current_user_can( $post_type->cap->create_posts ) || ! current_user_can( $post_type->cap->edit_posts ) )
				return new WP_Error( 'json_cannot_create', __( 'Sorry, you are not allowed to post on this site.' ), array( 'status' => 400 ) );
		}

		// Post status
		if ( ! empty( $data['status'] ) ) {
			$post['post_status'] = $data['status'];
			switch ( $post['post_status'] ) {
				case 'draft':
				case 'pending':
					break;
				case 'private':
					if ( ! current_user_can( $post_type->cap->publish_posts ) )
						return new WP_Error( 'json_cannot_create_private', __( 'Sorry, you are not allowed to create private posts in this post type' ), array( 'status' => 403 ) );
					break;
				case 'publish':
				case 'future':
					if ( ! current_user_can( $post_type->cap->publish_posts ) )
						return new WP_Error( 'json_cannot_publish', __( 'Sorry, you are not allowed to publish posts in this post type' ), array( 'status' => 403 ) );
					break;
				default:
					if ( ! get_post_status_object( $post['post_status'] ) )
						$post['post_status'] = 'draft';
				break;
			}
		}

		// Post title
		if ( ! empty( $data['title'] ) ) {
			$post['post_title'] = $data['title'];
		}

		// Post date
		if ( ! empty( $data['date'] ) ) {
			list( $post['post_date'], $post['post_date_gmt'] ) = $this->server->get_date_with_gmt( $data['date'] );
		}
		elseif ( ! empty( $data['date_gmt'] ) ) {
			list( $post['post_date'], $post['post_date_gmt'] ) = $this->server->get_date_with_gmt( $data['date_gmt'], true );
		}

		// Post modified
		if ( ! empty( $data['modified'] ) ) {
			list( $post['post_modified'], $post['post_modified_gmt'] ) = $this->server->get_date_with_gmt( $data['modified'] );
		}
		elseif ( ! empty( $data['modified_gmt'] ) ) {
			list( $post['post_modified'], $post['post_modified_gmt'] ) = $this->server->get_date_with_gmt( $data['modified_gmt'], true );
		}

		// Post slug
		if ( ! empty( $data['name'] ) ) {
			$post['post_name'] = $data['name'];
		}

		// Author
		if ( ! empty( $data['author'] ) ) {
			// Allow passing an author object
			if ( is_object( $data['author'] ) ) {
				if ( empty( $data['author']->ID ) ) {
					return new WP_Error( 'json_invalid_author', __( 'Invalid author object.' ), array( 'status' => 400 ) );
				}
				$data['author'] = absint( $data['author']->ID );
			}
			else {
				$data['author'] = absint( $data['author'] );
			}

			// Only check edit others' posts if we are another user
			if ( $data['author'] !== get_current_user_id() ) {
				if ( ! current_user_can( $post_type->cap->edit_others_posts ) )
					return new WP_Error( 'json_cannot_edit_others', __( 'You are not allowed to edit posts as this user.' ), array( 'status' => 401 ) );

				$author = get_userdata( $post['post_author'] );

				if ( ! $author )
					return new WP_Error( 'json_invalid_author', __( 'Invalid author ID.' ), array( 'status' => 400 ) );
			}
		}

		// Post password
		if ( ! empty( $data['password'] ) ) {
			$post['post_password'] = $data['password'];
			if ( ! current_user_can( $post_type->cap->publish_posts ) )
				return new WP_Error( 'json_cannot_create_passworded', __( 'Sorry, you are not allowed to create password protected posts in this post type' ), array( 'status' => 401 ) );
		}

		// Content and excerpt
		if ( ! empty( $data['content_raw'] ) ) {
			$post['post_content'] = $data['content_raw'];
		}
		if ( ! empty( $data['excerpt_raw'] ) ) {
			$post['post_excerpt'] = $data['excerpt_raw'];
		}

		// Parent
		if ( ! empty( $data['parent'] ) ) {
			$parent = get_post( $data['parent'] );
			$post['post_parent'] = $data['post_parent'];
		}

		// Menu order
		if ( ! empty( $data['menu_order'] ) ) {
			$post['menu_order'] = $data['menu_order'];
		}

		// Comment status
		if ( ! empty( $data['comment_status'] ) ) {
			$post['comment_status'] = $data['comment_status'];
		}

		// Ping status
		if ( ! empty( $data['ping_status'] ) ) {
			$post['ping_status'] = $data['ping_status'];
		}

		// Post format
		if ( ! empty( $data['post_format'] ) ) {
			$formats = get_post_format_slugs();
			if ( ! in_array( $data['post_format'], $formats ) ) {
				return new WP_Error( 'json_invalid_post_format', __( 'Invalid post format.' ), array( 'status' => 400 ) );
			}
			$post['post_format'] = $data['post_format'];
		}

		// Pre-insert hook
		$can_insert = apply_filters( 'json_pre_insert_post', true, $post, $data, $update );
		if ( is_wp_error( $can_insert ) ) {
			return $can_insert;
		}

		// Post meta
		// TODO: implement this
		$post_ID = $update ? wp_update_post( $post, true ) : wp_insert_post( $post, true );

		if ( is_wp_error( $post_ID ) ) {
			return $post_ID;
		}

		// Sticky
		if ( isset( $post['sticky'] ) )  {
			if ( $post['sticky'] )
				stick_post( $data['ID'] );
			else
				unstick_post( $data['ID'] );
		}

		do_action( 'json_insert_post', $post, $data, $update );

		return $post_ID;
	}

	/**
	 * Parse an RFC3339 timestamp into a DateTime
	 *
	 * @param string $date RFC3339 timestamp
	 * @param boolean $force_utc Force UTC timezone instead of using the timestamp's TZ?
	 * @return DateTime
	 */
	protected function parse_date( $date, $force_utc = false ) {
		// Default timezone to the server's current one
		$timezone = self::get_timezone();
		if ( $force_utc ) {
			$date = preg_replace( '/[+-]\d+:?\d+$/', '+00:00', $date );
			$timezone = new DateTimeZone( 'UTC' );
		}

		// Strip millisecond precision (a full stop followed by one or more digits)
		if ( strpos( $date, '.' ) !== false ) {
			$date = preg_replace( '/\.\d+/', '', $date );
		}
		$datetime = DateTime::createFromFormat( DateTime::RFC3339, $date );

		return $datetime;
	}

	/**
	 * Get a local date with its GMT equivalent, in MySQL datetime format
	 *
	 * @param string $date RFC3339 timestamp
	 * @param boolean $force_utc Should we force UTC timestamp?
	 * @return array Local and UTC datetime strings, in MySQL datetime format (Y-m-d H:i:s)
	 */
	protected function get_date_with_gmt( $date, $force_utc = false ) {
		$datetime = $this->server->parse_date( $date, $force_utc );

		$datetime->setTimezone( self::get_timezone() );
		$local = $datetime->format( 'Y-m-d H:i:s' );

		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		$utc = $datetime->format('Y-m-d H:i:s');

		return array( $local, $utc );
	}

	/**
	 * Retrieve the avatar for a user who provided a user ID or email address.
	 *
	 * {@see get_avatar()} doesn't return just the URL, so we have to
	 * reimplement this here.
	 *
	 * @todo Rework how we do this. Copying it is a hack.
	 *
	 * @since 2.5
	 * @param string $email Email address
	 * @return string <img> tag for the user's avatar
	*/
	protected function get_avatar( $email ) {
		if ( ! get_option( 'show_avatars' ) )
			return false;

		$email_hash = md5( strtolower( trim( $email ) ) );

		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			if ( !empty($email) )
				$host = sprintf( 'http://%d.gravatar.com', ( hexdec( $email_hash[0] ) % 2 ) );
			else
				$host = 'http://0.gravatar.com';
		}

		$avatar = "$host/avatar/$email_hash&d=404";

		$rating = get_option( 'avatar_rating' );
		if ( !empty( $rating ) )
			$avatar .= "&r={$rating}";

		return apply_filters( 'get_avatar', $avatar, $email, '96', '404', '' );
	}

	/**
	 * Prepares comment data for returning as a JSON response.
	 *
	 * @param stdClass $comment Comment object
	 * @param array $requested_fields Fields to retrieve from the comment
	 * @param string $context Where is the comment being loaded?
	 * @return array Comment data for JSON serialization
	 */
	protected function prepare_comment( $comment, $requested_fields = array( 'comment', 'meta' ), $context = 'single' ) {
		$fields = array(
			'ID' => (int) $comment->comment_ID,
			'post' => (int) $comment->comment_post_ID,
		);

		$post = (array) get_post( $fields['post'] );

		// Content
		$fields['content'] = apply_filters( 'comment_text', $comment->comment_content, $comment );
		// $fields['content_raw'] = $comment->comment_content;

		// Status
		switch ( $comment->comment_approved ) {
			case 'hold':
			case '0':
				$fields['status'] = 'hold';
				break;

			case 'approve':
			case '1':
				$fields['status'] = 'approved';
				break;

			case 'spam':
			case 'trash':
			default:
				$fields['status'] = $comment->comment_approved;
		}

		// Type
		$fields['type'] = apply_filters( 'get_comment_type', $comment->comment_type );
		if ( empty( $fields['type'] ) ) {
			$fields['type'] = 'comment';
		}

		// Post
		if ( 'single' === $context ) {
			$parent = get_post( $post['post_parent'], ARRAY_A );
			$fields['parent'] = $this->prepare_post( $parent, 'single-parent' );
		}

		// Parent
		if ( ( 'single' === $context || 'single-parent' === $context ) && (int) $comment->comment_parent ) {
			$parent_fields = array( 'meta' );
			if ( $context === 'single' )
				$parent_fields[] = 'comment';
			$parent = get_comment( $post['post_parent'] );
			$fields['parent'] = $this->prepare_comment( $parent, $parent_fields, 'single-parent' );
		}

		// Parent
		$fields['parent'] = (int) $comment->comment_parent;

		// Author
		if ( (int) $comment->user_id !== 0 ) {
			$fields['author'] = $this->prepare_author( (int) $comment->user_id );
		}
		else {
			$fields['author'] = array(
				'ID' => 0,
				'name' => $comment->comment_author,
				'URL' => $comment->comment_author_url,
				'avatar' => $this->server->get_avatar( $comment->comment_author_email ),
			);
		}

		// Date
		$timezone = $this->server->get_timezone();

		$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $comment->comment_date, $timezone );
		$fields['date'] = $date->format( 'c' );
		$fields['date_tz'] = $date->format( 'e' );
		$fields['date_gmt'] = date( 'c', strtotime( $comment->comment_date_gmt ) );

		// Meta
		$meta = array(
			'links' => array(
				'up' => json_url( sprintf( '/posts/%d', (int) $comment->comment_post_ID ) )
			),
		);
		if ( 0 !== (int) $comment->comment_parent ) {
			$meta['links']['in-reply-to'] = json_url( sprintf( '/posts/%d/comments/%d', (int) $comment->comment_post_ID, (int) $comment->comment_parent ) );
		}
		if ( 'single' !== $context ) {
			$meta['links']['self'] = json_url( sprintf( '/posts/%d/comments/%d', (int) $comment->comment_post_ID, (int) $comment->comment_ID ) );
		}

		// Remove unneeded fields
		$data = array();
		if ( in_array( 'comment', $requested_fields ) )
			$data = array_merge( $data, $fields );

		if ( in_array( 'meta', $requested_fields ) )
			$data['meta'] = $meta;

		return $data;
	}
}