/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { arrowLeft, arrowRight, Icon } from '@wordpress/icons';
import { Button } from '@wordpress/components';

export function SwitcherLoadingPlaceholder( {
	position,
}: {
	position: 'left' | 'right';
} ) {
	return (
		<Button
			data-testid="woocommerce-product-variation-switcher-footer-placeholder"
			className="woocommerce-product-variation-switcher-footer__button is-placeholder"
			disabled={ true }
		>
			{ position === 'left' && (
				<>
					<Icon
						icon={ arrowLeft }
						size={ 16 }
						className="woocommerce-product-variation-switcher-footer__arrow"
					/>
					<div className="woocommerce-product-variation-switcher-footer__product-image" />
				</>
			) }
			<div className="woocommerce-product-variation-switcher-footer__item-label" />

			{ position === 'right' && (
				<>
					<div className="woocommerce-product-variation-switcher-footer__product-image" />
					<Icon
						icon={ arrowRight }
						size={ 16 }
						className="woocommerce-product-variation-switcher-footer__arrow"
					/>
				</>
			) }
		</Button>
	);
}
