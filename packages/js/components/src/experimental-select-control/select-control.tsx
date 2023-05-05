/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	useCombobox,
	UseComboboxState,
	UseComboboxStateChangeOptions,
	useMultipleSelection,
	GetInputPropsOptions,
} from 'downshift';
import {
	useState,
	useEffect,
	createElement,
	Fragment,
} from '@wordpress/element';
import { search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	ChildrenType,
	DefaultItemType,
	getItemLabelType,
	getItemValueType,
} from './types';
import { SelectedItems } from './selected-items';
import { ComboBox } from './combo-box';
import { Menu } from './menu';
import { MenuItem } from './menu-item';
import { SuffixIcon } from './suffix-icon';
import {
	defaultGetItemLabel,
	defaultGetItemValue,
	defaultGetFilteredItems,
} from './utils';

export type SelectControlProps< ItemType > = {
	children?: ChildrenType< ItemType >;
	items: ItemType[];
	label: string | JSX.Element;
	getItemLabel?: getItemLabelType< ItemType >;
	getItemValue?: getItemValueType< ItemType >;
	getFilteredItems?: (
		allItems: ItemType[],
		inputValue: string,
		selectedItems: ItemType[],
		getItemLabel: getItemLabelType< ItemType >
	) => ItemType[];
	hasExternalTags?: boolean;
	multiple?: boolean;
	onInputChange?: (
		value: string | undefined,
		changes: Partial< Omit< UseComboboxState< ItemType >, 'inputValue' > >
	) => void;
	onRemove?: ( item: ItemType ) => void;
	onSelect?: ( selected: ItemType ) => void;
	onFocus?: ( data: { inputValue: string } ) => void;
	stateReducer?: (
		state: UseComboboxState< ItemType | null >,
		actionAndChanges: UseComboboxStateChangeOptions< ItemType | null >
	) => Partial< UseComboboxState< ItemType | null > >;
	placeholder?: string;
	selected: ItemType | ItemType[] | null;
	className?: string;
	disabled?: boolean;
	inputProps?: GetInputPropsOptions;
	suffix?: JSX.Element | null;
	showToggleButton?: boolean;
	/**
	 * This is a feature already implemented in downshift@7.0.0 through the
	 * reducer. In order for us to use it this prop is added temporarily until
	 * current downshift version get updated.
	 *
	 * @see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox
	 * @default false
	 */
	__experimentalOpenMenuOnFocus?: boolean;
};

export const selectControlStateChangeTypes = useCombobox.stateChangeTypes;

function SelectControl< ItemType = DefaultItemType >( {
	getItemLabel = defaultGetItemLabel,
	getItemValue = defaultGetItemValue,
	hasExternalTags = false,
	children = ( {
		items: renderItems,
		highlightedIndex,
		getItemProps,
		getMenuProps,
		isOpen,
	} ) => {
		return (
			<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
				{ renderItems.map( ( item, index: number ) => (
					<MenuItem
						key={ `${ getItemValue( item ) }${ index }` }
						index={ index }
						isActive={ highlightedIndex === index }
						item={ item }
						getItemProps={ getItemProps }
					>
						{ getItemLabel( item ) }
					</MenuItem>
				) ) }
			</Menu>
		);
	},
	multiple = false,
	items,
	label,
	getFilteredItems = defaultGetFilteredItems,
	onInputChange = () => null,
	onRemove = () => null,
	onSelect = () => null,
	onFocus = () => null,
	stateReducer = ( state, actionAndChanges ) => actionAndChanges.changes,
	placeholder,
	selected,
	className,
	disabled,
	inputProps = {},
	suffix = <SuffixIcon icon={ search } />,
	showToggleButton = false,
	__experimentalOpenMenuOnFocus = false,
}: SelectControlProps< ItemType > ) {
	const [ isFocused, setIsFocused ] = useState( false );
	const [ inputValue, setInputValue ] = useState( '' );

	let selectedItems = selected === null ? [] : selected;
	selectedItems = Array.isArray( selectedItems )
		? selectedItems
		: [ selectedItems ].filter( Boolean );
	const singleSelectedItem =
		! multiple && selectedItems.length ? selectedItems[ 0 ] : null;
	const filteredItems = getFilteredItems(
		items,
		inputValue,
		selectedItems,
		getItemLabel
	);
	const {
		getSelectedItemProps,
		getDropdownProps,
		removeSelectedItem,
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
	} = useMultipleSelection( { itemToString: getItemLabel, selectedItems } );

	useEffect( () => {
		if ( multiple ) {
			return;
		}

		setInputValue( getItemLabel( singleSelectedItem ) );
	}, [ getItemLabel, multiple, singleSelectedItem ] );

	const {
		isOpen,
		getLabelProps,
		getMenuProps,
		getToggleButtonProps,
		getInputProps,
		getComboboxProps,
		highlightedIndex,
		getItemProps,
		selectItem,
		selectedItem: comboboxSingleSelectedItem,
		openMenu,
		closeMenu,
	} = useCombobox< ItemType | null >( {
		initialSelectedItem: singleSelectedItem,
		inputValue,
		items: filteredItems,
		selectedItem: multiple ? null : singleSelectedItem,
		itemToString: getItemLabel,
		onSelectedItemChange: ( { selectedItem } ) => {
			if ( selectedItem ) {
				onSelect( selectedItem );
			} else if ( singleSelectedItem ) {
				onRemove( singleSelectedItem );
			}
		},
		onInputValueChange: ( { inputValue: value, ...changes } ) => {
			if ( value !== undefined ) {
				setInputValue( value );
				onInputChange( value, changes );
			}
		},
		stateReducer: ( state, actionAndChanges ) => {
			const { changes, type } = actionAndChanges;
			let newChanges;
			switch ( type ) {
				case selectControlStateChangeTypes.InputBlur:
					// Set input back to selected item if there is a selected item, blank otherwise.
					newChanges = {
						...changes,
						selectedItem:
							! changes.inputValue?.length && ! multiple
								? null
								: changes.selectedItem,
						inputValue:
							changes.selectedItem === state.selectedItem &&
							changes.inputValue?.length &&
							! multiple
								? getItemLabel( comboboxSingleSelectedItem )
								: '',
					};
					break;
				case selectControlStateChangeTypes.InputKeyDownEnter:
				case selectControlStateChangeTypes.FunctionSelectItem:
				case selectControlStateChangeTypes.ItemClick:
					if ( changes.selectedItem && multiple ) {
						newChanges = {
							...changes,
							inputValue: '',
						};
					}
					break;
				default:
					break;
			}
			return stateReducer( state, {
				...actionAndChanges,
				changes: newChanges ?? changes,
			} );
		},
	} );

	const onRemoveItem = ( item: ItemType ) => {
		selectItem( null );
		removeSelectedItem( item );
		onRemove( item );
	};

	const selectedItemTags = multiple ? (
		<SelectedItems
			items={ selectedItems }
			getItemLabel={ getItemLabel }
			getItemValue={ getItemValue }
			getSelectedItemProps={ getSelectedItemProps }
			onRemove={ onRemoveItem }
		/>
	) : null;

	return (
		<div
			className={ classnames(
				'woocommerce-experimental-select-control',
				className,
				{
					'is-focused': isFocused,
				}
			) }
		>
			{ /* Downshift's getLabelProps handles the necessary label attributes. */ }
			{ /* eslint-disable jsx-a11y/label-has-for */ }
			{ label && (
				<label
					{ ...getLabelProps() }
					className="woocommerce-experimental-select-control__label"
				>
					{ label }
				</label>
			) }
			{ /* eslint-enable jsx-a11y/label-has-for */ }
			<ComboBox
				comboBoxProps={ getComboboxProps() }
				getToggleButtonProps={ getToggleButtonProps }
				inputProps={ getInputProps( {
					...getDropdownProps( {
						preventKeyAction: isOpen,
					} ),
					className: 'woocommerce-experimental-select-control__input',
					onFocus: () => {
						setIsFocused( true );
						onFocus( { inputValue } );
						if ( __experimentalOpenMenuOnFocus ) {
							openMenu();
						}
					},
					onBlur: () => setIsFocused( false ),
					placeholder,
					disabled,
					...inputProps,
				} ) }
				suffix={ suffix }
				showToggleButton={ showToggleButton }
			>
				<>
					{ children( {
						items: filteredItems,
						highlightedIndex,
						getItemProps,
						getMenuProps,
						isOpen,
						getItemLabel,
						getItemValue,
						selectItem,
						setInputValue,
						openMenu,
						closeMenu,
					} ) }
					{ ! hasExternalTags && selectedItemTags }
				</>
			</ComboBox>

			{ hasExternalTags && selectedItemTags }
		</div>
	);
}

export { SelectControl };
