/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Fragment,
	useState,
	useEffect,
	useCallback,
	useMemo,
	useRef,
} from '@wordpress/element';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useDebounce, usePrevious } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import './style.scss';
import { constrainRangeSliderValues, formatCurrencyForInput } from './utils';
import SubmitButton from './submit-button';
import PriceLabel from './price-label';
import PriceInput from './price-input';

const PriceSlider = ( {
	initialMin,
	initialMax,
	minConstraint,
	maxConstraint,
	onChange,
	step,
	currencySymbol,
	priceFormat,
	showInputFields,
	showFilterButton,
	isLoading,
} ) => {
	const minRange = useRef();
	const maxRange = useRef();
	const [ minPrice, setMinPrice ] = useState( initialMin );
	const [ maxPrice, setMaxPrice ] = useState( initialMax );
	const [ formattedMinPrice, setFormattedMinPrice ] = useState(
		formatCurrencyForInput( minPrice, priceFormat, currencySymbol )
	);
	const [ formattedMaxPrice, setFormattedMaxPrice ] = useState(
		formatCurrencyForInput( maxPrice, priceFormat, currencySymbol )
	);
	const debouncedChangeValue = useDebounce( [ minPrice, maxPrice ], 500 );
	const prevMinConstraint = usePrevious( minConstraint );
	const prevMaxConstraint = usePrevious( maxConstraint );

	useEffect( () => {
		if ( isNaN( minConstraint ) ) {
			setMinPrice( 0 );
			return;
		}
		if (
			minPrice === undefined ||
			minConstraint > minPrice ||
			minPrice === prevMinConstraint
		) {
			setMinPrice( minConstraint );
		}
	}, [ minConstraint ] );

	useEffect( () => {
		if ( isNaN( maxConstraint ) ) {
			setMaxPrice( 100 );
			return;
		}
		if (
			maxPrice === undefined ||
			maxConstraint < maxPrice ||
			maxPrice === prevMaxConstraint
		) {
			setMaxPrice( maxConstraint );
		}
	}, [ maxConstraint ] );

	useEffect( () => {
		setFormattedMinPrice(
			formatCurrencyForInput( minPrice, priceFormat, currencySymbol )
		);
	}, [ minPrice, priceFormat, currencySymbol ] );

	useEffect( () => {
		setFormattedMaxPrice(
			formatCurrencyForInput( maxPrice, priceFormat, currencySymbol )
		);
	}, [ maxPrice, priceFormat, currencySymbol ] );

	const hasValidConstraints = useMemo( () => {
		return isFinite( minConstraint ) && isFinite( maxConstraint );
	}, [ minConstraint, maxConstraint ] );

	useEffect( () => {
		if ( ! showFilterButton && ! isLoading && hasValidConstraints ) {
			triggerChange();
		}
	}, [ debouncedChangeValue ] );

	/**
	 * Handles styles for the shaded area of the range slider.
	 */
	const progressStyles = useMemo( () => {
		if (
			! isFinite( minPrice ) ||
			! isFinite( maxPrice ) ||
			! hasValidConstraints
		) {
			return {
				'--low': '0%',
				'--high': '100%',
			};
		}

		const low =
			Math.round(
				100 *
					( ( minPrice - minConstraint ) /
						( maxConstraint - minConstraint ) )
			) - 0.5;
		const high =
			Math.round(
				100 *
					( ( maxPrice - minConstraint ) /
						( maxConstraint - minConstraint ) )
			) + 0.5;

		return {
			'--low': low + '%',
			'--high': high + '%',
		};
	}, [
		minPrice,
		maxPrice,
		minConstraint,
		maxConstraint,
		hasValidConstraints,
	] );

	/**
	 * Trigger the onChange prop callback with new values.
	 */
	const triggerChange = useCallback( () => {
		onChange( [ minPrice, maxPrice ] );
	}, [ minPrice, maxPrice ] );

	/**
	 * Works around an IE issue where only one range selector is visible by changing the display order
	 * based on the mouse position.
	 *
	 * @param {obj} event event data.
	 */
	const findClosestRange = useCallback(
		( event ) => {
			if ( isLoading || ! hasValidConstraints ) {
				return;
			}
			const bounds = event.target.getBoundingClientRect();
			const x = event.clientX - bounds.left;
			const minWidth = minRange.current.offsetWidth;
			const minValue = minRange.current.value;
			const maxWidth = maxRange.current.offsetWidth;
			const maxValue = maxRange.current.value;

			const minX = minWidth * ( minValue / maxConstraint );
			const maxX = maxWidth * ( maxValue / maxConstraint );

			const minXDiff = Math.abs( x - minX );
			const maxXDiff = Math.abs( x - maxX );

			/**
			 * The default z-index in the stylesheet as 20. 20 vs 21 is just for determining which range
			 * slider should be at the front and has no meaning beyond
			 */
			if ( minXDiff > maxXDiff ) {
				minRange.current.style.zIndex = 20;
				maxRange.current.style.zIndex = 21;
			} else {
				minRange.current.style.zIndex = 21;
				maxRange.current.style.zIndex = 20;
			}
		},
		[ isLoading, maxConstraint, hasValidConstraints ]
	);

	/**
	 * Called when the slider is dragged.
	 * @param {obj} event Event object.
	 */
	const rangeInputOnChange = useCallback(
		( event ) => {
			const isMin = event.target.classList.contains(
				'wc-block-price-filter__range-input--min'
			);
			const targetValue = event.target.value;
			const currentValues = isMin
				? [ targetValue, maxPrice ]
				: [ minPrice, targetValue ];
			const values = constrainRangeSliderValues(
				currentValues,
				minConstraint,
				maxConstraint,
				step,
				isMin
			);
			setMinPrice( parseInt( values[ 0 ], 10 ) );
			setMaxPrice( parseInt( values[ 1 ], 10 ) );
		},
		[ minPrice, maxPrice, minConstraint, maxConstraint, step ]
	);

	/**
	 * Called when a price input loses focus - commit changes to slider.
	 * @param {obj} event Event object.
	 */
	const priceInputOnBlur = useCallback(
		( event ) => {
			const isMin = event.target.classList.contains(
				'wc-block-price-filter__amount--min'
			);
			const targetValue = event.target.value.replace( /[^0-9.-]+/g, '' );
			const currentValues = isMin
				? [ targetValue, maxPrice ]
				: [ minPrice, targetValue ];
			const values = constrainRangeSliderValues(
				currentValues,
				minConstraint,
				maxConstraint,
				step,
				isMin
			);
			setMinPrice( parseInt( values[ 0 ], 10 ) );
			setMaxPrice( parseInt( values[ 1 ], 10 ) );
		},
		[ minPrice, maxPrice, minConstraint, maxConstraint, step ]
	);

	/**
	 * Called when a price input is typed in - store value but don't update slider.
	 * @param {obj} event Event object.
	 */
	const priceInputOnChange = useCallback(
		( event ) => {
			const newValue = event.target.value.replace( /[^0-9.-]+/g, '' );
			const isMin = event.target.classList.contains(
				'wc-block-price-filter__amount--min'
			);
			if ( isMin ) {
				setFormattedMinPrice(
					formatCurrencyForInput(
						newValue,
						priceFormat,
						currencySymbol
					)
				);
			} else {
				setFormattedMaxPrice(
					formatCurrencyForInput(
						newValue,
						priceFormat,
						currencySymbol
					)
				);
			}
		},
		[ priceFormat, currencySymbol ]
	);

	const classes = classnames(
		'wc-block-price-filter',
		showInputFields && 'wc-block-price-filter--has-input-fields',
		showFilterButton && 'wc-block-price-filter--has-filter-button',
		isLoading && 'is-loading',
		! hasValidConstraints && 'is-disabled'
	);

	return (
		<div className={ classes }>
			<div
				className="wc-block-price-filter__range-input-wrapper"
				onMouseMove={ findClosestRange }
				onFocus={ findClosestRange }
			>
				{ ! isLoading && hasValidConstraints && (
					<Fragment>
						<div
							className="wc-block-price-filter__range-input-progress"
							style={ progressStyles }
						/>
						<input
							type="range"
							className="wc-block-price-filter__range-input wc-block-price-filter__range-input--min"
							aria-label={ __(
								'Filter products by minimum price',
								'woo-gutenberg-products-block'
							) }
							value={ minPrice || 0 }
							onChange={ rangeInputOnChange }
							step={ step }
							min={ minConstraint }
							max={ maxConstraint }
							ref={ minRange }
						/>
						<input
							type="range"
							className="wc-block-price-filter__range-input wc-block-price-filter__range-input--max"
							aria-label={ __(
								'Filter products by maximum price',
								'woo-gutenberg-products-block'
							) }
							value={ maxPrice || 0 }
							onChange={ rangeInputOnChange }
							step={ step }
							min={ minConstraint }
							max={ maxConstraint }
							ref={ maxRange }
						/>
					</Fragment>
				) }
			</div>
			<div className="wc-block-price-filter__controls">
				{ showInputFields ? (
					<PriceInput
						disabled={ isLoading || ! hasValidConstraints }
						onChange={ priceInputOnChange }
						onBlur={ priceInputOnBlur }
						minPrice={ formattedMinPrice }
						maxPrice={ formattedMaxPrice }
					/>
				) : (
					<PriceLabel
						minPrice={ formattedMinPrice }
						maxPrice={ formattedMaxPrice }
					/>
				) }
				{ showFilterButton && (
					<SubmitButton
						disabled={ isLoading || ! hasValidConstraints }
						onClick={ triggerChange }
					/>
				) }
			</div>
		</div>
	);
};

PriceSlider.propTypes = {
	/**
	 * Callback fired when prices changes.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * Initial min value.
	 */
	initialMin: PropTypes.number,
	/**
	 * Initial max value.
	 */
	initialMax: PropTypes.number,
	/**
	 * Minimum allowed price.
	 */
	minConstraint: PropTypes.number,
	/**
	 * Maximum allowed price.
	 */
	maxConstraint: PropTypes.number,
	/**
	 * Step for slider inputs.
	 */
	step: PropTypes.number,
	/**
	 * Currency symbol to use when formatting prices for display.
	 */
	currencySymbol: PropTypes.string,
	/**
	 * Price format to use when formatting prices for display.
	 */
	priceFormat: PropTypes.string,
	/**
	 * Whether or not to show input fields above the slider.
	 */
	showInputFields: PropTypes.bool,
	/**
	 * Whether or not to show filter button above the slider.
	 */
	showFilterButton: PropTypes.bool,
	/**
	 * Whether or not to show filter button above the slider.
	 */
	isLoading: PropTypes.bool,
};

PriceSlider.defaultProps = {
	step: 1,
	currencySymbol: '$',
	priceFormat: '%1$s%2$s',
	showInputFields: true,
	showFilterButton: false,
	isLoading: false,
};

export default PriceSlider;
