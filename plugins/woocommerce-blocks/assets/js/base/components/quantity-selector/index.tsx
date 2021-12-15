/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import classNames from 'classnames';
import { useCallback } from '@wordpress/element';
import { DOWN, UP } from '@wordpress/keycodes';

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
	 * Whether the component should be interactable or not
	 */
	disabled: boolean;
}

const QuantitySelector = ( {
	className,
	quantity = 1,
	minimum = 1,
	maximum,
	onChange = () => void 0,
	itemName = '',
	disabled,
}: QuantitySelectorProps ): JSX.Element => {
	const classes = classNames(
		'wc-block-components-quantity-selector',
		className
	);

	const hasMaximum = typeof maximum !== 'undefined';
	const canDecrease = quantity > minimum;
	const canIncrease = ! hasMaximum || quantity < maximum;

	/**
	 * Handles keyboard up and down keys to change quantity value.
	 *
	 * @param {Object} event event data.
	 */
	const quantityInputOnKeyDown = useCallback(
		( event ) => {
			const isArrowDown =
				typeof event.key !== undefined
					? event.key === 'ArrowDown'
					: event.keyCode === DOWN;
			const isArrowUp =
				typeof event.key !== undefined
					? event.key === 'ArrowUp'
					: event.keyCode === UP;

			if ( isArrowDown && canDecrease ) {
				event.preventDefault();
				onChange( quantity - 1 );
			}

			if ( isArrowUp && canIncrease ) {
				event.preventDefault();
				onChange( quantity + 1 );
			}
		},
		[ quantity, onChange, canIncrease, canDecrease ]
	);

	return (
		<div className={ classes }>
			<input
				className="wc-block-components-quantity-selector__input"
				disabled={ disabled }
				type="number"
				step="1"
				min="0"
				value={ quantity }
				onKeyDown={ quantityInputOnKeyDown }
				onChange={ ( event ) => {
					let value =
						Number.isNaN( event.target.value ) ||
						! event.target.value
							? 0
							: parseInt( event.target.value, 10 );
					if ( hasMaximum ) {
						value = Math.min( value, maximum );
					}
					value = Math.max( value, minimum );
					if ( value !== quantity ) {
						onChange( value );
					}
				} }
				aria-label={ sprintf(
					/* translators: %s refers to the item name in the cart. */
					__(
						'Quantity of %s in your cart.',
						'woo-gutenberg-products-block'
					),
					itemName
				) }
			/>
			<button
				aria-label={ __(
					'Reduce quantity',
					'woo-gutenberg-products-block'
				) }
				className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus"
				disabled={ disabled || ! canDecrease }
				onClick={ () => {
					const newQuantity = quantity - 1;
					onChange( newQuantity );
					speak(
						sprintf(
							/* translators: %s refers to the item name in the cart. */
							__(
								'Quantity reduced to %s.',
								'woo-gutenberg-products-block'
							),
							newQuantity
						)
					);
				} }
			>
				&#65293;
			</button>
			<button
				aria-label={ __(
					'Increase quantity',
					'woo-gutenberg-products-block'
				) }
				disabled={ disabled || ! canIncrease }
				className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus"
				onClick={ () => {
					const newQuantity = quantity + 1;
					onChange( newQuantity );
					speak(
						sprintf(
							/* translators: %s refers to the item name in the cart. */
							__(
								'Quantity increased to %s.',
								'woo-gutenberg-products-block'
							),
							newQuantity
						)
					);
				} }
			>
				&#65291;
			</button>
		</div>
	);
};

export default QuantitySelector;
