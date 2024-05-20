/**
 * External dependencies
 */

import { Slot, Fill, MenuGroup } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { Test } from './types';

export const VariationQuickUpdateMenuGroup: React.FC< {
	name?: string;
	label?: string;
	children?: ( props: Test ) => React.ReactNode;
} > & {
	Slot: React.FC<
		Slot.Props & {
			name?: string;
		} & Test
	>;
} = ( { children, name = '', label } ) => {
	return (
		<Fill name={ name }>
			{ ( fillProps: Fill.Props & Test ) => {
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
