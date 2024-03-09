<?php

namespace Automattic\WooCommerce\Api\Interfaces;

use Automattic\WooCommerce\Api\Enums\WebhookApiType;
use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\ObjectTypes\User;
use Automattic\WooCommerce\Api\ObjectTypes\Webhook;

#[Description( 'Represents the static webhook details.' )]
trait WebhookDefinition {
	#[Description( 'Display name of the webhook.' )]
	public string $name;

	#[Description( 'The topic of the webhook.' )]
	public string $topic;

	#[Description( 'The URL where the webhook payload will be delivered.' )]
	public string $delivery_url;

	#[Description( 'Optional secret to be delivered as part of the webhook payload.' )]
	public string $secret;

	#[EnumType( WebhookApiType::class )]
	#[Description( 'WooCommerce API version that will be used to compose the webhook payload.' )]
	public string $api_version;
}
