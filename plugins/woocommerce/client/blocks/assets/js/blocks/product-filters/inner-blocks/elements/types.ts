/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { SelectableItemsBlockContext } from '../../../../types/type-defs/selectable-items';
import type { FilterItemFields } from '../../types';

export type BlockAttributes = {
	className?: string;
};

export type EditProps = BlockEditProps< BlockAttributes > & {
	context: SelectableItemsBlockContext< FilterItemFields >;
};

export const ELEMENT_KEYS = [
	'water',
	'fire',
	'earth',
	'tree',
	'metal',
] as const;

export type ElementKey = ( typeof ELEMENT_KEYS )[ number ];
