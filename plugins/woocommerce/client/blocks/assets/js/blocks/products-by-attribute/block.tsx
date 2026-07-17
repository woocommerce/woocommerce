/**
 * External dependencies
 */
import ServerSideRender from '@wordpress/server-side-render';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { usePreviewMode } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { Props } from './types';

export const ProductsByAttributeBlock = ( props: Props ): JSX.Element => {
	const { attributes, name } = props;
	const isPreviewMode = usePreviewMode();

	if ( isPreviewMode ) {
		return gridBlockPreview;
	}

	return <ServerSideRender block={ name } attributes={ attributes } />;
};
