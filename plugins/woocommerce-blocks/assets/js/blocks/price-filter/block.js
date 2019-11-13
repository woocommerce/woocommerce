/**
 * External dependencies
 */
import {
	useCollection,
	useQueryStateByKey,
	useQueryStateByContext,
} from '@woocommerce/base-hooks';
import { useCallback, Fragment } from '@wordpress/element';
import PriceSlider from '@woocommerce/base-components/price-slider';
import { CURRENCY } from '@woocommerce/settings';

/**
 * Component displaying a price filter.
 */
const PriceFilterBlock = ( { attributes, isPreview = false } ) => {
	const [ minPrice, setMinPrice ] = useQueryStateByKey(
		'product-grid',
		'min_price'
	);
	const [ maxPrice, setMaxPrice ] = useQueryStateByKey(
		'product-grid',
		'max_price'
	);
	const [ queryState ] = useQueryStateByContext( 'product-grid' );
	const { results, isLoading } = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/collection-data',
		query: {
			...queryState,
			min_price: undefined,
			max_price: undefined,
			orderby: undefined,
			order: undefined,
			per_page: undefined,
			page: undefined,
			calculate_price_range: true,
		},
	} );

	const { showInputFields, showFilterButton } = attributes;
	const minConstraint = isLoading
		? undefined
		: // Round up to nearest 10 to match the step attribute.
		  Math.floor( parseInt( results.min_price, 10 ) / 10 ) * 10;
	const maxConstraint = isLoading
		? undefined
		: // Round down to nearest 10 to match the step attribute.
		  Math.ceil( parseInt( results.max_price, 10 ) / 10 ) * 10;

	const onChange = useCallback(
		( prices ) => {
			if ( prices[ 0 ] === minConstraint ) {
				setMinPrice( undefined );
			} else if ( prices[ 0 ] !== minPrice ) {
				setMinPrice( prices[ 0 ] );
			}

			if ( prices[ 1 ] === maxConstraint ) {
				setMaxPrice( undefined );
			} else if ( prices[ 1 ] !== maxPrice ) {
				setMaxPrice( prices[ 1 ] );
			}
		},
		[ minConstraint, maxConstraint, minPrice, maxPrice ]
	);

	const TagName = `h${ attributes.headingLevel }`;

	return (
		<Fragment>
			{ ! isPreview && attributes.heading && (
				<TagName>{ attributes.heading }</TagName>
			) }
			<div className="wc-block-price-slider">
				<PriceSlider
					minConstraint={ minConstraint }
					maxConstraint={ maxConstraint }
					initialMin={ undefined }
					initialMax={ undefined }
					step={ 10 }
					currencySymbol={ CURRENCY.symbol }
					priceFormat={ CURRENCY.price_format }
					showInputFields={ showInputFields }
					showFilterButton={ showFilterButton }
					onChange={ onChange }
					isLoading={ isLoading }
				/>
			</div>
		</Fragment>
	);
};

export default PriceFilterBlock;
