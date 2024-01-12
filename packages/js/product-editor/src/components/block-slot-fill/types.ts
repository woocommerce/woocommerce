/**
 * External dependencies
 */
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

export type BlockSlotFillProps = {
	name: 'section-actions' | 'section-description';
};

export type BlockSlotProps = BlockSlotFillProps & SlotComponentProps;

export type BlockFillProps = BlockSlotFillProps &
	FillComponentProps & {
		slotContainerBlockName: string;
	};
