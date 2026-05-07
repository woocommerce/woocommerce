/**
 * External dependencies
 */
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';
import ProductList from './product-list';
import ProductEdit from './product-edit';

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
	const { params = {}, query = {} } = useLocation();
	const postType = params.postType ?? query.postType ?? 'product';
	const canvas = params.canvas ?? query.canvas;
	const showQuickEdit =
		params.quickEdit === 'true' ||
		query.quickEdit === 'true' ||
		params.quickEdit === true ||
		query.quickEdit === true;
	// Products list.
	if ( [ 'product' ].includes( postType ) ) {
		return {
			key: 'products-list',
			areas: {
				content: <ProductList />,
				edit: showQuickEdit ? <ProductEdit /> : undefined,
				preview: false,
				mobile: <ProductList postType={ postType } />,
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
