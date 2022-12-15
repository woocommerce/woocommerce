/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Link,
	useFormContext,
	MediaUploader,
	ImageGallery,
	ImageGalleryItem,
} from '@woocommerce/components';
import { Card, CardBody, DropZone } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { useState } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import classnames from 'classnames';
import { Icon, trash } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../layout/product-section-layout';
import DragAndDrop from '../images/drag-and-drop.svg';
import './images-section.scss';

type Image = MediaItem & {
	src: string;
};

export const ImagesSection: React.FC = () => {
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
		<ProductSectionLayout
			title={ __( 'Images', 'woocommerce' ) }
			description={
				<>
					<span>
						{ __(
							'For best results, use JPEG files that are 1000 by 1000 pixels or larger.',
							'woocommerce'
						) }
					</span>
					<Link
						className="woocommerce-form-section__header-link"
						href="https://woocommerce.com/posts/fast-high-quality-product-photos/"
						target="_blank"
						type="external"
						onClick={ () => {
							recordEvent( 'prepare_images_help' );
						} }
					>
						{ __( 'How should I prepare images?', 'woocommerce' ) }
					</Link>
				</>
			}
		>
			<Card
				className={ classnames( 'woocommerce-product-form__images', {
					'has-images': images.length > 0,
				} ) }
			>
				<CardBody>
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
								images.find(
									( img ) => media.id === img.id
								) === undefined
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
										{ __(
											'Drop here to remove',
											'woocommerce'
										) }
									</span>
									<DropZone
										onHTMLDrop={ () =>
											setIsRemoving( true )
										}
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
													( image ) =>
														image.id === img.id
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
												alt={ __(
													'Completed',
													'woocommerce'
												) }
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
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
