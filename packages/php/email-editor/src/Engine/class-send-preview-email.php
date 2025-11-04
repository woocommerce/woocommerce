<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;

/**
 * Class Send_Preview_Email
 *
 * This class is responsible for handling the functionality to send preview emails.
 * It is part of the email editor integrations utilities.
 *
 * @package Automattic\WooCommerce\EmailEditor\Integrations\Utils
 */
class Send_Preview_Email {

	/**
	 * Instance of the Renderer class used for rendering the editor emails.
	 *
	 * @var Renderer $renderer
	 */
	private Renderer $renderer;

	/**
	 * Instance of the Personalizer class used for rendering personalization tags.
	 *
	 * @var Personalizer $personalizer
	 */
	private Personalizer $personalizer;

	/**
	 * Send_Preview_Email constructor.
	 *
	 * @param Renderer     $renderer renderer instance.
	 * @param Personalizer $personalizer personalizer instance.
	 */
	public function __construct(
		Renderer $renderer,
		Personalizer $personalizer
	) {
		$this->renderer     = $renderer;
		$this->personalizer = $personalizer;
	}

	/**
	 * Sends a preview email.
	 *
	 * @param array $data The data required to send the preview email.
	 * @return bool Returns true if the preview email was sent successfully, false otherwise.
	 * @throws \Exception If the data is invalid.
	 */
	public function send_preview_email( $data ): bool {

		if ( is_bool( $data ) ) {
			// preview mail already sent. Do not process again.
			return $data;
		}

		$this->validate_data( $data );

		$email   = $data['email'];
		$post_id = $data['postId'];

		$post    = $this->fetch_post( $post_id );
		$subject = $post->post_title;

		$email_html_content = $this->render_html( $post );

		return $this->send_email( $email, $subject, $email_html_content );
	}

	/**
	 * Renders the HTML content of the post
	 *
	 * @param \WP_Post $post The WordPress post object.
	 * @return string
	 */
	public function render_html( $post ): string {
		$subject  = $post->post_title;
		$language = get_bloginfo( 'language' );

		// Add filter to set preview context for block renderers.
		add_filter( 'woocommerce_email_editor_rendering_email_context', array( $this, 'add_preview_context' ) );

		$rendered_data = $this->renderer->render(
			$post,
			$subject,
			__( 'Preview', 'woocommerce' ),
			$language
		);

		// Remove filter after rendering.
		remove_filter( 'woocommerce_email_editor_rendering_email_context', array( $this, 'add_preview_context' ) );

		$rendered_data = apply_filters( 'woocommerce_email_editor_send_preview_email_rendered_data', $rendered_data );

		return $this->set_personalize_content( $rendered_data['html'] );
	}

	/**
	 * Add preview context to email rendering.
	 *
	 * This filter callback adds the is_user_preview flag and current user information
	 * to the rendering context, allowing block renderers to show appropriate preview content.
	 *
	 * @param array $email_context Email context data.
	 * @return array Modified email context with preview flag.
	 */
	public function add_preview_context( $email_context ): array {
		$email_context['is_user_preview'] = true;
		return $email_context;
	}

	/**
	 * Personalize the content.
	 *
	 * @param string $content HTML content.
	 * @return string
	 */
	public function set_personalize_content( string $content ): string {
		$current_user = wp_get_current_user();
		$subscriber   = ! empty( $current_user->ID ) ? $current_user : null;

		$personalizer_context = array(
			'recipient_email' => $subscriber ? $subscriber->user_email : null,
			'is_user_preview' => true,
		);
		$personalizer_context = apply_filters( 'woocommerce_email_editor_send_preview_email_personalizer_context', $personalizer_context );

		$this->personalizer->set_context( $personalizer_context );
		return $this->personalizer->personalize_content( $content );
	}

	/**
	 * Sends an email preview.
	 *
	 * @param string $to The recipient email address.
	 * @param string $subject The subject of the email.
	 * @param string $body The body content of the email.
	 * @return bool Returns true if the email was sent successfully, false otherwise.
	 */
	public function send_email( string $to, string $subject, string $body ): bool {
		add_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );

		$result = wp_mail( $to, $subject, $body );

		// Reset content-type to avoid conflicts.
		remove_filter( 'wp_mail_content_type', array( $this, 'set_mail_content_type' ) );

		return $result;
	}


	/**
	 * Sets the mail content type. Used by $this->send_email.
	 *
	 * @param string $content_type The content type to be set for the mail.
	 * @return string The content type that was set.
	 */
	public function set_mail_content_type( string $content_type ): string {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return 'text/html';
	}

	/**
	 * Validates the provided data array.
	 *
	 * @param array $data The data array to be validated.
	 *
	 * @return void
	 * @throws \InvalidArgumentException If the data is invalid.
	 */
	private function validate_data( array $data ) {
		if ( empty( $data['email'] ) || empty( $data['postId'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Missing required data', 'woocommerce' ) );
		}

		if ( ! is_email( $data['email'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Invalid email', 'woocommerce' ) );
		}
	}


	/**
	 * Fetches a post_id post object based on the provided post ID.
	 *
	 * @param int $post_id The ID of the post to fetch.
	 * @return \WP_Post The WordPress post object.
	 * @throws \Exception If the post is invalid.
	 */
	private function fetch_post( $post_id ): \WP_Post {
		$post = get_post( intval( $post_id ) );
		if ( ! $post instanceof \WP_Post ) {
			throw new \Exception( esc_html__( 'Invalid post', 'woocommerce' ) );
		}
		return $post;
	}
}
