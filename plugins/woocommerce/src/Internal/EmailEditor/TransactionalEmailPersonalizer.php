<?php
/**
 * Class for handling transactional email personalization.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;

/**
 * Class TransactionalEmailPersonalizer that internally uses the Personalizer class.
 * The inheritance is not used here because Personalizer needs to pass Personalization_Tags_Registry and
 * the combination of two different dependency injection containers is not possible.
 */
class TransactionalEmailPersonalizer {
	/**
	 * Personalizer instance for handling email content personalization.
	 *
	 * @var Personalizer
	 */
	private Personalizer $personalizer;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container   = Email_Editor_Container::container();
		$this->personalizer = $editor_container->get( Personalizer::class );
	}

	/**
	 * Personalize transactional email content with specific handling.
	 *
	 * @param string    $content The content to personalize.
	 * @param \WC_Email $email The WooCommerce email object.
	 * @return string The personalized content.
	 */
	public function personalize_transactional_content( string $content, \WC_Email $email ): string {
		$this->configure_context_by_email( $email );
		return $this->personalizer->personalize_content( $content );
	}

	/**
	 * Configure personalization context based on WooCommerce email object.
	 *
	 * @param \WC_Email $email The WooCommerce email object.
	 * @return void
	 */
	public function configure_context_by_email( \WC_Email $email ): void {
		$prepared_context = $this->prepare_context_data( $this->personalizer->get_context(), $email );
		$this->personalizer->set_context( $prepared_context );
	}

	/**
	 * Prepare context data for email personalization.
	 * Adds new order specific context data.
	 *
	 * @param array     $context Previous version of context data.
	 * @param \WC_Email $email The WooCommerce email object.
	 * @return array Context data for personalization
	 */
	public function prepare_context_data( array $context, \WC_Email $email ): array {
		$context['recipient_email'] = $email->get_recipient();
		$context['order']           = $email->object instanceof \WC_Order ? $email->object : null;
		// For emails of type new_user or reset_password we want to set user directly from the object.
		if ( $email->object instanceof \WP_User ) {
			$context['wp_user'] = $email->object;
		} elseif ( $email->object instanceof \WC_Order ) {
			$context['wp_user'] = $email->object->get_user();
		} else {
			$context['wp_user'] = null;
		}
		$context['wc_email'] = $email;

		return $context;
	}
}
