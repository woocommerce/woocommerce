/**
 * External dependencies
 */
import type { DragEventHandler } from 'react';
import {
	Children,
	createElement,
	cloneElement,
	useState,
	useMemo,
} from '@wordpress/element';
import classnames from 'classnames';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { moveIndex } from '../sortable';
import { ImageGalleryWrapper } from './image-gallery-wrapper';
import { ImageGalleryToolbar } from './index';
import type { ImageGalleryChild, MediaUploadComponentType } from './types';

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
	const childElements = useMemo(
		() => Children.toArray( children ) as JSX.Element[],
		[ children ]
	);

	function cloneChild( child: JSX.Element, childIndex: number ) {
		const key = child.key || String( childIndex );
		const isToolbarVisible = key === activeToolbarKey;

		return cloneElement(
			child,
			{
				key,
				isDraggable: allowDragging && ! child.props.isCover,
				className: classnames( {
					'is-toolbar-visible': isToolbarVisible,
				} ),
				onClick() {
					setActiveToolbarKey( isToolbarVisible ? null : key );
				},
				onBlur( event: React.FocusEvent< HTMLDivElement > ) {
					if (
						isDragging ||
						event.currentTarget.contains( event.relatedTarget ) ||
						( event.relatedTarget &&
							( event.relatedTarget as Element ).closest(
								'.media-modal, .components-modal__frame'
							) ) ||
						( event.relatedTarget &&
							// Check if not a button within the toolbar is clicked, to prevent hiding the toolbar.
							( event.relatedTarget as Element ).closest(
								'.woocommerce-image-gallery__toolbar'
							) ) ||
						( event.relatedTarget &&
							// Prevent toolbar from hiding if the dropdown is clicked within the toolbar.
							( event.relatedTarget as Element ).closest(
								'.woocommerce-image-gallery__toolbar-dropdown-popover'
							) )
					) {
						return;
					}
					setActiveToolbarKey( null );
				},
			},
			isToolbarVisible && (
				<ImageGalleryToolbar
					value={ child.props.id }
					allowDragging={ allowDragging }
					childIndex={ childIndex }
					lastChild={ childIndex === childElements.length - 1 }
					moveItem={ ( fromIndex: number, toIndex: number ) => {
						onOrderChange(
							moveIndex< ImageGalleryChild >(
								fromIndex,
								toIndex,
								childElements
							)
						);
					} }
					removeItem={ ( removeIndex: number ) => {
						onRemove( {
							removeIndex,
							removedItem: childElements[ removeIndex ],
						} );
					} }
					replaceItem={ (
						replaceIndex: number,
						media: { id: number } & MediaItem
					) => {
						onReplace( { replaceIndex, media } );
					} }
					setToolBarItem={ ( toolBarItem ) => {
						onSelectAsCover( activeToolbarKey );
						setActiveToolbarKey( toolBarItem );
					} }
					MediaUploadComponent={ MediaUploadComponent }
				/>
			)
		);
	}

	return (
		<div
			className="woocommerce-image-gallery"
			style={ {
				gridTemplateColumns: 'min-content '.repeat( columns ),
			} }
		>
			<ImageGalleryWrapper
				allowDragging={ allowDragging }
				updateOrderedChildren={ onOrderChange }
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
				{ childElements.map( cloneChild ) }
			</ImageGalleryWrapper>
		</div>
	);
};
