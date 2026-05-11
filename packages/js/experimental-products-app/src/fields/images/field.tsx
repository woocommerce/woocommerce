/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useCallback, useEffect, useState } from '@wordpress/element';
import { Fieldset, IconButton } from '@wordpress/ui';
import clsx from 'clsx';
import type { Field } from '@wordpress/dataviews';
import { upload, closeSmall, dragHandle } from '@wordpress/icons';
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
	media_details?: {
		sizes?: Record< string, { source_url: string } >;
	};
};

type AttachmentModel = {
	fetch?: () => unknown;
	toJSON: () => Attachment;
};

type AttachmentSelection = {
	add?: ( attachment: AttachmentModel ) => void;
	first?: () => AttachmentModel | undefined;
	map?: (
		callback: ( attachment: AttachmentModel ) => Attachment
	) => Attachment[];
	toJSON?: () => Attachment | Attachment[];
};

type MediaFrame = {
	on: ( event: 'open' | 'select', callback: () => void ) => void;
	open: () => void;
	state: () => {
		get: ( key: string ) => AttachmentSelection | undefined;
	};
};

type MediaLibraryController = new (
	options: Record< string, unknown >
) => Record< string, unknown >;

type WPMedia = {
	( options: Record< string, unknown > ): MediaFrame;
	attachment?: ( id: number ) => AttachmentModel;
	controller?: {
		Library?: MediaLibraryController;
	};
	query?: ( options?: Record< string, unknown > ) => unknown;
};

declare global {
	interface Window {
		wp?: {
			media?: WPMedia;
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

const getSelectedAttachments = (
	selection: AttachmentSelection | undefined
): Attachment[] => {
	if ( ! selection ) {
		return [];
	}

	if ( typeof selection.map === 'function' ) {
		return selection.map( ( attachment ) => attachment.toJSON() );
	}

	const json = selection.toJSON?.();

	if ( Array.isArray( json ) ) {
		return json;
	}

	if ( json ) {
		return [ json ];
	}

	const firstAttachment = selection.first?.();

	return firstAttachment ? [ firstAttachment.toJSON() ] : [];
};

const setSelectedMediaAttachments = (
	media: WPMedia,
	selection: AttachmentSelection | undefined,
	images: ProductEntityRecord[ 'images' ]
) => {
	if ( ! media.attachment || ! selection?.add ) {
		return;
	}

	images.forEach( ( image ) => {
		const attachment = media.attachment?.( image.id );

		if ( ! attachment ) {
			return;
		}

		attachment.fetch?.();
		selection.add?.( attachment );
	} );
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
	Edit: ( { data, onChange, field } ) => {
		const dataImages = useMemo( () => data.images ?? [], [ data.images ] );
		const [ images, setImages ] = useState( dataImages );

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

				commitImages( mappedImages );
			},
			[ commitImages ]
		);

		const handleOpenMediaLibrary = useCallback( () => {
			const media = window.wp?.media;

			if ( ! media || ! media.controller?.Library || ! media.query ) {
				return;
			}

			const title = __( 'Add images', 'woocommerce' );
			const buttonText = __( 'Use images', 'woocommerce' );
			const multiple = 'add';
			const frame = media( {
				title,
				button: {
					text: buttonText,
				},
				multiple,
				library: {
					type: 'image',
				},
				states: [
					new media.controller.Library( {
						title,
						library: media.query( {
							type: 'image',
						} ),
						multiple,
						filterable: 'all',
						syncSelection: false,
					} ),
				],
			} );

			frame.on( 'open', () => {
				setSelectedMediaAttachments(
					media,
					frame.state().get( 'selection' ),
					images
				);
			} );

			frame.on( 'select', () => {
				const selectedAttachments = getSelectedAttachments(
					frame.state().get( 'selection' )
				);

				if ( selectedAttachments.length === 0 ) {
					return;
				}

				handleSelect( selectedAttachments );
			} );

			frame.open();
		}, [ handleSelect, images ] );

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
				<Fieldset.Legend>{ field.label }</Fieldset.Legend>
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
										showDragHandle={ images.length > 1 }
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
				</DragDropProvider>
			</Fieldset.Root>
		);
	},
};
