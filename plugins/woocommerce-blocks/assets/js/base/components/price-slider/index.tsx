/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	useMemo,
	useRef,
} from '@wordpress/element';
import classnames from 'classnames';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { Currency, isObject } from '@woocommerce/types';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import './style.scss';
import { constrainRangeSliderValues } from './constrain-range-slider-values';
import FilterSubmitButton from '../filter-submit-button';
import { isValidMaxValue, isValidMinValue } from './utils';
import FilterResetButton from '../filter-reset-button';

export interface PriceSliderProps {
	/**
	 * Currency configuration object.
	 */
	currency: Currency;
	/**
	 * Whether values are loading or not.
	 */
	isLoading?: boolean;
	/**
	 * Maximum constraint.
	 */
	maxConstraint: number | null | undefined;
	/**
	 * Maximum price for slider.
	 */
	maxPrice: number | null;
	/**
	 * Minimum constraint.
	 */
	minConstraint: number | null | undefined;
	/**
	 * Minimum price for slider.
	 */
	minPrice: number | null;
	/**
	 * Function to call on the change event.
	 */
	onChange: ( value: [ number, number ] ) => void;
	/**
	 * Function to call when submit event fires.
	 */
	onSubmit?: () => void;
	/**
	 * Whether to show the filter button for the slider.
	 */
	showFilterButton?: boolean;
	/**
	 * Whether to show input fields for the values or not.
	 */
	showInputFields?: boolean;
	/**
	 * Whether to show input fields inline with the slider or not.
	 */
	inlineInput?: boolean;
	/**
	 * What step values the slider uses.
	 */
	step?: number;
}

const PriceSlider = ( {
	minPrice,
	maxPrice,
	minConstraint,
	maxConstraint,
	onChange,
	step,
	currency,
	showInputFields = true,
	showFilterButton = false,
	inlineInput = true,
	isLoading = false,
	onSubmit = () => void 0,
}: PriceSliderProps ): JSX.Element => {
	const minRange = useRef< HTMLInputElement >( null );
	const maxRange = useRef< HTMLInputElement >( null );

	// We want step to default to 1 major unit, e.g. $1.
	const stepValue = step ? step : 10 ** currency.minorUnit;

	const [ minPriceInput, setMinPriceInput ] = useState( minPrice );
	const [ maxPriceInput, setMaxPriceInput ] = useState( maxPrice );

	useEffect( () => {
		setMinPriceInput( minPrice );
	}, [ minPrice ] );

	useEffect( () => {
		setMaxPriceInput( maxPrice );
	}, [ maxPrice ] );

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
	] );

	/**
	 * Works around an IE issue where only one range selector is visible by changing the display order
	 * based on the mouse position.
	 *
	 * @param {Object} event event data.
	 */
	const findClosestRange = useCallback(
		( event: React.MouseEvent< HTMLDivElement > ) => {
			if (
				isLoading ||
				! hasValidConstraints ||
				! minRange.current ||
				! maxRange.current
			) {
				return;
			}
			const bounds = ( event.target as Element ).getBoundingClientRect();
			const x = event.clientX - bounds.left;
			const minWidth = minRange.current.offsetWidth;
			const minValue = +minRange.current.value;
			const maxWidth = maxRange.current.offsetWidth;
			const maxValue = +maxRange.current.value;

			const minX = minWidth * ( minValue / maxConstraint );
			const maxX = maxWidth * ( maxValue / maxConstraint );

			const minXDiff = Math.abs( x - minX );
			const maxXDiff = Math.abs( x - maxX );

			/**
			 * The default z-index in the stylesheet as 20. 20 vs 21 is just for determining which range
			 * slider should be at the front and has no meaning beyond
			 */
			if ( minXDiff > maxXDiff ) {
				minRange.current.style.zIndex = '20';
				maxRange.current.style.zIndex = '21';
			} else {
				minRange.current.style.zIndex = '21';
				maxRange.current.style.zIndex = '20';
			}
		},
		[ isLoading, maxConstraint, hasValidConstraints ]
	);

	/**
	 * Called when the slider is dragged.
	 */
	const rangeInputOnChange = useCallback(
		( event: React.ChangeEvent< HTMLInputElement > ) => {
			const isMin = event.target.classList.contains(
				'wc-block-price-filter__range-input--min'
			);
			const targetValue = +event.target.value;
			const currentValues: [ number, number ] = isMin
				? [
						Math.round( targetValue / stepValue ) * stepValue,
						maxPrice,
				  ]
				: [
						minPrice,
						Math.round( targetValue / stepValue ) * stepValue,
				  ];
			const values = constrainRangeSliderValues(
				currentValues,
				minConstraint,
				maxConstraint,
				stepValue,
				isMin
			);
			onChange( values );
		},
		[
			onChange,
			minPrice,
			maxPrice,
			minConstraint,
			maxConstraint,
			stepValue,
		]
	);

	/**
	 * Called when a price input loses focus - commit changes to slider.
	 */
	const priceInputOnBlur = useCallback(
		( event: React.FocusEvent< HTMLInputElement > ) => {
			// Only refresh when finished editing the min and max fields.
			if (
				event.relatedTarget &&
				( event.relatedTarget as Element ).classList &&
				( event.relatedTarget as Element ).classList.contains(
					'wc-block-price-filter__amount'
				)
			) {
				return;
			}

			const isMin = event.target.classList.contains(
				'wc-block-price-filter__amount--min'
			);

			// When the user inserts in the max price input a value less or equal than the current minimum price,
			// we set to 0 the minimum price.
			if ( minPriceInput >= maxPriceInput ) {
				const values = constrainRangeSliderValues(
					[ 0, maxPriceInput ],
					null,
					null,
					stepValue,
					isMin
				);
				return onChange( [
					parseInt( values[ 0 ], 10 ),
					parseInt( values[ 1 ], 10 ),
				] );
			}

			const values = constrainRangeSliderValues(
				[ minPriceInput, maxPriceInput ],
				null,
				null,
				stepValue,
				isMin
			);
			onChange( values );
		},
		[ onChange, stepValue, minPriceInput, maxPriceInput ]
	);

	const debouncedUpdateQuery = useDebouncedCallback( onSubmit, 600 );

	const classes = classnames(
		'wc-block-price-filter',
		'wc-block-components-price-slider',
		showInputFields && 'wc-block-price-filter--has-input-fields',
		showInputFields && 'wc-block-components-price-slider--has-input-fields',
		showFilterButton && 'wc-block-price-filter--has-filter-button',
		showFilterButton &&
			'wc-block-components-price-slider--has-filter-button',
		isLoading && 'is-loading',
		! hasValidConstraints && 'is-disabled'
	);

	const activeElement = isObject( minRange.current )
		? minRange.current.ownerDocument.activeElement
		: undefined;
	const minRangeStep =
		activeElement && activeElement === minRange.current ? stepValue : 1;
	const maxRangeStep =
		activeElement && activeElement === maxRange.current ? stepValue : 1;

	const ariaReadableMinPrice = String(
		minPriceInput / 10 ** currency.minorUnit
	);
	const ariaReadableMaxPrice = String(
		maxPriceInput / 10 ** currency.minorUnit
	);

	const slider = (
		<div
			className="wc-block-price-filter__range-input-wrapper wc-block-components-price-slider__range-input-wrapper"
			onMouseMove={ findClosestRange }
			onFocus={ findClosestRange }
		>
			{ hasValidConstraints && (
				<div aria-hidden={ showInputFields }>
					<div
						className="wc-block-price-filter__range-input-progress wc-block-components-price-slider__range-input-progress"
						style={ progressStyles as React.CSSProperties }
					/>
					<input
						type="range"
						className="wc-block-price-filter__range-input wc-block-price-filter__range-input--min wc-block-components-price-slider__range-input wc-block-components-price-slider__range-input--min"
						aria-label={ __(
							'Filter products by minimum price',
							'woo-gutenberg-products-block'
						) }
						aria-valuetext={ ariaReadableMinPrice }
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
						disabled={ isLoading && ! hasValidConstraints }
						tabIndex={ showInputFields ? -1 : 0 }
					/>
					<input
						type="range"
						className="wc-block-price-filter__range-input wc-block-price-filter__range-input--max wc-block-components-price-slider__range-input wc-block-components-price-slider__range-input--max"
						aria-label={ __(
							'Filter products by maximum price',
							'woo-gutenberg-products-block'
						) }
						aria-valuetext={ ariaReadableMaxPrice }
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
						tabIndex={ showInputFields ? -1 : 0 }
					/>
				</div>
			) }
		</div>
	);

	return (
		<div className={ classes }>
			{ ( ! inlineInput || ! showInputFields ) && slider }
			{ showInputFields && (
				<div className="wc-block-price-filter__controls wc-block-components-price-slider__controls">
					<FormattedMonetaryAmount
						currency={ currency }
						displayType="input"
						className="wc-block-price-filter__amount wc-block-price-filter__amount--min wc-block-form-text-input wc-block-components-price-slider__amount wc-block-components-price-slider__amount--min"
						aria-label={ __(
							'Filter products by minimum price',
							'woo-gutenberg-products-block'
						) }
						allowNegative={ false }
						isAllowed={ isValidMinValue( {
							minConstraint,
							minorUnit: currency.minorUnit,
							currentMaxValue: maxPriceInput,
						} ) }
						onValueChange={ ( value ) => {
							if ( value === minPriceInput ) {
								return;
							}
							setMinPriceInput( value );
						} }
						onBlur={ priceInputOnBlur }
						disabled={ isLoading || ! hasValidConstraints }
						value={ minPriceInput }
					/>
					{ inlineInput && slider }
					<FormattedMonetaryAmount
						currency={ currency }
						displayType="input"
						className="wc-block-price-filter__amount wc-block-price-filter__amount--max wc-block-form-text-input wc-block-components-price-slider__amount wc-block-components-price-slider__amount--max"
						aria-label={ __(
							'Filter products by maximum price',
							'woo-gutenberg-products-block'
						) }
						isAllowed={ isValidMaxValue( {
							maxConstraint,
							minorUnit: currency.minorUnit,
						} ) }
						onValueChange={ ( value ) => {
							if ( value === maxPriceInput ) {
								return;
							}
							setMaxPriceInput( value );
						} }
						onBlur={ priceInputOnBlur }
						disabled={ isLoading || ! hasValidConstraints }
						value={ maxPriceInput }
					/>
				</div>
			) }

			{ ! showInputFields &&
				! isLoading &&
				Number.isFinite( minPrice ) &&
				Number.isFinite( maxPrice ) && (
					<div className="wc-block-price-filter__range-text wc-block-components-price-slider__range-text">
						<FormattedMonetaryAmount
							currency={ currency }
							value={ minPrice }
						/>
						<FormattedMonetaryAmount
							currency={ currency }
							value={ maxPrice }
						/>
					</div>
				) }
			<div className="wc-block-components-price-slider__actions">
				{ ( minPrice !== minConstraint ||
					maxPrice !== maxConstraint ) && (
					<FilterResetButton
						onClick={ () => {
							onChange( [ minConstraint, maxConstraint ] );
							debouncedUpdateQuery();
						} }
						screenReaderLabel={ __(
							'Reset price filter',
							'woo-gutenberg-products-block'
						) }
					/>
				) }
				{ showFilterButton && (
					<FilterSubmitButton
						className="wc-block-price-filter__button wc-block-components-price-slider__button"
						disabled={ isLoading || ! hasValidConstraints }
						onClick={ onSubmit }
						screenReaderLabel={ __(
							'Apply price filter',
							'woo-gutenberg-products-block'
						) }
					/>
				) }
			</div>
		</div>
	);
};

export default PriceSlider;
