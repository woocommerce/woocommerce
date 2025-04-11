/**
 * External dependencies
 */
import { DragEvent } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { BlockAttributes } from '@wordpress/blocks';
import { DropZone } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import classnames from 'classnames';
import { createElement, useState } from '@wordpress/element';
import { Icon, trash } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	MediaUploader,
	MediaUploaderErrorCallback,
	ImageGallery,
	ImageGalleryItem,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { PlaceHolder } from './place-holder';
import { SectionActions } from '../../../components/block-slot-fill';
import { mapUploadImageToImage } from '../../../utils/map-upload-image-to-image';

type UploadImage = {
	id?: number;
} & Record< string, string >;

export interface Image {
	id: number;
	src: string;
	name: string;
	alt: string;
}

export function ImageBlockEdit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const { property, multiple } = attributes;
	const [ propertyValue, setPropertyValue ] = useEntityProp<
		Image | Image[] | null
	>( 'postType', context.postType, property );
	const [ isRemovingZoneVisible, setIsRemovingZoneVisible ] =
		useState< boolean >( false );
	const [ isRemoving, setIsRemoving ] = useState< boolean >( false );
	const [ draggedImageId, setDraggedImageId ] = useState< number | null >(
		null
	);

	const blockProps = useWooBlockProps( attributes, {
		className: classnames( {
			'has-images': Array.isArray( propertyValue )
				? propertyValue.length > 0
				: Boolean( propertyValue ),
		} ),
	} );

	const { createErrorNotice } = useDispatch( 'core/notices' );

	function orderImages( newOrder: JSX.Element[] ) {
		if ( Array.isArray( propertyValue ) ) {
			const memoIds = propertyValue.reduce< Record< string, Image > >(
				( current, item ) => ( {
					...current,
					[ `${ item.id }` ]: item,
				} ),
				{}
			);
			const orderedImages = newOrder
				.filter( ( image ) => image?.props?.id in memoIds )
				.map( ( image ) => memoIds[ image?.props?.id ] );

			recordEvent(
				'product_images_change_image_order_via_image_gallery'
			);
			setPropertyValue( orderedImages );
		}
	}

	function uploadHandler( eventName: string ) {
		return function handleFileUpload( upload: MediaItem | MediaItem[] ) {
			recordEvent( eventName );

			if ( Array.isArray( upload ) ) {
				const images: Image[] = upload
					.filter( ( image ) => image.id )
					.map( ( image ) => ( {
						id: image.id,
						name: image.title,
						src: image.url,
						alt: image.alt,
					} ) );
				if ( upload[ 0 ]?.id ) {
					setPropertyValue( [
						...( propertyValue as Image[] ),
						...images,
					] );
				}
			} else if ( upload.id ) {
				setPropertyValue( mapUploadImageToImage( upload ) );
			}
		};
	}

	function handleSelect( selection: UploadImage | UploadImage[] ) {
		recordEvent( 'product_images_add_via_media_library' );

		if ( Array.isArray( selection ) ) {
			const images = selection
				.map( mapUploadImageToImage )
				.filter( ( image ) => image !== null );

			setPropertyValue( images as Image[] );
		} else {
			setPropertyValue( mapUploadImageToImage( selection ) );
		}
	}

	function handleDragStart( event: DragEvent< HTMLDivElement > ) {
		if ( Array.isArray( propertyValue ) ) {
			const { id: imageId, dataset } = event.target as HTMLElement;
			if ( imageId ) {
				setDraggedImageId( parseInt( imageId, 10 ) );
			} else if ( dataset?.index ) {
				const index = parseInt( dataset.index, 10 );
				setDraggedImageId( propertyValue[ index ]?.id ?? null );
			}
			setIsRemovingZoneVisible( ( current ) => ! current );
		}
	}

	function handleDragEnd() {
		if ( Array.isArray( propertyValue ) ) {
			if ( isRemoving && draggedImageId ) {
				recordEvent( 'product_images_remove_image_button_click' );
				setPropertyValue(
					propertyValue.filter( ( img ) => img.id !== draggedImageId )
				);
				setIsRemoving( false );
				setDraggedImageId( null );
			}
			setIsRemovingZoneVisible( ( current ) => ! current );
		}
	}

	function handleReplace( {
		replaceIndex,
		media,
	}: {
		replaceIndex: number;
		media: UploadImage;
	} ) {
		recordEvent( 'product_images_replace_image_button_click' );

		if ( Array.isArray( propertyValue ) ) {
			// Ignore the media if it is replaced by itseft.
			if ( propertyValue.some( ( img ) => media.id === img.id ) ) {
				return;
			}

			const image = mapUploadImageToImage( media );
			if ( image ) {
				const newImages = [ ...propertyValue ];
				newImages[ replaceIndex ] = image;
				setPropertyValue( newImages );
			}
		} else {
			setPropertyValue( mapUploadImageToImage( media ) );
		}
	}

	function handleRemove( { removedItem }: { removedItem: JSX.Element } ) {
		recordEvent( 'product_images_remove_image_button_click' );

		if ( Array.isArray( propertyValue ) ) {
			const remainingImages = propertyValue.filter(
				( image ) => String( image.id ) !== removedItem.props.id
			);
			setPropertyValue( remainingImages );
		} else {
			setPropertyValue( null );
		}
	}

	const handleMediaUploaderError: MediaUploaderErrorCallback = function (
		error
	) {
		createErrorNotice(
			sprintf(
				/* translators: %1$s is a line break, %2$s is the detailed error message */
				__( 'Error uploading image:%1$s%2$s', 'woocommerce' ),
				'\n',
				error.message
			)
		);
	};

	const isImageGalleryVisible =
		propertyValue !== null &&
		( ! Array.isArray( propertyValue ) || propertyValue.length > 0 );

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
					<SectionActions>
						<div className="woocommerce-product-form__media-uploader">
							<MediaUploader
								value={
									Array.isArray( propertyValue )
										? propertyValue.map( ( { id } ) => id )
										: propertyValue?.id ?? undefined
								}
								multipleSelect={ multiple ? 'add' : false }
								maxUploadFileSize={
									window.productBlockEditorSettings
										?.maxUploadFileSize
								}
								onError={ handleMediaUploaderError }
								onFileUploadChange={ uploadHandler(
									'product_images_add_via_file_upload_area'
								) }
								onMediaGalleryOpen={ () => {
									recordEvent(
										'product_images_media_gallery_open'
									);
								} }
								onSelect={ handleSelect }
								onUpload={ uploadHandler(
									'product_images_add_via_drag_and_drop_upload'
								) }
								label={ '' }
								buttonText={ __(
									'Choose an image',
									'woocommerce'
								) }
							/>
						</div>
					</SectionActions>
				) }
			</div>
			{ isImageGalleryVisible ? (
				<ImageGallery
					allowDragging={ false }
					onDragStart={ handleDragStart }
					onDragEnd={ handleDragEnd }
					onOrderChange={ orderImages }
					onReplace={ handleReplace }
					onRemove={ handleRemove }
					onSelectAsCover={ () =>
						recordEvent(
							'product_images_select_image_as_cover_button_click'
						)
					}
				>
					{ ( Array.isArray( propertyValue )
						? propertyValue
						: [ propertyValue ]
					).map( ( image, index ) => (
						<ImageGalleryItem
							key={ image.id }
							alt={ image.alt }
							src={ image.src }
							id={ `${ image.id }` }
							isCover={ multiple && index === 0 }
						/>
					) ) }
				</ImageGallery>
			) : (
				<PlaceHolder multiple={ multiple } />
			) }
		</div>
	);
}
