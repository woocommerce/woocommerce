/**
 * External dependencies
 */
import { Fill, Slot } from '@wordpress/components';

export type BlockSlotFillProps = {
	name:
		| 'section-actions'
		| 'section-description'
		| 'subsection-actions'
		| 'subsection-description';
};

export type BlockSlotProps = BlockSlotFillProps &
	React.ComponentProps< typeof Slot >;

export type BlockFillProps = BlockSlotFillProps &
	React.ComponentProps< typeof Fill > & {
		slotContainerBlockName: string | string[];
	};
