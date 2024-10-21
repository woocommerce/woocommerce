<?php

namespace Automattic\WooCommerce\Api\Queries;

use Automattic\WooCommerce\Api\Order;
use Automattic\WooCommerce\Api\Infrastructure\Connection;

#[RestQueryName('Orders')]
#[Description('Get a list of existing orders.')]
class GetOrders extends BaseQuery
{
	#[QueryArgument('id', 'The id of the order.')]
	#[QueryArgument('date_from', 'The minimum order date.', 'datetime')]
	#[QueryArgument('date_from', 'The maximum order date.', 'datetime')]
	#[ConnectionOf(Order::class)]
	public function run(?string $date_from, ?string $date_to, array $field_arguments = [], array $fields = null): Connection {
		// Pretend that we get a list of orders from the database, and that 'fields' only specifies the connection nodes.
		$orders = $this->get_orders_from_db($date_from, $date_to, $field_arguments);

		$result = new Connection();

		// Pretend that $fields only requests the connection nodes.
		$result->nodes = $orders;
		return $result;
	}
}
