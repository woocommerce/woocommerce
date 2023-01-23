/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME,
	ExperimentalWooTaskListFooterItem,
} from './utils';

export const ExperimentalWooTaskListFooter = () => {
	const slot = useSlot( EXPERIMENTAL_WC_TASKLIST_FOOTER_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div className="woocommerce-tasklist__footer">
			<ExperimentalWooTaskListFooterItem.Slot />
		</div>
	);
};
