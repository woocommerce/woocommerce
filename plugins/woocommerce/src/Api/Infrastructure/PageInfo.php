<?php

namespace Automattic\WooCommerce\Api\Infrastructure;

#[Description('Information about pagination in a connection.')]
class PageInfo {

	#[Description('When paginating backwards, the cursor to continue.')]
	public ?string $start_cursor;

	#[Description('When paginating forwards, the cursor to continue.')]
	public ?string $end_cursor;

	#[Description('When paginating forwards, are there more items?')]
	public bool $has_next_page;

	#[Description('When paginating backwards, are there more items?')]
	public bool $has_previous_page;
}
