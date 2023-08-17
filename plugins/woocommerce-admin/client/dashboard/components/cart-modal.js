/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { find } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';
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

function CartModal( props ) {
	const data = useSelect( ( select ) => {
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
	} );
	const [ purchaseNowButtonBusy, setPurchaseNowButtonBusy ] =
		useState( false );
	const [ purchaseLaterButtonBusy, setPurchaseLaterButtonBusy ] =
		useState( false );

	function onClickPurchaseNow() {
		const { onClickPurchaseNow } = props;
		setPurchaseNowButtonBusy( true );
		if ( ! data.productIds.length ) {
			return;
		}

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: data.productIds,
			purchase_install: true,
		} );

		const url = getInAppPurchaseUrl(
			'https://woocommerce.com/cart?utm_medium=product',
			{
				'wccom-replace-with': data.productIds.join( ',' ),
			}
		);

		if ( onClickPurchaseNow ) {
			onClickPurchaseNow( url );
			return;
		}

		window.location = url;
	}

	function onClickPurchaseLater() {
		const { productIds } = data;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		setPurchaseLaterButtonBusy( true );
		props.onClickPurchaseLater();
	}

	function onClose() {
		const { productIds } = data;

		recordEvent( 'tasklist_modal_proceed_checkout', {
			product_ids: productIds,
			purchase_install: false,
		} );

		props.onClose();
	}

	function renderProducts() {
		const { productIds, productTypes } = data;
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
						__( '%s — %s per year', 'woocommerce' ),
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

	return (
		<Modal
			title={ __(
				'Would you like to add the following paid features to your store now?',
				'woocommerce'
			) }
			onRequestClose={ () => onClose() }
			className="woocommerce-cart-modal"
		>
			{ renderProducts() }

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
					onClick={ () => onClickPurchaseLater() }
				>
					{ __( "I'll do it later", 'woocommerce' ) }
				</Button>

				<Button
					isPrimary
					isBusy={ purchaseNowButtonBusy }
					onClick={ () => onClickPurchaseNow() }
				>
					{ __( 'Buy now', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}
export default CartModal;
