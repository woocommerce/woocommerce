/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useCallback, useState } from '@wordpress/element';
import { IconButton } from '@wordpress/ui';
import clsx from 'clsx';
import type { Field } from '@wordpress/dataviews';
import { upload, closeSmall, dragHandle } from '@wordpress/icons';

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
	media_details?: {
		sizes?: Record< string, { source_url: string } >;
	};
};

declare global {
	interface Window {
		wp?: {
			media?: ( options: Record< string, unknown > ) => {
				on: ( event: 'select', callback: () => void ) => void;
				open: () => void;
				state: () => {
					get: ( key: string ) => {
						toJSON: () => Attachment[];
					};
				};
			};
		};
	}
}

const toProductImage = (
	att: Attachment
): ProductEntityRecord[ 'images' ][ number ] => {
	const sizes = att.media_details?.sizes as
		| Record< string, { source_url: string } >
		| undefined;
	const thumbnailUrl =
		sizes?.woocommerce_thumbnail?.source_url ||
		sizes?.thumbnail?.source_url ||
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

function moveItem< T >( items: T[], fromIndex: number, toIndex: number ) {
	const nextItems = [ ...items ];
	const [ movedItem ] = nextItems.splice( fromIndex, 1 );
	nextItems.splice( toIndex, 0, movedItem );
	return nextItems;
}

interface SortableImageProps {
	image: ProductEntityRecord[ 'images' ][ number ];
	alt: string;
	onRemove: () => void;
	isFeatured: boolean;
	showDragHandle: boolean;
	isDragging: boolean;
	onDragStart: ( id: number ) => void;
	onDragEnd: () => void;
	onDropOn: ( id: number ) => void;
}

function SortableImage( {
	image,
	alt,
	onRemove,
	isFeatured,
	showDragHandle,
	isDragging,
	onDragStart,
	onDragEnd,
	onDropOn,
}: SortableImageProps ) {
	const previewSrc = image.thumbnail || image.src;

	const stopPropagation = useCallback( ( event: React.SyntheticEvent ) => {
		event.stopPropagation();
	}, [] );

	return (
		<div
			role="group"
			aria-label={ image.name }
			onDragOver={ ( event ) => {
				if ( showDragHandle ) {
					event.preventDefault();
				}
			} }
			onDrop={ ( event ) => {
				event.preventDefault();
				onDropOn( image.id );
			} }
			className={ clsx( 'woocommerce-fields-controls__image-wrapper', {
				'is-dragging': isDragging,
				'is-featured': isFeatured,
			} ) }
		>
			<img className="product-image" src={ previewSrc } alt={ alt } />
			<div className="woocommerce-fields-controls__image-overlay" />
			{ showDragHandle && (
				<div className="woocommerce-fields-controls__image-drag-handle-container">
					<IconButton
						draggable
						icon={ dragHandle }
						label={ __( 'Drag to reorder', 'woocommerce' ) }
						className="woocommerce-fields-controls__image-drag-handle"
						variant="minimal"
						size="small"
						tone="neutral"
						onDragStart={ () => onDragStart( image.id ) }
						onDragEnd={ onDragEnd }
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
	label: __( 'Featured Image', 'woocommerce' ),
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
		const images = useMemo( () => data.images ?? [], [ data.images ] );
		const [ draggedImageId, setDraggedImageId ] = useState< number | null >(
			null
		);

		const handleSelect = useCallback(
			( selection: Attachment | Attachment[] ) => {
				const attachments = Array.isArray( selection )
					? selection
					: [ selection ];
				const mappedImages = attachments.map( toProductImage );
				const selectedIds = new Set(
					mappedImages.map( ( image ) => image.id )
				);
				const existingImages = images.filter( ( image ) =>
					selectedIds.has( image.id )
				);
				const existingIds = new Set(
					images.map( ( image ) => image.id )
				);
				const newImages = mappedImages.filter(
					( image ) => ! existingIds.has( image.id )
				);

				onChange( {
					images: [ ...existingImages, ...newImages ],
				} );
			},
			[ images, onChange ]
		);

		const handleOpenMediaLibrary = useCallback( () => {
			const media = window.wp?.media;

			if ( ! media ) {
				return;
			}

			const frame = media( {
				title: __( 'Add images', 'woocommerce' ),
				button: {
					text: __( 'Use images', 'woocommerce' ),
				},
				multiple: true,
				library: {
					type: 'image',
				},
			} );

			frame.on( 'select', () => {
				handleSelect( frame.state().get( 'selection' ).toJSON() );
			} );

			frame.open();
		}, [ handleSelect ] );

		const handleRemoveImage = useCallback(
			( imageToRemove: ProductEntityRecord[ 'images' ][ number ] ) => {
				onChange( {
					images: images.filter(
						( image ) => image.id !== imageToRemove.id
					),
				} );
			},
			[ images, onChange ]
		);

		const handleDropOnImage = useCallback(
			( targetImageId: number ) => {
				if (
					draggedImageId === null ||
					draggedImageId === targetImageId
				) {
					setDraggedImageId( null );
					return;
				}

				const sourceIndex = images.findIndex(
					( image ) => image.id === draggedImageId
				);
				const targetIndex = images.findIndex(
					( image ) => image.id === targetImageId
				);

				if ( sourceIndex < 0 || targetIndex < 0 ) {
					setDraggedImageId( null );
					return;
				}

				onChange( {
					images: moveItem( images, sourceIndex, targetIndex ),
				} );
				setDraggedImageId( null );
			},
			[ draggedImageId, images, onChange ]
		);

		const removeCallbacks = useMemo( () => {
			const callbacks = new Map< number | string, () => void >();
			images.forEach( ( image ) => {
				callbacks.set( image.id, () => handleRemoveImage( image ) );
			} );
			return callbacks;
		}, [ images, handleRemoveImage ] );

		return (
			<div className="woocommerce-fields-control__featured-image">
				<div className="woocommerce-fields-controls__featured-image-uploaded-images">
					{ images.map( ( image, index ) => {
						const onRemove = removeCallbacks.get( image.id );

						if ( ! onRemove ) {
							return null;
						}

						return (
							<SortableImage
								key={ image.id }
								image={ image }
								alt={ image.alt || data.name }
								onRemove={ onRemove }
								isFeatured={ index === 0 }
								showDragHandle={ images.length > 1 }
								isDragging={ draggedImageId === image.id }
								onDragStart={ setDraggedImageId }
								onDragEnd={ () => setDraggedImageId( null ) }
								onDropOn={ handleDropOnImage }
							/>
						);
					} ) }
				</div>
				<div className="woocommerce-fields-control__featured-image-actions">
					<IconButton
						variant="minimal"
						icon={ upload }
						label={ __( 'Add images', 'woocommerce' ) }
						onClick={ handleOpenMediaLibrary }
					/>
				</div>
			</div>
		);
	},
};
