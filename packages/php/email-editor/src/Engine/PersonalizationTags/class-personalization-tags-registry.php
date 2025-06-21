<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;

/**
 * Registry for personalization tags.
 */
class Personalization_Tags_Registry {
	/**
	 * Logger instance.
	 *
	 * @var Email_Editor_Logger
	 */
	private Email_Editor_Logger $logger;

	/**
	 * List of registered personalization tags.
	 *
	 * @var Personalization_Tag[]
	 */
	private $tags = array();

	/**
	 * Constructor.
	 *
	 * @param Email_Editor_Logger $logger Logger instance.
	 */
	public function __construct( Email_Editor_Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Initialize the personalization tags registry.
	 * This method should be called only once.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->logger->info( 'Initializing personalization tags registry' );
		apply_filters( 'woocommerce_email_editor_register_personalization_tags', $this );
		$this->logger->info( 'Personalization tags registry initialized', array( 'tags_count' => count( $this->tags ) ) );
	}

	/**
	 * Register a new personalization instance in the registry.
	 *
	 * @param Personalization_Tag $tag The personalization tag to register.
	 * @return void
	 */
	public function register( Personalization_Tag $tag ): void {
		if ( isset( $this->tags[ $tag->get_token() ] ) ) {
			$this->logger->warning(
				'Personalization tag already registered',
				array(
					'token'    => $tag->get_token(),
					'name'     => $tag->get_name(),
					'category' => $tag->get_category(),
				)
			);
			return;
		}

		$this->tags[ $tag->get_token() ] = $tag;

		$this->logger->debug(
			'Personalization tag registered',
			array(
				'token'    => $tag->get_token(),
				'name'     => $tag->get_name(),
				'category' => $tag->get_category(),
			)
		);
	}

	/**
	 * Retrieve a personalization tag by its token.
	 * Example: get_by_token( 'user:first_name' ) will return the instance of Personalization_Tag with identical token.
	 *
	 * @param string $token The token of the personalization tag.
	 * @return Personalization_Tag|null The array data or null if not found.
	 */
	public function get_by_token( string $token ): ?Personalization_Tag {
		return $this->tags[ $token ] ?? null;
	}

	/**
	 * Retrieve all registered personalization tags.
	 *
	 * @return array List of all registered personalization tags.
	 */
	public function get_all() {
		return $this->tags;
	}
}
