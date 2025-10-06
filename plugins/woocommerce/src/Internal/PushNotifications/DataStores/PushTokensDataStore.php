<?php
/**
 * PushTokensDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\PushNotifications\DataStores;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use InvalidArgumentException;
use WC_Object_Data_Store_Interface;

/**
 * Data store class for push tokens.
 */
class PushTokensDataStore implements WC_Object_Data_Store_Interface {
	/**
	 * Creates a post representing the push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be created.
	 */
	public function create( &$push_token ) {
		if ( ! $push_token->can_be_created() ) {
			throw new InvalidArgumentException(
				'Can\'t create push token because the push token object data is incorrect.'
			);
		}

		$id = wp_insert_post(
			[
				'post_author'     => $push_token->get_user_id(),
				'post_type'       => PushToken::POST_TYPE,
				'post_status'     => 'publish',
				'meta_input'      => array(
					'platform'    => $push_token->get_platform(),
					'token'       => $push_token->get_token(),
					'device_uuid' => $push_token->get_device_uuid(),
				),
			]
		);

		$push_token->set_id( $id );
	}

	/**
	 * Gets post representing a push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read.
	 * @return null|PushToken
	 */
	public function read( &$push_token ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException( 'Can\'t read push token because the push token object is incomplete.' );
		}

		$post = get_post( $push_token->get_id() );

		if ( ! $post ) {
			return null;
		}

		$meta = $this->read_meta( $push_token );

		$push_token->set_user_id( $post->post_author );
		$push_token->set_token( $meta['token'] ?? null );
		$push_token->set_platform( $meta['platform'] ?? null );
		$push_token->set_device_uuid( $meta['device_uuid'] ?? null );
	}

	/**
	 * Updates a post representing the push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be updated.
	 */
	public function update( &$push_token ) {
		if ( ! $push_token->can_be_updated() ) {
			throw new InvalidArgumentException( 'Can\'t update push token because the push token object is incomplete.' );
		}

		wp_update_post(
			[
				'ID'              => $push_token->get_id(),
				'post_author'     => $push_token->get_user_id(),
				'post_type'       => PushToken::POST_TYPE,
				'post_status'     => 'publish',
				'meta_input'      => array(
					'platform'    => $push_token->get_platform(),
					'token'       => $push_token->get_token(),
					'device_uuid' => $push_token->get_device_uuid(),
				),
			]
		);
	}

	/**
	 * Deletes a push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $args Not used, enforced by interface.
	 * @return void
	 * @throws InvalidArgumentException If the token can't be deleted.
	 */
	public function delete( &$push_token, $args = array() ) {
		if ( ! $push_token->can_be_deleted() ) {
			throw new InvalidArgumentException( 'Can\'t delete push token because the push token object is incomplete.' );
		}

		wp_delete_post( $push_token->get_id() );
	}

	/**
	 * Returns an array of post meta objects as key => value pairs.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 * @return array
	 */
	public function read_meta( &$push_token ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException( 'Can\'t read meta for push token object with incomplete data.' );
		}

		return array_map(
			fn ( $meta ) => $meta[0] ?? $meta,
			get_post_meta( $push_token->get_id() )
		);
	}

	/**
	 * Add new piece of meta to the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function add_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException( 'Can\'t add meta for push token object with incomplete data.' );
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException( 'Can\'t add meta for push token without meta key.' );
		}

		return add_post_meta(
			$push_token->get_id(),
			$meta['meta_key'],
			$meta['meta_value'] ?? null,
			true
		);
	}

	/**
	 * Updates meta for the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing the meta key and value.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function update_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException( 'Can\'t update meta for push token object with incomplete data.' );
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException( 'Can\'t update meta for push token without meta key.' );
		}

		return update_post_meta(
			$push_token->get_id(),
			$meta['meta_key'],
			$meta['meta_value'] ?? null
		);
	}

	/**
	 * Deletes meta for the given push token.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @param array     $meta Array containing at least the meta key.
	 * @throws InvalidArgumentException If the token can't be read or meta key not given.
	 */
	public function delete_meta( &$push_token, $meta ) {
		if ( ! $push_token->can_be_read() ) {
			throw new InvalidArgumentException( 'Can\'t delete meta for push token object with incomplete data.' );
		}

		if ( empty( $meta['meta_key'] ) ) {
			throw new InvalidArgumentException( 'Can\'t delete meta for push token without meta key.' );
		}

		return delete_post_meta( $push_token->get_id(), $meta['meta_key'] );
	}

	/**
	 * Find tokens for this user and platform that match either the token
	 * or device UUID. We check the token value to avoid creating a duplicate.
	 * We check the device UUID value because only one token should be issued
	 * per device, therefore if we already have one then we can update it to
	 * avoid creating a duplicate.
	 *
	 * @param PushToken $push_token An instance of PushToken.
	 * @return null|PushToken
	 * @throws InvalidArgumentException If push token is missing data.
	 */
	public function get_by_token_or_device_id( &$push_token ): ?PushToken {
		if ( ! $push_token->get_user_id() || ! $push_token->get_platform() ) {
			throw new InvalidArgumentException(
				'Can\'t retrieve token using token or device UUID for push token object without user ID and platform.',
			);
		}

		$args = [
			'post_type'           => PushToken::POST_TYPE,
			'author'              => $push_token->get_user_id(),
			'meta_query'          => array(
				'relation'        => 'AND',
				array(
					'key'         => 'platform',
					'compare'     => '=',
					'value'       => $push_token->get_platform(),
				),
				array(
					'relation'    => 'OR',
					array(
						'key'     => 'token',
						'compare' => '=',
						'value'   => $push_token->get_token(),
					),
					array(
						'key'     => 'device_uuid',
						'compare' => '=',
						'value'   => $push_token->get_device_uuid(),
					),
				),
			),
		];

		$push_token_data = get_posts( $args );

		if ( ! $push_token_data ) {
			return null;
		}

		$push_token->set_id( $push_token_data[0]->ID );
		$push_token->set_user_id( $push_token_data[0]->post_author );

		$meta = $this->read_meta( $push_token );

		$push_token->set_token( $meta['token'] ?? null );
		$push_token->set_device_uuid( $meta['device_uuid'] ?? null );
		$push_token->set_platform( $meta['platform'] ?? null );

		return $push_token;
	}
}
