<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Mutations;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\OperationResult;

#[Description( 'Delete a widget' )]
#[RequiredCapability( 'manage_options' )]
class DeleteWidget {
	public function execute(
		#[Description( 'The widget id to delete' )]
		int $id,
		#[Description( 'When true, ignore "not found" errors' )]
		bool $force = false,
	): OperationResult {
		$result = new OperationResult();
		if ( Store::delete_widget( $id ) ) {
			$result->success = true;
			$result->message = sprintf( 'Deleted widget %d.', $id );
			return $result;
		}
		$result->success = $force;
		$result->message = $force ? 'Widget not found, force was set.' : 'Widget not found.';
		return $result;
	}
}
