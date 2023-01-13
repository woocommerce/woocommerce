/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { __experimentalWooProductSectionItem as WooProductSectionItem } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { Section } from './types';

export const Sections: React.FC< { sections: Section[] } > = ( {
	sections,
} ) => {
	return (
		<>
			{ sections.map( ( section ) => (
				<WooProductSectionItem
					key={ section.id }
					id={ section.id }
					location={ section.location }
					pluginId={ section.plugin_id }
					order={ section.order }
				>
					<Card>
						<CardBody>Test</CardBody>
					</Card>
				</WooProductSectionItem>
			) ) }
		</>
	);
};
