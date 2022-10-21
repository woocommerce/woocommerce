/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { CheckboxControl, Icon, Spinner } from '@wordpress/components';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { plus } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
} from '@woocommerce/data';
import {
	selectControlStateChangeTypes,
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
		( searchString?: string | undefined ) => {
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
						setFetchedItems( attributeTerms );
						setIsFetching( false );
						return attributeTerms;
					},
					( error ) => {
						setIsFetching( false );
						return error;
					}
				);
		},
		[ attributeId ]
	);

	const debouncedSearch = useDebounce( fetchItems, 250 );

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

	return (
		<>
			<SelectControl< ProductAttributeTerm >
				items={ fetchedItems }
				multiple
				disabled={ disabled || ! attributeId }
				label={ label }
				getFilteredItems={ ( allItems, inputValue ) => {
					if (
						inputValue.length > 0 &&
						! allItems.find(
							( item ) =>
								item.name.toLowerCase() ===
								inputValue.toLowerCase()
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
				} }
				onInputChange={ debouncedSearch }
				placeholder={ placeholder || '' }
				getItemLabel={ ( item ) => item?.name || '' }
				getItemValue={ ( item ) => item?.slug || '' }
				stateReducer={ ( state, actionAndChanges ) => {
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
				} }
				selected={ value }
				onSelect={ onSelect }
				onRemove={ onRemove }
				className={
					'woocommerce-attribute-term-field ' +
					attributeTermInputId.current
				}
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
