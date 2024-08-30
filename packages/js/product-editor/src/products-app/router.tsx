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
		sidebar: React.JSX.Element | React.FunctionComponent;
		content?: React.JSX.Element | React.FunctionComponent;
		edit?: React.JSX.Element | React.FunctionComponent;
		mobile?: React.JSX.Element | React.FunctionComponent | boolean;
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
				content: isListLayout ? 380 : undefined,
				edit: showQuickEdit && ! isListLayout ? 380 : undefined,
			},
		};
	}

	// Fallback shows the home page preview
	return {
		key: 'default',
		areas: {
			sidebar: () => null,
			preview: false,
			mobile: canvas === 'edit',
		},
	};
}
