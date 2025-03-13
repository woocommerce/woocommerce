/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { selectImage, removeImage } from './settings-email-image-url-handlers';

type ExistingImageProps = {
	inputId: string;
	setImageUrl: ( imageUrl: string ) => void;
	imageUrl: string;
};

export const ExistingImage: React.FC< ExistingImageProps > = ( {
	inputId,
	imageUrl,
	setImageUrl,
} ) => {
	const [ backgroundColor, setBackgroundColor ] = useState( 'transparent' );

	useEffect( () => {
		const element = jQuery( '#woocommerce_email_body_background_color' );
		if ( ! element.length ) {
			return;
		}
		setBackgroundColor( element.val() as string );

		const handleChange = ( jqEvent: JQuery.Event ) => {
			const event = jqEvent as JQuery.Event & {
				target: HTMLInputElement;
			};
			const value = event.target.value;
			setBackgroundColor( value );
		};

		element.on( 'change', handleChange );

		return () => {
			element.off( 'change', handleChange );
		};
	}, [] );
	return (
		<div>
			<div>
				<button
					style={ { backgroundColor } }
					onClick={ () => selectImage( inputId, setImageUrl ) }
					className="wc-settings-email-select-image"
					type="button"
				>
					<img
						src={ imageUrl }
						className="wc-settings-email-logo-image"
						alt={ __( 'Image preview', 'woocommerce' ) }
					/>
				</button>
			</div>
			<Button
				variant="secondary"
				onClick={ () => selectImage( inputId, setImageUrl ) }
			>
				{ __( 'Change image', 'woocommerce' ) }
			</Button>{ ' ' }
			<Button
				variant="tertiary"
				onClick={ () => removeImage( inputId, setImageUrl ) }
			>
				{ __( 'Remove', 'woocommerce' ) }
			</Button>
		</div>
	);
};
