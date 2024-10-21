/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME,
	WooCYSSecondaryButton,
} from './utils';

export const WooCYSSecondaryButtonSlot = () => {
	const slot = useSlot(
		EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME
	);

	const hasFills = Boolean( slot?.fills?.length );
	if ( ! hasFills ) {
		return null;
	}

	return <WooCYSSecondaryButton.Slot />;
};
