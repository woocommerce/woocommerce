/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from 'react';
import { MediaUpload } from '@wordpress/media-utils';
import { Button } from '@wordpress/components';

const DEFAULT_ALLOWED_MEDIA_TYPES = [ 'image' ];

type MediaUploaderProps = {
	MediaUploadComponent?: < T extends boolean = false >(
		props: MediaUpload.Props< T >
	) => JSX.Element;
	allowedMediaTypes?: string[];
	buttonText?: string;
	label?: string;
	onSelect: ( value: { id: number } & { [ k: string ]: any } ) => void;
};

export const MediaUploader = ( {
	allowedMediaTypes = DEFAULT_ALLOWED_MEDIA_TYPES,
	buttonText = __( 'Choose images', 'woocommerce' ),
	label = __( 'Drag images here or click to upload', 'woocommerce' ),
	MediaUploadComponent = MediaUpload,
	onSelect,
}: MediaUploaderProps ) => {
	return (
		<div className="woocommerce-media-uploader">
			<div className="woocommerce-media-uploader__label">{ label }</div>
			<MediaUploadComponent
				onSelect={ onSelect }
				allowedTypes={ allowedMediaTypes }
				render={ ( { open } ) => (
					<Button onClick={ open }>{ buttonText }</Button>
				) }
			/>
		</div>
	);
};
