/**
 * @todo Create guards against __experimentalUseSlot use.
 */
/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	createSlotFill,
	__experimentalUseSlot as useSlot,
} from 'wordpress-components';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';
import { Children, cloneElement } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import BlockErrorBoundary from '../error-boundary';

const slotName = '__experimentalOrderShippingPackages';
const { Fill, Slot: OrderShippingPackagesSlot } = createSlotFill( slotName );

function ExperimentalOrderShippingPackages( { children } ) {
	return (
		<Fill>
			{ ( fillProps ) => {
				return Children.map( children, ( fill ) => {
					return (
						<BlockErrorBoundary
							renderError={
								CURRENT_USER_IS_ADMIN ? null : () => null
							}
						>
							{ cloneElement( fill, fillProps ) }
						</BlockErrorBoundary>
					);
				} );
			} }
		</Fill>
	);
}

function Slot( { className, collapsible, noResultsMessage, renderOption } ) {
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const { fills } = useSlot( slotName );
	const hasMultiplePackages = fills.length > 1;
	return (
		<OrderShippingPackagesSlot
			bubblesVirtually
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
}

ExperimentalOrderShippingPackages.Slot = Slot;

export default ExperimentalOrderShippingPackages;
