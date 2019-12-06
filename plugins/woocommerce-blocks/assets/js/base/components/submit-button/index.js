/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

const SubmitButton = ( { className, disabled, onClick } ) => {
	return (
		<button
			type="submit"
			className={ classNames( 'wc-block-submit-button', className ) }
			disabled={ disabled }
			onClick={ onClick }
		>
			{ // translators: Submit button text for filters.
			__( 'Go', 'woo-gutenberg-products-block' ) }
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
