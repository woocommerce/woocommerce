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
import { getSettingWithCoercion } from '@woocommerce/settings';
import { addQueryArgs, removeQueryArgs } from '@wordpress/url';
import { isBoolean } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import usePriceConstraints from './use-price-constraints.js';
import { getUrlParameter } from '../../utils/filters';
import './style.scss';

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
			paramObject[ key ] = value.toString();
		} else {
			delete paramObject[ key ];
		}
	}

	// Clean the URL before we add our new query parameters to it.
	const cleanUrl = removeQueryArgs( url, ...Object.keys( params ) );

	return addQueryArgs( cleanUrl, paramObject );
}

/**
 * Formats price values taking into account precision
 *
 * @param {string} value
 * @param {number} minorUnit
 *
 * @return {number} Formatted price.
 */

function formatPrice( value, minorUnit ) {
	return Number( value ) * 10 ** minorUnit;
}

/**
 * Component displaying a price filter.
 *
 * @param {Object}  props            Component props.
 * @param {Object}  props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor   Whether in editor context or not.
 */
const PriceFilterBlock = ( { attributes, isEditor = false } ) => {
	const hasFilterableProducts = getSettingWithCoercion(
		'has_filterable_products',
		false,
		isBoolean
	);

	const filteringForPhpTemplate = getSettingWithCoercion(
		'is_rendering_php_template',
		false,
		isBoolean
	);

	/**
	 * Important: Only used on the PHP rendered Block pages to track
	 * the price filter defaults coming from the URL
	 */
	const [ hasSetPhpFilterDefaults, setHasSetPhpFilterDefaults ] = useState(
		false
	);

	const minPriceParam = getUrlParameter( 'min_price' );
	const maxPriceParam = getUrlParameter( 'max_price' );
	const [ queryState ] = useQueryStateByContext();
	const { results, isLoading } = useCollectionData( {
		queryPrices: true,
		queryState,
	} );

	const currency = getCurrencyFromPriceResponse( results.price_range );

	const [ minPriceQuery, setMinPriceQuery ] = useQueryStateByKey(
		'min_price',
		formatPrice( minPriceParam, currency.minorUnit ) || null
	);
	const [ maxPriceQuery, setMaxPriceQuery ] = useQueryStateByKey(
		'max_price',
		formatPrice( maxPriceParam, currency.minorUnit ) || null
	);

	const [ minPrice, setMinPrice ] = useState(
		formatPrice( minPriceParam, currency.minorUnit ) || null
	);
	const [ maxPrice, setMaxPrice ] = useState(
		formatPrice( maxPriceParam, currency.minorUnit ) || null
	);

	const { minConstraint, maxConstraint } = usePriceConstraints( {
		minPrice: results.price_range
			? results.price_range.min_price
			: undefined,
		maxPrice: results.price_range
			? results.price_range.max_price
			: undefined,
		minorUnit: currency.minorUnit,
	} );

	/**
	 * Important: For PHP rendered block templates only.
	 *
	 * When we render the PHP block template (e.g. Classic Block) we need
	 * to set the default min_price and max_price values from the URL
	 * for the filter to work alongside the Active Filters block.
	 */
	useEffect( () => {
		if ( ! hasSetPhpFilterDefaults && filteringForPhpTemplate ) {
			setMinPriceQuery(
				formatPrice( minPriceParam, currency.minorUnit )
			);
			setMaxPriceQuery(
				formatPrice( maxPriceParam, currency.minorUnit )
			);

			setHasSetPhpFilterDefaults( true );
		}
	}, [
		currency.minorUnit,
		filteringForPhpTemplate,
		hasSetPhpFilterDefaults,
		maxPriceParam,
		minPriceParam,
		setMaxPriceQuery,
		setMinPriceQuery,
	] );

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
					min_price: finalMinPrice / 10 ** currency.minorUnit,
					max_price: finalMaxPrice / 10 ** currency.minorUnit,
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
			currency.minorUnit,
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

			if (
				filteringForPhpTemplate &&
				hasSetPhpFilterDefaults &&
				! attributes.showFilterButton
			) {
				debouncedUpdateQuery( prices[ 0 ], prices[ 1 ] );
			}
		},
		[
			minPrice,
			maxPrice,
			setMinPrice,
			setMaxPrice,
			filteringForPhpTemplate,
			hasSetPhpFilterDefaults,
			debouncedUpdateQuery,
			attributes.showFilterButton,
		]
	);

	// Track price STATE changes - if state changes, update the query.
	useEffect( () => {
		if ( ! attributes.showFilterButton && ! filteringForPhpTemplate ) {
			debouncedUpdateQuery( minPrice, maxPrice );
		}
	}, [
		minPrice,
		maxPrice,
		attributes.showFilterButton,
		debouncedUpdateQuery,
		filteringForPhpTemplate,
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

	if ( ! hasFilterableProducts ) {
		return null;
	}

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
