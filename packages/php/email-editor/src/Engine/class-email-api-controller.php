<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Validator\Builder;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class for email API controller.
 */
class Email_Api_Controller {
	/**
	 * Personalization tags registry to get all personalization tags.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private Personalization_Tags_Registry $personalization_tags_registry;

	/**
	 * Email_Api_Controller constructor with all dependencies.
	 *
	 * @param Personalization_Tags_Registry $personalization_tags_registry Personalization tags registry.
	 */
	public function __construct( Personalization_Tags_Registry $personalization_tags_registry ) {
		$this->personalization_tags_registry = $personalization_tags_registry;
	}

	/**
	 * Returns email specific data.
	 *
	 * @return array - Email specific data such styles.
	 */
	public function get_email_data(): array {
		// Here comes code getting Email specific data that will be passed on 'email_data' attribute.
		return array();
	}

	/**
	 * Update Email specific data we store.
	 *
	 * @param array   $data - Email specific data.
	 * @param WP_Post $email_post - Email post object.
	 */
	public function save_email_data( array $data, WP_Post $email_post ): void {
		// Here comes code saving of Email specific data that will be passed on 'email_data' attribute.
	}

	/**
	 * Sends preview email.
	 *
	 * @param WP_REST_Request $request Route request parameters.
	 * @return WP_REST_Response
	 * @phpstan-param WP_REST_Request<array{_locale: string, email: string, postId: int}> $request
	 */
	public function send_preview_email_data( WP_REST_Request $request ): WP_REST_Response {
		/**
		 * $data - Post Data
		 * format
		 * [_locale] => user
		 * [email] => Provided email address
		 * [postId] => POST_ID
		 *
		 * @var array{_locale: string, email: string, postId: int} $data
		 */
		$data = $request->get_params();
		try {
			$result = apply_filters( 'woocommerce_email_editor_send_preview_email', $data );
			return new WP_REST_Response(
				array(
					'success' => (bool) $result,
					'result'  => $result,
				),
				$result ? 200 : 400
			);
		} catch ( \Exception $exception ) {
			return new WP_REST_Response( array( 'error' => $exception->getMessage() ), 400 );
		}
	}

	/**
	 * Returns all registered personalization tags.
	 *
	 * @return WP_REST_Response
	 */
	public function get_personalization_tags(): WP_REST_Response {
		$tags = $this->personalization_tags_registry->get_all();
		return new WP_REST_Response(
			array(
				'success' => true,
				'result'  => array_values(
					array_map(
						function ( Personalization_Tag $tag ) {
							return array(
								'name'          => $tag->get_name(),
								'token'         => $tag->get_token(),
								'category'      => $tag->get_category(),
								'attributes'    => $tag->get_attributes(),
								'valueToInsert' => $tag->get_value_to_insert(),
								'postTypes'     => $tag->get_post_types(),
							);
						},
						$tags
					),
				),
			),
			200
		);
	}

	/**
	 * Returns the schema for email data.
	 *
	 * @return array
	 */
	public function get_email_data_schema(): array {
		return Builder::object()->to_array();
	}
}
