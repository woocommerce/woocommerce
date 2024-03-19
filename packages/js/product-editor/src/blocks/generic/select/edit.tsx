/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { SelectControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import type { ProductEditorBlockEditProps } from '../../../types';
import type { SelectBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< SelectBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const { property, label, placeholder, help, disabled, options, multiple } =
		attributes;

	const [ value, setValue ] = useProductEntityProp< string | string[] >(
		property,
		{
			postType,
			fallbackValue: '',
		}
	);

	function renderHelp() {
		if ( help ) {
			return <span dangerouslySetInnerHTML={ sanitizeHTML( help ) } />;
		}
	}

	return (
		<div { ...blockProps }>
			<SelectControl
				value={ value }
				disabled={ disabled }
				label={ label }
				onChange={ setValue }
				help={ renderHelp() }
				placeholder={ placeholder }
				options={ options }
				multiple={ multiple as never }
			/>
		</div>
	);
}
