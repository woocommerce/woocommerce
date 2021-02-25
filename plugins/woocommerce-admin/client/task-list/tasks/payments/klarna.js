/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { Link, Stepper } from '@woocommerce/components';

class Klarna extends Component {
	constructor( props ) {
		super( props );
		this.continue = this.continue.bind( this );
	}

	continue() {
		const { markConfigured, plugin } = this.props;

		const slug =
			plugin === 'checkout' ? 'klarna_checkout' : 'klarna_payments';

		markConfigured( slug );
	}

	renderConnectStep() {
		const { plugin } = this.props;

		const slug =
			plugin === 'checkout' ? 'klarna-checkout' : 'klarna-payments';
		const section = plugin === 'checkout' ? 'kco' : 'klarna_payments';

		const link = (
			<Link
				href={
					adminUrl +
					'admin.php?page=wc-settings&tab=checkout&section=' +
					section
				}
				target="_blank"
				type="external"
			/>
		);

		const helpLink = (
			<Link
				href={
					'https://docs.woocommerce.com/document/' +
					slug +
					'/#section-3'
				}
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
				<Button isPrimary onClick={ this.continue }>
					{ __( 'Continue', 'woocommerce-admin' ) }
				</Button>
			</Fragment>
		);
	}

	render() {
		const { installStep } = this.props;

		return (
			<Stepper
				isVertical
				isPending={ ! installStep.isComplete }
				currentStep={ installStep.isComplete ? 'connect' : 'install' }
				steps={ [
					installStep,
					{
						key: 'connect',
						label: __(
							'Connect your Klarna account',
							'woocommerce-admin'
						),
						content: this.renderConnectStep(),
					},
				] }
			/>
		);
	}
}

export default Klarna;
