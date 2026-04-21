/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';
import ProductList from './product-list';

const { useLocation } = unlock( routerPrivateApis );

export type Route = {
	key: string;
	areas: {
		sidebar?: React.JSX.Element;
		content?: React.JSX.Element;
		edit?: React.JSX.Element;
		mobile?: React.JSX.Element | boolean;
		preview?: boolean;
	};
	widths?: {
		content?: number;
		edit?: number;
		sidebar?: number;
	};
};

export default function useLayoutAreas() {
	const { params = {} } = useLocation();
	const { postType = 'product', canvas, quickEdit: showQuickEdit } = params;
	// Products list.
	if ( [ 'product' ].includes( postType ) ) {
		return {
			key: 'products-list',
			areas: {
				content: <ProductList />,
				preview: false,
				mobile: <ProductList postType={ postType } />,
			},
			widths: {
				edit: showQuickEdit ? 380 : undefined,
			},
		};
	}

	// Fallback shows the home page preview
	return {
		key: 'default',
		areas: {
			preview: false,
			mobile: canvas === 'edit',
		},
	};
}
