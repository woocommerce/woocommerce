/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductFieldSection as ProductFieldSection,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ImagesGalleryField } from './index';
import { IMAGES_SECTION_ID, TAB_GENERAL_ID, PLUGIN_ID } from '../constants';

import './images-section.scss';

export const ImagesSectionFills = () => (
	<>
		<WooProductSectionItem
			id={ IMAGES_SECTION_ID }
			tabs={ [ { name: TAB_GENERAL_ID, order: 3 } ] }
			pluginId={ PLUGIN_ID }
		>
			<ProductFieldSection
				id={ IMAGES_SECTION_ID }
				title={ __( 'Images', 'woocommerce' ) }
				description={
					<>
						<span>
							{ __(
								'For best results, use JPEG files that are 1000 by 1000 pixels or larger.',
								'woocommerce'
							) }
						</span>
						<Link
							className="woocommerce-form-section__header-link"
							href="https://woo.com/posts/fast-high-quality-product-photos/"
							target="_blank"
							type="external"
							onClick={ () => {
								recordEvent( 'prepare_images_help' );
							} }
						>
							{ __(
								'How should I prepare images?',
								'woocommerce'
							) }
						</Link>
					</>
				}
			/>
		</WooProductSectionItem>
		<WooProductFieldItem
			id="gallery"
			sections={ [ { name: IMAGES_SECTION_ID, order: 1 } ] }
			pluginId={ PLUGIN_ID }
		>
			<ImagesGalleryField />
		</WooProductFieldItem>
	</>
);
