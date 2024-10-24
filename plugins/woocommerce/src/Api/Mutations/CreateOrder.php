<?php

class CreateOrder extends BaseQuery {

	#[QueryArgument('order', 'The data for the new order.')]
	public function run(NewOrder $order_data, array $field_arguments = [], array $fields = null): Order {
		return new Order();
	}
}
