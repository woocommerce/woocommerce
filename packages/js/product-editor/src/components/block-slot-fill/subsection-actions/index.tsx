/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BlockFill } from '../block-fill';
import { BlockFillProps } from '../types';

export type SubsectionActionsProps = Omit<
	BlockFillProps,
	'name' | 'slotContainerBlockName'
> & {
	containerBlockName?: string;
};

export function SubsectionActions( {
	containerBlockName = 'woocommerce/product-subsection',
	...restProps
}: SubsectionActionsProps ) {
	return (
		<BlockFill
			{ ...restProps }
			name="subsection-actions"
			slotContainerBlockName={ containerBlockName }
		/>
	);
}
