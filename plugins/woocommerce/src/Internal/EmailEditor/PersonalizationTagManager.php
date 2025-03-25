<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Manages personalization tags for WooCommerce emails.
 *
 * @internal
 */
class PersonalizationTagManager {

	/**
	 * Initialize the personalization tag manager.
	 *
	 * @internal
	 * @return void
	 */
	final public function init(): void {
		add_filter( 'woocommerce_email_editor_register_personalization_tags', array( $this, 'register_personalization_tags' ) );
	}

	/**
	 * Register WooCommerce personalization tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return Personalization_Tags_Registry
	 */
	public function register_personalization_tags( Personalization_Tags_Registry $registry ) {
		$registry->register(
			new Personalization_Tag(
				__( 'Shopper Email', 'woocommerce' ),
				'woocommerce/shopper-email',
				__( 'Shopper', 'woocommerce' ),
				function ( array $context ): string {
					return $context['recipient_email'] ?? '';
				},
			)
		);

		// Site Personalization Tags.
		$registry->register(
			new Personalization_Tag(
				__( 'Site Title', 'woocommerce' ),
				'woocommerce/site-title',
				__( 'Site', 'woocommerce' ),
				function (): string {
					return htmlspecialchars_decode( get_bloginfo( 'name' ) );
				},
			)
		);
		$registry->register(
			new Personalization_Tag(
				__( 'Homepage URL', 'woocommerce' ),
				'woocommerce/site-homepage-url',
				__( 'Site', 'woocommerce' ),
				function (): string {
					return get_bloginfo( 'url' );
				},
			)
		);
		return $registry;
	}
}
