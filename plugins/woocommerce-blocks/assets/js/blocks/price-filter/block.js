/**
 * External dependencies
 */
import { usePrevious } from '@woocommerce/base-hooks';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { useCallback, useState, useEffect } from '@wordpress/element';
import PriceSlider from '@woocommerce/base-components/price-slider';
import { useDebouncedCallback } from 'use-debounce';
import PropTypes from 'prop-types';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { getSetting } from '@woocommerce/settings';
import { getQueryArg, addQueryArgs, removeQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import usePriceConstraints from './use-price-constraints.js';
import './style.scss';

/**
 * Returns specified parameter from URL
 *
 * @param {string} paramName Parameter you want the value of.
 */
function findGetParameter( paramName ) {
	if ( ! window ) {
		return null;
	}
	return getQueryArg( window.location.href, paramName );
}

/**
 * Formats filter values into a string for the URL parameters needed for filtering PHP templates.
 *
 * @param {string} url    Current page URL.
 * @param {Object} params Parameters and their constraints.
 *
 * @return {string} New URL with query parameters in it.
 */
function formatParams( url, params ) {
	const paramObject = {};

	for ( const [ key, value ] of Object.entries( params ) ) {
		if ( value ) {
			paramObject[ key ] = ( value / 100 ).toString();
		} else {
			delete paramObject[ key ];
		}
	}

	// Clean the URL before we add our new query parameters to it.
	const cleanUrl = removeQueryArgs( url, ...Object.keys( params ) );

	return addQueryArgs( cleanUrl, paramObject );
}

/**
 * Component displaying a price filter.
 *
 * @param {Object}  props            Component props.
 * @param {Object}  props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor   Whether in editor context or not.
 */
const PriceFilterBlock = ( { attributes, isEditor = false } ) => {
	const filteringForPhpTemplate = getSetting(
		'is_rendering_php_template',
		''
	);

	const minPriceParam = findGetParameter( 'min_price' );
	const maxPriceParam = findGetParameter( 'max_price' );

	const [ minPriceQuery, setMinPriceQuery ] = useQueryStateByKey(
		'min_price',
		Number( minPriceParam ) * 100 || null
	);
	const [ maxPriceQuery, setMaxPriceQuery ] = useQueryStateByKey(
		'max_price',
		Number( maxPriceParam ) * 100 || null
	);
	const [ queryState ] = useQueryStateByContext();
	const { results, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState,
	} );

	const [ minPrice, setMinPrice ] = useState(
		Number( minPriceParam ) * 100 || null
	);
	const [ maxPrice, setMaxPrice ] = useState(
		Number( maxPriceParam ) * 100 || null
	);

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

	// Updates the query based on slider values.
	const onSubmit = useCallback(
		( newMinPrice, newMaxPrice ) => {
			const finalMaxPrice =
				newMaxPrice >= Number( maxConstraint )
					? undefined
					: newMaxPrice;
			const finalMinPrice =
				newMinPrice <= Number( minConstraint )
					? undefined
					: newMinPrice;

			// For block templates that render the PHP Classic Template block we need to add the filters as params and reload the page.
			if ( filteringForPhpTemplate && window ) {
				const newUrl = formatParams( window.location.href, {
					min_price: finalMinPrice,
					max_price: finalMaxPrice,
				} );

				// If the params have changed, lets reload the page.
				if ( window.location.href !== newUrl ) {
					window.location.href = newUrl;
				}
			} else {
				setMinPriceQuery( finalMinPrice );
				setMaxPriceQuery( finalMaxPrice );
			}
		},
		[
			minConstraint,
			maxConstraint,
			setMinPriceQuery,
			setMaxPriceQuery,
			filteringForPhpTemplate,
		]
	);

	// Updates the query after a short delay.
	const debouncedUpdateQuery = useDebouncedCallback( onSubmit, 500 );

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
		[ minPrice, maxPrice, setMinPrice, setMaxPrice ]
	);

	// Track price STATE changes - if state changes, update the query.
	useEffect( () => {
		if ( ! attributes.showFilterButton ) {
			debouncedUpdateQuery( minPrice, maxPrice );
		}
	}, [
		minPrice,
		maxPrice,
		attributes.showFilterButton,
		debouncedUpdateQuery,
	] );

	// Track price query/price constraint changes so the slider reflects current filters.
	const previousMinPriceQuery = usePrevious( minPriceQuery );
	const previousMaxPriceQuery = usePrevious( maxPriceQuery );
	const previousMinConstraint = usePrevious( minConstraint );
	const previousMaxConstraint = usePrevious( maxConstraint );
	useEffect( () => {
		if (
			! Number.isFinite( minPrice ) ||
			( minPriceQuery !== previousMinPriceQuery && // minPrice from query changed
				minPriceQuery !== minPrice ) || // minPrice from query doesn't match the UI min price
			( minConstraint !== previousMinConstraint && // minPrice from query changed
				minConstraint !== minPrice ) // minPrice from query doesn't match the UI min price
		) {
			setMinPrice(
				Number.isFinite( minPriceQuery ) ? minPriceQuery : minConstraint
			);
		}
		if (
			! Number.isFinite( maxPrice ) ||
			( maxPriceQuery !== previousMaxPriceQuery && // maxPrice from query changed
				maxPriceQuery !== maxPrice ) || // maxPrice from query doesn't match the UI max price
			( maxConstraint !== previousMaxConstraint && // maxPrice from query changed
				maxConstraint !== maxPrice ) // maxPrice from query doesn't match the UI max price
		) {
			setMaxPrice(
				Number.isFinite( maxPriceQuery ) ? maxPriceQuery : maxConstraint
			);
		}
	}, [
		minPrice,
		maxPrice,
		minPriceQuery,
		maxPriceQuery,
		minConstraint,
		maxConstraint,
		previousMinConstraint,
		previousMaxConstraint,
		previousMinPriceQuery,
		previousMaxPriceQuery,
	] );

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
		<>
			{ ! isEditor && attributes.heading && (
				<TagName className="wc-block-price-filter__title">
					{ attributes.heading }
				</TagName>
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
					onSubmit={ () => onSubmit( minPrice, maxPrice ) }
					isLoading={ isLoading }
				/>
			</div>
		</>
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
