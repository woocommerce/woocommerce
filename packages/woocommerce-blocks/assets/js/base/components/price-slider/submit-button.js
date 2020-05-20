/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

const SubmitButton = ( { disabled, onClick } ) => {
	return (
		<button
			type="submit"
			className="wc-block-price-filter__button wc-block-form-button"
			disabled={ disabled }
			onClick={ onClick }
		>
			{ // translators: Submit button text for the price filter.
			__( 'Go', 'woocommerce' ) }
		</button>
	);
};

SubmitButton.propTypes = {
	/**
	 * Is the button disabled?
	 */
	disabled: PropTypes.bool,
	/**
	 * On click callback.
	 */
	onClick: PropTypes.func.isRequired,
};

SubmitButton.defaultProps = {
	disabled: false,
};

export default SubmitButton;
