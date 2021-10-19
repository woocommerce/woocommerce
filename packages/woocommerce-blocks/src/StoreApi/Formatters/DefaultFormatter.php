<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Formatters;

/**
 * Default Formatter.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class DefaultFormatter implements FormatterInterface {
	/**
	 * Format a given value and return the result.
	 *
	 * @param mixed $value Value to format.
	 * @param array $options Options that influence the formatting.
	 * @return mixed
	 */
	public function format( $value, array $options = [] ) {
		return $value;
	}
}
