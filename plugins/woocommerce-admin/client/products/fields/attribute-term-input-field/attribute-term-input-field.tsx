/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
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

type AttributeTermInputFieldProps = {
	value?: ProductAttributeTerm[];
	onChange: ( value: ProductAttributeTerm[] ) => void;
	attributeId?: number;
	placeholder?: string;
	disabled?: boolean;
};

export const AttributeTermInputField: React.FC<
	AttributeTermInputFieldProps
> = ( { value = [], onChange, placeholder, disabled, attributeId } ) => {
	const [ fetchedItems, setFetchedItems ] = useState<
		ProductAttributeTerm[]
	>( [] );
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = useCallback(
		( searchString: string | undefined ) => {
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
			fetchItems( '' );
		}
	}, [ disabled, attributeId ] );

	const onRemove = ( item: ProductAttributeTerm ) => {
		onChange( value.filter( ( opt ) => opt.slug !== item.slug ) );
	};

	const onSelect = ( item: ProductAttributeTerm ) => {
		const isSelected = value.find( ( i ) => i.slug === item.slug );
		if ( isSelected ) {
			onRemove( item );
			return;
		}
		onChange( [ ...value, item ] );
	};

	const selectedTermSlugs = ( value || [] ).map( ( term ) => term.slug );

	return (
		<SelectControl< ProductAttributeTerm >
			items={ fetchedItems }
			multiple
			disabled={ disabled || ! attributeId }
			label=""
			getFilteredItems={ ( allItems ) => allItems }
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
			className="woocommerce-attribute-term-field"
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
								const isSelected = selectedTermSlugs.includes(
									item.slug
								);

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
										<>
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
										</>
									</MenuItem>
								);
							} ),
						].filter(
							( child ): child is JSX.Element => child !== null
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
