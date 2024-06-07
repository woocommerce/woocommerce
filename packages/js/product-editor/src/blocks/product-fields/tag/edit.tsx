/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { createElement } from '@wordpress/element';
import { BlockAttributes } from '@wordpress/blocks';
import { BaseControl } from '@wordpress/components';
import { ProductTag } from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { TagField } from '../../../components/tags-field';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
	context: { postType, isInSelectedTab },
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { name, label, placeholder } = attributes;
	const [ tags, setTags ] = useEntityProp<
		Pick< ProductTag, 'id' | 'name' >[]
	>( 'postType', postType || 'product', name || 'tags' );

	const tagFieldId = useInstanceId( BaseControl, 'tag-field' ) as string;

	return (
		<div { ...blockProps }>
			{
				<TagField
					id={ tagFieldId }
					isVisible={ isInSelectedTab }
					label={ label || __( 'Tags', 'woocommerce' ) }
					placeholder={
						placeholder ||
						__( 'Search or create tags…', 'woocommerce' )
					}
					onChange={ setTags }
					value={ tags || [] }
				/>
			}
		</div>
	);
}
