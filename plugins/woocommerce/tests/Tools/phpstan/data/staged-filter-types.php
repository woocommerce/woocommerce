<?php

use function PHPStan\Testing\assertType;

class Staged_Filter_Types_Fixture extends WC_Data {
	public function types(): void {
		/**
		 * Count.
		 *
		 * @param int                         $count  Count.
		 * @param Staged_Filter_Types_Fixture $object Object.
		 * @param bool                        $staged Staged.
		 */
		$count = $this->apply_staged_filters( 'count', 1, $this );
		assertType( 'int', $count );

		$untyped = $this->apply_staged_filters( 'untyped', 1, $this );
		assertType( 'mixed', $untyped );
	}
}
