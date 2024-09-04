/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { SectionNav } from './section-nav';
import { Content } from './content';

const LegacySettings = ( { page } ) => {
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const sections = settingsData[ page ]?.sections;
	const { section } = getQuery();
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];

	const title = settingsData[ page ]?.label;

	return (
		<>
			<div className="woocommerce-settings-layout-title">
				<h1>{ title }</h1>
			</div>
			<SectionNav data={ settingsData[ page ] } section={ section }>
				<div className="woocommerce-settings-layout-main">
					<Content data={ contentData } />
				</div>
			</SectionNav>
		</>
	);
};

export default LegacySettings;
