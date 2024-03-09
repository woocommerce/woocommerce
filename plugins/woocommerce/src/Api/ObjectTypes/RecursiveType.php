<?php

namespace Automattic\WooCommerce\Api\ObjectTypes;

class RecursiveType {
	public int $the_int;

	public ?RecursiveType $the_recursive_type;
}
