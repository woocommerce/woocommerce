/**
 * External dependencies
 */
import CheckboxList from '@woocommerce/base-components/checkbox-list';
import { getSetting } from '@woocommerce/settings';
import { useCallback, useState } from '@wordpress/element';

export interface ProductStockControlProps {
	value: Array< string >;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

/**
 * A pre-configured SelectControl for product stock settings.
 */
const ProductStockControl = ( {
	value,
	setAttributes,
}: ProductStockControlProps ): JSX.Element => {
	// Should out of stock items be hidden?
	const [ hideOutOfStockItems ] = useState(
		getSetting( 'hideOutOfStockItems', false )
	);

	// Get the stock status options.
	const [ { outofstock, ...otherStockStatusOptions } ] = useState(
		getSetting( 'stockStatusOptions', {} )
	);

	// Determine whether or not to use the out of stock status.
	const [ STOCK_STATUS_OPTIONS ] = useState(
		hideOutOfStockItems
			? otherStockStatusOptions
			: { outofstock, ...otherStockStatusOptions }
	);

	// Set the initial state to the default or saved value.
	const [ checkedOptions, setChecked ] = useState( value );

	/**
	 * Valid options must be in an array of [ 'value' : 'mystatus', 'label' : 'My label' ] format.
	 * stockStatusOptions are returned as [ 'mystatus' : 'My label' ].
	 * Formatting is corrected here.
	 */
	const [ displayOptions ] = useState(
		Object.entries( STOCK_STATUS_OPTIONS )
			.map( ( [ slug, name ] ) => ( { value: slug, label: name } ) )
			.filter( ( status ) => !! status.label )
			.sort( ( a, b ) => a.value.localeCompare( b.value ) )
	);

	/**
	 * When a checkbox in the list changes, update state.
	 */
	const onChange = useCallback(
		( checkedValue: string ) => {
			const previouslyChecked = checkedOptions.includes( checkedValue );

			const newChecked = checkedOptions.filter(
				( filteredValue ) => filteredValue !== checkedValue
			);

			if ( ! previouslyChecked ) {
				newChecked.push( checkedValue );
				newChecked.sort();
			}

			setChecked( newChecked );
			setAttributes( {
				stockStatus: newChecked,
			} );
		},
		[ checkedOptions, setAttributes ]
	);

	return (
		<CheckboxList
			checked={ checkedOptions }
			options={ displayOptions }
			onChange={ onChange }
		/>
	);
};

export default ProductStockControl;
