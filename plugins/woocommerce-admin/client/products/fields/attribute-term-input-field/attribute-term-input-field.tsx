/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { CheckboxControl, Icon, Spinner } from '@wordpress/components';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { plus } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
} from '@woocommerce/data';
import {
	SelectControlProps,
	selectControlStateChangeTypes,
	useAsyncFilter,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './attribute-term-input-field.scss';
import { CreateAttributeTermModal } from './create-attribute-term-modal';

type AttributeTermInputFieldProps = {
	value?: ProductAttributeTerm[];
	onChange: ( value: ProductAttributeTerm[] ) => void;
	attributeId?: number;
	placeholder?: string;
	disabled?: boolean;
	label?: string;
};

let uniqueId = 0;

/**
 * Add a new item at the end of the list if the
 * current set of items do not match exactly the
 * input's value
 *
 * @param allItems The item list
 * @param inputValue The input's value
 * @return The `allItems` + 1 new item if condition is met
 */
function addCreateNewAttributeTermItem(
	allItems: ProductAttributeTerm[],
	inputValue: string
) {
	if (
		inputValue.length > 0 &&
		! allItems.some(
			( item ) => item.name.toLowerCase() === inputValue.toLowerCase()
		)
	) {
		return [
			...allItems,
			{
				id: -99,
				name: inputValue,
			} as ProductAttributeTerm,
		];
	}
	return allItems;
}

export const AttributeTermInputField: React.FC<
	AttributeTermInputFieldProps
> = ( {
	value = [],
	onChange,
	placeholder,
	disabled,
	attributeId,
	label = '',
} ) => {
	const attributeTermInputId = useRef(
		`woocommerce-attribute-term-field-${ ++uniqueId }`
	);
	const [ fetchedItems, setFetchedItems ] = useState<
		ProductAttributeTerm[]
	>( [] );
	const [ isFetching, setIsFetching ] = useState( false );
	const [ addNewAttributeTermName, setAddNewAttributeTermName ] =
		useState< string >();

	const fetchItems = useCallback(
		( searchString?: string ) => {
			setIsFetching( true );
			return resolveSelect(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			)
				.getProductAttributeTerms< ProductAttributeTerm[] >( {
					search: searchString || '',
					attribute_id: attributeId,
				} )
				.then(
					( attributeTerms ) => {
						const items = addCreateNewAttributeTermItem(
							attributeTerms,
							searchString ?? ''
						);
						setFetchedItems( items );
						setIsFetching( false );
						return items;
					},
					( error ) => {
						setIsFetching( false );
						return error;
					}
				);
		},
		[ attributeId ]
	);

	useEffect( () => {
		if (
			! disabled &&
			attributeId !== undefined &&
			! fetchedItems.length
		) {
			fetchItems();
		}
	}, [ disabled, attributeId ] );

	const onRemove = ( item: ProductAttributeTerm ) => {
		onChange( value.filter( ( opt ) => opt.slug !== item.slug ) );
	};

	const onSelect = ( item: ProductAttributeTerm ) => {
		// Add new item.
		if ( item.id === -99 ) {
			setAddNewAttributeTermName( item.name );
			return;
		}
		const isSelected = value.find( ( i ) => i.slug === item.slug );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		onChange( [ ...value, item ] );
	};

	const focusSelectControl = () => {
		const selectControlInputField: HTMLInputElement | null =
			document.querySelector(
				'.' +
					attributeTermInputId.current +
					' .woocommerce-experimental-select-control__input'
			);
		if ( selectControlInputField ) {
			setTimeout( () => {
				selectControlInputField.focus();
			}, 0 );
		}
	};

	const selectedTermSlugs = ( value || [] ).map( ( term ) => term.slug );

	const selectProps: SelectControlProps< ProductAttributeTerm > = {
		items: fetchedItems,
		multiple: true,
		disabled: disabled || ! attributeId,
		label,
		placeholder: placeholder || '',
		getItemLabel: ( item: ProductAttributeTerm | null ) => item?.name || '',
		getItemValue: ( item: ProductAttributeTerm | null ) => item?.slug || '',
		stateReducer: ( state, actionAndChanges ) => {
			const { changes, type } = actionAndChanges;
			switch ( type ) {
				case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:
					return {
						...changes,
						inputValue: state.inputValue,
					};
				case selectControlStateChangeTypes.ItemClick:
					if (
						changes.selectedItem &&
						changes.selectedItem.id === -99
					) {
						return changes;
					}
					return {
						...changes,
						isOpen: true,
						inputValue: state.inputValue,
						highlightedIndex: state.highlightedIndex,
					};
				default:
					return changes;
			}
		},
		selected: value,
		onSelect,
		onRemove,
		className:
			'woocommerce-attribute-term-field ' + attributeTermInputId.current,
		__experimentalOpenMenuOnFocus: true,
	};

	const selectPropsWithAsyncFilter = useAsyncFilter< ProductAttributeTerm >( {
		filter: fetchItems,
		...selectProps,
	} );

	return (
		<>
			<SelectControl< ProductAttributeTerm >
				{ ...selectPropsWithAsyncFilter }
			>
				{ ( {
					items,
					highlightedIndex,
					getItemProps,
					getMenuProps,
					isOpen,
				} ) => {
					return (
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ [
								isFetching ? (
									<div
										key="loading-spinner"
										className="woocommerce-attribute-term-field__loading-spinner"
									>
										<Spinner />
									</div>
								) : null,
								...items.map( ( item, menuIndex ) => {
									const isSelected =
										selectedTermSlugs.includes( item.slug );

									return (
										<MenuItem
											key={ `${ item.slug }` }
											index={ menuIndex }
											isActive={
												highlightedIndex === menuIndex
											}
											item={ item }
											getItemProps={ getItemProps }
										>
											{ item.id !== -99 ? (
												<CheckboxControl
													onChange={ () => null }
													checked={ isSelected }
													label={
														<span
															style={ {
																fontWeight:
																	isSelected
																		? 'bold'
																		: 'normal',
															} }
														>
															{ item.name }
														</span>
													}
												/>
											) : (
												<div className="woocommerce-attribute-term-field__add-new">
													<Icon
														icon={ plus }
														size={ 20 }
														className="woocommerce-attribute-term-field__add-new-icon"
													/>
													<span>
														{ sprintf(
															/* translators: The name of the new attribute term to be created */
															__(
																'Create "%s"',
																'woocommerce'
															),
															item.name
														) }
													</span>
												</div>
											) }
										</MenuItem>
									);
								} ),
							].filter(
								( child ): child is JSX.Element =>
									child !== null
							) }
						</Menu>
					);
				} }
			</SelectControl>
			{ addNewAttributeTermName && attributeId !== undefined && (
				<CreateAttributeTermModal
					initialAttributeTermName={ addNewAttributeTermName }
					onCancel={ () => {
						setAddNewAttributeTermName( undefined );
						focusSelectControl();
					} }
					attributeId={ attributeId }
					onCreated={ ( newAttribute ) => {
						onSelect( newAttribute );
						setAddNewAttributeTermName( undefined );
						focusSelectControl();
					} }
				/>
			) }
		</>
	);
};
