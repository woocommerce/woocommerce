/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { BaseControl, Button } from '@wordpress/components';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { get } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Link, ProductImage } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';

class ProductStockCard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			quantity: props.product.stock_quantity,
			editing: false,
		};

		this.beginEdit = this.beginEdit.bind( this );
		this.cancelEdit = this.cancelEdit.bind( this );
		this.onQuantityChange = this.onQuantityChange.bind( this );
		this.updateStock = this.updateStock.bind( this );
	}

	componentDidUpdate() {
		this.quantityInput && this.quantityInput.focus();
	}

	beginEdit() {
		this.setState( { editing: true } );
	}

	cancelEdit() {
		this.setState( {
			editing: false,
			quantity: this.props.product.stock_quantity,
		} );
	}

	onQuantityChange( event ) {
		this.setState( { quantity: event.target.value } );
	}

	updateStock() {
		const { product, updateItem } = this.props;

		this.setState( { editing: false }, () => {
			const data = {
				stock_quantity: this.state.quantity,
				type: product.type,
				parent_id: product.parent_id,
			};

			updateItem( 'products', product.id, data );
		} );
	}

	getActions() {
		const { editing } = this.state;

		if ( editing ) {
			return [
				<Button onClick={ this.updateStock } isPrimary>
					{ __( 'Save', 'woocommerce-admin' ) }
				</Button>,
				<Button onClick={ this.cancelEdit }>{ __( 'Cancel', 'woocommerce-admin' ) }</Button>,
			];
		}

		return [
			<Button isDefault onClick={ this.beginEdit }>
				{ __( 'Update stock', 'woocommerce-admin' ) }
			</Button>,
		];
	}

	getBody() {
		const { editing, quantity } = this.state;

		if ( editing ) {
			return (
				<Fragment>
					<BaseControl className="woocommerce-stock-activity-card__edit-quantity">
						<input
							className="components-text-control__input"
							type="number"
							value={ quantity }
							onChange={ this.onQuantityChange }
							ref={ input => {
								this.quantityInput = input;
							} }
						/>
					</BaseControl>
					<span>{ __( 'in stock', 'woocommerce-admin' ) }</span>
				</Fragment>
			);
		}

		return (
			<span className="woocommerce-stock-activity-card__stock-quantity">
				{ sprintf( __( '%d in stock', 'woocommerce-admin' ), quantity ) }
			</span>
		);
	}

	render() {
		const { product } = this.props;
		const title = (
			<Link
				href={ 'post.php?action=edit&post=' + ( product.parent_id || product.id ) }
				type="wp-admin"
			>
				{ product.name }
			</Link>
		);
		let subtitle = null;

		if ( 'variation' === product.type ) {
			subtitle = Object.values( product.attributes )
				.map( attr => attr.option )
				.join( ', ' );
		}

		const productImage = get( product, [ 'images', 0 ] ) || get( product, [ 'image' ] );
		const productImageClasses = classnames(
			'woocommerce-stock-activity-card__image-overlay__product',
			{
				'is-placeholder': ! productImage || ! productImage.src,
			}
		);
		const icon = (
			<div className="woocommerce-stock-activity-card__image-overlay">
				<div className={ productImageClasses }>
					<ProductImage product={ product } />
				</div>
			</div>
		);

		return (
			<ActivityCard
				className="woocommerce-stock-activity-card"
				title={ title }
				subtitle={ subtitle }
				icon={ icon }
				actions={ this.getActions() }
			>
				{ this.getBody() }
			</ActivityCard>
		);
	}
}

export default compose(
	withDispatch( dispatch => {
		const { updateItem } = dispatch( 'wc-api' );
		return {
			updateItem,
		};
	} )
)( ProductStockCard );
