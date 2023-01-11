/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductFieldSection as ProductFieldSection,
	useFormContext,
} from '@woocommerce/components';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';
import {
	PRODUCTS_STORE_NAME,
	WCDataSelector,
	Product,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../shared/edit-product-link-modal';
import {
	DetailsNameField,
	DetailsCategoriesField,
	DetailsFeatureField,
	DetailsSummaryField,
	DetailsDescriptionField,
	DETAILS_SECTION_ID,
} from './index';
import './product-details-section.scss';

const DetailsSection = () => {
	const { values } = useFormContext< Product >();
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const { permalinkPrefix, permalinkSuffix } = useSelect(
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( values.id ) {
				const parts = getPermalinkParts( values.id );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

	return (
		<>
			<WooProductSectionItem
				id={ DETAILS_SECTION_ID }
				location="tab/general"
				pluginId="core"
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
			>
				<DetailsNameField
					setShowProductLinkEditModal={ setShowProductLinkEditModal }
				/>
			</WooProductFieldItem>
			<WooProductFieldItem
				id="details/categories"
				section={ DETAILS_SECTION_ID }
				pluginId="core"
			>
				<DetailsCategoriesField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="details/feature"
				section={ DETAILS_SECTION_ID }
				pluginId="core"
			>
				<DetailsFeatureField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="details/summary"
				section={ DETAILS_SECTION_ID }
				pluginId="core"
			>
				<DetailsSummaryField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="details/description"
				section={ DETAILS_SECTION_ID }
				pluginId="core"
			>
				<DetailsDescriptionField />
			</WooProductFieldItem>
			{ showProductLinkEditModal && (
				<EditProductLinkModal
					permalinkPrefix={ permalinkPrefix || '' }
					permalinkSuffix={ permalinkSuffix || '' }
					product={ values }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
				/>
			) }
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-details-section', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <DetailsSection />;
	},
} );
