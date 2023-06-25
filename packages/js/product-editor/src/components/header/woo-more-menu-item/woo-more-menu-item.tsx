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

type WooProductMoreMenuSlot = React.FC< BaseSlot.Props >;

type WooProductMoreMenuFill = React.FC< BaseFill.Props > & {
	Slot?: WooProductMoreMenuSlot;
};

type CreateSlotFillReturn = {
	Fill: WooProductMoreMenuFill;
	Slot: WooProductMoreMenuSlot;
};

const { Fill, Slot }: CreateSlotFillReturn = createSlotFill(
	'WooProductMoreMenuItem'
);

Fill.Slot = ( { fillProps } ) => (
	<Slot fillProps={ fillProps }>
		{ ( fills ) => {
			return isEmpty( fills ) ? null : <>{ fills }</>;
		} }
	</Slot>
);

export const WooProductMoreMenuItem = Fill as WooProductMoreMenuFill & {
	Slot: WooProductMoreMenuSlot;
};
