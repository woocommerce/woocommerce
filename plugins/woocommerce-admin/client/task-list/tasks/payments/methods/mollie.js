/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { Stepper, Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';

export const Mollie = ( { installStep, markConfigured } ) => {
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
						'Connect your Mollie account',
						'woocommerce-admin'
					),
					content: (
						<MollieConnectStep
							onFinish={ () => {
								markConfigured( 'mollie' );
							} }
						/>
					),
				},
			] }
		/>
	);
};

const MollieConnectStep = ( { onFinish } ) => {
	const settingsLink = (
		<Link
			href={ `${ adminUrl }admin.php?page=wc-settings&tab=mollie_settings#mollie-payments-for-woocommerce` }
			target="_blank"
			type="external"
		/>
	);

	const accountLink = (
		<Link
			href={ 'https://www.mollie.com/dashboard/signup' }
			target="_blank"
			type="external"
		/>
	);

	const configureText = interpolateComponents( {
		mixedString: __(
			'Create a {{accountLink}}Mollie account{{/accountLink}} and finish the configuration in the {{settingsLink}}Mollie settings.{{/settingsLink}}',
			'woocommerce-admin'
		),
		components: {
			accountLink,
			settingsLink,
		},
	} );

	return (
		<>
			<p>{ configureText }</p>
			<Button isPrimary onClick={ onFinish }>
				{ __( 'Continue', 'woocommerce-admin' ) }
			</Button>
		</>
	);
};
