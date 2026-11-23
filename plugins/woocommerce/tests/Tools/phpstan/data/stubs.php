<?php

class WC_Data {
	protected $data_store;

	public function save() {}

	public function get_data_store() {
		return $this->data_store;
	}

	protected function refuse_unstaged_override( string $method, string $stage_method ): void {}
}

class WC_Data_Store {
	public static function load( string $type ) {
		return new self();
	}
}
