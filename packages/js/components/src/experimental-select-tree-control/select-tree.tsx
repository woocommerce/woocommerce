/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { createElement, useRef, useState } from 'react';
import classNames from 'classnames';
import { search } from '@wordpress/icons';
import { Dropdown, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useLinkedTree } from '../experimental-tree-control/hooks/use-linked-tree';
import { Tree } from '../experimental-tree-control/tree';
import {
	Item,
	LinkedTree,
	TreeControlProps,
} from '../experimental-tree-control/types';
import { SelectedItems } from '../experimental-select-control/selected-items';
import { ComboBox } from '../experimental-select-control/combo-box';
import { SuffixIcon } from '../experimental-select-control/suffix-icon';

interface SelectTreeProps
	extends Omit< TreeControlProps, 'getItemLabel' | 'getItemValue' > {
	selected?: Item[];
	getSelectedItemProps?: any;
	treeRef?: React.ForwardedRef< HTMLOListElement >;
	suffix?: JSX.Element | null;
	isLoading?: boolean;
	label: string | JSX.Element;
	onInputChange?: ( value: string | undefined ) => void;
}

export const SelectTree = function SelectTree( {
	items,
	getSelectedItemProps,
	treeRef: ref,
	suffix = <SuffixIcon icon={ search } />,
	placeholder,
	isLoading,
	onInputChange,
	shouldShowCreateButton,
	...props
}: SelectTreeProps ) {
	const linkedTree = useLinkedTree( items );

	const [ isFocused, setIsFocused ] = useState( false );

	const comboBoxRef = useRef< HTMLDivElement >( null );

	// getting the parent's parent div width to set the width of the dropdown
	const comboBoxWidth =
		comboBoxRef.current?.parentElement?.parentElement?.getBoundingClientRect()
			.width;

	const shouldItemBeExpanded = ( item: LinkedTree ): boolean => {
		if ( ! props.createValue || ! item.children?.length ) return false;
		return item.children.some( ( child ) => {
			if (
				new RegExp( props.createValue || '', 'ig' ).test(
					child.data.name
				)
			) {
				return true;
			}
			return shouldItemBeExpanded( child );
		} );
	};

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
						id={ `${ props.id }-menu` }
						ref={ ref }
						items={ linkedTree }
						onTreeBlur={ onClose }
						shouldItemBeExpanded={ shouldItemBeExpanded }
						shouldShowCreateButton={ shouldShowCreateButton }
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
						htmlFor={ `${ props.id }-input` }
						id={ `${ props.id }-label` }
						className="woocommerce-experimental-select-control__label"
					>
						{ props.label }
					</label>
					<ComboBox
						comboBoxProps={ {
							className:
								'woocommerce-experimental-select-control__combo-box-wrapper',
							ref: comboBoxRef,
							role: 'combobox',
							'aria-expanded': isOpen,
							'aria-haspopup': 'tree',
							'aria-labelledby': `${ props.id }-label`,
							'aria-owns': `${ props.id }-menu`,
						} }
						inputProps={ {
							className:
								'woocommerce-experimental-select-control__input',
							id: `${ props.id }-input`,
							'aria-autocomplete': 'list',
							'aria-controls': `${ props.id }-menu`,
							autoComplete: 'off',
							onFocus: () => {
								if ( ! isOpen ) {
									onToggle();
								}
								setIsFocused( true );
							},
							onBlur: ( event ) => {
								// if blurring to an element inside the dropdown, don't close it
								if (
									isOpen &&
									! document
										.querySelector(
											'.woocommerce-experimental-select-control ~ .components-popover'
										)
										?.contains( event.relatedTarget )
								) {
									onClose();
								}
								setIsFocused( false );
							},
							onKeyDown: ( event ) => {
								const baseQuery =
									'.woocommerce-experimental-select-tree-control__dropdown > .components-popover';
								if ( event.key === 'ArrowDown' ) {
									event.preventDefault();
									// focus on the first element from the Popover
									(
										document.querySelector(
											`${ baseQuery } input, ${ baseQuery } button`
										) as
											| HTMLInputElement
											| HTMLButtonElement
									 )?.focus();
								}
								if ( event.key === 'Tab' ) {
									onClose();
								}
							},
							onChange: ( event ) =>
								onInputChange &&
								onInputChange( event.target.value ),
							placeholder,
						} }
						suffix={ suffix }
					>
						<SelectedItems
							items={ ( props.selected as Item[] ) || [] }
							getItemLabel={ ( item ) => item?.name || '' }
							getItemValue={ ( item ) => item?.id || '' }
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
