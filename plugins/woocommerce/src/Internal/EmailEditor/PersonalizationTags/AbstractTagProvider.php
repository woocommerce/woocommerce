<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Abstract class for personalization tag providers.
 *
 * @internal
 */
abstract class AbstractTagProvider {
	/**
	 * Register tags with the registry.
	 *
	 * @param Personalization_Tags_Registry $registry The personalization tags registry.
	 * @return void
	 */
	abstract public function register_tags( Personalization_Tags_Registry $registry ): void;
}
