/**
 * External dependencies
 */
import { createSlotFill } from 'wordpress-components';
import { Children, cloneElement } from '@wordpress/element';
import classnames from 'classnames';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';
import { useStoreCart } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import BlockErrorBoundary from '../error-boundary';

const slotName = '__experimentalOrderMeta';
const { Fill, Slot: OrderMetaSlot } = createSlotFill( slotName );

function ExperimentalOrderMeta( { children } ) {
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

function Slot( { className } ) {
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	return (
		<OrderMetaSlot
			bubblesVirtually
			className={ classnames(
				className,
				'wc-block-components-order-meta'
			) }
			fillProps={ { extensions, cart } }
		/>
	);
}

ExperimentalOrderMeta.Slot = Slot;

export default ExperimentalOrderMeta;
