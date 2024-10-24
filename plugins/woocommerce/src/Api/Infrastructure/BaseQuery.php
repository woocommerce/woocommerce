<?php

namespace Automattic\WooCommerce\Api\Infrastructure;

abstract class BaseQuery {
	protected function get_pagination_arguments(array $field_arguments, string $field_arguments_key): array {
		$arguments = $field_arguments[$field_arguments_key] ?? null;

		if(is_null($arguments)) {
			return [
				'first' => 100
			];
		}

		$first = $arguments['first'] ?? null;
		if(!is_null($first)) {
			return [
				'first' => $first,
				'after' => $arguments['after'] ?? null
			];
		}

		$last = $arguments['last'] ?? null;
		if(!is_null($last)) {
			return [
				'last'   => $last,
				'before' => $arguments['before'] ?? null
			];
		}

		return [
			'first' => 100
		];
	}
}
