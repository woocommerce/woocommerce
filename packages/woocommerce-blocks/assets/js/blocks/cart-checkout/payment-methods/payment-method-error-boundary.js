/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import { StoreNoticesContainer } from '@woocommerce/base-context';

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
			const notices = [
				{
					id: '0',
					content: errorText,
					isDismissible: false,
					status: 'error',
				},
			];
			return <StoreNoticesContainer notices={ notices } />;
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
