/**
 * External dependencies
 */
import {
	__experimentalProductFieldSection as ProductFieldSection,
	__experimentalWooProductSectionItem as WooProductSectionItem,
} from '@woocommerce/product-editor';
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
					tabs={ [
						{ name: section.location, order: section.order },
					] }
					pluginId={ section.plugin_id }
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
