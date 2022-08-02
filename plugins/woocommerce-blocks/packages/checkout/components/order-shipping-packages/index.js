/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { createSlotFill, useSlot } from '../../slot';

const slotName = '__experimentalOrderShippingPackages';
const {
	Fill: ExperimentalOrderShippingPackages,
	Slot: OrderShippingPackagesSlot,
} = createSlotFill( slotName );

const Slot = ( {
	className,
	collapsible,
	noResultsMessage,
	renderOption,
	extensions,
	cart,
	components,
	context,
	shippingRates,
} ) => {
	const { fills } = useSlot( slotName );
	const hasMultiplePackages = fills.length > 1 || shippingRates?.length > 1;
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
				components,
				context,
			} }
		/>
	);
};

ExperimentalOrderShippingPackages.Slot = Slot;

export default ExperimentalOrderShippingPackages;
