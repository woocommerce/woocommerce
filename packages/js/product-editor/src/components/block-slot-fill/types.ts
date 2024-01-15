/**
 * External dependencies
 */
import { Fill, Slot } from '@wordpress/components';

export type BlockSlotFillProps = {
	name: 'section-actions' | 'section-description';
};

export type BlockSlotProps = BlockSlotFillProps & Slot.Props;

export type BlockFillProps = BlockSlotFillProps &
	Fill.Props & {
		slotContainerBlockName: string;
	};
