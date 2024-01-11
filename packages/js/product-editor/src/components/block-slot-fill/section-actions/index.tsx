/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BlockFill } from '../block-fill';
import { BlockFillProps } from '../types';

export type SectionActionsProps = Omit<
	BlockFillProps,
	'name' | 'slotContainerBlockName'
> & {
	containerBlockName?: string;
};

export function SectionActions( {
	containerBlockName = 'woocommerce/product-section',
	...restProps
}: SectionActionsProps ) {
	return (
		<BlockFill
			{ ...restProps }
			name="section-actions"
			slotContainerBlockName={ containerBlockName }
		/>
	);
}
