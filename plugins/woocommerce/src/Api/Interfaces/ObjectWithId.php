<?php

namespace Automattic\WooCommerce\Api\Interfaces;

#[Description('Object with an unique id.')]
abstract class ObjectWithId {

	#[Description('Unique id of the object.')]
	public int $id;
}
