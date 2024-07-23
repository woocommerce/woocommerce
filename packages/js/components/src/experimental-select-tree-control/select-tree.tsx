/**
 * External dependencies
 */
import { chevronDown, chevronUp, closeSmall } from '@wordpress/icons';
import classNames from 'classnames';
import {
	createElement,
	useEffect,
	useState,
	Fragment,
	useRef,
} from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { BaseControl, Button, TextControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import {
	expandNodeNumber,
	getLinkedTree,
	getVisibleNodeIndex as getVisibleNodeIndex,
} from '../experimental-tree-control/hooks/use-linked-tree';
import {
	Item,
	LinkedTree,
	TreeControlProps,
} from '../experimental-tree-control/types';
import { SelectedItems } from '../experimental-select-control/selected-items';
import { ComboBox } from '../experimental-select-control/combo-box';
import { SuffixIcon } from '../experimental-select-control/suffix-icon';
import { SelectTreeMenu } from './select-tree-menu';
import { escapeHTML } from '../utils';
import { SelectedItemFocusHandle } from '../experimental-select-control/types';

interface SelectTreeProps extends TreeControlProps {
	id: string;
	selected?: Item | Item[];
	treeRef?: React.ForwardedRef< HTMLOListElement >;
	isLoading?: boolean;
	disabled?: boolean;
	label: string | JSX.Element;
	help?: string | JSX.Element;
	onInputChange?: ( value: string | undefined ) => void;
	initialInputValue?: string | undefined;
	isClearingAllowed?: boolean;
	onClear?: () => void;
}

export const SelectTree = function SelectTree( {
	items,
	treeRef: ref,
	isLoading,
	disabled,
	initialInputValue,
	onInputChange,
	shouldShowCreateButton,
	help,
	isClearingAllowed = false,
	onClear = () => {},
	...props
}: SelectTreeProps ) {
	const [ linkedTree, setLinkedTree ] = useState< LinkedTree[] >( [] );

	useEffect( () => setLinkedTree( getLinkedTree( items ) ), [ items ] );

	const selectTreeInstanceId = useInstanceId(
		SelectTree,
		'woocommerce-experimental-select-tree-control__dropdown'
	) as string;
	const menuInstanceId = useInstanceId(
		SelectTree,
		'woocommerce-select-tree-control__menu'
	) as string;

	const selectedItemsFocusHandle = useRef< SelectedItemFocusHandle >( null );

	function isEventOutside( event: React.FocusEvent ) {
		const isInsideSelect = document
			.getElementById( selectTreeInstanceId )
			?.contains( event.relatedTarget );

		const isInsidePopover = document
			.getElementById( menuInstanceId )
			?.closest(
				'.woocommerce-experimental-select-tree-control__popover-menu'
			)
			?.contains( event.relatedTarget );
		const isInRemoveTag = event.relatedTarget?.classList.contains(
			'woocommerce-tag__remove'
		);
		return ! isInsideSelect && ! isInRemoveTag && ! isInsidePopover;
	}

	const recalculateInputValue = () => {
		if ( onInputChange ) {
			if ( ! props.multiple && props.selected ) {
				onInputChange( ( props.selected as Item ).label );
			} else {
				onInputChange( '' );
			}
		}
	};

	const focusOnInput = () => {
		(
			document.querySelector( `#${ props.id }-input` ) as HTMLInputElement
		 )?.focus();
	};

	const [ isFocused, setIsFocused ] = useState( false );
	const [ isOpen, setIsOpen ] = useState( false );
	const [ inputValue, setInputValue ] = useState( '' );
	const isReadOnly = ! isOpen && ! isFocused;
	const [ highlightedIndex, setHighlightedIndex ] = useState( -1 );

	useEffect( () => {
		if ( initialInputValue !== undefined && isFocused ) {
			setInputValue( initialInputValue as string );
		}
	}, [ isFocused ] );

	useEffect(
		() =>
			document
				.querySelector(
					'.experimental-woocommerce-tree-item--testnathan'
				)
				?.scrollIntoView( false ),
		[ highlightedIndex ]
	);

	let placeholder: string | undefined = '';
	if ( Array.isArray( props.selected ) ) {
		placeholder = props.selected.length === 0 ? props.placeholder : '';
	} else if ( props.selected ) {
		placeholder = props.placeholder;
	}

	const inputProps: React.InputHTMLAttributes< HTMLInputElement > = {
		className: 'woocommerce-experimental-select-control__input',
		id: `${ props.id }-input`,
		'aria-autocomplete': 'list',
		'aria-controls': `${ props.id }-menu`,
		autoComplete: 'off',
		disabled,
		onFocus: ( event ) => {
			if ( props.multiple ) {
				speak(
					__(
						'To select existing items, type its exact label and separate with commas or the Enter key.',
						'woocommerce'
					)
				);
			}
			if ( ! isOpen ) {
				setIsOpen( true );
			}
			setIsFocused( true );
			if (
				Array.isArray( props.selected ) &&
				props.selected?.some(
					( item: Item ) => item.label === event.target.value
				)
			) {
				setInputValue( '' );
			}
		},
		onBlur: ( event ) => {
			event.preventDefault();
			if ( isEventOutside( event ) ) {
				setIsOpen( false );
				setIsFocused( false );
				recalculateInputValue();
			}
		},
		onKeyDown: ( event ) => {
			setIsOpen( true );
			if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				const a = getVisibleNodeIndex(
					linkedTree,
					Math.min( highlightedIndex + 1, items.length - 1 ),
					'down'
				);
				setHighlightedIndex( a !== undefined ? a : highlightedIndex );
			} else if ( event.key === 'ArrowUp' ) {
				const a = getVisibleNodeIndex(
					linkedTree,
					Math.max( highlightedIndex - 1, -1 ),
					'up'
				);
				setHighlightedIndex( a !== undefined ? a : highlightedIndex );
			} else if ( event.key === 'Tab' || event.key === 'Escape' ) {
				setIsOpen( false );
				recalculateInputValue();
			} else if ( event.key === ',' || event.key === 'Enter' ) {
				event.preventDefault();
				const item = items.find(
					( i ) => i.label === escapeHTML( inputValue )
				);
				const isAlreadySelected =
					Array.isArray( props.selected ) &&
					Boolean(
						props.selected.find(
							( i ) => i.label === escapeHTML( inputValue )
						)
					);
				if ( props.onSelect && item && ! isAlreadySelected ) {
					props.onSelect( item );
					setInputValue( '' );
					recalculateInputValue();
				}
			} else if (
				event.key === 'Backspace' &&
				// test if the cursor is at the beginning of the input with nothing selected
				( event.target as HTMLInputElement ).selectionStart === 0 &&
				( event.target as HTMLInputElement ).selectionEnd === 0 &&
				selectedItemsFocusHandle.current
			) {
				selectedItemsFocusHandle.current();
			} else if ( event.key === 'ArrowRight' ) {
				setLinkedTree(
					expandNodeNumber( linkedTree, highlightedIndex, true )
				);
			} else if ( event.key === 'ArrowLeft' ) {
				setLinkedTree(
					expandNodeNumber( linkedTree, highlightedIndex, false )
				);
			} else if ( event.key === 'Home' ) {
				setHighlightedIndex( 0 );
			} else if ( event.key === 'End' ) {
				setHighlightedIndex( items.length - 1 );
			}
		},
		onChange: ( event ) => {
			if ( onInputChange ) {
				onInputChange( event.target.value );
			}
			setInputValue( event.target.value );
		},
		placeholder,
		value: inputValue,
	};

	const handleClear = () => {
		if ( isClearingAllowed ) {
			onClear();
		}
	};

	return (
		<div
			id={ selectTreeInstanceId }
			className={ `woocommerce-experimental-select-tree-control__dropdown` }
			tabIndex={ -1 }
		>
			<div
				className={ classNames(
					'woocommerce-experimental-select-control',
					{
						'is-read-only': isReadOnly,
						'is-focused': isFocused,
						'is-multiple': props.multiple,
						'has-selected-items':
							Array.isArray( props.selected ) &&
							props.selected.length,
					}
				) }
			>
				<BaseControl
					label={ props.label }
					id={ `${ props.id }-input` }
					help={
						props.multiple && ! help
							? __(
									'Separate with commas or the Enter key.',
									'woocommerce'
							  )
							: help
					}
				>
					<>
						{ props.multiple ? (
							<ComboBox
								comboBoxProps={ {
									className:
										'woocommerce-experimental-select-control__combo-box-wrapper',
									role: 'combobox',
									'aria-expanded': isOpen,
									'aria-haspopup': 'tree',
									'aria-owns': `${ props.id }-menu`,
								} }
								inputProps={ inputProps }
								suffix={
									<div className="woocommerce-experimental-select-control__suffix-items">
										{ isClearingAllowed && isOpen && (
											<Button
												label={ __(
													'Remove all',
													'woocommerce'
												) }
												onClick={ handleClear }
											>
												<SuffixIcon
													className="woocommerce-experimental-select-control__icon-clear"
													icon={ closeSmall }
												/>
											</Button>
										) }
										<SuffixIcon
											icon={
												isOpen ? chevronUp : chevronDown
											}
										/>
									</div>
								}
							>
								<SelectedItems
									isReadOnly={ isReadOnly }
									ref={ selectedItemsFocusHandle }
									items={ ( props.selected as Item[] ) || [] }
									getItemLabel={ ( item ) =>
										item?.label || ''
									}
									getItemValue={ ( item ) =>
										item?.value || ''
									}
									onRemove={ ( item ) => {
										if (
											! Array.isArray( item ) &&
											props.onRemove
										) {
											props.onRemove( item );
										}
									} }
									onBlur={ ( event ) => {
										if ( isEventOutside( event ) ) {
											setIsOpen( false );
											setIsFocused( false );
										}
									} }
									onSelectedItemsEnd={ focusOnInput }
									getSelectedItemProps={ () => ( {} ) }
								/>
							</ComboBox>
						) : (
							<TextControl
								{ ...inputProps }
								value={ decodeEntities(
									props.createValue || ''
								) }
								onChange={ ( value ) => {
									if ( onInputChange ) onInputChange( value );
									const item = items.find(
										( i ) => i.label === escapeHTML( value )
									);
									if ( props.onSelect && item ) {
										props.onSelect( item );
									}
									if ( ! value && props.onRemove ) {
										props.onRemove(
											props.selected as Item
										);
									}
								} }
							/>
						) }
						<SelectTreeMenu
							{ ...props }
							onSelect={ ( item ) => {
								if ( ! props.multiple && onInputChange ) {
									onInputChange( ( item as Item ).label );
									setIsOpen( false );
									setIsFocused( false );
									focusOnInput();
								}
								if ( props.onSelect ) {
									props.onSelect( item );
								}
							} }
							id={ menuInstanceId }
							ref={ ref }
							isEventOutside={ isEventOutside }
							isLoading={ isLoading }
							isOpen={ isOpen }
							highlightedIndex={ highlightedIndex }
							onExpand={ ( index ) => {
								setLinkedTree(
									expandNodeNumber( linkedTree, index, true )
								);
							} }
							items={ linkedTree }
							shouldShowCreateButton={ shouldShowCreateButton }
							onEscape={ () => {
								focusOnInput();
								setIsOpen( false );
							} }
							onClose={ () => {
								setIsOpen( false );
							} }
							onFirstItemLoop={ focusOnInput }
						/>
					</>
				</BaseControl>
			</div>
		</div>
	);
};
