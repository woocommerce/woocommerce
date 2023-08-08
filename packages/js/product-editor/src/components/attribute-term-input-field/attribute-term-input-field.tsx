/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { CheckboxControl, Icon, Spinner } from '@wordpress/components';
import { resolveSelect, useDispatch } from '@wordpress/data';
import {
	useCallback,
	useEffect,
	useRef,
	useState,
	createElement,
	Fragment,
} from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
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
import { cleanForSlug } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';

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
	const { invalidateResolutionForStoreSelector } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
	);
	const attributeTermInputId = useRef(
		`woocommerce-attribute-term-field-${ ++uniqueId }`
	);
	const [ fetchedItems, setFetchedItems ] = useState<
		ProductAttributeTerm[]
	>( [] );
	const [ isFetching, setIsFetching ] = useState( false );
	const [ isCreatingTerm, setIsCreatingTerm ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices' );
	const { createProductAttributeTerm, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME );

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

	const createAttributeTerm = async (
		attribute: Partial< ProductAttributeTerm >
	) => {
		recordEvent( 'product_attribute_term_add', {
			source: TRACKS_SOURCE,
		} );
		setIsCreatingTerm( true );
		try {
			const newAttribute: ProductAttributeTerm =
				await createProductAttributeTerm( {
					...attribute,
					attribute_id: attributeId,
				} );
			recordEvent( 'product_attribute_term_add_success', {
				source: TRACKS_SOURCE,
			} );
			onChange( [ ...value, newAttribute ] );
			invalidateResolutionForStoreSelector( 'getProductAttributes' );
			setIsCreatingTerm( false );
		} catch ( e ) {
			recordEvent( 'product_attribute_term_add_failed', {
				source: TRACKS_SOURCE,
			} );
			createNotice(
				'error',
				__( 'Failed to create attribute term.', 'woocommerce' )
			);
			setIsCreatingTerm( false );
		}
	};

	const onSelect = ( item: ProductAttributeTerm ) => {
		// Add new item.
		if ( item.id === -99 ) {
			createAttributeTerm( {
				name: item.name,
				slug: cleanForSlug( item.name ),
			} );
			focusSelectControl();
			return;
		}
		const isSelected = value.find( ( i ) => i.slug === item.slug );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		onChange( [ ...value, item ] );
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
								isOpen: true,
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
				__experimentalOpenMenuOnFocus
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
								isFetching || isCreatingTerm ? (
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
		</>
	);
};
