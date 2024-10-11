/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { BaseControl, Button } from '@wordpress/components';
import clsx from 'clsx';
import { Component, Fragment } from '@wordpress/element';
import { ESCAPE } from '@wordpress/keycodes';
import { get } from 'lodash';
import { Link, ProductImage } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { ActivityCard } from '~/activity-panel/activity-card';
import { getAdminSetting } from '~/utils/admin-settings';

export class ProductStockCard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			quantity: props.product.stock_quantity,
			editing: false,
			edited: false,
		};

		this.beginEdit = this.beginEdit.bind( this );
		this.cancelEdit = this.cancelEdit.bind( this );
		this.onQuantityChange = this.onQuantityChange.bind( this );
		this.handleKeyDown = this.handleKeyDown.bind( this );
		this.onSubmit = this.onSubmit.bind( this );
	}

	recordStockEvent( eventName, eventProps = {} ) {
		recordEvent( `activity_panel_stock_${ eventName }`, eventProps );
	}

	beginEdit() {
		const { product } = this.props;

		this.setState(
			{
				editing: true,
				quantity: product.stock_quantity,
			},
			() => {
				if ( this.quantityInput ) {
					this.quantityInput.focus();
				}
			}
		);
		this.recordStockEvent( 'update_stock' );
	}

	cancelEdit() {
		const { product } = this.props;

		this.setState( {
			editing: false,
			quantity: product.stock_quantity,
		} );
		this.recordStockEvent( 'cancel' );
	}

	handleKeyDown( event ) {
		if ( event.keyCode === ESCAPE ) {
			this.cancelEdit();
		}
	}

	onQuantityChange( event ) {
		this.setState( { quantity: event.target.value } );
	}

	async onSubmit() {
		const { product, updateProductStock, createNotice } = this.props;
		const quantity = parseInt( this.state.quantity, 10 );

		// Bail on an actual update if the quantity is unchanged.
		if ( product.stock_quantity === quantity ) {
			this.setState( { editing: false } );
			return;
		}

		this.setState( { editing: false, edited: true } );

		const success = await updateProductStock( product, quantity );

		if ( success ) {
			createNotice(
				'success',
				sprintf(
					/* translators: %s = name of the product having stock updated */
					__( '%s stock updated', 'woocommerce' ),
					product.name
				),
				{
					actions: [
						{
							label: __( 'Undo', 'woocommerce' ),
							onClick: () => {
								updateProductStock(
									product,
									product.stock_quantity
								);

								this.recordStockEvent( 'undo' );
							},
						},
					],
				}
			);
		} else {
			createNotice(
				'error',
				sprintf(
					/* translators: %s = name of the product having stock updated */
					__( '%s stock could not be updated', 'woocommerce' ),
					product.name
				)
			);
		}

		this.recordStockEvent( 'save', {
			quantity,
		} );
	}

	getActions() {
		const { editing } = this.state;

		if ( editing ) {
			return [
				<Button key="save" type="submit" isPrimary>
					{ __( 'Save', 'woocommerce' ) }
				</Button>,
				<Button key="cancel" type="reset">
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>,
			];
		}

		return [
			<Button key="update" isSecondary onClick={ this.beginEdit }>
				{ __( 'Update stock', 'woocommerce' ) }
			</Button>,
		];
	}

	getBody() {
		const { product } = this.props;
		const { editing, quantity } = this.state;

		if ( editing ) {
			return (
				<Fragment>
					<BaseControl className="woocommerce-stock-activity-card__edit-quantity">
						<input
							className="components-text-control__input"
							type="number"
							value={ quantity }
							onKeyDown={ this.handleKeyDown }
							onChange={ this.onQuantityChange }
							ref={ ( input ) => {
								this.quantityInput = input;
							} }
						/>
					</BaseControl>
					<span>{ __( 'in stock', 'woocommerce' ) }</span>
				</Fragment>
			);
		}

		return (
			<span
				className={ clsx(
					'woocommerce-stock-activity-card__stock-quantity',
					{
						'out-of-stock': product.stock_quantity < 1,
					}
				) }
			>
				{ sprintf(
					/* translators: %d = stock quantity of the product being updated */
					__( '%d in stock', 'woocommerce' ),
					product.stock_quantity
				) }
			</span>
		);
	}

	render() {
		const { product } = this.props;
		const { edited, editing } = this.state;
		const notifyLowStockAmount = getAdminSetting(
			'notifyLowStockAmount',
			0
		);
		const lowStockAmount = Number.isFinite( product.low_stock_amount )
			? product.low_stock_amount
			: notifyLowStockAmount;
		const isLowStock = product.stock_quantity <= lowStockAmount;
		const lastOrderDate = product.last_order_date
			? sprintf(
					/* translators: %s = time since last product order. e.g.: "10 minutes ago" - translated. */
					__( 'Last ordered %s', 'woocommerce' ),
					moment.utc( product.last_order_date ).fromNow()
			  )
			: null;

		// Hide cards that are not in low stock and have not been edited.
		// This allows clearing cards which are no longer in low stock after
		// closing & opening the panel without having to make another request.
		if ( ! isLowStock && ! edited ) {
			return null;
		}

		const title = (
			<Link
				href={
					'post.php?action=edit&post=' +
					( product.parent_id || product.id )
				}
				onClick={ () => this.recordStockEvent( 'product_name' ) }
				type="wp-admin"
			>
				{ product.name }
			</Link>
		);
		let subtitle = null;

		if ( product.type === 'variation' ) {
			subtitle = Object.values( product.attributes )
				.map( ( attr ) => attr.option )
				.join( ', ' );
		}

		const productImage =
			get( product, [ 'images', 0 ] ) || get( product, [ 'image' ] );
		const productImageClasses = clsx(
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
		const activityCardClasses = clsx( 'woocommerce-stock-activity-card', {
			'is-dimmed': ! editing && ! isLowStock,
		} );

		const activityCard = (
			<ActivityCard
				className={ activityCardClasses }
				title={ title }
				subtitle={ subtitle }
				icon={ icon }
				date={ lastOrderDate }
				actions={ this.getActions() }
			>
				{ this.getBody() }
			</ActivityCard>
		);

		if ( editing ) {
			return (
				<form onReset={ this.cancelEdit } onSubmit={ this.onSubmit }>
					{ activityCard }
				</form>
			);
		}

		return activityCard;
	}
}
