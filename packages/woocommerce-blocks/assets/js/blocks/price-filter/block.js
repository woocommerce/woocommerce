/**
 * External dependencies
 */
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-hooks';
import { Fragment, useCallback, useState, useEffect } from '@wordpress/element';
import PriceSlider from '@woocommerce/base-components/price-slider';
import { useDebouncedCallback } from 'use-debounce';
import PropTypes from 'prop-types';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import usePriceConstraints from './use-price-constraints.js';

/**
 * Component displaying a price filter.
 *
 * @param {Object} props Component props.
 * @param {Object} props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor Whether in editor context or not.
 */
const PriceFilterBlock = ( { attributes, isEditor = false } ) => {
	const [ minPriceQuery, setMinPriceQuery ] = useQueryStateByKey(
		'min_price'
	);
	const [ maxPriceQuery, setMaxPriceQuery ] = useQueryStateByKey(
		'max_price'
	);
	const [ queryState ] = useQueryStateByContext();
	const { results, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState,
	} );

	const [ minPrice, setMinPrice ] = useState();
	const [ maxPrice, setMaxPrice ] = useState();

	const currency = getCurrencyFromPriceResponse( results.price_range );

	const { minConstraint, maxConstraint } = usePriceConstraints( {
		minPrice: results.price_range
			? results.price_range.min_price
			: undefined,
		maxPrice: results.price_range
			? results.price_range.max_price
			: undefined,
		minorUnit: currency.minorUnit,
	} );

	// Updates the query after a short delay.
	const [ debouncedUpdateQuery ] = useDebouncedCallback( () => {
		onSubmit();
	}, 500 );

	// Updates the query based on slider values.
	const onSubmit = useCallback( () => {
		setMinPriceQuery( minPrice === minConstraint ? undefined : minPrice );
		setMaxPriceQuery( maxPrice === maxConstraint ? undefined : maxPrice );
	}, [ minPrice, maxPrice, minConstraint, maxConstraint ] );

	// Callback when slider or input fields are changed.
	const onChange = useCallback(
		( prices ) => {
			if ( prices[ 0 ] !== minPrice ) {
				setMinPrice( prices[ 0 ] );
			}
			if ( prices[ 1 ] !== maxPrice ) {
				setMaxPrice( prices[ 1 ] );
			}
		},
		[ minConstraint, maxConstraint, minPrice, maxPrice ]
	);

	// Track price STATE changes - if state changes, update the query.
	useEffect( () => {
		if ( ! attributes.showFilterButton ) {
			debouncedUpdateQuery();
		}
	}, [ minPrice, maxPrice, attributes.showFilterButton ] );

	// Track PRICE QUERY changes so the slider reflects current filters.
	useEffect( () => {
		if ( minPriceQuery !== minPrice ) {
			setMinPrice(
				Number.isFinite( minPriceQuery ) ? minPriceQuery : minConstraint
			);
		}
		if ( maxPriceQuery !== maxPrice ) {
			setMaxPrice(
				Number.isFinite( maxPriceQuery ) ? maxPriceQuery : maxConstraint
			);
		}
	}, [ minPriceQuery, maxPriceQuery, minConstraint, maxConstraint ] );

	if (
		! isLoading &&
		( minConstraint === null ||
			maxConstraint === null ||
			minConstraint === maxConstraint )
	) {
		return null;
	}

	const TagName = `h${ attributes.headingLevel }`;

	return (
		<Fragment>
			{ ! isEditor && attributes.heading && (
				<TagName>{ attributes.heading }</TagName>
			) }
			<div className="wc-block-price-slider">
				<PriceSlider
					minConstraint={ minConstraint }
					maxConstraint={ maxConstraint }
					minPrice={ minPrice }
					maxPrice={ maxPrice }
					currency={ currency }
					showInputFields={ attributes.showInputFields }
					showFilterButton={ attributes.showFilterButton }
					onChange={ onChange }
					onSubmit={ onSubmit }
					isLoading={ isLoading }
				/>
			</div>
		</Fragment>
	);
};

PriceFilterBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * Whether it's in the editor or frontend display.
	 */
	isEditor: PropTypes.bool,
};

export default PriceFilterBlock;
