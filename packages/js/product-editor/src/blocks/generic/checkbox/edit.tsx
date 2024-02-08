/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { CheckboxBlockAttributes } from './types';
import { Checkbox } from '../../../components/checkbox-control';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< CheckboxBlockAttributes > ) {
	const {
		property,
		title,
		label,
		tooltip,
		checkedValue,
		uncheckedValue,
		disabled,
	} = attributes;

	const blockProps = useWooBlockProps( attributes );

	const [ value, setValue ] = useProductEntityProp< boolean | string | null >(
		property,
		{
			postType,
			fallbackValue: false,
		}
	);

	return (
		<div { ...blockProps }>
			<Checkbox
				value={ value || false }
				onChange={ setValue }
				label={ label || '' }
				title={ title }
				tooltip={ tooltip }
				checkedValue={ checkedValue }
				uncheckedValue={ uncheckedValue }
				disabled={ disabled }
			/>
		</div>
	);
}
