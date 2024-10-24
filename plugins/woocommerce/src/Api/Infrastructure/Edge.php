<?php

namespace Automattic\WooCommerce\Api\OutputObjects;

#[Description('An edge in a connection.')]
class Edge {

	#[Description('A cursor for use in pagination.')]
	public string $cursor;

	#[Description('The item at the end of the edge.')]
	public array $node;
}
