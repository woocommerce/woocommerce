/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { applyFilters, addFilter, removeFilter } from '@wordpress/hooks';
import { useEffect } from '@wordpress/element';

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

export const useGetRoute = ( section ) => {
	const { page } = useSettingsLocation();
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const sections = settingsData[ page ]?.sections;
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];

	useEffect( () => {
		const uniqueNamespaceIdentifier = `woocommerce_${ new Date().getTime() }`; // unique key for namespace
		const newPage = {
			page: 'my-example',
			areas: {
				content: <MyExample section={ section } />,
				edit: <MyExampleEdit />,
			},
			widths: {
				content: undefined,
				edit: 380,
			},
		};
		addFilter(
			'woocommerce_admin_settings_pages',
			uniqueNamespaceIdentifier,
			( pages ) => {
				console.log( 'adding filter as it was not found', pages );
				return [ ...pages, newPage ];
			}
		);
		return () =>
			removeFilter(
				'woocommerce_admin_settings_pages',
				uniqueNamespaceIdentifier
			);
	}, [ section ] );

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

	console.log( 'applying filter' );
	const routes = applyFilters( 'woocommerce_admin_settings_pages', [] );
	console.log( 'applied filter', routes );

	console.log( 'page', page );
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
