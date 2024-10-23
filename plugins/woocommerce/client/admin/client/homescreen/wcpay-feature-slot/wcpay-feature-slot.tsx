/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_HOMESCREEN_WC_PAY_FEATURE_SLOT_NAME,
	WooHomescreenWCPayFeatureItem,
} from './utils';

export const WooHomescreenWCPayFeature = ( {
	className,
}: {
	className: string;
} ) => {
	const slot = useSlot( EXPERIMENTAL_WC_HOMESCREEN_WC_PAY_FEATURE_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div className={ clsx( 'woocommerce-homescreen__header', className ) }>
			<WooHomescreenWCPayFeatureItem.Slot />
		</div>
	);
};
