/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';

/**
 * Internal dependencies
 */
import { ErrorObject } from '.';

export interface ErrorMessageProps {
	/**
	 * The error object.
	 */
	error: ErrorObject;
}

const getErrorMessage = ( { message, type }: ErrorObject ) => {
	if ( ! message ) {
		return __(
			'An error has prevented the block from being updated.',
			'woocommerce'
		);
	}

	if ( type === 'general' ) {
		return (
			<span>
				{ __( 'The following error was returned', 'woocommerce' ) }
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

const ErrorMessage = ( { error }: ErrorMessageProps ): JSX.Element => (
	<div className="wc-block-error-message">{ getErrorMessage( error ) }</div>
);

export default ErrorMessage;
