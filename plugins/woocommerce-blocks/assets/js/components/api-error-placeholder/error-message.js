/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { escapeHTML } from '@wordpress/escape-html';

const getErrorMessage = ( { apiMessage, message } ) => {
	if ( message ) {
		return message;
	}

	if ( apiMessage ) {
		return (
			<span>
				{ __( 'The following error was returned from the API', 'woo-gutenberg-products-block' ) }
				<br />
				<code>{ escapeHTML( apiMessage ) }</code>
			</span>
		);
	}

	return __( 'An unknown error occurred which prevented the block from being updated.', 'woo-gutenberg-products-block' );
};

const ErrorMessage = ( { error } ) => (
	<div className="wc-block-error-message">
		{ getErrorMessage( error ) }
	</div>
);

ErrorMessage.propTypes = {
	/**
	 * The error object.
	 */
	error: PropTypes.shape( {
		/**
		 * API error message to display in case of a missing `message`.
		 */
		apiMessage: PropTypes.node,
		/**
		 * Human-readable error message to display.
		 */
		message: PropTypes.string,
	} ),
};

export default ErrorMessage;
