/**
 * External dependencies
 */
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

export type BlockSlotFillProps = {
	clientId: string;
	name: string;
};

export type BlockSlotProps = BlockSlotFillProps & SlotComponentProps;

export type BlockFillProps = BlockSlotFillProps &
	FillComponentProps & {
		slotContainerBlockName: string;
	};
