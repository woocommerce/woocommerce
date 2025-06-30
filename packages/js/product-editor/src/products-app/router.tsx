/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import ProductList from './product-list';
import ProductEdit from './product-edit';
import DataViewsSidebarContent from './sidebar-dataviews';
import SidebarNavigationScreen from './sidebar-navigation-screen';

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
	const {
		postType = 'product',
		layout = 'table',
		canvas,
		quickEdit: showQuickEdit,
		postId,
	} = params;
	// Products list.
	if ( [ 'product' ].includes( postType ) ) {
		const isListLayout = layout === 'list' || ! layout;
		return {
			key: 'products-list',
			areas: {
				sidebar: (
					<SidebarNavigationScreen
						title={ 'Products' }
						isRoot
						content={ <DataViewsSidebarContent /> }
					/>
				),
				content: <ProductList />,
				preview: false,
				mobile: <ProductList postType={ postType } />,
				edit: showQuickEdit && (
					<ProductEdit postType={ postType } postId={ postId } />
				),
			},
			widths: {
				edit: showQuickEdit && ! isListLayout ? 380 : undefined,
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
