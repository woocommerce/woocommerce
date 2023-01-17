/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	MediaUploader,
	ImageGallery,
	ImageGalleryItem,
} from '@woocommerce/components';
import { CardBody, DropZone } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { useState } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { Icon, trash } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import DragAndDrop from '../../images/drag-and-drop.svg';

type Image = MediaItem & {
	src: string;
};

export const ImagesGalleryField = () => {
	const { getInputProps, setValue } = useFormContext< Product >();
	const images = ( getInputProps( 'images' ).value as Image[] ) || [];
	const [ isRemovingZoneVisible, setIsRemovingZoneVisible ] =
		useState< boolean >( false );
	const [ isRemoving, setIsRemoving ] = useState< boolean >( false );
	const [ draggedImageId, setDraggedImageId ] = useState< number | null >(
		null
	);

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
		setValue( 'images', orderedImages );
	};
	const onFileUpload = ( files: MediaItem[] ) => {
		if ( files[ 0 ].id ) {
			recordEvent( 'product_images_add_via_file_upload_area' );
			setValue( 'images', [ ...images, ...files ] );
		}
	};

	return (
		<>
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
						setValue(
							'images',
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
						images[ replaceIndex ] = media as Image;
						recordEvent(
							'product_images_replace_image_button_click'
						);
						setValue( 'images', images );
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
			<div className="woocommerce-product-form__image-drop-zone">
				{ isRemovingZoneVisible ? (
					<CardBody>
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
								label={ __(
									'Drop here to remove',
									'woocommerce'
								) }
							/>
						</div>
					</CardBody>
				) : (
					<CardBody>
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
									setValue( 'images', [
										...images,
										...newImages,
									] );
								}
							} }
							onUpload={ ( files ) => {
								if ( files[ 0 ].id ) {
									recordEvent(
										'product_images_add_via_drag_and_drop_upload'
									);
									setValue( 'images', [
										...images,
										...files,
									] );
								}
							} }
							label={
								<>
									<img
										src={ DragAndDrop }
										alt={ __( 'Completed', 'woocommerce' ) }
										className="woocommerce-product-form__drag-and-drop-image"
									/>
									<span>
										{ __(
											'Drag images here or click to upload',
											'woocommerce'
										) }
									</span>
								</>
							}
						/>
					</CardBody>
				) }
			</div>
		</>
	);
};
