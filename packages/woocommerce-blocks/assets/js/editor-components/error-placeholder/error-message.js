/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { escapeHTML } from '@wordpress/escape-html';

const getErrorMessage = ( { message, type } ) => {
	if ( ! message ) {
		return __(
			'An unknown error occurred which prevented the block from being updated.',
			'woocommerce'
		);
	}

	if ( type === 'general' ) {
		return (
			<span>
				{ __(
					'The following error was returned',
					'woocommerce'
				) }
				<br />
				<code>{ escapeHTML( message ) }</code>
			</span>
		);
	}

	if ( type === 'api' ) {
		return (
			<span>
				{ __(
					'The following error was returned from the API',
					'woocommerce'
				) }
				<br />
				<code>{ escapeHTML( message ) }</code>
			</span>
		);
	}

	return message;
};

const ErrorMessage = ( { error } ) => (
	<div className="wc-block-error-message">{ getErrorMessage( error ) }</div>
);

ErrorMessage.propTypes = {
	/**
	 * The error object.
	 */
	error: PropTypes.shape( {
		/**
		 * Human-readable error message to display.
		 */
		message: PropTypes.node,
		/**
		 * Context in which the error was triggered. That will determine how the error is displayed to the user.
		 */
		type: PropTypes.oneOf( [ 'api', 'general' ] ),
	} ),
};

export default ErrorMessage;
