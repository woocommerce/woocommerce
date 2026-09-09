/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { FinanceProvider } from '../../types';

type ProviderSelectProps = {
	providers: FinanceProvider[];
	value: string | null;
	onChange: ( providerId: string ) => void;
};

/**
 * Single-select provider switcher. Hidden when there is nothing to switch between.
 */
export const ProviderSelect = ( {
	providers,
	value,
	onChange,
}: ProviderSelectProps ) => {
	if ( providers.length <= 1 ) {
		return null;
	}

	return (
		<SelectControl
			className="woocommerce-finance-provider-select"
			label={ __( 'Payout provider', 'woocommerce' ) }
			value={ value ?? '' }
			options={ providers.map( ( provider ) => ( {
				label: provider.title,
				value: provider.provider_id,
			} ) ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
	);
};
