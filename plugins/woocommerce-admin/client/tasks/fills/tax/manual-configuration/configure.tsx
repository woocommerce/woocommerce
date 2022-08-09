/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { SETTINGS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TaxChildProps } from '../utils';

export const Configure: React.FC<
	Pick< TaxChildProps, 'isPending' | 'onManual' >
> = ( { isPending, onManual } ) => {
	const { generalSettings } = useSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );

		return {
			generalSettings: getSettings( 'general' )?.general,
		};
	} );

	return (
		<>
			<Button
				isPrimary
				disabled={ isPending }
				isBusy={ isPending }
				onClick={ () => {
					recordEvent( 'tasklist_tax_config_rates', {} );
					onManual();
				} }
			>
				{ __( 'Configure', 'woocommerce' ) }
			</Button>
			<p>
				{ generalSettings?.woocommerce_calc_taxes !== 'yes' &&
					interpolateComponents( {
						mixedString: __(
							/*eslint-disable max-len*/
							'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
							'woocommerce'
						),
						components: {
							link: (
								<Link
									href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_medium=product#section-1"
									target="_blank"
									type="external"
								>
									<></>
								</Link>
							),
						},
					} ) }
			</p>
		</>
	);
};
