/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import interpolateComponents from 'interpolate-components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PartnerCard } from '../components/partner-card';
import logo from './logo.png';

export const Card = ( { task } ) => {
	const { avalaraActivated } = task.additionalData;

	return (
		<PartnerCard
			name={ __( 'Avalara', 'woocommerce-admin' ) }
			logo={ logo }
			description={ __(
				'Powerful all-in-one tax tool',
				'woocommerce-admin'
			) }
			benefits={ [
				__( 'Real-time sales tax calculation', 'woocommerce-admin' ),
				interpolateComponents( {
					mixedString: __(
						'{{strong}}Multi{{/strong}}-economic nexus compliance',
						'woocommerce-admin'
					),
					components: {
						strong: <strong />,
					},
				} ),
				__(
					'Cross-border and multi-channel compliance',
					'woocommerce-admin'
				),
				__( 'Automate filing & remittance', 'woocommerce-admin' ),
				__(
					'Return-ready, jurisdiction-level reporting.',
					'woocommerce-admin'
				),
			] }
			terms={ __(
				'30-day free trial. No credit card needed.',
				'woocommerce-admin'
			) }
			actionText={
				avalaraActivated
					? __( 'Continue setup', 'woocommerce-admin' )
					: __( 'Enable & set up', 'woocommerce-admin' )
			}
			onClick={ () => {
				recordEvent( 'tasklist_tax_select_option', {
					selected_option: 'avalara',
				} );

				if ( avalaraActivated ) {
					window.location.href = getAdminLink(
						'/admin.php?page=wc-settings&tab=tax&section=avatax'
					);

					return;
				}

				window.open(
					new URL(
						'https://woocommerce.com/products/woocommerce-avatax/'
					),
					'_blank'
				);
			} }
		/>
	);
};
