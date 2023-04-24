/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DropZone } from '@wordpress/components';
import classnames from 'classnames';
import { createElement, useState } from '@wordpress/element';
import { Icon, trash } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';
import {
	MediaUploader,
	ImageGallery,
	ImageGalleryItem,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

type Image = MediaItem & {
	src: string;
};

export function Edit() {
	const [ images, setImages ] = useEntityProp< MediaItem[] >(
		'postType',
		'product',
		'images'
	);
	const [ isRemovingZoneVisible, setIsRemovingZoneVisible ] =
		useState< boolean >( false );
	const [ isRemoving, setIsRemoving ] = useState< boolean >( false );
	const [ draggedImageId, setDraggedImageId ] = useState< number | null >(
		null
	);

	const blockProps = useBlockProps( {
		className: classnames( {
			'has-images': images.length > 0,
		} ),
	} );

	const toggleRemoveZone = () => {
		setIsRemovingZoneVisible( ! isRemovingZoneVisible );
	};

	const orderImages = ( newOrder: JSX.Element[] ) => {
		const orderedImages = newOrder.map( ( image ) => {
			return images.find(
				( file ) => file.id === parseInt( image?.props?.id, 10 )
			);
		} );
		recordEvent( 'product_images_change_image_order_via_image_gallery' );
		setImages( orderedImages as MediaItem[] );
	};

	const onFileUpload = ( files: MediaItem[] ) => {
		if ( files[ 0 ].id ) {
			recordEvent( 'product_images_add_via_file_upload_area' );
			setImages( [ ...images, ...files ] );
		}
	};

	return (
		<div { ...blockProps }>
			<div className="woocommerce-product-form__image-drop-zone">
				{ isRemovingZoneVisible ? (
					<div className="woocommerce-product-form__remove-image-drop-zone">
						<span>
							<Icon
								icon={ trash }
								size={ 20 }
								className="icon-control"
							/>
							{ __( 'Drop here to remove', 'woocommerce' ) }
						</span>
						<DropZone
							onHTMLDrop={ () => setIsRemoving( true ) }
							onDrop={ () => setIsRemoving( true ) }
							label={ __( 'Drop here to remove', 'woocommerce' ) }
						/>
					</div>
				) : (
					<MediaUploader
						multipleSelect={ true }
						onError={ () => null }
						onFileUploadChange={ onFileUpload }
						onSelect={ ( files ) => {
							const newImages = files.filter(
								( img: Image ) =>
									! images.find(
										( image ) => image.id === img.id
									)
							);
							if ( newImages.length > 0 ) {
								recordEvent(
									'product_images_add_via_media_library'
								);
								setImages( [ ...images, ...newImages ] );
							}
						} }
						onUpload={ ( files ) => {
							if ( files[ 0 ].id ) {
								recordEvent(
									'product_images_add_via_drag_and_drop_upload'
								);
								setImages( [ ...images, ...files ] );
							}
						} }
						label={ '' }
					/>
				) }
			</div>
			<ImageGallery
				onDragStart={ ( event ) => {
					const { id: imageId, dataset } =
						event.target as HTMLElement;
					if ( imageId ) {
						setDraggedImageId( parseInt( imageId, 10 ) );
					} else {
						const index = dataset?.index;
						if ( index ) {
							setDraggedImageId(
								images[ parseInt( index, 10 ) ]?.id
							);
						}
					}
					toggleRemoveZone();
				} }
				onDragEnd={ () => {
					if ( isRemoving && draggedImageId ) {
						recordEvent(
							'product_images_remove_image_button_click'
						);
						setImages(
							images.filter(
								( img ) => img.id !== draggedImageId
							)
						);
						setIsRemoving( false );
						setDraggedImageId( null );
					}
					toggleRemoveZone();
				} }
				onOrderChange={ orderImages }
				onReplace={ ( { replaceIndex, media } ) => {
					if (
						images.find( ( img ) => media.id === img.id ) ===
						undefined
					) {
						images[ replaceIndex ] = media as MediaItem;
						recordEvent(
							'product_images_replace_image_button_click'
						);
						setImages( images );
					}
				} }
				onSelectAsCover={ () =>
					recordEvent(
						'product_images_select_image_as_cover_button_click'
					)
				}
			>
				{ images.map( ( image ) => (
					<ImageGalleryItem
						key={ image.id || image.url }
						alt={ image.alt }
						src={ image.url || image.src }
						id={ `${ image.id }` }
					/>
				) ) }
			</ImageGallery>
		</div>
	);
}
