<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\TextHolders;

/**
 * Class to hold texts for the REST API orders controller documentation.
 */
class OrdersTexts
{

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public static function controller_subtitle() {
		return 'Handles WooCommerce orders.';
	}

	public static function controller_about_text() {
		return <<<'EOT'
Endpoints to handle WooCommerce orders.

There's a separate section for [order refunds](orders/order-refunds).
EOT;
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing
}
