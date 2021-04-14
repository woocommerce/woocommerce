/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { Stepper, Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';

export const PayUIndia = ( { installStep, markConfigured } ) => {
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
						'Connect to your PayU account',
						'woocommerce-admin'
					),
					content: (
						<PayUCredentialsStep
							onFinish={ () => {
								markConfigured( 'payubiz' );
							} }
						/>
					),
				},
			] }
		/>
	);
};

const PayUCredentialsStep = ( { onFinish } ) => {
	const settingsLink = (
		<Link
			href={ `${ adminUrl }admin.php?page=wc-settings&tab=checkout&section=payubiz` }
			target="_blank"
			type="external"
		/>
	);

	const accountLink = (
		<Link
			href={ 'https://onboarding.payu.in/app/account' }
			target="_blank"
			type="external"
		/>
	);

	const configureText = interpolateComponents( {
		mixedString: __(
			'PayU can be configured under your {{settingsLink}}store settings.{{/settingsLink}} Create your PayU account {{accountLink}}here.{{/accountLink}}',
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
