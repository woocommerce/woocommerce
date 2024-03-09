<?php

namespace Automattic\WooCommerce\Api\ObjectTypes;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\Interfaces\WebhookDefinition;

#[Description( 'Represents a full webhook, including static definition and current status.' )]
class Webhook {
	use WebhookDefinition;

	public int $id;

	public int $failure_count;

	public bool $pending_delivery;

	public string $date_created;

	#[EnumType( WebhookStatus::class )]
	public string $status;

	public ?User $user;
}
