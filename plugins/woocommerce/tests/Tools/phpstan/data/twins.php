<?php

class Twins_Fixture extends WC_Data {
	public function stage_status( $status ) {
		$this->refuse_unstaged_override( 'update_status', 'stage_status' );
		$this->data['status'] = $status;
	}

	public function stage_paid() {
		$this->refuse_unstaged_override( 'mark_paid', 'stage_paid' );
		$this->data['paid'] = true;
	}

	public function update_status( $status ) {
		$this->write_through(
			function () use ( $status ) {
				return $this->stage_status( $status );
			}
		);
		$this->save();
	}

	public function mark_paid() {
		$this->stage_paid();
		$this->save();
	}

	public function add_note( $note ) {
		$this->stage_status( 'noted' );
		$this->save();
	}
}
