/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { Component } from 'react';
import { addQueryArgs } from '@wordpress/url';

class ProductButton extends Component {
	static propTypes = {
		className: PropTypes.string,
		product: PropTypes.object.isRequired,
	};

	state = {
		addedToCart: false,
		addingToCart: false,
		cartQuantity: null,
	};

	onAddToCart = () => {
		const { product } = this.props;

		this.setState( { addingToCart: true } );

		return apiFetch( {
			method: 'POST',
			path: '/wc/blocks/cart/add',
			data: {
				product_id: product.id,
				quantity: 1,
			},
			cache: 'no-store',
		} )
			.then( ( response ) => {
				const newQuantity = response.quantity;

				this.setState( {
					addedToCart: true,
					addingToCart: false,
					cartQuantity: newQuantity,
				} );
			} )
			.catch( ( response ) => {
				if ( response.code ) {
					return ( document.location.href = addQueryArgs(
						product.permalink,
						{ wc_error: response.message }
					) );
				}

				document.location.href = product.permalink;
			} );
	};

	getButtonText = () => {
		const { product } = this.props;
		const { cartQuantity } = this.state;

		if ( Number.isFinite( cartQuantity ) ) {
			return sprintf(
				__( '%d in cart', 'woo-gutenberg-products-block' ),
				cartQuantity
			);
		}

		return product.add_to_cart.text;
	};

	render = () => {
		const { product, className } = this.props;
		const { addingToCart, addedToCart } = this.state;

		const wrapperClasses = classnames(
			className,
			'wc-block-grid__product-add-to-cart',
			'wp-block-button'
		);

		const buttonClasses = classnames(
			'wp-block-button__link',
			'add_to_cart_button',
			{
				loading: addingToCart,
				added: addedToCart,
			}
		);

		if ( Object.keys( product ).length === 0 ) {
			return (
				<div className={ wrapperClasses }>
					<button className={ buttonClasses } disabled={ true } />
				</div>
			);
		}

		const allowAddToCart =
			! product.has_options &&
			product.is_purchasable &&
			product.is_in_stock;
		const buttonText = this.getButtonText();

		return (
			<div className={ wrapperClasses }>
				{ allowAddToCart ? (
					<button
						onClick={ this.onAddToCart }
						aria-label={ product.add_to_cart.description }
						className={ buttonClasses }
						disabled={ addingToCart }
					>
						{ buttonText }
					</button>
				) : (
					<a
						href={ product.permalink }
						aria-label={ product.add_to_cart.description }
						className={ buttonClasses }
						rel="nofollow"
					>
						{ buttonText }
					</a>
				) }
			</div>
		);
	};
}

export default ProductButton;
