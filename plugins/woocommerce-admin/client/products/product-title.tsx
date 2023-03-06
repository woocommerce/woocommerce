/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { getNewPath } from '@woocommerce/navigation';
import {
	getProductTitle,
	getProductVariationTitle,
	getTruncatedProductVariationTitle,
} from '@woocommerce/product-editor';
import { useFormContext } from '@woocommerce/components';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ProductBreadcrumbs } from './product-breadcrumbs';
import { ProductStatusBadge } from './product-status-badge';
import { WooHeaderPageTitle } from '~/header/utils';
import './product-title.scss';

export const ProductTitle: React.FC = () => {
	const { values } = useFormContext< Product >();
	const { productId, variationId } = useParams();
	const { isLoading, persistedName, productVariation } = useSelect(
		( select: WCDataSelector ) => {
			const {
				getProduct,
				hasFinishedResolution: hasProductFinishedResolution,
			} = select( PRODUCTS_STORE_NAME );
			const {
				getProductVariation,
				hasFinishedResolution: hasProductVariationFinishedResolution,
			} = select( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
			const product = productId
				? getProduct( parseInt( productId, 10 ) )
				: null;
			const variation =
				variationId && productId
					? getProductVariation( {
							id: parseInt( variationId, 10 ),
							product_id: parseInt( productId, 10 ),
					  } )
					: null;

			const isProductLoading =
				productId &&
				! hasProductFinishedResolution( 'getProduct', [
					parseInt( productId, 10 ),
				] );

			const isVariationLoading =
				variationId &&
				productId &&
				! hasProductVariationFinishedResolution(
					'getProductVariation',
					[
						{
							id: parseInt( variationId, 10 ),
							product_id: parseInt( productId, 10 ),
						},
					]
				);

			return {
				persistedName: product?.name,
				productVariation: variation,
				isLoading: isProductLoading || isVariationLoading,
			};
		}
	);

	const productTitle = getProductTitle(
		values.name,
		values.type,
		persistedName
	);
	const productVariationTitle =
		productVariation && getProductVariationTitle( productVariation );

	const pageHierarchy = [
		{
			href: getAdminLink( 'edit.php?post_type=product' ),
			title: __( 'Products', 'woocommerce' ),
		},
		{
			href: getNewPath( {}, '/product/' + productId ),
			type: 'wc-admin',
			title: (
				<>
					{ productTitle }
					<ProductStatusBadge />
				</>
			),
		},
		productVariationTitle && {
			title: (
				<span title={ productVariationTitle }>
					{ getTruncatedProductVariationTitle( productVariation ) }
				</span>
			),
		},
	].filter( ( page ) => !! page ) as {
		href: string;
		title: string | JSX.Element;
	}[];

	const current = pageHierarchy.pop();

	if ( isLoading ) {
		return null;
	}

	return (
		<WooHeaderPageTitle>
			<span className="woocommerce-product-title">
				<ProductBreadcrumbs breadcrumbs={ pageHierarchy } />
				<span className="woocommerce-product-title__wrapper">
					{ current?.title }
				</span>
			</span>
		</WooHeaderPageTitle>
	);
};
