<?php
namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class SetSiteOptionsProcessor implements StepProcessor {
	private $schema;
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	public function process() {
		foreach ( $this->schema->options as $key => $value ) {
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			update_option( $key, $value );
		}
	}
}
