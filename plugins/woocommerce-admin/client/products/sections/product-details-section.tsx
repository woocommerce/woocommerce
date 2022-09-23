/**
 * External dependencies
 */
import { CheckboxControl, Button, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { cleanForSlug } from '@wordpress/url';
import { EnrichedLabel, useFormContext } from '@woocommerce/components';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-details-section.scss';
import { ProductSectionLayout } from '../layout/product-section-layout';
import { EditProductLinkModal } from '../shared/edit-product-link-modal';
import { getCheckboxProps, getTextControlProps } from './utils';

const PRODUCT_DETAILS_SLUG = 'product-details';

export const ProductDetailsSection: React.FC = () => {
	const { getInputProps, values, touched, errors } =
		useFormContext< Product >();
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

	function doesNameHaveError(): boolean {
		return Boolean( touched.name ) && Boolean( errors.name );
	}

	return (
		<ProductSectionLayout
			title={ __( 'Product info', 'woocommerce' ) }
			description={ __(
				'This info will be displayed on the product page, category pages, social media, and search results.',
				'woocommerce'
			) }
		>
			<div>
				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					name={ `${ PRODUCT_DETAILS_SLUG }-name` }
					placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
					{ ...getTextControlProps( getInputProps( 'name' ) ) }
				/>
				{ values.id && ! doesNameHaveError() && permalinkPrefix && (
					<div className="product-details-section__product-link">
						{ __( 'Product link', 'woocommerce' ) }:&nbsp;
						<a
							href={ values.permalink }
							target="_blank"
							rel="noreferrer"
						>
							{ permalinkPrefix }
							{ values.slug || cleanForSlug( values.name ) }
							{ permalinkSuffix }
						</a>
						<Button
							variant="link"
							onClick={ () =>
								setShowProductLinkEditModal( true )
							}
						>
							{ __( 'Edit', 'woocommerce' ) }
						</Button>
					</div>
				) }
			</div>
			<CheckboxControl
				label={
					<EnrichedLabel
						label={ __( 'Feature this product', 'woocommerce' ) }
						helpDescription={ __(
							'Include this product in a featured section on your website with a widget or shortcode.',
							'woocommerce'
						) }
						moreUrl="https://woocommerce.com/document/woocommerce-shortcodes/#products"
						tooltipLinkCallback={ () =>
							recordEvent( 'add_product_learn_more', {
								category: PRODUCT_DETAILS_SLUG,
							} )
						}
					/>
				}
				{ ...getCheckboxProps( {
					...getInputProps( 'featured' ),
					name: 'featured',
					className: 'product-details-section__feature-checkbox',
				} ) }
			/>
			{ showProductLinkEditModal && (
				<EditProductLinkModal
					permalinkPrefix={ permalinkPrefix || '' }
					permalinkSuffix={ permalinkSuffix || '' }
					product={ values }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
				/>
			) }
		</ProductSectionLayout>
	);
};
