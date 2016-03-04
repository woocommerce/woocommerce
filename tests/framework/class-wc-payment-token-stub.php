<?php
/**
 * Stub/Dummy class to test WC_Payment_Token methods only
 *
 * @since 2.6
 */
class WC_Payment_Token_Stub extends \WC_Payment_Token {
	/** @protected string Token Type String */
	protected $type = 'stub';

	/**
	 * Returns meta
	 * @return string
	 */
	public function get_extra() {
		return isset( $this->meta['extra'] ) ? $this->meta['extra'] : '';
	}

	/**
	 * Set meta
	 * @param string $extra
	 */
	public function set_extra( $extra ) {
		$this->meta['extra'] = $extra;
	}
}
