/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { createElement, useState } from 'react';
import classNames from 'classnames';
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
	getSelectedItemProps?: any;
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	label: string | JSX.Element;
	onInputChange?: ( value: string | undefined, changes: any ) => void;
	getFilteredItems?: (
		allItems: any,
		inputValue: string,
		selectedItems: any,
		getItemLabel: getItemLabelType< Item >
	) => any[];
	onSelect?( value: Item ): void;
	myRef?: React.ForwardedRef< HTMLOListElement >;
}

export const SelectTree = function SelectTree( {
	items,
	getItemLabel = ( item: any ) => item?.label || '',
	getItemValue = ( item: any ) => item?.value || '',
	getSelectedItemProps,
	myRef: ref,
	...props
}: TreeControlProps & ThisProps ) {
	const linkedTree = useLinkedTree( items );

	const [ isFocused, setIsFocused ] = useState( false );

	return (
		<div
			className={ classNames( 'woocommerce-experimental-select-control', {
				'is-focused': isFocused,
			} ) }
		>
			<label
				htmlFor="test-input"
				className="woocommerce-experimental-select-control__label"
			>
				Categories Beta
			</label>

			<ComboBox
				comboBoxProps={ {
					className:
						'woocommerce-experimental-select-control__combo-box-wrapper',
				} }
				inputProps={ {
					className: 'woocommerce-experimental-select-control__input',
					id: 'test-input',
					onFocus: () => {
						setIsFocused( true );
					},
					onBlur: () => setIsFocused( false ),
					onChange: ( value: any ) => console.log( value ),
				} }
			>
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
};
