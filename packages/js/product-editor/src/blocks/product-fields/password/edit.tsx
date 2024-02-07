/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	BaseControl,
	CheckboxControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

import { RequirePasswordBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { store as productEditorUiStore } from '../../../store/product-editor-ui';

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

	const postPasswordId = useInstanceId(
		BaseControl,
		'post_password'
	) as string;

	const { requirePassword } = useDispatch( productEditorUiStore );

	const isPasswordRequired: boolean = useSelect( ( select ) => {
		return select( productEditorUiStore ).isPasswordRequired();
	}, [] );

	return (
		<div { ...blockProps }>
			<CheckboxControl
				label={ label }
				checked={ isPasswordRequired }
				className="wp-block-woocommerce-product-password-fields__field"
				onChange={ ( selected ) => {
					requirePassword( selected );
					if ( ! selected ) {
						setPostPassword( '' );
					}
				} }
			/>
			{ isPasswordRequired && (
				<BaseControl
					id={ postPasswordId }
					label={ __( 'Password', 'woocommerce' ) }
				>
					<InputControl
						id={ postPasswordId }
						value={ postPassword }
						onChange={ setPostPassword }
					/>
				</BaseControl>
			) }
		</div>
	);
}
