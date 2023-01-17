/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalProductFieldSection as ProductFieldSection,
} from '@woocommerce/components';
import { ProductFormSection } from '@woocommerce/data';

export const Sections: React.FC< { sections: ProductFormSection[] } > = ( {
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
					<ProductFieldSection
						id={ section.id }
						title={ section.title }
						description={ section.description }
					/>
				</WooProductSectionItem>
			) ) }
		</>
	);
};
