<?php
namespace Automattic\WooCommerce\Internal\PRReadinessDummy;

class DummyLintFailure {
	public function check(){
		if(true){
			return true;
		}
	}
}
