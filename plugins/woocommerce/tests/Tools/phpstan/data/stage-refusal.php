<?php

class Stage_Refusal_Fixture extends WC_Data {
	public function stage_good() {
		$this->refuse_unstaged_override( 'good', 'stage_good' );
		$this->data['good'] = true;
	}

	public function stage_late() {
		$this->data['late'] = true;
		$this->refuse_unstaged_override( 'late', 'stage_late' );
	}

	public function stage_missing() {
		$this->data['missing'] = true;
	}
}
