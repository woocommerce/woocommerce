/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { createElement, useRef, useState, Fragment } from 'react';
import classNames from 'classnames';
import { search } from '@wordpress/icons';
import { Dropdown, Spinner } from '@wordpress/components';

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
import { SuffixIcon } from '../experimental-select-control/suffix-icon';
interface ThisProps {
	getSelectedItemProps?: any;
	getItemLabel?: getItemLabelType< Item >;
	getItemValue?: getItemValueType< Item >;
	label: string | JSX.Element;
	onInputChange?: ( value: string | undefined ) => void;
	getFilteredItems: (
		allItems: any,
		inputValue: string,
		selectedItems: any,
		getItemLabel: getItemLabelType< Item >
	) => any[];
	onSelect?( value: Item ): void;
	myRef?: React.ForwardedRef< HTMLOListElement >;
	suffix?: JSX.Element | null;
	placeholder?: string;
	isLoading?: boolean;
}

export const SelectTree = function SelectTree( {
	items,
	getItemLabel = ( item: any ) => item?.name || '',
	getItemValue = ( item: any ) => item?.id || '',
	getSelectedItemProps,
	getFilteredItems,
	myRef: ref,
	suffix = <SuffixIcon icon={ search } />,
	placeholder,
	isLoading,
	...props
}: TreeControlProps & ThisProps ) {
	const filteredItems = getFilteredItems(
		items,
		props.createValue || '',
		props.selected,
		getItemLabel
	);
	const linkedTree = useLinkedTree( filteredItems );

	const [ isFocused, setIsFocused ] = useState( false );

	const preventClose = useRef( false ); // using a ref instead of state to prevent re-renders

	const comboBoxRef = useRef< HTMLDivElement >( null );

	// getting the parent's parent div width to set the width of the dropdown
	const comboBoxWidth =
		comboBoxRef.current?.parentElement?.parentElement?.getBoundingClientRect()
			.width;

	return (
		<Dropdown
			className="woocommerce-experimental-select-tree-control__dropdown"
			contentClassName="woocommerce-experimental-select-tree-control__dropdown-content"
			focusOnMount={ false }
			renderContent={ ( { onClose } ) =>
				isLoading ? (
					<div
						style={ {
							width: comboBoxWidth,
						} }
					>
						<Spinner />
					</div>
				) : (
					<Tree
						{ ...props }
						ref={ ref }
						items={ linkedTree }
						onTreeBlur={ onClose }
						style={ {
							width: comboBoxWidth,
						} }
					/>
				)
			}
			renderToggle={ ( { isOpen, onToggle, onClose } ) => (
				<div
					className={ classNames(
						'woocommerce-experimental-select-control',
						{
							'is-focused': isFocused,
						}
					) }
				>
					<label
						htmlFor="categories-input"
						className="woocommerce-experimental-select-control__label"
					>
						{ props.label }
					</label>
					<ComboBox
						comboBoxProps={ {
							className:
								'woocommerce-experimental-select-control__combo-box-wrapper',
							ref: comboBoxRef,
						} }
						inputProps={ {
							className:
								'woocommerce-experimental-select-control__input',
							id: 'categories-input',
							onFocus: () => {
								if ( ! isOpen ) {
									onToggle();
								}
								setIsFocused( true );
							},
							onBlur: ( event ) => {
								// if blurring to an element inside the dropdown, don't close it
								// in that case we need to know when the focus has left the tree
								if (
									isOpen &&
									! preventClose.current &&
									! document
										.querySelector(
											'.woocommerce-experimental-select-control ~ .components-popover'
										)
										?.contains( event.relatedTarget )
								) {
									onClose();
								}
								if ( preventClose.current ) {
									preventClose.current = false;
								}
								setIsFocused( false );
							},
							onKeyDown: ( event ) => {
								if ( event.key === 'ArrowDown' ) {
									event.preventDefault();
									preventClose.current = true;
									(
										document.querySelector(
											'.components-checkbox-control__input-container > input'
										) as HTMLInputElement
									 )?.focus();
								}
								if ( event.key === 'Tab' ) {
									onClose();
								}
							},
							onChange: ( event ) =>
								props.onInputChange &&
								props.onInputChange( event.target.value ),
							placeholder,
						} }
						suffix={ suffix }
					>
						<SelectedItems
							items={ ( props.selected as Item[] ) || [] }
							getItemLabel={ getItemLabel || defaultGetItemLabel }
							getItemValue={ getItemValue || defaultGetItemValue }
							onRemove={ ( item ) => {
								if (
									! Array.isArray( item ) &&
									props.onRemove
								) {
									props.onRemove( item );
									onClose();
								}
							} }
							getSelectedItemProps={ () => ( {} ) }
						/>
					</ComboBox>
				</div>
			) }
		/>
	);
};
