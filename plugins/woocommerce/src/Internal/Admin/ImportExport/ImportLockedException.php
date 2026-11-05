<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport;

use RuntimeException;

/**
 * Thrown when an importer cannot acquire its entity-wide import lock because another
 * import (admin or CLI) already holds it.
 *
 * @since 11.4.0
 */
class ImportLockedException extends RuntimeException {

}
