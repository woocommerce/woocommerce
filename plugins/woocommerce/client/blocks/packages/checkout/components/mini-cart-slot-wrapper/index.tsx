/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import type { ComponentType } from 'react';

interface SlotComponentProps {
	extensions: Record< string, unknown >;
	cart: Record< string, unknown >;
	context?: string;
	[ key: string ]: unknown;
}

interface MiniCartSlotWrapperProps {
	slotComponent: ComponentType< SlotComponentProps >;
	slotProps?: Record< string, unknown >;
}

/**
 * Generic wrapper for Mini-Cart slot components.
 * Automatically provides cart data in the correct format and handles re-rendering.
 */
const MiniCartSlotWrapper = ( {
	slotComponent: SlotComponent,
	slotProps = {},
}: MiniCartSlotWrapperProps ) => {
	// useStoreCart provides cart in camelCase format
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();

	return (
		<SlotComponent
			extensions={ extensions || {} }
			cart={ cart }
			{ ...slotProps }
		/>
	);
};

export default MiniCartSlotWrapper;
