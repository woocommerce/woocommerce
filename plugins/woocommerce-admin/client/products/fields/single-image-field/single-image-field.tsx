/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	MediaUploader,
	ImageGallery,
	ImageGalleryItem,
} from '@woocommerce/components';
import { MediaItem } from '@wordpress/media-utils';
import uniqueId from 'lodash/uniqueId';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './single-image-field.scss';

export function SingleImageField( {
	id,
	label,
	value,
	className,
	onChange,
	...props
}: SingleImageFieldProps ) {
	const fieldId = id ?? uniqueId();

	function handleChange( image?: MediaItem ) {
		if ( typeof onChange === 'function' ) {
			onChange( image );
		}
	}

	return (
		<div
			{ ...props }
			className={ classNames(
				'woocommerce-single-image-field',
				className
			) }
		>
			<label
				htmlFor={ fieldId }
				className="components-base-control__label woocommerce-single-image-field__label"
			>
				{ label }
			</label>

			{ value ? (
				<div
					id={ fieldId }
					className="woocommerce-single-image-field__gallery"
					tabIndex={ -1 }
					role="region"
				>
					<ImageGallery
						onReplace={ ( { media } ) => handleChange( media ) }
						onRemove={ () => handleChange( undefined ) }
					>
						<ImageGalleryItem
							key={ value.id }
							id={ String( value.id ) }
							alt={ value.alt }
							src={ value.url }
						/>
					</ImageGallery>
				</div>
			) : (
				<div
					id={ fieldId }
					className="woocommerce-single-image-field__drop-zone"
					tabIndex={ -1 }
					role="region"
				>
					<MediaUploader
						onError={ () => null }
						onSelect={ ( image ) =>
							handleChange( image as MediaItem )
						}
						onUpload={ ( [ image ] ) => handleChange( image ) }
						onFileUploadChange={ ( [ image ] ) =>
							handleChange( image )
						}
						label={ __(
							'Drag image here or click to upload',
							'woocommerce'
						) }
						buttonText={ __( 'Choose image', 'woocommerce' ) }
					/>
				</div>
			) }
		</div>
	);
}

export type SingleImageFieldProps = Omit<
	React.DetailedHTMLProps<
		React.HTMLAttributes< HTMLDivElement >,
		HTMLDivElement
	>,
	'onChange'
> & {
	label: string;
	value?: MediaItem;
	onChange?( value?: MediaItem ): void;
};
