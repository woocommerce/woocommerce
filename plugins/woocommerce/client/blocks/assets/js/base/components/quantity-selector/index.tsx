/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import clsx from 'clsx';
import {
	useCallback,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { DOWN, ENTER, UP } from '@wordpress/keycodes';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import './style.scss';
import type { QuantitySelectorProps } from './types';

const QuantitySelector = ( {
	className,
	quantity = 1,
	minimum = 1,
	maximum,
	onChange = () => void 0,
	step = 1,
	itemName = '',
	disabled,
	editable = true,
}: QuantitySelectorProps ): JSX.Element => {
	const classes = clsx( 'wc-block-components-quantity-selector', className );
	const inputRef = useRef< HTMLInputElement | null >( null );
	const decreaseButtonRef = useRef< HTMLButtonElement | null >( null );
	const increaseButtonRef = useRef< HTMLButtonElement | null >( null );
	const hasMaximum = typeof maximum !== 'undefined';
	const canDecrease = ! disabled && quantity - step >= minimum;
	const canIncrease =
		! disabled && ( ! hasMaximum || quantity + step <= maximum );
	const [ internalState, setInternalState ] = useState< number >( quantity );
	const expectedQuantityRef = useRef< number >( quantity );
	const expectedQuantityTypeRef = useRef< 'input' | 'increase' | 'decrease' >(
		'input'
	);

	/**
	 * The goal of this function is to normalize what was inserted,
	 * but after the customer has stopped typing.
	 */
	const normalizeQuantity = useCallback(
		( initialValue: number ) => {
			// We copy the starting value.
			let value = initialValue;

			// We check if we have a maximum value, and select the lowest between what was inserted and the maximum.
			if ( hasMaximum ) {
				value = Math.min(
					value,
					// the maximum possible value in step increments.
					Math.floor( maximum / step ) * step
				);
			}

			// Select the biggest between what's inserted, the minimum value in steps.
			value = Math.max( value, Math.ceil( minimum / step ) * step );

			// We round off the value to our steps.
			value = Math.round( value / step ) * step;

			// Round to avoid floating-point precision errors (e.g., 1.4000000000000001 -> 1.4).
			const stepDecimalPlaces =
				step.toString().split( '.' )[ 1 ]?.length || 0;
			value =
				Math.round( value * Math.pow( 10, stepDecimalPlaces ) ) /
				Math.pow( 10, stepDecimalPlaces );

			return value;
		},
		[ hasMaximum, maximum, minimum, step ]
	);

	// Debounced callback for onChange to prevent excessive server calls
	const debouncedOnChange = useDebouncedCallback(
		( newQuantity: number ) => {
			onChange( newQuantity );
		},
		600 // 600ms delay
	);

	const commitValue = useCallback(
		( value: number ) => {
			if ( isNaN( value ) ) {
				setInternalState( quantity );
				return;
			}
			// Cancel any pending debounced changes to prevent race conditions
			debouncedOnChange.cancel();

			const normalized = normalizeQuantity( value );
			setInternalState( normalized );

			// Update expected quantity to prevent useEffect from overriding local state
			expectedQuantityRef.current = normalized;

			if ( normalized !== quantity ) {
				onChange( normalized );
			}
		},
		[ normalizeQuantity, quantity, debouncedOnChange, onChange ]
	);

	/**
	 * Normalize qty on mount before render, and keep in sync with the quantity prop.
	 */
	useLayoutEffect( () => {
		if ( quantity === expectedQuantityRef.current ) {
			// Reset expected quantity type to 'input' if the prop matches the current state.
			expectedQuantityTypeRef.current = 'input';
			return;
		}
		if (
			expectedQuantityTypeRef.current === 'increase' &&
			quantity < expectedQuantityRef.current
		) {
			return;
		}
		if (
			expectedQuantityTypeRef.current === 'decrease' &&
			quantity > expectedQuantityRef.current
		) {
			return;
		}
		setInternalState( quantity );
		expectedQuantityRef.current = quantity;
		expectedQuantityTypeRef.current = 'input';
	}, [ quantity, normalizeQuantity ] );

	/**
	 * Handles keyboard up and down keys to change quantity value.
	 */
	const handleInputKeyDown = useCallback(
		( event: React.KeyboardEvent< HTMLInputElement > ) => {
			const isArrowDown =
				event.key === 'ArrowDown' || event.keyCode === DOWN;
			const isArrowUp = event.key === 'ArrowUp' || event.keyCode === UP;
			const isEnter = event.key === 'Enter' || event.keyCode === ENTER;

			if ( isArrowDown ) {
				event.preventDefault();
				decreaseButtonRef.current?.click();
			}

			if ( isArrowUp ) {
				event.preventDefault();
				increaseButtonRef.current?.click();
			}

			if ( isEnter ) {
				event.preventDefault();
				inputRef.current?.blur();
			}
		},
		[]
	);

	const handleInputChange = useCallback(
		( event: React.ChangeEvent< HTMLInputElement > ) => {
			const raw = event.target.value;
			const value = Number( raw );

			setInternalState( value );

			if ( isNaN( value ) ) {
				debouncedOnChange.cancel();
				return;
			}

			const normalized = normalizeQuantity( value );

			// Update expected quantity to prevent useEffect from overriding local state
			expectedQuantityRef.current = normalized;

			// Only push if the value is valid and matches the normalized value
			if ( normalized === value && normalized !== quantity ) {
				debouncedOnChange( normalized );
			} else {
				// Cancel any pending debounced changes if the value is invalid
				debouncedOnChange.cancel();
			}
		},
		[ debouncedOnChange, normalizeQuantity, quantity ]
	);

	const handleInputBlur = useCallback( () => {
		commitValue( internalState );
	}, [ internalState, commitValue ] );

	const formatValue = useCallback( ( value: number ) => {
		return value.toString();
	}, [] );

	return (
		<div className={ classes }>
			<input
				ref={ inputRef }
				className="wc-block-components-quantity-selector__input"
				disabled={ disabled }
				readOnly={ ! editable }
				type="number"
				step={ step }
				min={ minimum }
				max={ maximum }
				value={ formatValue( internalState ) }
				onKeyDown={ handleInputKeyDown }
				onBlur={ handleInputBlur }
				onChange={ handleInputChange }
				aria-label={ sprintf(
					/* translators: %s refers to the item name in the cart. */
					__( 'Quantity of %s in your cart.', 'woocommerce' ),
					itemName
				) }
			/>
			{ editable && (
				<>
					<button
						ref={ decreaseButtonRef }
						aria-label={ sprintf(
							/* translators: %s refers to the item name in the cart. */
							__( 'Reduce quantity of %s', 'woocommerce' ),
							itemName
						) }
						className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus"
						disabled={ ! canDecrease }
						onClick={ () => {
							const newQuantity = internalState - step;
							commitValue( newQuantity );
							expectedQuantityTypeRef.current = 'decrease';
							speak(
								sprintf(
									/* translators: %s refers to the item's new quantity in the cart. */
									__(
										'Quantity reduced to %s.',
										'woocommerce'
									),
									newQuantity
								)
							);
						} }
					>
						&#8722;
					</button>
					<button
						ref={ increaseButtonRef }
						aria-label={ sprintf(
							/* translators: %s refers to the item's name in the cart. */
							__( 'Increase quantity of %s', 'woocommerce' ),
							itemName
						) }
						disabled={ ! canIncrease }
						className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus"
						onClick={ () => {
							const newQuantity = internalState + step;
							commitValue( newQuantity );
							expectedQuantityTypeRef.current = 'increase';
							speak(
								sprintf(
									/* translators: %s refers to the item's new quantity in the cart. */
									__(
										'Quantity increased to %s.',
										'woocommerce'
									),
									newQuantity
								)
							);
						} }
					>
						&#65291;
					</button>
				</>
			) }
		</div>
	);
};

export default QuantitySelector;
