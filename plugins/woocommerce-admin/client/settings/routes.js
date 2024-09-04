/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

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

export const getRoute = () => {
	const { section, page } = useSettingsLocation();
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

	const legacyPages = Object.keys( settingsData ).filter(
		( p ) => ! settingsData[ p ].is_modern
	);

	if ( legacyPages.includes( page ) ) {
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

	const pages = [
		{
			page: 'my-example',
			areas: {
				content: <MyExample />,
				edit: <MyExampleEdit />,
			},
			widths: {
				content: undefined,
				edit: 380,
			},
		},
	];

	return pages.find( ( p ) => p.page === page );
};
