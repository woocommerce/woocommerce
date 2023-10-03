/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { RadioField } from '../../components/radio-field';
import { RadioBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< RadioBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { description, options, property, title } = attributes;
	const [ value, setValue ] = useEntityProp< string >(
		'postType',
		'product',
		property
	);

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
