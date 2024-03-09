<?php

namespace Automattic\WooCommerce\Api\Interfaces;

#[Description( 'Represents an object that can be uniquely identified by an id.' )]
trait IdentifiableObject {
	#[Description( 'Object id' )]
	public ?int $id;

	#[Description( 'Object name' )]
	public ?string $name;
}
