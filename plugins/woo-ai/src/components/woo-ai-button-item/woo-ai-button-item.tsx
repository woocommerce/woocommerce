/**
 * External dependencies
 */
import {
	createSlotFill,
	Slot as BaseSlot,
	Fill as BaseFill,
} from '@wordpress/components';

type WooAIButtonItemSlot = React.FC< BaseSlot.Props >;

type WooAIButtonItemFill = React.FC< BaseFill.Props > & {
	Slot?: WooAIButtonItemSlot;
};

type CreateSlotFillReturn = {
	Fill: WooAIButtonItemFill;
	Slot: WooAIButtonItemSlot;
};

const { Fill, Slot }: CreateSlotFillReturn =
	createSlotFill( 'WooAIButtonItem' );

Fill.Slot = ( { fillProps } ) => (
	<Slot fillProps={ fillProps }>
		{ ( fills ) => {
			return fills.length === 0 ? null : <>{ fills }</>;
		} }
	</Slot>
);

export const WooAIButtonItem = Fill as WooAIButtonItemFill & {
	Slot: WooAIButtonItemSlot;
};
