/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { useRef, useState, useEffect, useCallback } from '@wordpress/element';
import { DOWN, UP } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import './style.scss';

const QuantitySelector = ( {
	className,
	quantity = 1,
	onChange = () => null,
	itemName = '',
} ) => {
	const classes = classNames( 'wc-block-quantity-selector', className );
	const inputRef = useRef( null );
	const [ currentValue, setCurrentValue ] = useState( quantity );

	useEffect( () => {
		if ( currentValue !== inputRef.current.value ) {
			inputRef.current.value = currentValue;
		}
		onChange( currentValue );
	}, [ currentValue, onChange ] );

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

			if ( isArrowDown && currentValue > 0 ) {
				event.preventDefault();
				setCurrentValue( currentValue - 1 );
			}

			if ( isArrowUp ) {
				event.preventDefault();
				setCurrentValue( currentValue + 1 );
			}
		},
		[ currentValue, setCurrentValue ]
	);

	return (
		<div className={ classes }>
			<input
				className="wc-block-quantity-selector__input"
				type="number"
				step="1"
				min="0"
				value={ quantity }
				ref={ inputRef }
				onKeyDown={ quantityInputOnKeyDown }
				onChange={ ( event ) => {
					const value =
						isNaN( event.target.value ) || ! event.target.value
							? 0
							: parseInt( event.target.value, 10 );
					setCurrentValue( value );
				} }
				aria-label={ sprintf(
					__(
						// translators: %s Item name.
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
				className="wc-block-quantity-selector__button wc-block-quantity-selector__button--minus"
				disabled={ currentValue <= 0 }
				onClick={ () => {
					const newQuantity = currentValue - 1;
					setCurrentValue( newQuantity );
					speak(
						sprintf(
							__(
								// translators: %s Quantity amount.
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
				className="wc-block-quantity-selector__button wc-block-quantity-selector__button--plus"
				onClick={ () => {
					const newQuantity = currentValue + 1;
					setCurrentValue( newQuantity );
					speak(
						sprintf(
							__(
								// translators: %s Quantity amount.
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

QuantitySelector.propTypes = {
	className: PropTypes.string,
	quantity: PropTypes.number,
	onChange: PropTypes.func,
	itemName: PropTypes.string,
};

export default QuantitySelector;
