/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { useCallback } from '@wordpress/element';
import { DOWN, UP } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import './style.scss';

const QuantitySelector = ( {
	className,
	quantity = 1,
	minimum = 1,
	maximum,
	onChange = () => null,
	itemName = '',
	disabled,
} ) => {
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
						isNaN( event.target.value ) || ! event.target.value
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
					__(
						// translators: %s Item name.
						'Quantity of %s in your cart.',
						'woocommerce'
					),
					itemName
				) }
			/>
			<button
				aria-label={ __(
					'Reduce quantity',
					'woocommerce'
				) }
				className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus"
				disabled={ disabled || ! canDecrease }
				onClick={ () => {
					const newQuantity = quantity - 1;
					onChange( newQuantity );
					speak(
						sprintf(
							__(
								// translators: %s Quantity amount.
								'Quantity reduced to %s.',
								'woocommerce'
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
					'woocommerce'
				) }
				disabled={ disabled || ! canIncrease }
				className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus"
				onClick={ () => {
					const newQuantity = quantity + 1;
					onChange( newQuantity );
					speak(
						sprintf(
							__(
								// translators: %s Quantity amount.
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
		</div>
	);
};

QuantitySelector.propTypes = {
	className: PropTypes.string,
	quantity: PropTypes.number,
	minimum: PropTypes.number,
	maximum: PropTypes.number,
	onChange: PropTypes.func,
	itemName: PropTypes.string,
	disabled: PropTypes.bool,
};

export default QuantitySelector;
