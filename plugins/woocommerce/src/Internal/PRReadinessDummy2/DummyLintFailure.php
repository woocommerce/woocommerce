<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PRReadinessDummy2;

class DummyLintFailure {
	public function check(){
		if(true){
			return true;
		}
	}
}
