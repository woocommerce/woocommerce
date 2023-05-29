/**
 * External dependencies
 */
import { WooHeaderItem } from '@woocommerce/admin-layout';
import { Product } from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { PreviewButton } from './preview-button';
import { SaveDraftButton } from './save-draft-button';
import { PublishButton } from './publish-button';
import { Tabs } from '../tabs';

export type HeaderProps = {
	productId: Product[ 'id' ];
	onTabSelect: ( tabId: string | null ) => void;
};

export function Header( { productId, onTabSelect }: HeaderProps ) {
	const lastPersistedProduct = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return getEntityRecord< Product >(
				'postType',
				'product',
				productId
			);
		},
		[ productId ]
	);

	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		'product',
		'name'
	);

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<div className="woocommerce-product-header__inner">
				<div />

				<h1 className="woocommerce-product-header__title">
					{ getHeaderTitle(
						editedProductName,
						lastPersistedProduct.name
					) }
				</h1>

				<div className="woocommerce-product-header__actions">
					<SaveDraftButton
						productId={ lastPersistedProduct.id }
						productStatus={ lastPersistedProduct.status }
					/>

					<PreviewButton
						productId={ lastPersistedProduct.id }
						productStatus={ lastPersistedProduct.status }
					/>

					<PublishButton
						productId={ lastPersistedProduct.id }
						productStatus={ lastPersistedProduct.status }
					/>

					<WooHeaderItem.Slot name="product" />
					<MoreMenu />
				</div>
			</div>
			<Tabs onChange={ onTabSelect } />
		</div>
	);
}
