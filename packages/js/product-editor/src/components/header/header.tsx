/**
 * External dependencies
 */
import { WooHeaderItem } from '@woocommerce/admin-layout';
import { Product, ProductVariation } from '@woocommerce/data';
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
	onTabSelect: ( tabId: string | null ) => void;
	productType?: string;
};

export function Header( {
	onTabSelect,
	productType = 'product',
}: HeaderProps ) {
	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);

	const lastPersistedProduct = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return getEntityRecord< Product | ProductVariation >(
				'postType',
				productType,
				productId
			);
		},
		[ productId ]
	);

	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		productType,
		'name'
	);

	if ( ! productId ) {
		return null;
	}

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
						lastPersistedProduct?.name
					) }
				</h1>

				<div className="woocommerce-product-header__actions">
					<SaveDraftButton
						productType={ productType }
						productStatus={ lastPersistedProduct.status }
					/>

					<PreviewButton
						productType={ productType }
						productStatus={ lastPersistedProduct.status }
					/>

					<PublishButton
						productType={ productType }
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
