<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Traits;

use Automattic\WooCommerce\Api\Attributes\RequiredCapability;

/**
 * Trait that grants the manage_woocommerce capability requirement.
 *
 * Classes using this trait inherit the capability via the builder's
 * resolve_capabilities() method, which inspects traits for attributes.
 */
#[RequiredCapability( 'manage_woocommerce' )]
trait RequiresManageWoocommerce {
}
