/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { Spinner, ToggleControl } from '@wordpress/components';
import { createElement, useMemo } from '@wordpress/element';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../../constants';
import { useMetaboxHiddenProduct } from '../../../hooks/use-metabox-hidden-product';
import { ProductEditorBlockEditProps } from '../../../types';
import { CustomFieldsToggleBlockAttributes } from './types';

const METABOX_HIDDEN_VALUE = 'postcustom';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< CustomFieldsToggleBlockAttributes > ) {
	const { label, _templateBlockId } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const innerBlockProps = useInnerBlocksProps(
		{
			className:
				'wp-block-woocommerce-product-custom-fields-toggle-field__inner-blocks',
		},
		{
			templateLock: 'all',
			renderAppender: false,
		}
	);

	const { isLoading, metaboxhiddenProduct, saveMetaboxhiddenProduct } =
		useMetaboxHiddenProduct();

	const isChecked = useMemo( () => {
		return (
			metaboxhiddenProduct &&
			! metaboxhiddenProduct.some(
				( value ) => value === METABOX_HIDDEN_VALUE
			)
		);
	}, [ metaboxhiddenProduct ] );

	async function handleChange( checked: boolean ) {
		const values = checked
			? metaboxhiddenProduct.filter(
					( value ) => value !== METABOX_HIDDEN_VALUE
			  )
			: [ ...metaboxhiddenProduct, METABOX_HIDDEN_VALUE ];

		recordEvent( 'product_custom_fields_toggle_click', {
			block_id: _templateBlockId,
			source: TRACKS_SOURCE,
			metaboxhidden_product: values,
		} );

		await saveMetaboxhiddenProduct( values );
	}

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-custom-fields-toggle-field__content">
				<ToggleControl
					label={ label }
					checked={ isChecked }
					disabled={ isLoading }
					onChange={ handleChange }
				/>

				{ isLoading && <Spinner /> }
			</div>

			{ isChecked && <div { ...innerBlockProps } /> }
		</div>
	);
}
