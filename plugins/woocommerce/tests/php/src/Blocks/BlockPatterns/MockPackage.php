<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks;

/**
 * MockPackage class.
 */
class MockPackage {
    /**
     * Get path.
     *
     * @return string
     */
    public function get_path( $type ) {
        return __DIR__ . '/patterns';
    }
}
