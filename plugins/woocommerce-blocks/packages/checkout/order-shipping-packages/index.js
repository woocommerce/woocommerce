/**
 * External dependencies
 */
import classnames from 'classnames';
import { useStoreCart } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { createSlotFill, useSlot } from '../slot';

const slotName = '__experimentalOrderShippingPackages';
const {
	Fill: ExperimentalOrderShippingPackages,
	Slot: OrderShippingPackagesSlot,
} = createSlotFill( slotName );

const Slot = ( { className, collapsible, noResultsMessage, renderOption } ) => {
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const { fills } = useSlot( slotName );
	const hasMultiplePackages = fills.length > 1;
	return (
		<OrderShippingPackagesSlot
			className={ classnames(
				'wc-block-components-shipping-rates-control',
				className
			) }
			fillProps={ {
				collapsible,
				collapse: hasMultiplePackages,
				showItems: hasMultiplePackages,
				noResultsMessage,
				renderOption,
				extensions,
				cart,
			} }
		/>
	);
};

ExperimentalOrderShippingPackages.Slot = Slot;

export default ExperimentalOrderShippingPackages;
