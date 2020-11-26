/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { withDispatch } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';

class WCPay extends Component {
	componentDidMount() {
		const { createNotice, markConfigured } = this.props;
		const query = getQuery();
		// Handle redirect back from WCPay on-boarding
		if ( query[ 'wcpay-connection-success' ] ) {
			createNotice(
				'success',
				__(
					'WooCommerce Payments connected successfully.',
					'woocommerce-admin'
				)
			);
			markConfigured( 'wcpay', { 'wcpay-connection-success': '1' } );
		}
	}

	render() {
		return null;
	}
}

export default withDispatch( ( dispatch ) => {
	const { createNotice } = dispatch( 'core/notices' );
	return {
		createNotice,
	};
} )( WCPay );
