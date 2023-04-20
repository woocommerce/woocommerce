/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import classNames from 'classnames';
import { search } from '@wordpress/icons';
import { useInstanceId } from '@wordpress/compose';
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useLinkedTree } from '../experimental-tree-control/hooks/use-linked-tree';
import { Item, TreeControlProps } from '../experimental-tree-control/types';
import { SelectedItems } from '../experimental-select-control/selected-items';
import { ComboBox } from '../experimental-select-control/combo-box';
import { SuffixIcon } from '../experimental-select-control/suffix-icon';
import { SelectTreeMenu } from './select-tree-menu';

interface SelectTreeProps extends TreeControlProps {
	id: string;
	selected?: Item | Item[];
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
	const menuInstanceId = useInstanceId(
		SelectTree,
		'woocommerce-select-tree-control__menu'
	);

	const [ isFocused, setIsFocused ] = useState( false );
	const [ isOpen, setIsOpen ] = useState( false );

	const inputProps: any = {
		className: 'woocommerce-experimental-select-control__input',
		id: `${ props.id }-input`,
		'aria-autocomplete': 'list',
		'aria-controls': `${ props.id }-menu`,
		autoComplete: 'off',
		onFocus: () => {
			if ( ! isOpen ) {
				setIsOpen( true );
			}
			setIsFocused( true );
		},
		onBlur: ( event: any ) => {
			// if blurring to an element inside the dropdown, don't close it
			if (
				isOpen &&
				! document
					.querySelector( '.' + menuInstanceId )
					?.contains( event.relatedTarget )
			) {
				setIsOpen( false );
				if ( ! props.multiple ) {
					if ( props.selected && onInputChange ) {
						onInputChange( ( props.selected as Item ).label );
					} else if ( onInputChange ) {
						onInputChange( '' );
					}
				}
			}
			setIsFocused( false );
		},
		onKeyDown: ( event: any ) => {
			setIsOpen( true );
			if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				// focus on the first element from the Popover
				(
					document.querySelector(
						`.${ menuInstanceId } input, .${ menuInstanceId } button`
					) as HTMLInputElement | HTMLButtonElement
				 )?.focus();
			}
			if ( event.key === 'Tab' ) {
				setIsOpen( false );
			}
		},
		onChange: ( event: any ) =>
			onInputChange && onInputChange( event.target.value ),
		placeholder,
	};

	return (
		<div
			className="woocommerce-experimental-select-tree-control__dropdown"
			tabIndex={ -1 }
		>
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
				{ props.multiple ? (
					<ComboBox
						comboBoxProps={ {
							className:
								'woocommerce-experimental-select-control__combo-box-wrapper',
							role: 'combobox',
							'aria-expanded': isOpen,
							'aria-haspopup': 'tree',
							'aria-labelledby': `${ props.id }-label`,
							'aria-owns': `${ props.id }-menu`,
						} }
						inputProps={ {
							...inputProps,
							onChange: ( event: any ) =>
								onInputChange &&
								onInputChange( event.target.value ),
						} }
						suffix={ suffix }
					>
						<SelectedItems
							items={ ( props.selected as Item[] ) || [] }
							getItemLabel={ ( item ) => item?.label || '' }
							getItemValue={ ( item ) => item?.value || '' }
							onRemove={ ( item ) => {
								if (
									! Array.isArray( item ) &&
									props.onRemove
								) {
									props.onRemove( item );
									setIsOpen( false );
								}
							} }
							getSelectedItemProps={ () => ( {} ) }
						/>
					</ComboBox>
				) : (
					<TextControl
						{ ...inputProps }
						value={ props.createValue }
						onChange={ ( value ) => {
							if ( onInputChange ) onInputChange( value );
							const item = items.find(
								( i ) => i.label === value
							);
							if ( props.onSelect && item ) {
								props.onSelect( item );
							}
						} }
					/>
				) }
			</div>
			<SelectTreeMenu
				{ ...props }
				onSelect={ ( item ) => {
					if ( ! props.multiple && onInputChange ) {
						onInputChange( ( item as Item ).label );
						setIsOpen( false );
						setIsFocused( false );
					}
					if ( props.onSelect ) {
						props.onSelect( item );
					}
				} }
				id={ `${ props.id }-menu` }
				className={ menuInstanceId.toString() }
				ref={ ref }
				isOpen={ isOpen }
				items={ linkedTree }
				shouldShowCreateButton={ shouldShowCreateButton }
				onClose={ () => setIsOpen( false ) }
			/>
		</div>
	);
};
