/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { createSlotFill } from '../slot';

const slotName = '__experimentalOrderMeta';

const { Fill: ExperimentalOrderMeta, Slot: OrderMetaSlot } = createSlotFill(
	slotName
);

const Slot = ( { className, extensions, cart } ) => {
	return (
		<OrderMetaSlot
			className={ classnames(
				className,
				'wc-block-components-order-meta'
			) }
			fillProps={ { extensions, cart } }
		/>
	);
};

ExperimentalOrderMeta.Slot = Slot;

export default ExperimentalOrderMeta;
