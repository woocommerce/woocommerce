/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { usePreviewMode } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { Props } from './types';
import { ProductBestSellersInspectorControls } from './inspector-controls';

export const ProductBestSellersBlock = ( props: Props ): JSX.Element => {
	const { attributes, name } = props;
	const isPreviewMode = usePreviewMode();

	if ( isPreviewMode ) {
		return gridBlockPreview;
	}

	return (
		<div className="wc-block-product-best-sellers">
			<ProductBestSellersInspectorControls { ...props } />
			<Disabled>
				<ServerSideRender block={ name } attributes={ attributes } />
			</Disabled>
		</div>
	);
};
