/**
 * External dependencies
 */
import {
	createElement,
	cloneElement,
	useState,
	useEffect,
} from '@wordpress/element';
import { DragEventHandler } from 'react';
import classnames from 'classnames';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { moveIndex } from '../sortable';
import { ImageGalleryToolbar } from './index';
import { ImageGalleryChild, MediaUploadComponentType } from './types';
import { removeItem, replaceItem } from './utils';
import { ImageGalleryWrapper } from './image-gallery-wrapper';

export type ImageGalleryProps = {
	children: ImageGalleryChild | ImageGalleryChild[];
	columns?: number;
	onRemove?: ( props: {
		removeIndex: number;
		removedItem: ImageGalleryChild;
	} ) => void;
	onReplace?: ( props: {
		replaceIndex: number;
		media: { id: number } & MediaItem;
	} ) => void;
	allowDragging?: boolean;
	onSelectAsCover?: ( itemId: string | null ) => void;
	onOrderChange?: ( items: ImageGalleryChild[] ) => void;
	MediaUploadComponent?: MediaUploadComponentType;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGallery: React.FC< ImageGalleryProps > = ( {
	children,
	columns = 4,
	allowDragging = true,
	onSelectAsCover = () => null,
	onOrderChange = () => null,
	onRemove = () => null,
	onReplace = () => null,
	MediaUploadComponent = MediaUpload,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: ImageGalleryProps ) => {
	const [ activeToolbarKey, setActiveToolbarKey ] = useState< string | null >(
		null
	);
	const [ isDragging, setIsDragging ] = useState< boolean >( false );
	const [ orderedChildren, setOrderedChildren ] = useState<
		ImageGalleryChild[]
	>( [] );

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setOrderedChildren(
			( Array.isArray( children ) ? children : [ children ] ).map(
				( child, index ) =>
					cloneElement( child, { key: child.key || String( index ) } )
			)
		);
	}, [ children ] );

	const updateOrderedChildren = ( items: ImageGalleryChild[] ) => {
		setOrderedChildren( items );
		onOrderChange( items );
	};

	return (
		<div
			className="woocommerce-image-gallery"
			style={ {
				gridTemplateColumns: 'min-content '.repeat( columns ),
			} }
		>
			<ImageGalleryWrapper
				allowDragging={ allowDragging }
				updateOrderedChildren={ updateOrderedChildren }
				onDragStart={ ( event ) => {
					setIsDragging( true );
					onDragStart( event );
				} }
				onDragEnd={ ( event ) => {
					setIsDragging( false );
					onDragEnd( event );
				} }
				onDragOver={ onDragOver }
			>
				{ orderedChildren.map( ( child, childIndex ) => {
					const isToolbarVisible = child.key === activeToolbarKey;
					const isCoverItem = ( childIndex === 0 ) as boolean;

					return cloneElement(
						child,
						{
							isCover: isCoverItem,
							className: classnames( {
								'is-toolbar-visible': isToolbarVisible,
							} ),
							onClick: () => {
								setActiveToolbarKey(
									isToolbarVisible
										? null
										: ( child.key as string )
								);
							},
							onBlur: (
								event: React.FocusEvent< HTMLDivElement >
							) => {
								if (
									isDragging ||
									event.currentTarget.contains(
										event.relatedTarget
									) ||
									( event.relatedTarget &&
										(
											event.relatedTarget as Element
										 ).closest(
											'.media-modal, .components-modal__frame'
										) )
								) {
									return;
								}
								setActiveToolbarKey( null );
							},
						},
						isToolbarVisible ? (
							<ImageGalleryToolbar
								allowDragging={ allowDragging }
								childIndex={ childIndex }
								lastChild={
									childIndex === orderedChildren.length - 1
								}
								moveItem={ (
									fromIndex: number,
									toIndex: number
								) => {
									updateOrderedChildren(
										moveIndex< ImageGalleryChild >(
											fromIndex,
											toIndex,
											orderedChildren
										)
									);
								} }
								removeItem={ ( removeIndex: number ) => {
									onRemove( {
										removeIndex,
										removedItem:
											orderedChildren[ removeIndex ],
									} );
									updateOrderedChildren(
										removeItem(
											orderedChildren,
											removeIndex
										)
									);
								} }
								replaceItem={ (
									replaceIndex: number,
									media: { id: number } & MediaItem
								) => {
									onReplace( { replaceIndex, media } );
									setOrderedChildren(
										replaceItem< {
											src: string;
											alt: string;
										} >( orderedChildren, replaceIndex, {
											src: media.url as string,
											alt: media.alt as string,
										} )
									);
								} }
								setToolBarItem={ ( toolBarItem ) => {
									onSelectAsCover( activeToolbarKey );
									setActiveToolbarKey( toolBarItem );
								} }
								MediaUploadComponent={ MediaUploadComponent }
							/>
						) : null
					);
				} ) }
			</ImageGalleryWrapper>
		</div>
	);
};
