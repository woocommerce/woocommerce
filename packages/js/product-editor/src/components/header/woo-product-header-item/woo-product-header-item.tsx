/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
import {
	createSlotFill,
	Slot as BaseSlot,
	Fill as BaseFill,
} from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

type WooProductHeaderSlot = React.FC< BaseSlot.Props >;

type WooProductHeaderFill = React.FC< BaseFill.Props > & {
	Slot?: WooProductHeaderSlot;
};

type CreateSlotFillReturn = {
	Fill: WooProductHeaderFill;
	Slot: WooProductHeaderSlot;
};

const { Fill, Slot }: CreateSlotFillReturn = createSlotFill(
	'WooProductHeaderItem'
);

Fill.Slot = ( { fillProps } ) => (
	<Slot fillProps={ fillProps }>
		{ ( fills ) => {
			return isEmpty( fills ) ? null : <>{ fills }</>;
		} }
	</Slot>
);

export const WooProductHeaderItem = Fill as WooProductHeaderFill & {
	Slot: WooProductHeaderSlot;
};
