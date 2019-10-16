/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import classNames from 'classnames';
import { escapeHTML } from '@wordpress/escape-html';
import {
	Button,
	Placeholder,
	Spinner,
} from '@wordpress/components';

const getErrorMessage = ( { apiMessage, message } ) => {
	if ( message ) {
		return message;
	}

	if ( apiMessage ) {
		return (
			<span>
				{ __( 'The following error was returned from the API', 'woocommerce' ) }
				<br />
				<code>{ escapeHTML( apiMessage ) }</code>
			</span>
		);
	}

	return __( 'An unknown error occurred which prevented the block from being updated.', 'woocommerce' );
};

const ApiErrorPlaceholder = ( { className, error, isLoading, onRetry } ) => (
	<Placeholder
		icon={ <Gridicon icon="notice" /> }
		label={ __( 'Sorry, an error occurred', 'woocommerce' ) }
		className={ classNames( 'wc-block-api-error', className ) }
	>
		<div className="wc-block-error__message">
			{ getErrorMessage( error ) }
		</div>
		{ onRetry && (
			<Fragment>
				{ isLoading ? (
					<Spinner />
				) : (
					<Button isDefault onClick={ onRetry }>
						{ __( 'Retry', 'woocommerce' ) }
					</Button>
				) }
			</Fragment>
		) }
	</Placeholder>
);

ApiErrorPlaceholder.propTypes = {
	/**
	 * Classname to add to placeholder in addition to the defaults.
	 */
	className: PropTypes.string,
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
	/**
	 * Whether there is a request running, so the 'Retry' button is hidden and
	 * a spinner is shown instead.
	 */
	isLoading: PropTypes.bool,
	/**
	 * Callback to retry an action.
	 */
	onRetry: PropTypes.func,
};

export default ApiErrorPlaceholder;
