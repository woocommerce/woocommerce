/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductFieldSection as ProductFieldSection,
} from '@woocommerce/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import {
	DetailsNameField,
	DetailsCategoriesField,
	DetailsFeatureField,
	DetailsSummaryField,
	DetailsDescriptionField,
	DETAILS_SECTION_ID,
} from './index';
import './product-details-section.scss';

const DetailsSection = () => (
	<>
		<WooProductSectionItem
			id={ DETAILS_SECTION_ID }
			location="tab/general"
			pluginId="core"
			order={ 1 }
		>
			<ProductFieldSection
				id="general/details"
				title={ __( 'Product details', 'woocommerce' ) }
				description={ __(
					'This info will be displayed on the product page, category pages, social media, and search results.',
					'woocommerce'
				) }
			/>
		</WooProductSectionItem>
		<WooProductFieldItem
			id="details/name"
			section={ DETAILS_SECTION_ID }
			pluginId="core"
			order={ 1 }
		>
			<DetailsNameField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="details/categories"
			section={ DETAILS_SECTION_ID }
			pluginId="core"
			order={ 3 }
		>
			<DetailsCategoriesField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="details/feature"
			section={ DETAILS_SECTION_ID }
			pluginId="core"
			order={ 5 }
		>
			<DetailsFeatureField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="details/summary"
			section={ DETAILS_SECTION_ID }
			pluginId="core"
			order={ 7 }
		>
			<DetailsSummaryField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="details/description"
			section={ DETAILS_SECTION_ID }
			pluginId="core"
			order={ 9 }
		>
			<DetailsDescriptionField />
		</WooProductFieldItem>
	</>
);

registerPlugin( 'wc-admin-product-editor-details-section', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <DetailsSection />;
	},
} );
