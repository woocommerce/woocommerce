/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';
import interpolateComponents from '@automattic/interpolate-components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PartnerCard } from '../components/partner-card';
import { TaxChildProps } from '../utils';
import logo from './logo.png';

export const Card: React.FC< TaxChildProps > = ( { task } ) => {
	const { additionalData: { avalaraActivated } = {} } = task;

	return (
		<PartnerCard
			name={ __( 'Avalara', 'woocommerce' ) }
			logo={ logo }
			description={ __( 'Powerful all-in-one tax tool', 'woocommerce' ) }
			benefits={ [
				__( 'Real-time sales tax calculation', 'woocommerce' ),
				interpolateComponents( {
					mixedString: __(
						'{{strong}}Multi{{/strong}}-economic nexus compliance',
						'woocommerce'
					),
					components: {
						strong: <strong />,
					},
				} ),
				__(
					'Cross-border and multi-channel compliance',
					'woocommerce'
				),
				__( 'Automate filing & remittance', 'woocommerce' ),
				__(
					'Return-ready, jurisdiction-level reporting.',
					'woocommerce'
				),
			] }
			terms={ '' }
			actionText={
				avalaraActivated
					? __( 'Continue setup', 'woocommerce' )
					: __( 'Download', 'woocommerce' )
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
						'https://woo.com/products/woocommerce-avatax/'
					).toString(),
					'_blank'
				);
			} }
		/>
	);
};
