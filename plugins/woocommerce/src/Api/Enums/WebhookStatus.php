<?php

namespace Automattic\WooCommerce\Api\Enums;

#[Description( 'Webhook status enum.' )]
class WebhookStatus {
	public const ACTIVE   = 'active';
	public const PAUSED   = 'paused';
	public const DISABLED = 'disabled';
}
