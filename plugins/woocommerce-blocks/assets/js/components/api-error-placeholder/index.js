/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import classNames from 'classnames';
import {
	Button,
	Placeholder,
	Spinner,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

class ApiErrorPlaceholder extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			retrying: false,
		};
		this.onRetry = this.onRetry.bind( this );
	}

	onRetry() {
		const { onRetry } = this.props;

		this.setState( { retrying: true } );
		onRetry();
	}

	render() {
		const { onRetry, errorMessage, className } = this.props;
		const { retrying } = this.state;
		return (
			<Placeholder
				icon={ <Gridicon icon="notice" /> }
				label={ __( 'Sorry, an error occurred', 'woo-gutenberg-products-block' ) }
				className={ classNames( 'wc-block-api-error', className ) }
			>
				<div className="wc-block-error__message">{ errorMessage }</div>
				{ onRetry && (
					<Fragment>
						{ !! retrying ? (
							<Spinner />
						) : (
							<Button isDefault onClick={ this.onRetry }>
								{ __( 'Retry', 'woo-gutenberg-products-block' ) }
							</Button>
						) }
					</Fragment>
				) }
			</Placeholder>
		);
	}
}

ApiErrorPlaceholder.propTypes = {
	/**
	 * Callback to retry an action.
	 */
	onRetry: PropTypes.func.isRequired,
	/**
	 * The error message to display from the API.
	 */
	errorMessage: PropTypes.node,
	/**
	 * Classname to add to placeholder in addition to the defaults.
	 */
	className: PropTypes.string,
};

export default ApiErrorPlaceholder;
