/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, TextControl } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
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

	beginEdit() {
		this.setState( { editing: true } );
	}

	cancelEdit() {
		this.setState( {
			editing: false,
			quantity: this.props.product.stock_quantity,
		} );
	}

	onQuantityChange( quantity ) {
		this.setState( { quantity } );
	}

	updateStock() {
		const { product, updateItem } = this.props;

		this.setState( { editing: false }, () => {
			updateItem( 'products', product.id, { stock_quantity: this.state.quantity } );
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
					<TextControl
						className="woocommerce-stock-activity-card__edit-quantity"
						type="number"
						value={ quantity }
						onChange={ this.onQuantityChange }
					/>
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
			<Link href={ 'post.php?action=edit&post=' + product.id } type="wp-admin">
				{ product.name }
			</Link>
		);

		return (
			<ActivityCard
				className="woocommerce-stock-activity-card"
				title={ title }
				icon={ <ProductImage product={ product } /> }
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
