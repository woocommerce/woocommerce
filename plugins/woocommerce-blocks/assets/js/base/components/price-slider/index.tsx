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
	useLayoutEffect,
} from '@wordpress/element';
import clsx from 'clsx';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
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
	 * Whether values are updating or not. The update starts when the price slider is changed.
	 */
	isUpdating?: boolean;
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
	/**
	 * Whether we're in the editor or not.
	 */
	isEditor?: boolean;
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
	isUpdating = false,
	isEditor = false,
	onSubmit = () => void 0,
}: PriceSliderProps ): JSX.Element => {
	const minRange = useRef< HTMLInputElement >( null );
	const maxRange = useRef< HTMLInputElement >( null );

	// We want step to default to 1 major unit, e.g. $1.
	const stepValue = step ? step : 10 ** currency.minorUnit;

	const [ minPriceInput, setMinPriceInput ] = useState( minPrice );
	const [ maxPriceInput, setMaxPriceInput ] = useState( maxPrice );

	const wrapper = useRef< HTMLInputElement >( null );
	const [ wrapperWidth, setWrapperWidth ] = useState( 0 );

	useEffect( () => {
		setMinPriceInput( minPrice );
	}, [ minPrice ] );

	useEffect( () => {
		setMaxPriceInput( maxPrice );
	}, [ maxPrice ] );

	useLayoutEffect( () => {
		if ( inlineInput && wrapper.current ) {
			setWrapperWidth( wrapper.current?.offsetWidth );
		}
	}, [ inlineInput, setWrapperWidth ] );

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
			100 *
			( ( minPrice - minConstraint ) /
				( maxConstraint - minConstraint ) );

		const high =
			100 *
			( ( maxPrice - minConstraint ) /
				( maxConstraint - minConstraint ) );

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
	 * Selects the price field when clicked.
	 *
	 * @param {Object} event event data.
	 */
	const handleSelectOnClick = (
		event:
			| React.FocusEvent< HTMLInputElement >
			| React.MouseEvent< HTMLInputElement >
	) => {
		const target = event.currentTarget;
		if ( target ) {
			target.select();
		}
	};

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
	 * Handles price change logic and calls onChange.
	 */
	const handlePriceChange = useDebouncedCallback(
		(
			minInsertedPrice: number,
			maxInsertedPrice: number,
			isMin: boolean
		) => {
			// When the user inserts in the max price input a value less or equal than the current minimum price,
			// we set to 0 the minimum price.
			if ( minInsertedPrice >= maxInsertedPrice ) {
				const values = constrainRangeSliderValues(
					[ 0, maxInsertedPrice ],
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
				[ minInsertedPrice, maxInsertedPrice ],
				null,
				null,
				stepValue,
				isMin
			);
			onChange( values );
		},
		1000
	);

	const debouncedUpdateQuery = useDebouncedCallback( onSubmit, 600 );

	const classes = clsx(
		'wc-block-price-filter',
		'wc-block-components-price-slider',
		showInputFields && 'wc-block-price-filter--has-input-fields',
		showInputFields && 'wc-block-components-price-slider--has-input-fields',
		showFilterButton && 'wc-block-price-filter--has-filter-button',
		showFilterButton &&
			'wc-block-components-price-slider--has-filter-button',
		! hasValidConstraints && 'is-disabled',
		( inlineInput || wrapperWidth <= 300 ) &&
			'wc-block-components-price-slider--is-input-inline'
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

	const inlineInputAvailable = inlineInput && wrapperWidth > 300;

	const slider = (
		<div
			className={ clsx(
				'wc-block-price-filter__range-input-wrapper',
				'wc-block-components-price-slider__range-input-wrapper',
				{ 'is-loading': isLoading && isUpdating }
			) }
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
							'woocommerce'
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
							'woocommerce'
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

	const getInputClassName = ( type: 'min' | 'max' ) =>
		`wc-block-price-filter__amount wc-block-price-filter__amount--${ type } wc-block-form-text-input wc-block-components-price-slider__amount wc-block-components-price-slider__amount--${ type }`;

	const commonFormattedMonetaryAmountProps = {
		currency,
		decimalScale: 0,
	};

	const commonFormattedMonetaryAmountInputProps = {
		...commonFormattedMonetaryAmountProps,
		displayType: 'input',
		allowNegative: false,
		disabled: isLoading || ! hasValidConstraints,
		onClick: handleSelectOnClick,
	};

	return (
		<div className={ classes } ref={ wrapper }>
			{ ( ! inlineInputAvailable || ! showInputFields ) && slider }
			{ showInputFields && (
				<div className="wc-block-price-filter__controls wc-block-components-price-slider__controls">
					{ ! isUpdating ? (
						<FormattedMonetaryAmount
							{ ...commonFormattedMonetaryAmountInputProps }
							className={ getInputClassName( 'min' ) }
							aria-label={ __(
								'Filter products by minimum price',
								'woocommerce'
							) }
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
								handlePriceChange(
									value,
									maxPriceInput as number,
									true
								);
							} }
							value={ minPriceInput }
						/>
					) : (
						<div className="input-loading"></div>
					) }
					{ inlineInputAvailable && slider }
					{ ! isUpdating ? (
						<FormattedMonetaryAmount
							{ ...commonFormattedMonetaryAmountInputProps }
							className={ getInputClassName( 'max' ) }
							aria-label={ __(
								'Filter products by maximum price',
								'woocommerce'
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
								handlePriceChange(
									minPriceInput as number,
									value,
									false
								);
							} }
							value={ maxPriceInput }
						/>
					) : (
						<div className="input-loading"></div>
					) }
				</div>
			) }

			{ ! showInputFields &&
				! isUpdating &&
				Number.isFinite( minPrice ) &&
				Number.isFinite( maxPrice ) && (
					<div className="wc-block-price-filter__range-text wc-block-components-price-slider__range-text">
						<FormattedMonetaryAmount
							{ ...commonFormattedMonetaryAmountProps }
							value={ minPrice }
						/>
						<FormattedMonetaryAmount
							{ ...commonFormattedMonetaryAmountProps }
							value={ maxPrice }
						/>
					</div>
				) }
			{
				<div className="wc-block-components-price-slider__actions">
					{ ( isEditor ||
						( ! isUpdating &&
							( minPrice !== minConstraint ||
								maxPrice !== maxConstraint ) ) ) && (
						<FilterResetButton
							onClick={ () => {
								onChange( [ minConstraint, maxConstraint ] );
								debouncedUpdateQuery();
							} }
							screenReaderLabel={ __(
								'Reset price filter',
								'woocommerce'
							) }
						/>
					) }
					{ showFilterButton && (
						<FilterSubmitButton
							className="wc-block-price-filter__button wc-block-components-price-slider__button"
							isLoading={ isUpdating }
							disabled={ isLoading || ! hasValidConstraints }
							onClick={ onSubmit }
							screenReaderLabel={ __(
								'Apply price filter',
								'woocommerce'
							) }
						/>
					) }
				</div>
			}
		</div>
	);
};

export default PriceSlider;
