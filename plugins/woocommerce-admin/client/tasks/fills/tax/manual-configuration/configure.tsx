/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsSelector, TaxChildProps } from '../utils';

export const Configure: React.FC< TaxChildProps > = ( {
	isPending,
	onManual,
} ) => {
	const { generalSettings } = useSelect( ( select ) => {
		const { getSettings } = select(
			SETTINGS_STORE_NAME
		) as SettingsSelector;

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
				{ __( 'Configure', 'woocommerce-admin' ) }
			</Button>
			<p>
				{ generalSettings.woocommerce_calc_taxes !== 'yes' &&
					interpolateComponents( {
						mixedString: __(
							/*eslint-disable max-len*/
							'By clicking "Configure" you\'re enabling tax rates and calculations. More info {{link}}here{{/link}}.',
							/*eslint-enable max-len*/
							'woocommerce-admin'
						),
						components: {
							link: (
								<Link
									href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_medium=product#section-1"
									target="_blank"
									type="external"
								/>
							),
						},
					} ) }
			</p>
		</>
	);
};
