/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { Stepper, Link } from '@woocommerce/components';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { useSelect } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';

export const MERCADOPAGO_PLUGIN = 'woocommerce-mercadopago';

export const MercadoPago = ( { installStep, markConfigured } ) => {
	const { countryCode } = useSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { general: generalSettings = {} } = getSettings( 'general' );
		return {
			countryCode: getCountryCode(
				generalSettings.woocommerce_default_country
			),
		};
	} );

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
						'Connect to your Mercado Pago account',
						'woocommerce-admin'
					),
					content: (
						<MercadoPagoCredentialsStep
							countryCode={ countryCode }
							onFinish={ () => markConfigured( 'mercadopago' ) }
						/>
					),
				},
			] }
		/>
	);
};

const MercadoPagoCredentialsStep = ( { countryCode, onFinish } ) => {
	const getRegistrationURL = () => {
		const mercadoPagoURL = 'https://www.mercadopago.com';
		if (
			! [ 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ].includes(
				countryCode
			)
		) {
			return mercadoPagoURL;
		}

		// As each country has its own domain, we will return the correct one. Otherwise, for example, a Spanish speaker could be redirected to a Mercado Pago page in Portuguese, etc.
		return `${ mercadoPagoURL }.${ countryCode.toLowerCase() }/registration-company?confirmation_url=${ mercadoPagoURL }.${ countryCode.toLowerCase() }%2Fcomo-cobrar`;
	};

	const settingsLink = (
		<Link
			href={ `${ adminUrl }admin.php?page=wc-settings&tab=checkout` }
			target="_blank"
			type="external"
		/>
	);

	const accountLink = (
		<Link href={ getRegistrationURL() } target="_blank" type="external" />
	);

	const configureText = interpolateComponents( {
		mixedString: __(
			'Mercado Pago can be configured under your {{settingsLink}}store settings.{{/settingsLink}} Create your Mercado Pago account {{accountLink}}here.{{/accountLink}}',
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
