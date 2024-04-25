/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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

	const [ isPasswordRequired, setPasswordRequired ] = useState(
		Boolean( postPassword )
	);

	useEffect( () => {
		if ( ! isPasswordRequired && postPassword !== '' ) {
			setPasswordRequired( true );
		}
	}, [ postPassword ] );

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
					setPasswordRequired( selected );
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
