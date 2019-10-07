/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button } from 'newspack-components';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordEvent } from 'lib/tracks';

class Klarna extends Component {
	constructor( props ) {
		super( props );
		this.continue = this.continue.bind( this );
	}

	continue() {
		const slug = 'checkout' === this.props.plugin ? 'klarna-checkout' : 'klarna-payments';
		recordEvent( 'tasklist_payment_connect_method', { payment_method: slug } );
		this.props.markConfigured( slug );
		return;
	}

	render() {
		const slug = 'checkout' === this.props.plugin ? 'klarna-checkout' : 'klarna-payments';
		const section = 'checkout' === this.props.plugin ? 'kco' : 'klarna_payments';

		const link = (
			<Link
				href={ adminUrl + 'admin.php?page=wc-settings&tab=checkout&section=' + section }
				target="_blank"
				type="external"
			/>
		);

		const helpLink = (
			<Link
				href={ 'https://docs.woocommerce.com/document/' + slug + '/#section-3' }
				target="_blank"
				type="external"
			/>
		);

		const configureText = interpolateComponents( {
			mixedString: __(
				'Klarna can be configured under your {{link}}store settings{{/link}}. Figure out {{helpLink}}what you need{{/helpLink}}.',
				'woocommerce-admin'
			),
			components: {
				link,
				helpLink,
			},
		} );
		return (
			<Fragment>
				<p>{ configureText }</p>
				<Button isPrimary isDefault onClick={ this.continue }>
					{ __( 'Continue', 'woocommerce-admin' ) }
				</Button>
			</Fragment>
		);
	}
}

export default Klarna;
