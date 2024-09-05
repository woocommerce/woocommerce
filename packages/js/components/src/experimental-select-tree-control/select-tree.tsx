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
	toggleNode,
	createLinkedTree,
	getVisibleNodeIndex as getVisibleNodeIndex,
	getNodeDataByIndex,
} from '../experimental-tree-control/linked-tree-utils';
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
	const [ highlightedIndex, setHighlightedIndex ] = useState( -1 );

	// whenever the items change, the linked tree needs to be recalculated
	useEffect( () => {
		setLinkedTree( createLinkedTree( items, props.createValue ) );
	}, [ items.length ] );

	// reset highlighted index when the input value changes
	useEffect( () => setHighlightedIndex( -1 ), [ props.createValue ] );

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

	useEffect( () => {
		if ( initialInputValue !== undefined && isFocused ) {
			setInputValue( initialInputValue as string );
		}
	}, [ isFocused ] );

	// Scroll the newly highlighted item into view
	useEffect(
		() =>
			document
				.querySelector(
					'.experimental-woocommerce-tree-item--highlighted'
				)
				?.scrollIntoView?.( {
					block: 'nearest',
				} ),
		[ highlightedIndex ]
	);

	let placeholder: string | undefined = '';
	if ( Array.isArray( props.selected ) ) {
		placeholder = props.selected.length === 0 ? props.placeholder : '';
	} else if ( props.selected ) {
		placeholder = props.placeholder;
	}

	// reset highlighted index when the input value changes
	useEffect( () => {
		if (
			highlightedIndex === items.length &&
			! shouldShowCreateButton?.( props.createValue )
		) {
			setHighlightedIndex( items.length - 1 );
		}
	}, [ props.createValue ] );

	const inputProps: React.InputHTMLAttributes< HTMLInputElement > = {
		className: 'woocommerce-experimental-select-control__input',
		id: `${ props.id }-input`,
		'aria-autocomplete': 'list',
		'aria-activedescendant':
			highlightedIndex >= 0
				? `woocommerce-experimental-tree-control__menu-item-${ highlightedIndex }`
				: undefined,
		'aria-controls': menuInstanceId,
		'aria-owns': menuInstanceId,
		role: 'combobox',
		autoComplete: 'off',
		'aria-expanded': isOpen,
		'aria-haspopup': 'tree',
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
				if (
					// is advancing from the last menu item to the create button
					highlightedIndex === items.length - 1 &&
					shouldShowCreateButton?.( props.createValue )
				) {
					setHighlightedIndex( items.length );
				} else {
					const visibleNodeIndex = getVisibleNodeIndex(
						linkedTree,
						Math.min( highlightedIndex + 1, items.length ),
						'down'
					);
					if ( visibleNodeIndex !== undefined ) {
						setHighlightedIndex( visibleNodeIndex );
					}
				}
			} else if ( event.key === 'ArrowUp' ) {
				event.preventDefault();
				if ( highlightedIndex > 0 ) {
					const visibleNodeIndex = getVisibleNodeIndex(
						linkedTree,
						Math.max( highlightedIndex - 1, -1 ),
						'up'
					);
					if ( visibleNodeIndex !== undefined ) {
						setHighlightedIndex( visibleNodeIndex );
					}
				} else {
					setHighlightedIndex( -1 );
				}
			} else if ( event.key === 'Tab' || event.key === 'Escape' ) {
				setIsOpen( false );
				recalculateInputValue();
			} else if ( event.key === 'Enter' || event.key === ',' ) {
				event.preventDefault();
				if (
					highlightedIndex === items.length &&
					shouldShowCreateButton
				) {
					props.onCreateNew?.();
				} else if (
					// is selecting an item
					highlightedIndex !== -1
				) {
					const nodeData = getNodeDataByIndex(
						linkedTree,
						highlightedIndex
					);
					if ( ! nodeData ) {
						return;
					}
					if ( props.multiple && Array.isArray( props.selected ) ) {
						if (
							! Boolean(
								props.selected.find(
									( i ) => i.label === nodeData.label
								)
							)
						) {
							if ( props.onSelect ) {
								props.onSelect( nodeData );
							}
						} else if ( props.onRemove ) {
							props.onRemove( nodeData );
						}
						setInputValue( '' );
					} else {
						onInputChange?.( nodeData.label );
						props.onSelect?.( nodeData );
						setIsOpen( false );
						setIsFocused( false );
						focusOnInput();
					}
				} else if ( inputValue ) {
					// no highlighted item, but there is an input value, check if it matches any item

					const item = items.find(
						( i ) => i.label === escapeHTML( inputValue )
					);
					const isAlreadySelected = Array.isArray( props.selected )
						? Boolean(
								props.selected.find(
									( i ) =>
										i.label === escapeHTML( inputValue )
								)
						  )
						: props.selected?.label === escapeHTML( inputValue );
					if ( item && ! isAlreadySelected ) {
						props.onSelect?.( item );
						setInputValue( '' );
						recalculateInputValue();
					}
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
					toggleNode( linkedTree, highlightedIndex, true )
				);
			} else if ( event.key === 'ArrowLeft' ) {
				setLinkedTree(
					toggleNode( linkedTree, highlightedIndex, false )
				);
			} else if ( event.key === 'Home' ) {
				event.preventDefault();
				setHighlightedIndex( 0 );
			} else if ( event.key === 'End' ) {
				event.preventDefault();
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
									items={
										! Array.isArray( props.selected )
											? [ props.selected ]
											: props.selected
									}
									getItemLabel={ ( item ) =>
										item?.label || ''
									}
									getItemValue={ ( item ) =>
										item?.value || ''
									}
									onRemove={ ( item ) => {
										if (
											item &&
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
							onExpand={ ( index, value ) => {
								setLinkedTree(
									toggleNode( linkedTree, index, value )
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
