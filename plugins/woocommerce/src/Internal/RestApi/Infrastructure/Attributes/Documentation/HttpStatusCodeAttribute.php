<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide a possible HTTP status code returned by a controller endpoint.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class HttpStatusCodeAttribute {

	public function __construct(
		public int $code,
		public string $description
	) {}
}
