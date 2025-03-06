<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer as EmailRenderer;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
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
	 * Email theme controller
	 * We use it to get email CSS.
	 *
	 * @var Theme_Controller
	 */
	private $theme_controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container       = Email_Editor_Container::container();
		$this->renderer         = $editor_container->get( EmailRenderer::class );
		$this->personalizer     = $editor_container->get( Personalizer::class );
		$this->theme_controller = $editor_container->get( Theme_Controller::class );
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
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string|null Modified email content
	 */
	public function maybe_render_block_email( \WC_Email $wc_email ): ?string {
		$email_post = $this->get_email_post_by_wc_email( $wc_email );
		if ( ! $email_post ) {
			return null;
		}

		$woo_content = $this->capture_woo_content( $wc_email );
		return $this->render_block_email( $email_post, $woo_content, $wc_email );
	}

	/**
	 * Maybe render block-based email content.
	 *
	 * @param \WP_Post  $email_post Email post.
	 * @param string    $woo_content WooCommerce email content.
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string Modified email content
	 */
	private function render_block_email( \WP_Post $email_post, string $woo_content, \WC_Email $wc_email ): ?string {
		$subject   = $wc_email->get_subject(); // We will get subject from $email_post after we add it to the editor.
		$preheader = ''; // We will get the preheader from $email_post after we add it to the editor.
		try {
			$this->personalizer->set_context( $this->prepare_context_data( $wc_email ) );
			$rendered_email_data = $this->renderer->render( $email_post, $subject, $preheader, 'en' );
			$personalized_email  = $this->personalizer->personalize_content( $rendered_email_data['html'] );
			$rendered_email      = str_replace( self::WOO_EMAIL_CONTENT_PLACEHOLDER, $woo_content, $personalized_email );
			add_filter( 'woocommerce_email_styles', array( $this, 'prepare_css' ), 10, 2 );
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

	public function prepare_css( string $css, \WC_Email $wc_email ): string {
		remove_filter( 'woocommerce_email_styles', array( $this, 'prepare_css' ) );
		$editor_css = $this->theme_controller->get_stylesheet_for_rendering();

		// Remove color and font-family declarations from WooCommerce CSS.
		$css = preg_replace('/color\s*:\s*[^;]+;/', '', $css);
		$css = preg_replace('/font-family\s*:\s*[^;]+;/', '', $css);

		return $css . "\n" . $editor_css;
	}

	/**
	 * Capture the WooCommerce content excluding headers and footers.
	 *
	 * @param \WC_Email $wc_email WooCommerce email.
	 * @return string
	 */
	private function capture_woo_content( \WC_Email $wc_email ): string {
		// Store the existing header and footer callbacks.
		global $wp_filter;
		$original_header_filters = isset( $wp_filter['woocommerce_email_header'] ) ? clone $wp_filter['woocommerce_email_header'] : null;
		$original_footer_filters = isset( $wp_filter['woocommerce_email_footer'] ) ? clone $wp_filter['woocommerce_email_footer'] : null;

		// Remove header and footer filters because we want to get only the main content.
		remove_all_filters( 'woocommerce_email_header' );
		remove_all_filters( 'woocommerce_email_footer' );

		$woo_content = $wc_email->get_content_html();

		// Restore the original header and footer filters.
		if ( $original_header_filters ) {
			foreach ( $original_header_filters->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $filter ) {
					add_filter( 'woocommerce_email_header', $filter['function'], $priority, $filter['accepted_args'] );
				}
			}
		}
		if ( $original_footer_filters ) {
			foreach ( $original_footer_filters->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $filter ) {
					add_filter( 'woocommerce_email_footer', $filter['function'], $priority, $filter['accepted_args'] );
				}
			}
		}

		return $woo_content;
	}
}
