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

function DataViewsSidebarContent() {
	return <div>Sidebar dataviews</div>;
}
function PostList() {
	return <div>Post List</div>;
}

const { useLocation } = unlock( routerPrivateApis );

export default function useLayoutAreas() {
	const { params = {} } = useLocation();
	const { postType, layout, canvas } = params;
	const labels = useSelect(
		( select ) => {
			return select( coreStore ).getPostType( postType )?.labels;
		},
		[ postType ]
	);

	// Products list.
	if ( [ 'product' ].includes( postType ) ) {
		const isListLayout = layout === 'list' || ! layout;
		return {
			key: 'products-list',
			areas: {
				sidebar: (
					<SidebarNavigationScreen
						title={ labels?.name }
						isRoot
						content={ <DataViewsSidebarContent /> }
					/>
				),
				content: <PostList postType={ postType } />,
				preview: false,
				mobile: <PostList postType={ postType } />,
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
