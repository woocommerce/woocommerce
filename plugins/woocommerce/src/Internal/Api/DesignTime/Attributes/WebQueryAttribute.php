<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Marks an API class method as visible to the GraphQL engine as a query
 * and to the REST API v4 engine as a GET request.
 */
#[Attribute( Attribute::TARGET_METHOD )]
class WebQueryAttribute {
	public function __construct(
		public string $graphql_name,
		public ?string $rest_name = null
	) {     }
}
