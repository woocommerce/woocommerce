/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { applyFilters, addFilter, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { Content } from './content';
import { MyExample, MyExampleEdit } from './pages/my-example';

const NotFound = () => {
	return <h1>Not Found</h1>;
};

export const useSettingsLocation = () => {
	const { section, path, ...otherQueryParams } = getQuery();
	const page = path.split( '/settings/' ).pop();
	return { ...otherQueryParams, section, page };
};

export const getRoute = ( section ) => {
	const { page } = useSettingsLocation();
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const sections = settingsData[ page ]?.sections;
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];

	if ( ! Object.keys( settingsData ).includes( page ) ) {
		return {
			page,
			areas: {
				content: <NotFound />,
				edit: null,
			},
			widths: {
				content: undefined,
				edit: undefined,
			},
		};
	}

	const legacyRoutes = Object.keys( settingsData ).filter(
		( p ) => ! settingsData[ p ].is_modern
	);

	if ( legacyRoutes.includes( page ) ) {
		return {
			page,
			areas: {
				content: <Content data={ contentData } />,
				edit: null,
			},
			widths: {
				content: undefined,
				edit: undefined,
			},
		};
	}

	addFilter( 'woocommerce_admin_settings_pages', 'woocommerce', ( pages ) => {
		return [
			...pages,
			{
				page: 'my-example',
				areas: {
					content: <MyExample section={ section } />,
					edit: <MyExampleEdit />,
				},
				widths: {
					content: undefined,
					edit: 380,
				},
			},
		];
	} );

	const routes = applyFilters( 'woocommerce_admin_settings_pages', [] );
	removeAllFilters( 'woocommerce_admin_settings_pages' );

	const pageRoute = routes.find( ( route ) => route.page === page );

	if ( ! pageRoute ) {
		return {
			page,
			areas: {
				content: <NotFound />,
				edit: null,
			},
			widths: {
				content: undefined,
				edit: undefined,
			},
		};
	}

	return pageRoute;
};
