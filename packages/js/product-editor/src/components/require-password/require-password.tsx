/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	BaseControl,
	CheckboxControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

import { RequirePasswordProps } from './types';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { TRACKS_SOURCE } from '../../constants';

export function RequirePassword( {
	label,
	postPassword,
	onInputChange,
}: RequirePasswordProps ) {
	const postPasswordId = useInstanceId(
		BaseControl,
		'post_password'
	) as string;

	const { requirePassword } = useDispatch( productEditorUiStore );

	const isPasswordRequired: boolean = useSelect( ( select ) => {
		return select( productEditorUiStore ).isPasswordRequired();
	}, [] );

	return (
		<>
			<CheckboxControl
				label={ label }
				checked={ isPasswordRequired }
				className="wp-block-woocommerce-product-password-fields__field"
				onChange={ ( selected ) => {
					recordEvent( 'product_catalog_require_password', {
						source: TRACKS_SOURCE,
						value: selected,
					} );
					requirePassword( selected );
					if ( ! selected ) {
						onInputChange( '' );
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
						onChange={ onInputChange }
					/>
				</BaseControl>
			) }
		</>
	);
}
