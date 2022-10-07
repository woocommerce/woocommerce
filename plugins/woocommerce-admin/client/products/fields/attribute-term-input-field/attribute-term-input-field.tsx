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
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';

type AttributeTermInputFieldProps = {
	value: ProductAttributeTerm[];
	onChange: ( value: ProductAttributeTerm[] ) => void;
	attributeId?: number;
	placeholder?: string;
	disabled?: boolean;
};

export const AttributeTermInputField: React.FC<
	AttributeTermInputFieldProps
> = ( { value, onChange, placeholder, disabled, attributeId } ) => {
	const [ fetchedItems, setFetchedItems ] = useState<
		ProductAttributeTerm[]
	>( [] );
	const [ isFetching, setIsFetching ] = useState( false );

	const fetchItems = useCallback(
		( value: string | undefined ) => {
			setIsFetching( true );
			resolveSelect( EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME )
				.getProductAttributeTerms< ProductAttributeTerm[] >( {
					search: value || '',
					attribute_id: attributeId,
				} )
				.then( ( attributeTerms ) => {
					setFetchedItems( attributeTerms );
					setIsFetching( false );
				} );
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

	return (
		<SelectControl< ProductAttributeTerm >
			items={ fetchedItems }
			multiple
			disabled={ disabled || ! attributeId }
			label=""
			onInputChange={ debouncedSearch }
			placeholder={ placeholder || '' }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.slug || '' }
			selected={ value }
			onSelect={ ( item ) => {
				onChange( [ ...value, item ] );
			} }
			onRemove={ ( item ) =>
				onChange( value.filter( ( opt ) => opt.slug !== item.slug ) )
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
						{ isFetching ? (
							<Spinner />
						) : (
							items.map( ( item, menuIndex ) => {
								const isSelected = value.includes( item );

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
							} )
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
