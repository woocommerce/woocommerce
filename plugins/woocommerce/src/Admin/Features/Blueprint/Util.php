<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class Util {
	public static function snake_to_camel($string) {
		// Split the string by underscores
		$words = explode('_', $string);

		// Capitalize the first letter of each word
		$words = array_map('ucfirst', $words);

		// Join the words back together
		return implode('', $words);
	}
}
