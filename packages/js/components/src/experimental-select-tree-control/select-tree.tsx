/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { chevronDown } from '@wordpress/icons';
import classNames from 'classnames';
import { createElement, useState } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';

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
	suffix = <SuffixIcon icon={ chevronDown } />,
	placeholder,
	isLoading,
	onInputChange,
	shouldShowCreateButton,
	...props
}: SelectTreeProps ) {
	const linkedTree = useLinkedTree( items );
	const selectTreeInstanceId = useInstanceId(
		SelectTree,
		'woocommerce-experimental-select-tree-control__dropdown'
	);
	const menuInstanceId = useInstanceId(
		SelectTree,
		'woocommerce-select-tree-control__menu'
	);
	const isEventOutside = ( event: React.FocusEvent ) => {
		return ! document
			.querySelector( '.' + selectTreeInstanceId )
			?.contains( event.relatedTarget );
	};

	const [ isFocused, setIsFocused ] = useState( false );
	const [ isOpen, setIsOpen ] = useState( false );

	return (
		<div
			className={ `woocommerce-experimental-select-tree-control__dropdown ${ selectTreeInstanceId }` }
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
						className:
							'woocommerce-experimental-select-control__input',
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
						onBlur: ( event ) => {
							// if blurring to an element inside the dropdown, don't close it
							if ( isEventOutside( event ) ) {
								setIsOpen( false );
							}
							setIsFocused( false );
						},
						onKeyDown: ( event ) => {
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
						onChange: ( event ) =>
							onInputChange &&
							onInputChange( event.target.value ),
						placeholder,
					} }
					suffix={ suffix }
				>
					<SelectedItems
						isReadOnly={ ! isOpen }
						items={ ( props.selected as Item[] ) || [] }
						getItemLabel={ ( item ) => item?.label || '' }
						getItemValue={ ( item ) => item?.value || '' }
						onRemove={ ( item ) => {
							if ( ! Array.isArray( item ) && props.onRemove ) {
								props.onRemove( item );
							}
						} }
						getSelectedItemProps={ () => ( {} ) }
					/>
				</ComboBox>
			</div>
			<SelectTreeMenu
				{ ...props }
				id={ `${ props.id }-menu` }
				className={ menuInstanceId.toString() }
				ref={ ref }
				isEventOutside={ isEventOutside }
				isOpen={ isOpen }
				items={ linkedTree }
				shouldShowCreateButton={ shouldShowCreateButton }
				onClose={ () => {
					setIsOpen( false );
				} }
			/>
		</div>
	);
};
