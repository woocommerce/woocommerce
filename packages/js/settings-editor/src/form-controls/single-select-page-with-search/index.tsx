/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useCallback,
	useState,
} from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';
import { Spinner, BaseControl, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';
import type { PageItem } from './types';
import { sanitizeHTML } from '../../utils';
import { Suffix } from './suffix';
import { useItems, useSelectedItem } from './hooks';

type SingleSelectPageWithSearchEditProps =
	DataFormControlProps< DataFormItem > & {
		/**
		 * The help text to display below the control.
		 */
		help?: React.ReactNode;
		/**
		 * The class name to apply to the control.
		 */
		className?: string;
		/**
		 * The pages to exclude from the search results.
		 */
		exclude?: string[];
	};

export const SingleSelectPageWithSearch = ( {
	data,
	field,
	onChange,
	hideLabelFromVision,
	help,
	className,
	exclude,
}: SingleSelectPageWithSearchEditProps ) => {
	const value = field.getValue( { item: data } ) ?? '';
	const { id } = field;

	const { selectedItem, isLoading } = useSelectedItem( value );
	const [ isFocused, setIsFocused ] = useState( false );

	const { items: allItems, isFetching } = useItems( exclude );

	const handleSelect = useCallback(
		( item: PageItem | null ) => {
			onChange( { [ id ]: item?.value || '' } );
			setIsFocused( false );
		},
		[ onChange, id ]
	);

	const label =
		field.label === id ? undefined : (
			<div
				dangerouslySetInnerHTML={ {
					__html: sanitizeHTML( field.label ),
				} }
			/>
		);

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id={ id }
			help={ help }
			label={ label }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<input type="hidden" id={ id } value={ value } />
			<SelectControl< PageItem >
				__experimentalOpenMenuOnFocus
				className={ className }
				placeholder={
					isLoading
						? __( 'Loading…', 'woocommerce' )
						: __( 'Search for a page…', 'woocommerce' )
				}
				label=""
				// The select control input does not require an id since its value represents the label (which displays the page title to the user). A hidden input with the actual id is provided above, ensuring that the value is saved correctly.
				inputProps={ {
					id: undefined,
					'aria-readonly': true,
					'aria-label': __(
						'Use up and down arrow keys to navigate',
						'woocommerce'
					),
				} }
				items={ allItems }
				selected={ isFocused ? null : selectedItem }
				onSelect={ handleSelect }
				onFocus={ () => setIsFocused( true ) }
				onBlur={ () => setIsFocused( false ) }
				onRemove={ () => handleSelect( null ) }
				suffix={
					<Suffix
						value={ value }
						isLoading={ isLoading || isFetching }
						isFocused={ isFocused }
						onRemove={ () => handleSelect( null ) }
					/>
				}
			>
				{ ( {
					items,
					isOpen,
					highlightedIndex,
					getItemProps,
					getMenuProps,
				} ) => (
					<Fragment>
						<Icon icon={ search } size={ 20 } />
						<Menu isOpen={ isOpen } getMenuProps={ getMenuProps }>
							{ isFetching ? (
								<div className="woocommerce-single-select-page-with-search__menu-loading">
									<Spinner />
								</div>
							) : (
								items.map( ( item, index: number ) => (
									<MenuItem
										key={ `${ item.value }${ index }` }
										index={ index }
										isActive={ highlightedIndex === index }
										item={ item }
										getItemProps={ getItemProps }
									>
										{ item.label }
									</MenuItem>
								) )
							) }
						</Menu>
					</Fragment>
				) }
			</SelectControl>
		</BaseControl>
	);
};
