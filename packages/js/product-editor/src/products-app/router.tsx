/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import SidebarNavigationScreen from '@wordpress/edit-site/build-module/components/sidebar-navigation-screen';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import ProductList from './product-list';
import DataViewsSidebarContent from './sidebar-dataviews';

const { useLocation } = unlock( routerPrivateApis );

export default function useLayoutAreas() {
	const { params = {} } = useLocation();
	const { postType = 'product', layout = 'table', canvas } = params;
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
			},
			widths: {
				content: isListLayout ? 380 : undefined,
			},
		};
	}

	// Fallback shows the home page preview
	return {
		key: 'default',
		areas: {
			sidebar: () => null,
			preview: () => null,
			mobile: canvas === 'edit',
		},
	};
}
