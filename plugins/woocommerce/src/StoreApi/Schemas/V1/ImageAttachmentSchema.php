<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ImageAttachmentSchema class.
 */
class ImageAttachmentSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'image';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'image';

	/**
	 * Product schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return [
			'id'               => [
				'description' => __( 'Image ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'src'              => [
				'description' => __( 'Full size image URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'thumbnail'        => [
				'description' => __( 'Thumbnail URL.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'srcset'           => [
				'description' => __( 'Full size image srcset for responsive images.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'sizes'            => [
				'description' => __( 'Full size image sizes for responsive images.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'thumbnail_srcset' => [
				'description' => __( 'Thumbnail srcset for responsive images.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'thumbnail_sizes'  => [
				'description' => __( 'Thumbnail sizes for responsive images.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'name'             => [
				'description' => __( 'Image name.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
			'alt'              => [
				'description' => __( 'Image alternative text.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit', 'embed' ],
			],
		];
	}

	/**
	 * Convert a WooCommerce product into an object suitable for the response.
	 *
	 * @param int    $attachment_id  Image attachment ID.
	 * @param string $thumbnail_size Registered image size to use for the `thumbnail`
	 *                               (and `thumbnail_srcset`/`thumbnail_sizes`) fields.
	 *                               Defaults to `woocommerce_thumbnail`. Callers that render
	 *                               the image at small dimensions (for example the reviews
	 *                               blocks, which display a 3em avatar-style image) can pass
	 *                               a smaller registered size such as `thumbnail` to avoid
	 *                               serving an unnecessarily large image.
	 * @return object|null
	 */
	public function get_item_response( $attachment_id, $thumbnail_size = 'woocommerce_thumbnail' ) {
		if ( ! $attachment_id ) {
			return null;
		}

		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! is_array( $attachment ) ) {
			return null;
		}

		$thumbnail = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );

		return (object) [
			'id'               => (int) $attachment_id,
			'src'              => current( $attachment ),
			'thumbnail'        => current( $thumbnail ),
			'srcset'           => (string) wp_get_attachment_image_srcset( $attachment_id, 'full' ),
			'sizes'            => (string) wp_get_attachment_image_sizes( $attachment_id, 'full' ),
			'thumbnail_srcset' => (string) wp_get_attachment_image_srcset( $attachment_id, $thumbnail_size ),
			'thumbnail_sizes'  => (string) wp_get_attachment_image_sizes( $attachment_id, $thumbnail_size ),
			'name'             => get_the_title( $attachment_id ),
			'alt'              => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		];
	}

}
