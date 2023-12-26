/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { RadioField } from '../../../components/radio-field';
import { RadioBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< RadioBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { description, options, property, title } = attributes;
	const [ value, setValue ] = useProductEntityProp< string >( property, {
		postType,
		fallbackValue: '',
	} );

	return (
		<div { ...blockProps }>
			<RadioField
				title={ title }
				description={ description }
				selected={ value }
				options={ options }
				onChange={ ( selected ) => setValue( selected || '' ) }
			/>
		</div>
	);
}
