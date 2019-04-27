<?php
/**
 * Stub/Dummy class to test WC_Payment_Token methods only
 *
 * @since 2.6.0
 */
class WC_Payment_Token_Stub extends WC_Payment_Token {

	/** @protected string Token Type String */
	protected $type = 'stub';

	/**
	 * Returns meta.
	 * @since 2.6.0
	 * @return string
	 */
	public function get_extra() {
		return $this->get_meta( 'extra' );
	}

	/**
	 * Set meta.
	 * @since 2.6.0
	 * @param string $extra
	 */
	public function set_extra( $extra ) {
		$this->add_meta_data( 'extra', $extra, true );
	}
}
