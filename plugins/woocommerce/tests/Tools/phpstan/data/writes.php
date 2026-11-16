<?php

class Writes_Fixture extends WC_Data {
	public function through_data_store_property() {
		$this->data_store->update_status( $this );
	}

	public function through_loaded_data_store() {
		$store = WC_Data_Store::load( 'order' );
		$store->increase_count( $this );
	}

	public function through_get_data_store() {
		$this->get_data_store()->set_flag( $this, true );
	}

	public function through_wpdb() {
		global $wpdb;
		$wpdb->query( 'UPDATE wp_posts SET post_status = 1' );
	}

	public function through_function() {
		wp_update_post( array( 'ID' => 1 ) );
	}

	public function through_save() {
		$this->set_prop( 'flag', true );
		$this->save();
	}

	public function reads_only() {
		$this->data_store->read_meta( $this );
		return $this->get_data_store()->get_flag( $this );
	}
}
