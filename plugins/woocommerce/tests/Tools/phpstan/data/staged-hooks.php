<?php

class Staged_Hooks_Fixture extends WC_Data {
	public function hooks( bool $flag ): void {
		/**
		 * Right docblock.
		 *
		 * @param bool                 $flag   Flag.
		 * @param Staged_Hooks_Fixture $object Object.
		 * @param bool                 $staged Staged.
		 */
		$this->do_staged_action( 'right_action', $flag, $this );

		/**
		 * No @param for $staged.
		 *
		 * @param bool                 $flag   Flag.
		 * @param Staged_Hooks_Fixture $object Object.
		 */
		$this->do_staged_action( 'missing_staged', $flag, $this );

		/**
		 * Wrong type.
		 *
		 * @param int                  $flag   Flag.
		 * @param Staged_Hooks_Fixture $object Object.
		 * @param bool                 $staged Staged.
		 */
		$this->apply_staged_filters( 'wrong_type', $flag, $this );

		$this->apply_staged_filters( 'no_docblock', $flag, $this );

		/**
		 * Right filter.
		 *
		 * @param bool                 $flag   Flag.
		 * @param Staged_Hooks_Fixture $object Object.
		 * @param bool                 $staged Staged.
		 */
		$this->apply_staged_filters( 'right_filter', $flag, $this );
	}
}
