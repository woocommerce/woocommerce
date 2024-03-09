<?php

namespace Automattic\WooCommerce\Api\InputTypes;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\Interfaces\WebhookDefinition;

class CreateWebhookInput {
	use WebhookDefinition;

	public int $user_id;

	#[EnumType( WebhookStatus::class )]
	#[Description( 'Initial status the webhook witll be created with' )]
	public string $status;
}
