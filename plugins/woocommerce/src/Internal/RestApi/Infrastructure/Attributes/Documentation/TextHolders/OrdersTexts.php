<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\TextHolders;

class OrdersTexts
{
	public static function controller_subtitle() {
		return "Handles WooCommerce orders.";
	}

	public static function controller_about_text() {
		return <<<'EOT'
Endpoints to handle WooCommerce orders.

There's a separate section for [order refunds](orders/order-refunds).
EOT;
	}
}
