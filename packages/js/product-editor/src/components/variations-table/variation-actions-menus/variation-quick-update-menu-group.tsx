/**
 * External dependencies
 */

import { Slot, Fill, MenuGroup } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { VariationQuickUpdateSelectionProps } from './types';

export const VariationQuickUpdateMenuGroup: React.FC< {
	name?: string;
	label?: string;
	children?: ( props: VariationQuickUpdateSelectionProps ) => React.ReactNode;
} > & {
	Slot: React.FC<
		Slot.Props & {
			name?: string;
		} & VariationQuickUpdateSelectionProps
	>;
} = ( { children, name = '', label } ) => {
	return (
		<Fill name={ name }>
			{ ( fillProps: VariationQuickUpdateSelectionProps ) => {
				return (
					<MenuGroup label={ label }>
						{ children && children( fillProps ) }
					</MenuGroup>
				);
			} }
		</Fill>
	);
};

VariationQuickUpdateMenuGroup.Slot = ( {
	name = '',
	onChange,
	onClose,
	selection,
} ) => (
	<Slot name={ name } fillProps={ { onChange, onClose, selection } }>
		{ ( fills ) => <>{ fills }</> }
	</Slot>
);
