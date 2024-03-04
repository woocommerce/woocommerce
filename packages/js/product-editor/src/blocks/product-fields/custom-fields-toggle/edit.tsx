/**
 * External dependencies
 */
import { Spinner, ToggleControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';

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
	const { isLoading, metaboxhiddenProduct, saveMetaboxhiddenProduct } =
		useMetaboxHiddenProduct();

	function isChecked() {
		return (
			metaboxhiddenProduct &&
			! metaboxhiddenProduct.some(
				( value ) => value === METABOX_HIDDEN_VALUE
			)
		);
	}

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
			<ToggleControl
				label={ label }
				checked={ isChecked() }
				disabled={ isLoading }
				onChange={ handleChange }
			/>

			{ isLoading && <Spinner /> }
		</div>
	);
}
