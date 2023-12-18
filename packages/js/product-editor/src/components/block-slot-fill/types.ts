/**
 * External dependencies
 */
import { Fill, Slot } from '@wordpress/components';

export type BlockSlotFillProps = {
	clientId: string;
	name: string;
};

export type BlockSlotProps = BlockSlotFillProps & Slot.Props;

export type BlockFillProps = BlockSlotFillProps &
	Fill.Props & {
		slotContainerBlockName: string;
	};
