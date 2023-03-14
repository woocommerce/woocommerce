/**
 * External dependencies
 */
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { useLinkedTree } from './hooks/use-linked-tree';
import { Tree } from './tree';
import { Item, TreeControlProps } from './types';
import { SelectedItems } from '../experimental-select-control/selected-items';
import {
	getItemLabelType,
	getItemValueType,
} from '../experimental-select-control/types';
import {
	defaultGetItemLabel,
	defaultGetItemValue,
} from '../experimental-select-control/utils';
import { ComboBox } from '../experimental-select-control/combo-box';

interface ThisProps {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	getSelectedItemProps?: any;
	getItemLabel: getItemLabelType< Item >;
	getItemValue: getItemValueType< Item >;
}

export const SelectTree = forwardRef( function ForwardedTree(
	{
		items,
		getItemLabel = ( item: any ) => item?.label || '',
		getItemValue = ( item: any ) => item?.value || '',
		getSelectedItemProps,
		...props
	}: TreeControlProps & ThisProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	const linkedTree = useLinkedTree( items );

	return (
		<div>
			<label className="woocommerce-experimental-select-control__label">
				Categories Beta
			</label>

			<ComboBox comboBoxProps={ {} } inputProps={ {} }>
				<SelectedItems
					items={ ( props.selected as Item[] ) || [] }
					getItemLabel={ getItemLabel || defaultGetItemLabel }
					getItemValue={ getItemValue || defaultGetItemValue }
					onRemove={ () => {} }
					getSelectedItemProps={ () => ( {} ) }
				/>
			</ComboBox>

			<Tree { ...props } ref={ ref } items={ linkedTree } />
		</div>
	);
} );
