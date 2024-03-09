<?php

namespace Automattic\WooCommerce\Api\Enums;

#[Description( 'API version used for the webhook payload.' )]
class WebhookApiType {
	#[Description( 'WooCommerce API v1' )]
	public const API_V1 = 'wp_api_v1';

	#[Description( 'WooCommerce API v2' )]
	public const API_V2 = 'wp_api_v2';

	#[Description( 'WooCommerce API v3' )]
	public const API_V3 = 'wp_api_v3';

	#[Description( 'WooCommerce Legacy API v3' )]
	public const LEGACY_V3 = 'legacy_v3';
}
