/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	// @ts-expect-error no exported member.
	PluginDocumentSettingPanel,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { useProductTypeSelector } from '../../../shared/stores/product-type-template-state';

function ProductTypeSwitcher() {
	const { productTypes, current, set } = useProductTypeSelector();

	return (
		<SelectControl
			label={ __( 'Type switcher', 'woocommerce' ) }
			value={ current?.slug }
			options={ productTypes.map( ( productType ) => ( {
				label: productType.label,
				value: productType.slug,
			} ) ) }
			onChange={ ( slug ) => {
				set( slug );
				recordEvent(
					'blocks_add_to_cart_with_options_product_type_switched',
					{
						context: 'inspector',
						from: current?.slug,
						to: slug,
					}
				);
			} }
			help={ __(
				'Switch product type to see how the template adapts to each one.',
				'woocommerce'
			) }
		/>
	);
}

export default function ProductTypeSelectorPlugin() {
	const { slug, type } = useSelect( ( select ) => {
		const { slug: currentPostSlug, type: currentPostType } = select(
			'core/editor'
		).getCurrentPost< {
			slug: string;
			type: string;
		} >();

		return {
			slug: currentPostSlug,
			type: currentPostType,
		};
	}, [] );

	const { registeredListeners } = useProductTypeSelector();

	// Only add the panel if the current post is a template and has the Add To Cart block.
	const isPanelVisible =
		type === 'wp_template' &&
		slug === 'single-product' &&
		registeredListeners.length > 0;

	if ( ! isPanelVisible ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="woocommerce/product-type-selector"
			title={ __( 'Product Type', 'woocommerce' ) }
		>
			<ProductTypeSwitcher />
		</PluginDocumentSettingPanel>
	);
}
