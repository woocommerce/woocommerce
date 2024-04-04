/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Button, Modal } from '@wordpress/components';
import { find } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';
import { withSelect } from '@wordpress/data';
import { List } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getProductIdsForCart } from '../utils';
import sanitizeHTML from '../../lib/sanitize-html';
import { getInAppPurchaseUrl } from '../../lib/in-app-purchase';
import { getAdminSetting } from '~/utils/admin-settings';

class CartModal extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			purchaseNowButtonBusy: false,
			purchaseLaterButtonBusy: false,
		};
	}

	onClickPurchaseNow() {
		const { productIds, onClickPurchaseNow } = this.props;
		this.setState( { purchaseNowButtonBusy: true } );
		if ( ! productIds.length ) {
			return;
		}

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: true,
		} );

		const url = getInAppPurchaseUrl(
			'woocommerce.com/cart?utm_medium=product',
			{
				'wccom-replace-with': productIds.join( ',' ),
			}
		);

		if ( onClickPurchaseNow ) {
			onClickPurchaseNow( url );
			return;
		}

		window.location = url;
	}

	onClickPurchaseLater() {
		const { productIds } = this.props;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		this.setState( { purchaseLaterButtonBusy: true } );
		this.props.onClickPurchaseLater();
	}

	onClose() {
		const { onClose, productIds } = this.props;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		onClose();
	}

	renderProducts() {
		const { productIds, productTypes } = this.props;
		const { themes = [] } = getAdminSetting( 'onboarding', {} );
		const listItems = [];

		productIds.forEach( ( productId ) => {
			const productInfo = find( productTypes, ( productType ) => {
				return productType.product === productId;
			} );

			if ( productInfo ) {
				listItems.push( {
					title: productInfo.label,
					content: productInfo.description,
				} );
			}

			const themeInfo = find( themes, ( theme ) => {
				return theme.id === productId;
			} );

			if ( themeInfo ) {
				listItems.push( {
					title: sprintf(
						/* translators: 1: theme title, 2: theme price */
						__( '%1$s — %2$s per year', 'woocommerce' ),
						themeInfo.title,
						decodeEntities( themeInfo.price )
					),
					content: (
						<span
							dangerouslySetInnerHTML={ sanitizeHTML(
								themeInfo.excerpt
							) }
						/>
					),
				} );
			}
		} );

		return <List items={ listItems } />;
	}

	render() {
		const { purchaseNowButtonBusy, purchaseLaterButtonBusy } = this.state;
		return (
			<Modal
				title={ __(
					'Would you like to add the following paid features to your store now?',
					'woocommerce'
				) }
				onRequestClose={ () => this.onClose() }
				className="woocommerce-cart-modal"
			>
				{ this.renderProducts() }

				<p className="woocommerce-cart-modal__help-text">
					{ __(
						"You won't have access to this functionality until the extensions have been purchased and installed.",
						'woocommerce'
					) }
				</p>

				<div className="woocommerce-cart-modal__actions">
					<Button
						isLink
						isBusy={ purchaseLaterButtonBusy }
						onClick={ () => this.onClickPurchaseLater() }
					>
						{ __( "I'll do it later", 'woocommerce' ) }
					</Button>

					<Button
						isPrimary
						isBusy={ purchaseNowButtonBusy }
						onClick={ () => this.onClickPurchaseNow() }
					>
						{ __( 'Buy now', 'woocommerce' ) }
					</Button>
				</div>
			</Modal>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getInstalledPlugins } = select( PLUGINS_STORE_NAME );
		const { getProductTypes, getProfileItems } = select(
			ONBOARDING_STORE_NAME
		);
		const profileItems = getProfileItems();
		const installedPlugins = getInstalledPlugins();
		const productTypes = getProductTypes();
		const productIds = getProductIdsForCart(
			productTypes,
			profileItems,
			false,
			installedPlugins
		);

		return { profileItems, productIds, productTypes };
	} )
)( CartModal );
