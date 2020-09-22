/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from 'react';
import { Notice } from 'wordpress-components';
import PropTypes from 'prop-types';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';

class PaymentMethodErrorBoundary extends Component {
	state = { errorMessage: '', hasError: false };

	static getDerivedStateFromError( error ) {
		return {
			errorMessage: error.message,
			hasError: true,
		};
	}

	render() {
		const { hasError, errorMessage } = this.state;
		const { isEditor } = this.props;

		if ( hasError ) {
			let errorText = __(
				'This site is experiencing difficulties with this payment method. Please contact the owner of the site for assistance.',
				'woocommerce'
			);
			if ( isEditor || CURRENT_USER_IS_ADMIN ) {
				if ( errorMessage ) {
					errorText = errorMessage;
				} else {
					errorText = __(
						"There was an error with this payment method. Please verify it's configured correctly.",
						'woocommerce'
					);
				}
			}
			return (
				<Notice isDismissible={ false } status="error">
					{ errorText }
				</Notice>
			);
		}

		return this.props.children;
	}
}

PaymentMethodErrorBoundary.propTypes = {
	isEditor: PropTypes.bool,
};

PaymentMethodErrorBoundary.defaultProps = {
	isEditor: false,
};

export default PaymentMethodErrorBoundary;
