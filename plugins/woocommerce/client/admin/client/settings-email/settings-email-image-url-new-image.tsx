/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import imageUploadIcon from './icon-image-upload.svg';
import { selectImage } from './settings-email-image-url-handlers';

type NewImageProps = {
	inputId: string;
	setImageUrl: ( imageUrl: string ) => void;
};

export const NewImage: React.FC< NewImageProps > = ( {
	inputId,
	setImageUrl,
} ) => {
	return (
		<div>
			<button
				onClick={ () => selectImage( inputId, setImageUrl ) }
				className="wc-settings-email-select-image"
				type="button"
			>
				<img
					src={ imageUploadIcon }
					width="24"
					height="24"
					alt={ __( 'Image upload icon', 'woocommerce' ) }
					className="wc-settings-email-select-image-icon"
				/>
			</button>
		</div>
	);
};
