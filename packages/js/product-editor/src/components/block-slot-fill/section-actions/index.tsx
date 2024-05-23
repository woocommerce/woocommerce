/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { BlockFillProps } from '../types';

export type SectionActionsProps = Omit<
	BlockFillProps,
	'name' | 'slotContainerBlockName'
> & {
	containerBlockName?: string | string[];
};

export function SectionActions( {}: SectionActionsProps ) {
	return <div></div>;
}
