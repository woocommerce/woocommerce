<?php

namespace Automattic\WooCommerce\Api\OutputObjects;

class Connection {

	#[ArrayOf(Edge::class)]
	#[Description('A list of edges.')]
	public array $edges;

	#[Description('A list of nodes.')]
	public array $nodes;

	#[Description('Information to aid in pagination.')]
	public PageInfo $page_info;

	#[Description('Identifies the total count of items in the connection.')]
	public int $total_count;
}
