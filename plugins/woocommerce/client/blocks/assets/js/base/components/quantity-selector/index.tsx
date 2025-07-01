/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import clsx from 'clsx';
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import { DOWN, UP, ENTER } from '@wordpress/keycodes';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import './style.scss';

export interface QuantitySelectorProps {
	/**
	 * Component wrapper classname
	 *
	 * @default 'wc-block-components-quantity-selector'
	 */
	className?: string;
	/**
	 * Current quantity
	 */
	quantity?: number;
	/**
	 * Minimum quantity
	 */
	minimum?: number;
	/**
	 * Maximum quantity
	 */
	maximum: number;
	/**
	 * Input step attribute.
	 */
	step?: number;
	/**
	 * Event handler triggered when the quantity is changed
	 */
	onChange: ( newQuantity: number ) => void;
	/**
	 * Name of the item the quantity selector refers to
	 *
	 * Used for a11y purposes
	 */
	itemName?: string;
	/**
	 * Whether the component should be interactable
	 */
	disabled: boolean;
	/**
	 * Whether the component should be editable
	 *
	 * @default true
	 */
	editable?: boolean;
}

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
	const [ inputValue, setInputValue ] = useState< string >(
		quantity.toString()
	);
	const [ isFocused, setIsFocused ] = useState< boolean >( false );
	const inputRef = useRef< HTMLInputElement | null >( null );
	const decreaseButtonRef = useRef< HTMLButtonElement | null >( null );
	const increaseButtonRef = useRef< HTMLButtonElement | null >( null );
	const hasMaximum = typeof maximum !== 'undefined';
	const canDecrease = ! disabled && quantity - step >= minimum;
	const canIncrease =
		! disabled && ( ! hasMaximum || quantity + step <= maximum );

	// Debounced callback for onChange to prevent excessive server calls
	const debouncedOnChange = useDebouncedCallback(
		( newQuantity: number ) => {
			onChange( newQuantity );
		},
		300 // 300ms delay
	);

	// Sync local state with prop if quantity changes externally (but not when focused)
	useEffect( () => {
		if ( ! isFocused ) {
			setInputValue( quantity.toString() );
		}
	}, [ quantity, isFocused ] );

	const normalizeQuantity = useCallback(
		( initialValue: number, snapToStep = true ) => {
			let value = initialValue;
			if ( hasMaximum ) {
				value = Math.min( value, Math.floor( maximum / step ) * step );
			}
			value = Math.max( value, Math.ceil( minimum / step ) * step );

			if ( snapToStep ) {
				// Snap to closest step increment
				value =
					Math.round( ( value - minimum ) / step ) * step + minimum;
			}

			return value;
		},
		[ hasMaximum, maximum, minimum, step ]
	);

	const commitValue = useCallback(
		( rawValue: string ) => {
			const value = Number( rawValue );
			if ( isNaN( value ) ) {
				setInputValue( quantity.toString() );
				return;
			}
			const normalized = normalizeQuantity( value );
			setInputValue( normalized.toString() );
			if ( normalized !== quantity ) {
				onChange( normalized );
			}
		},
		[ normalizeQuantity, quantity, onChange ]
	);

	const handleInputChange = (
		event: React.ChangeEvent< HTMLInputElement >
	) => {
		const raw = event.target.value;
		setInputValue( raw );
		const value = Number( raw );
		if ( ! isNaN( value ) ) {
			const normalized = normalizeQuantity( value, false );
			// Check if the value is a valid step increment
			const remainder = ( ( value - minimum ) / step ) % 1;
			const isValidStep =
				Math.abs( remainder ) < 1e-8 ||
				Math.abs( remainder - 1 ) < 1e-8;
			// Only push if the value is valid and matches the normalized value
			if (
				normalized === value &&
				normalized !== quantity &&
				isValidStep
			) {
				debouncedOnChange( normalized );
			} else {
				// Cancel any pending debounced changes if the value is invalid
				debouncedOnChange.cancel();
			}
		}
	};

	const handleInputBlur = () => {
		setIsFocused( false );
		// Flush any pending debounced changes
		debouncedOnChange.flush();
		commitValue( inputValue );
	};

	const handleInputKeyDown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		const isArrowDown = event.key === 'ArrowDown' || event.keyCode === DOWN;
		const isArrowUp = event.key === 'ArrowUp' || event.keyCode === UP;
		const isEnter = event.key === 'Enter' || event.keyCode === ENTER;
		if ( isArrowDown && canDecrease ) {
			event.preventDefault();
			onChange( quantity - step );
		}
		if ( isArrowUp && canIncrease ) {
			event.preventDefault();
			onChange( quantity + step );
		}
		if ( isEnter ) {
			commitValue( inputValue );
			if ( inputRef.current ) {
				inputRef.current.blur();
			}
		}
	};

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
				value={ inputValue }
				onChange={ handleInputChange }
				onFocus={ () => setIsFocused( true ) }
				onBlur={ handleInputBlur }
				onKeyDown={ handleInputKeyDown }
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
							const newQuantity = quantity - step;
							onChange( newQuantity );
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
							setInputValue( newQuantity.toString() );
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
							const newQuantity = quantity + step;
							onChange( newQuantity );
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
							setInputValue( newQuantity.toString() );
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
