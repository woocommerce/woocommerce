<?php
/**
 * WooCommerce REST Functions
 *
 * Functions for REST specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses and formats a MySQL datetime (Y-m-d H:i:s) for ISO8601/RFC3339.
 *
 * Requered WP 4.4 or later.
 * See https://developer.wordpress.org/reference/functions/mysql_to_rfc3339/
 *
 * @since 2.6.0
 * @param string       $date
 * @return string|null ISO8601/RFC3339 formatted datetime.
 */
function wc_rest_prepare_date_response( $date ) {
	// Check if mysql_to_rfc3339 exists first!
	if ( ! function_exists( 'mysql_to_rfc3339' ) ) {
		return null;
	}

	// Return null if $date is empty/zeros.
	if ( '0000-00-00 00:00:00' === $date ) {
		return null;
	}

	// Return the formatted datetime.
	return mysql_to_rfc3339( $date );
}

/**
 * Upload image from URL.
 *
 * @since 2.6.0
 * @param string $image_url
 * @return array|WP_Error Attachment data or error message.
 */
function wc_rest_upload_image_from_url( $image_url ) {
	$file_name   = basename( current( explode( '?', $image_url ) ) );
	$wp_filetype = wp_check_filetype( $file_name, null );
	$parsed_url  = @parse_url( $image_url );

	// Check parsed URL.
	if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_image_url', sprintf( __( 'Invalid URL %s.', 'woocommerce' ), $image_url ), array( 'status' => 400 ) );
	}

	// Ensure url is valid.
	$image_url = esc_url_raw( $image_url );

	// Get the file.
	$response = wp_safe_remote_get( $image_url, array(
		'timeout' => 10
	) );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_remote_image_url', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ) . ' ' . sprintf( __( 'Error: %s.', 'woocommerce' ), $response->get_error_message() ), array( 'status' => 400 ) );
	} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_remote_image_url', sprintf( __( 'Error getting remote image %s.', 'woocommerce' ), $image_url ), array( 'status' => 400 ) );
	}

	// Ensure we have a file name and type.
	if ( ! $wp_filetype['type'] ) {
		$headers = wp_remote_retrieve_headers( $response );
		if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
			$disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
			$disposition = sanitize_file_name( $disposition );
			$file_name   = $disposition;
		} elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
			$file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
		}
		unset( $headers );
	}

	// Upload the file.
	$upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

	if ( $upload['error'] ) {
		return new WP_Error( 'woocommerce_rest_image_upload_error', $upload['error'], array( 'status' => 400 ) );
	}

	// Get filesize.
	$filesize = filesize( $upload['file'] );

	if ( 0 == $filesize ) {
		@unlink( $upload['file'] );
		unset( $upload );

		return new WP_Error( 'woocommerce_rest_image_upload_file_error', __( 'Zero size file downloaded.', 'woocommerce' ), array( 'status' => 400 ) );
	}

	do_action( 'woocommerce_rest_api_uploaded_image_from_url', $upload, $image_url );

	return $upload;
}

/**
 * Set uploaded image as attachment.
 *
 * @since 2.6.0
 * @param array $upload Upload information from wp_upload_bits.
 * @param int $id Post ID. Default to 0.
 * @return int Attachment ID
 */
function wc_rest_set_uploaded_image_as_attachment( $upload, $id = 0 ) {
	$info    = wp_check_filetype( $upload['file'] );
	$title   = '';
	$content = '';

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	if ( $image_meta = wp_read_image_metadata( $upload['file'] ) ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}
		if ( trim( $image_meta['caption'] ) ) {
			$content = $image_meta['caption'];
		}
	}

	$attachment = array(
		'post_mime_type' => $info['type'],
		'guid'           => $upload['url'],
		'post_parent'    => $id,
		'post_title'     => $title,
		'post_content'   => $content,
	);

	$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
	if ( ! is_wp_error( $attachment_id ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	}

	return $attachment_id;
}

/**
 * Validate reports request arguments.
 *
 * @since 2.6.0
 * @param mixed $value
 * @param WP_REST_Request $request
 * @param string $param
 * @return WP_Error|boolean
 */
function wc_rest_validate_reports_request_arg( $value, $request, $param ) {

	$attributes = $request->get_attributes();
	if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
		return true;
	}
	$args = $attributes['args'][ $param ];

	if ( 'string' === $args['type'] && ! is_string( $value ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_param', sprintf( __( '%1$s is not of type %2$s', 'woocommerce' ), $param, 'string' ) );
	}

	if ( 'date' === $args['format'] ) {
		$regex = '#^\d{4}-\d{2}-\d{2}$#';

		if ( ! preg_match( $regex, $value, $matches ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_date', __( 'The date you provided is invalid.', 'woocommerce' ) );
		}
	}

	return true;
}

/**
 * Encodes a value according to RFC 3986.
 * Supports multidimensional arrays.
 *
 * @since 2.6.0
 * @param string|array $value The value to encode.
 * @return string|array       Encoded values.
 */
function wc_rest_urlencode_rfc3986( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wc_rest_urlencode_rfc3986', $value );
	} else {
		// Percent symbols (%) must be double-encoded.
		return str_replace( '%', '%25', rawurlencode( rawurldecode( $value ) ) );
	}
}

/**
 * Check permissions of posts on REST API.
 *
 * @since 2.6.0
 * @param string $post_type Post type.
 * @param string $context   Request context.
 * @param int    $object_id Post ID.
 * @return bool
 */
function wc_rest_check_post_permissions( $post_type, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'read_private_posts',
		'create' => 'publish_posts',
		'edit'   => 'edit_post',
		'delete' => 'delete_post',
		'batch'  => 'edit_others_posts',
	);

	if ( 'revision' === $post_type ) {
		$permission = false;
	} else {
		$cap = $contexts[ $context ];
		$post_type_object = get_post_type_object( $post_type );
		$permission = current_user_can( $post_type_object->cap->$cap, $object_id );
	}

	return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $object_id, $post_type );
}

/**
 * Check permissions of users on REST API.
 *
 * @since 2.6.0
 * @param string $context   Request context.
 * @param int    $object_id Post ID.
 * @return bool
 */
function wc_rest_check_user_permissions( $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'list_users',
		'create' => 'edit_users',
		'edit'   => 'edit_users',
		'delete' => 'delete_users',
		'batch'  => 'edit_users',
	);

	$permission = current_user_can( $contexts[ $context ], $object_id );

	return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $object_id, 'user' );
}

/**
 * Check permissions of product terms on REST API.
 *
 * @since 2.6.0
 * @param string $taxonomy  Taxonomy.
 * @param string $context   Request context.
 * @param int    $object_id Post ID.
 * @return bool
 */
function wc_rest_check_product_term_permissions( $taxonomy, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'manage_terms',
		'create' => 'edit_terms',
		'edit'   => 'edit_terms',
		'delete' => 'delete_terms',
		'batch'  => 'edit_terms',
	);

	$cap = $contexts[ $context ];
	$taxonomy_object = get_taxonomy( $taxonomy );
	$permission = current_user_can( $taxonomy_object->cap->$cap, $object_id );

	return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, $object_id, $taxonomy );
}

/**
 * Check manager permissions on REST API.
 *
 * @since 2.6.0
 * @param string $object  Object.
 * @param string $context Request context.
 * @return bool
 */
function wc_rest_check_manager_permissions( $object, $context = 'read' ) {
	$objects = array(
		'reports'    => 'view_woocommerce_reports',
		'settings'   => 'manage_woocommerce',
		'attributes' => 'manage_product_terms',
	);

	$permission = current_user_can( $objects[ $object ] );

	return apply_filters( 'woocommerce_rest_check_permissions', $permission, $context, 0, $object );
}
