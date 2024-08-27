// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import SidebarNavigationScreen from '@wordpress/edit-site/build-module/components/sidebar-navigation-screen';
// import DataViewsSidebarContent from '@wordpress/edit-site/build-module/components/sidebar-dataviews';
// import PostList from '@wordpress/edit-site/build-module/components/post-list';

/**
 * Internal dependencies
 */
import ProductList from './product-list/index';
import ProductEdit from './product-edit/index';

function DataViewsSidebarContent() {
	return <div>Sidebar dataviews</div>;
}

const { useLocation } = unlock( routerPrivateApis );

export default function useLayoutAreas() {
	const { params = {} } = useLocation();
	const {
		postType = 'product',
		layout = 'table',
		canvas,
		quickEdit,
		postId,
	} = params;
	const labels = useSelect(
		( select ) => {
			return select( coreStore ).getPostType( postType )?.labels;
		},
		[ postType ]
	);

	// Products list.
	if ( [ 'product' ].includes( postType ) ) {
		const isListLayout = layout === 'list' || ! layout;
		const showQuickEdit = quickEdit && ! isListLayout;
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
