/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Content } from './content';

const NotFound = () => {
	return <h1>Not Found</h1>;
};

export const useSettingsLocation = () => {
	const { section, path } = getQuery();
	const page = path.split( '/settings/' ).pop();
	return { section, page };
};

export const getRoute = () => {
	const { section, page } = useSettingsLocation();
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const sections = settingsData[ page ]?.sections;
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];
	const isPage = Object.keys( settingsData ).includes( page );

	if ( isPage ) {
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
};
