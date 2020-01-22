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

/**
 * Internal dependencies
 */
import './style.scss';
import { constrainRangeSliderValues } from './constrain-range-slider-values';
import { formatPrice } from '../../utils/price';
import SubmitButton from './submit-button';
import PriceLabel from './price-label';
import PriceInput from './price-input';

const PriceSlider = ( {
	minPrice,
	maxPrice,
	minConstraint,
	maxConstraint,
	onChange = () => {},
	step = 10,
	currencySymbol = '$',
	priceFormat = '%1$s%2$s',
	showInputFields = true,
	showFilterButton = false,
	isLoading = false,
	onSubmit = () => {},
} ) => {
	const minRange = useRef();
	const maxRange = useRef();

	const [ formattedMinPrice, setFormattedMinPrice ] = useState(
		formatPrice( minPrice, priceFormat, currencySymbol )
	);
	const [ formattedMaxPrice, setFormattedMaxPrice ] = useState(
		formatPrice( maxPrice, priceFormat, currencySymbol )
	);

	useEffect( () => {
		setFormattedMinPrice(
			formatPrice( minPrice, priceFormat, currencySymbol )
		);
	}, [ minPrice, priceFormat, currencySymbol ] );

	useEffect( () => {
		setFormattedMaxPrice(
			formatPrice( maxPrice, priceFormat, currencySymbol )
		);
	}, [ maxPrice, priceFormat, currencySymbol ] );

	/**
	 * Checks if the min and max constraints are valid.
	 */
	const hasValidConstraints = useMemo( () => {
		return isFinite( minConstraint ) && isFinite( maxConstraint );
	}, [ minConstraint, maxConstraint ] );

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
		step,
	] );

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
				? [ Math.round( targetValue / step ) * step, maxPrice ]
				: [ minPrice, Math.round( targetValue / step ) * step ];
			const values = constrainRangeSliderValues(
				currentValues,
				minConstraint,
				maxConstraint,
				step,
				isMin
			);
			onChange( [
				parseInt( values[ 0 ], 10 ),
				parseInt( values[ 1 ], 10 ),
			] );
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
				null,
				null,
				step,
				isMin
			);
			onChange( [
				parseInt( values[ 0 ], 10 ),
				parseInt( values[ 1 ], 10 ),
			] );
			setFormattedMinPrice(
				formatPrice(
					parseInt( values[ 0 ], 10 ),
					priceFormat,
					currencySymbol
				)
			);
			setFormattedMaxPrice(
				formatPrice(
					parseInt( values[ 1 ], 10 ),
					priceFormat,
					currencySymbol
				)
			);
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
					formatPrice( newValue, priceFormat, currencySymbol )
				);
			} else {
				setFormattedMaxPrice(
					formatPrice( newValue, priceFormat, currencySymbol )
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

	const minRangeStep =
		minRange && document.activeElement === minRange.current ? step : 1;
	const maxRangeStep =
		maxRange && document.activeElement === maxRange.current ? step : 1;

	return (
		<div className={ classes }>
			<div
				className="wc-block-price-filter__range-input-wrapper"
				onMouseMove={ findClosestRange }
				onFocus={ findClosestRange }
			>
				{ hasValidConstraints && (
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
								'woocommerce'
							) }
							value={
								Number.isFinite( minPrice )
									? minPrice
									: minConstraint
							}
							onChange={ rangeInputOnChange }
							step={ minRangeStep }
							min={ minConstraint }
							max={ maxConstraint }
							ref={ minRange }
							disabled={ isLoading }
						/>
						<input
							type="range"
							className="wc-block-price-filter__range-input wc-block-price-filter__range-input--max"
							aria-label={ __(
								'Filter products by maximum price',
								'woocommerce'
							) }
							value={
								Number.isFinite( maxPrice )
									? maxPrice
									: maxConstraint
							}
							onChange={ rangeInputOnChange }
							step={ maxRangeStep }
							min={ minConstraint }
							max={ maxConstraint }
							ref={ maxRange }
							disabled={ isLoading }
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
						onClick={ onSubmit }
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
	 * Callback fired when the filter button is pressed.
	 */
	onSubmit: PropTypes.func,
	/**
	 * Min value.
	 */
	minPrice: PropTypes.number,
	/**
	 * Max value.
	 */
	maxPrice: PropTypes.number,
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

export default PriceSlider;
