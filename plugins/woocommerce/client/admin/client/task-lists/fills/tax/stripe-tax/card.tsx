/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { Plugins } from '@woocommerce/components';
import { dispatch, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { PartnerCard } from '../components/partner-card';
import { TaxChildProps } from '../utils';
import StripeTaxLogo from './stripe-tax-logo.svg';
import { createNoticesFromResponse } from '~/lib/notices';

const STRIPE_TAX_PLUGIN_SLUG = 'stripe-tax-for-woocommerce';

const redirectToStripeTaxSettings = () => {
	window.location.href = getAdminLink(
		'/admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce'
	);
};

export const Card: React.FC< TaxChildProps > = ( {
	task: {
		additionalData: { stripeTaxActivated } = {
			stripeTaxActivated: false,
		},
	},
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	return (
		<PartnerCard
			name={ __( 'Stripe Tax', 'woocommerce' ) }
			logo={ StripeTaxLogo }
			description={ __( 'Powerful global tax tool', 'woocommerce' ) }
			benefits={ [
				__( 'Real-time sales tax calculation', 'woocommerce' ),
				__( 'Multi-economic nexus compliance', 'woocommerce' ),
				__( 'Detailed tax transaction reports', 'woocommerce' ),
				__( 'Coverage in over 55 countries', 'woocommerce' ),
			] }
			terms={ __(
				'Free to install, then pay as you go.',
				'woocommerce'
			) }
			onClick={ () => {} }
		>
			{ stripeTaxActivated ? (
				<Button
					variant="secondary"
					onClick={ () => {
						recordEvent(
							'tasklist_tax_setup_stripe_tax_to_settings'
						);
						redirectToStripeTaxSettings();
					} }
				>
					{ __( 'Continue to setttings', 'woocommerce' ) }
				</Button>
			) : (
				<Plugins
					installText={ __( 'Install for free', 'woocommerce' ) }
					onClick={ () => {
						recordEvent( 'tasklist_tax_select_option', {
							selected_option: STRIPE_TAX_PLUGIN_SLUG,
						} );
					} }
					onComplete={ () => {
						recordEvent( 'tasklist_tax_install_plugin_success', {
							selected_option: STRIPE_TAX_PLUGIN_SLUG,
						} );
						const { updateAndPersistSettingsForGroup } =
							dispatch( SETTINGS_STORE_NAME );
						updateAndPersistSettingsForGroup( 'general', {
							general: {
								woocommerce_calc_taxes: 'yes', // Stripe tax requires tax calculation to be enabled so let's do it here to save the user from doing it manually
							},
						} ).then( () => {
							createSuccessNotice(
								__(
									"Stripe Tax for Woocommerce has been successfully installed. Let's configure it now.",
									'woocommerce'
								)
							);
							redirectToStripeTaxSettings();
						} );
					} }
					onError={ ( errors, response ) => {
						recordEvent( 'tasklist_tax_install_plugin_error', {
							selected_option: STRIPE_TAX_PLUGIN_SLUG,
							errors,
						} );
						createNoticesFromResponse( response );
					} }
					installButtonVariant="secondary"
					pluginSlugs={ [ STRIPE_TAX_PLUGIN_SLUG ] }
				/>
			) }
		</PartnerCard>
	);
};
