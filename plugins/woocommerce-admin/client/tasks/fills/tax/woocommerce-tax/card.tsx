/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from 'interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { PartnerCard } from '../components/partner-card';
import logo from './logo.png';
import { TaxChildProps } from '../utils';

export const Card: React.FC< TaxChildProps > = ( { isPending } ) => {
	return (
		<PartnerCard
			name={ __( 'WooCommerce Tax', 'woocommerce-admin' ) }
			isPending={ isPending }
			logo={ logo }
			description={ __( 'Best for new stores', 'woocommerce-admin' ) }
			benefits={ [
				__( 'Real-time sales tax calculation', 'woocommerce-admin' ),
				interpolateComponents( {
					mixedString: __(
						'{{strong}}Single{{/strong}} economic nexus compliance',
						'woocommerce-admin'
					),
					components: {
						strong: <strong />,
					},
				} ),
				interpolateComponents( {
					mixedString: __(
						'Powered by {{link}}Jetpack{{/link}}',
						'woocommerce-admin'
					),
					components: {
						link: (
							<Link
								type="external"
								href="https://woocommerce.com/products/jetpack/?utm_medium=product"
								target="_blank"
							/>
						),
					},
				} ),
				// eslint-disable-next-line @wordpress/i18n-translator-comments
				__( '100% free', 'woocommerce-admin' ),
			] }
			terms={ interpolateComponents( {
				mixedString: __(
					'By installing WooCommerce Tax and Jetpack you agree to the {{link}}Terms of Service{{/link}}.',
					'woocommerce-admin'
				),
				components: {
					link: (
						<Link
							href={ 'https://wordpress.com/tos/' }
							target="_blank"
							type="external"
						/>
					),
				},
			} ) }
			actionText={ __( 'Continue setup', 'woocommerce-admin' ) }
			onClick={ () => {
				recordEvent( 'tasklist_tax_select_option', {
					selected_option: 'woocommerce-tax',
				} );
				updateQueryString( {
					partner: 'woocommerce-tax',
				} );
			} }
		/>
	);
};
