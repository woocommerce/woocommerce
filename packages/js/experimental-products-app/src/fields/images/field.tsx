/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useCallback, useEffect, useState } from '@wordpress/element';
import { Fieldset, IconButton } from '@wordpress/ui';
import clsx from 'clsx';
import type { Field } from '@wordpress/dataviews';
import { upload, closeSmall, dragHandle } from '@wordpress/icons';
import { MediaUpload } from '@wordpress/media-utils';
import { DragDropProvider, type DragEndEvent } from '@dnd-kit/react';
import { isSortable, useSortable } from '@dnd-kit/react/sortable';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type Attachment = {
	id: number;
	url?: string;
	alt?: string;
	title?: string;
	date?: string;
	date_gmt?: string;
	modified?: string;
	modified_gmt?: string;
	sizes?: Record< string, { source_url?: string; url?: string } >;
	media_details?: {
		sizes?: Record< string, { source_url?: string; url?: string } >;
	};
};

const toProductImage = (
	att: Attachment
): ProductEntityRecord[ 'images' ][ number ] => {
	const sizes = att.media_details?.sizes || att.sizes;
	const thumbnailUrl =
		sizes?.woocommerce_thumbnail?.source_url ||
		sizes?.woocommerce_thumbnail?.url ||
		sizes?.thumbnail?.source_url ||
		sizes?.thumbnail?.url ||
		'';

	return {
		id: att.id,
		src: att.url || '',
		alt: att.alt || '',
		name: att.title || '',
		thumbnail: thumbnailUrl,
		date_created: att.date || '',
		date_created_gmt: att.date_gmt || '',
		date_modified: att.modified || '',
		date_modified_gmt: att.modified_gmt || '',
	};
};

interface SortableImageProps {
	image: ProductEntityRecord[ 'images' ][ number ];
	index: number;
	alt: string;
	onRemove: () => void;
	showDragHandle: boolean;
}

function SortableImage( {
	image,
	index,
	alt,
	onRemove,
	showDragHandle,
}: SortableImageProps ) {
	const previewSrc = image.thumbnail || image.src;
	const { ref, handleRef, isDragging } = useSortable( {
		id: image.id,
		index,
		disabled: ! showDragHandle,
	} );

	const stopPropagation = useCallback( ( event: React.SyntheticEvent ) => {
		event.stopPropagation();
	}, [] );

	return (
		<div
			ref={ ref }
			role="group"
			aria-label={ image.name }
			className={ clsx( 'woocommerce-fields-controls__image-wrapper', {
				'is-dragging': isDragging,
			} ) }
		>
			<img className="product-image" src={ previewSrc } alt={ alt } />
			<div className="woocommerce-fields-controls__image-overlay" />
			{ showDragHandle && (
				<div className="woocommerce-fields-controls__image-drag-handle-container">
					<IconButton
						ref={ handleRef }
						icon={ dragHandle }
						label={ __( 'Drag to reorder', 'woocommerce' ) }
						className="woocommerce-fields-controls__image-drag-handle"
						variant="minimal"
						size="small"
						tone="neutral"
					/>
				</div>
			) }
			{ ! isDragging && (
				<IconButton
					icon={ closeSmall }
					label={ __( 'Remove image', 'woocommerce' ) }
					onClick={ onRemove }
					onPointerDown={ stopPropagation }
					onKeyDown={ stopPropagation }
					className="woocommerce-fields-controls__image-remove-button"
					variant="minimal"
					size="small"
					tone="neutral"
				/>
			) }
		</div>
	);
}

const fieldDefinition = {
	label: __( 'Images', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	render: ( { item } ) => {
		const featuredImage = item.images?.at( 0 );

		if ( ! featuredImage ) {
			return null;
		}

		return (
			<img
				className="product-image"
				src={ featuredImage.src }
				alt={ featuredImage.alt || featuredImage.name || item.name }
				style={ {
					objectFit: 'cover',
					borderRadius: 8,
				} }
			/>
		);
	},
	Edit: ( { data, onChange } ) => {
		const isVariation = data.type === 'variation';
		const dataImages = useMemo( () => {
			const nextImages = data.images ?? [];

			return isVariation ? nextImages.slice( 0, 1 ) : nextImages;
		}, [ data.images, isVariation ] );
		const [ images, setImages ] = useState( dataImages );
		const uploadLabel = isVariation
			? __( 'Add image', 'woocommerce' )
			: __( 'Add images', 'woocommerce' );

		useEffect( () => {
			setImages( dataImages );
		}, [ dataImages ] );

		const commitImages = useCallback(
			( nextImages: ProductEntityRecord[ 'images' ] ) => {
				setImages( nextImages );
				onChange( {
					images: nextImages,
				} );
			},
			[ onChange ]
		);

		const handleSelect = useCallback(
			( selection: Attachment | Attachment[] ) => {
				const attachments = Array.isArray( selection )
					? selection
					: [ selection ];
				const mappedImages = attachments.map( toProductImage );

				commitImages(
					isVariation ? mappedImages.slice( 0, 1 ) : mappedImages
				);
			},
			[ commitImages, isVariation ]
		);

		const handleRemoveImage = useCallback(
			( imageToRemove: ProductEntityRecord[ 'images' ][ number ] ) => {
				commitImages(
					images.filter( ( image ) => image.id !== imageToRemove.id )
				);
			},
			[ commitImages, images ]
		);

		const handleDragEnd = useCallback(
			( event: DragEndEvent ) => {
				if ( event.canceled ) {
					return;
				}

				const { source } = event.operation;

				if ( ! isSortable( source ) ) {
					return;
				}

				const { initialIndex, index } = source;

				if (
					initialIndex === index ||
					initialIndex < 0 ||
					index < 0 ||
					initialIndex >= images.length ||
					index >= images.length
				) {
					return;
				}

				const reorderedImages = [ ...images ];
				const [ movedImage ] = reorderedImages.splice(
					initialIndex,
					1
				);
				reorderedImages.splice( index, 0, movedImage );

				commitImages( reorderedImages );
			},
			[ commitImages, images ]
		);

		const removeCallbacks = useMemo( () => {
			const callbacks = new Map< number | string, () => void >();
			images.forEach( ( image ) => {
				callbacks.set( image.id, () => handleRemoveImage( image ) );
			} );
			return callbacks;
		}, [ images, handleRemoveImage ] );

		return (
			<Fieldset.Root>
				<DragDropProvider onDragEnd={ handleDragEnd }>
					<div className="woocommerce-fields-control__featured-image">
						<div className="woocommerce-fields-controls__featured-image-uploaded-images">
							{ images.map( ( image, index ) => {
								const onRemove = removeCallbacks.get(
									image.id
								);

								if ( ! onRemove ) {
									return null;
								}

								return (
									<SortableImage
										key={ image.id }
										image={ image }
										index={ index }
										alt={ image.alt || data.name }
										onRemove={ onRemove }
										showDragHandle={
											! isVariation && images.length > 1
										}
									/>
								);
							} ) }
						</div>
						<div className="woocommerce-fields-control__featured-image-actions">
							<MediaUpload
								allowedTypes={ [ 'image' ] }
								multiple={ isVariation ? false : 'add' }
								onSelect={ handleSelect }
								title={ uploadLabel }
								value={ images.map( ( image ) => image.id ) }
								render={ ( { open }: { open: () => void } ) => (
									<IconButton
										variant="minimal"
										icon={ upload }
										label={ uploadLabel }
										onClick={ open }
									/>
								) }
							/>
						</div>
					</div>
				</DragDropProvider>
			</Fieldset.Root>
		);
	},
};
