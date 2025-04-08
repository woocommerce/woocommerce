/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */

import { RequirePasswordBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { RequirePassword } from '../../../components/require-password';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< RequirePasswordBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label } = attributes;

	const [ postPassword, setPostPassword ] = useEntityProp< string >(
		'postType',
		'product',
		'post_password'
	);

	return (
		<div { ...blockProps }>
			<RequirePassword
				label={ label }
				postPassword={ postPassword }
				onInputChange={ setPostPassword }
			/>
		</div>
	);
}
