/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { createSlotFill } from '../slot';

const slotName = '__experimentalDiscountsMeta';

const {
	Fill: ExperimentalDiscountsMeta,
	Slot: DiscountsMetaSlot,
} = createSlotFill( slotName );

const Slot = ( { className, extensions, cart } ) => {
	return (
		<DiscountsMetaSlot
			className={ classnames(
				className,
				'wc-block-components-discounts-meta'
			) }
			fillProps={ { extensions, cart } }
		/>
	);
};

ExperimentalDiscountsMeta.Slot = Slot;

export default ExperimentalDiscountsMeta;
