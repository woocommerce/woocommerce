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
	const [ showRemoveZone, setShowRemoveZone ] = useState( false );
	const [ imageIdToRemove, setImageIdToRemove ] = useState< number >( 0 );

	const getUniqueImages = ( files: Image[] ) => {
		if ( ! files ) {
			return [];
		}
		files.forEach( ( image: Image ) => {
			if (
				image.id &&
				images.find( ( file ) => file.id === image.id ) === undefined
			) {
				images.push( image );
			}
		} );
		setValue( 'images', images );
		return images;
	};

	const toggleRemoveZone = () => {
		setShowRemoveZone( ! showRemoveZone );
	};

	const setImageToRemove = ( image: string ) => {
		const tempNode = document.createElement( 'div' );
		tempNode.innerHTML = image;
		const imageId = tempNode.getElementsByTagName( 'img' )[ 0 ].id;
		setImageIdToRemove( parseInt( imageId, 10 ) );
	};

	const orderImages = ( newOrder: JSX.Element[] ) => {
		const orderedImages = newOrder.map( ( image ) => {
			return images.find(
				( file ) =>
					file.id === parseInt( image?.props?.children?.key, 10 )
			);
		} );
		setValue( 'images', orderedImages );
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
						onDragStart={ toggleRemoveZone }
						onDragEnd={ () => {
							if ( imageIdToRemove ) {
								setValue(
									'images',
									images.filter(
										( img ) => img.id !== imageIdToRemove
									)
								);
								setImageIdToRemove( 0 );
							}
							toggleRemoveZone();
						} }
						onOrderChange={ orderImages }
					>
						{ images.map( ( image ) => (
							<ImageGalleryItem
								key={ image.id }
								alt={ image.alt }
								src={ image.url || image.src }
								id={ `${ image.id }` }
							/>
						) ) }
					</ImageGallery>
					<div className="woocommerce-product-form__image-drop-zone">
						{ showRemoveZone ? (
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
										onHTMLDrop={ setImageToRemove }
										label={ __(
											'Drop here to remove',
											'woocommerce'
										) }
									/>
								</div>
							</CardBody>
						) : (
							<MediaUploader
								onError={ () => null }
								onSelect={ ( file ) =>
									setValue(
										'images',
										getUniqueImages( [ file as Image ] )
									)
								}
								onUpload={ ( files ) =>
									setValue(
										'images',
										getUniqueImages( files as Image[] )
									)
								}
								label={
									<>
										<img
											src={ DragAndDrop }
											alt="Completed"
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
						) }
					</div>
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
