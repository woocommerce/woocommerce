<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer as EmailRenderer;
use Automattic\WooCommerce\Internal\EmailEditor\Renderer\Blocks\WooContent;

/**
 * Class responsible for rendering block-based emails.
 */
class BlockEmailRenderer {
	const WOO_EMAIL_CONTENT_PLACEHOLDER_BLOCK = 'woo/email-content';
	const WOO_EMAIL_CONTENT_PLACEHOLDER       = '##WOO_CONTENT##';

	/**
	 * Service for rendering block emails
	 *
	 * @var EmailRenderer
	 */
	private $renderer;

	/**
	 * Service for personalization of emails
	 * It replaces personalization tags with actual values
	 *
	 * @var Personalizer
	 */
	private $personalizer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container   = Email_Editor_Container::container();
		$this->renderer     = $editor_container->get( EmailRenderer::class );
		$this->personalizer = $editor_container->get( Personalizer::class );
	}

	/**
	 * Initialize the renderer.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'woocommerce_blocks_renderer_initialized', array( $this, 'register_block_renderers' ) );
	}

	/**
	 * Callback for registering WooCommerce email block renderers.
	 *
	 * @param Blocks_Registry $blocks_registry Block renderer registry.
	 */
	public function register_block_renderers( $blocks_registry ): void {
		$blocks_registry->add_block_renderer( self::WOO_EMAIL_CONTENT_PLACEHOLDER_BLOCK, new WooContent() );
	}

	/**
	 * Maybe render block-based email content.
	 *
	 * @param \WP_Post  $email_post Email post.
	 * @param string    $woo_content WooCommerce email content.
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string Modified email content
	 */
	public function render_block_email( \WP_Post $email_post, string $woo_content, \WC_Email $wc_email ): ?string {
		$subject   = $wc_email->get_subject(); // We will get subject from $email_post after we add it to the editor.
		$preheader = ''; // We will get the preheader from $email_post after we add it to the editor.
		try {
			$this->personalizer->set_context( $this->prepare_context_data( $wc_email ) );
			$rendered_email_data = $this->renderer->render( $email_post, $subject, $preheader, 'en' );
			$personalized_email  = $this->personalizer->personalize_content( $rendered_email_data['html'] );
			$rendered_email      = str_replace( self::WOO_EMAIL_CONTENT_PLACEHOLDER, $woo_content, $personalized_email );
			return $rendered_email;
		} catch ( \Exception $e ) {
			wc_caught_exception( $e, __METHOD__, array( $email_post, $woo_content, $wc_email ) );
			return null;
		}
	}

	/**
	 * Get the email post for a given WC_Email.
	 * Temporarily using the email ID as the post title for storing the association.
	 *
	 * @param \WC_Email $email WooCommerce email.
	 * @return \WP_Post|null
	 */
	public function get_email_post_by_wc_email( \WC_Email $email ): ?\WP_Post {
		$args = array(
			'post_type'      => Integration::EMAIL_POST_TYPE,
			'name'           => $email->id,
			'post_status'    => 'draft', // Temporarily use draft status we will change it to publish or custom active later.
			'posts_per_page' => 1,
		);

		$posts = get_posts( $args );
		if ( empty( $posts ) ) {
			return null;
		}

		return $posts[0];
	}

	/**
	 * Prepare context data for personalization.
	 *
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return array
	 */
	private function prepare_context_data( \WC_Email $wc_email ): array {
		$context                    = array();
		$context['recipient_email'] = $wc_email->get_recipient();
		$context['order']           = $wc_email->object instanceof \WC_Order ? $wc_email->object : null;
		$context['wp_user']         = $wc_email->object instanceof \WP_User ? $wc_email->object : null;
		return $context;
	}
}
