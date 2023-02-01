/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
} from '@woocommerce/components';
import { __experimentalProductFieldSection as ProductFieldSection } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import {
	DetailsNameField,
	DetailsCategoriesField,
	DetailsFeatureField,
	DetailsSummaryField,
	DetailsDescriptionField,
} from './index';

import { DETAILS_SECTION_ID, PLUGIN_ID, TAB_GENERAL_ID } from '../constants';

import './product-details-section.scss';

export const DetailsSectionFills = () => (
	<>
		<WooProductSectionItem
			id={ DETAILS_SECTION_ID }
			tabs={ [ { name: TAB_GENERAL_ID, order: 1 } ] }
			pluginId={ PLUGIN_ID }
		>
			<ProductFieldSection
				id={ DETAILS_SECTION_ID }
				title={ __( 'Product details', 'woocommerce' ) }
				description={ __(
					'This info will be displayed on the product page, category pages, social media, and search results.',
					'woocommerce'
				) }
			/>
		</WooProductSectionItem>
		<WooProductFieldItem
			id="name"
			sections={ [ { name: DETAILS_SECTION_ID, order: 1 } ] }
			pluginId={ PLUGIN_ID }
		>
			<DetailsNameField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="categories"
			sections={ [ { name: DETAILS_SECTION_ID, order: 3 } ] }
			pluginId={ PLUGIN_ID }
		>
			<DetailsCategoriesField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="feature"
			sections={ [ { name: DETAILS_SECTION_ID, order: 5 } ] }
			pluginId={ PLUGIN_ID }
		>
			<DetailsFeatureField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="summary"
			sections={ [ { name: DETAILS_SECTION_ID, order: 7 } ] }
			pluginId={ PLUGIN_ID }
		>
			<DetailsSummaryField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="description"
			sections={ [ { name: DETAILS_SECTION_ID, order: 9 } ] }
			pluginId={ PLUGIN_ID }
		>
			<DetailsDescriptionField />
		</WooProductFieldItem>
		<WooProductFieldItem
			id="my-field-item"
			sections={ [ { name: DETAILS_SECTION_ID, order: 2 } ] }
			pluginId={ PLUGIN_ID }
		>
			<div className="my-class">Test field</div>
		</WooProductFieldItem>
	</>
);
