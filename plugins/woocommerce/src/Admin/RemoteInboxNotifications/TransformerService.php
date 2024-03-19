<?php

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

use Automattic\WooCommerce\Admin\DeprecatedClassFacade;

/**
 * A simple service class for the Transformer classes.
 *
 * Class TransformerService
 *
 * @package Automattic\WooCommerce\Admin\RemoteInboxNotifications
 *
 * @deprecated 8.8.0
 */
class TransformerService extends DeprecatedClassFacade {
	/**
	 * The name of the non-deprecated class that this facade covers.
	 *
	 * @var string
	 */
	protected static $facade_over_classname = 'Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\TransformerService';

	/**
	 * The version that this class was deprecated in.
	 *
	 * @var string
	 */
	protected static $deprecated_in_version = '8.8.0';
}
