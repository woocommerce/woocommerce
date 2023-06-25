/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { RadioField } from '../../components/radio-field';
import { RadioBlockAttributes } from './types';

export function Edit( { attributes }: BlockEditProps< RadioBlockAttributes > ) {
	const blockProps = useBlockProps();
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
