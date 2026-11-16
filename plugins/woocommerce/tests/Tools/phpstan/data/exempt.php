<?php

class Exempt_Fixture extends WC_Data {
	public function save() {
		$this->data_store->update( $this );
	}

	public function create() {
		$this->data_store->create( $this );
	}

	public function update() {
		$this->data_store->update( $this );
	}

	public function save_items() {
		$this->data_store->save_items( $this );
	}

	public function save_meta_data() {
		$this->data_store->update_meta( $this );
	}

	public function apply_changes() {
		$this->save();
	}

	public function delete( $force = false ) {
		$this->data_store->delete( $this );
	}

	public function delete_meta_data_value( $key ) {
		$this->data_store->delete_meta( $this, $key );
	}

	public function hold_applied_coupons() {
		$this->data_store->update_usage( $this );
	}

	public static function save_static() {
		WC_Data_Store::load( 'order' )->update_all();
	}
}

class Not_WC_Data_Fixture {
	private $data_store;

	public function update_status() {
		$this->data_store->update_status( $this );
		wp_update_post( array( 'ID' => 1 ) );
	}

	public function stage_status() {
		$this->status = 'done';
	}
}
