/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { chevronRight, chevronLeft, trash } from '@wordpress/icons';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';
import { __ } from '@wordpress/i18n';
import {
	Toolbar,
	ToolbarButton,
	ToolbarGroup,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	// eslint-disable-next-line @woocommerce/dependency-group
	ToolbarItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SortableHandle } from '../sortable';
import { MediaUploadComponentType } from './types';
import { ImageGalleryToolbarDropdown } from './image-gallery-toolbar-dropdown';

export type ImageGalleryToolbarProps = {
	childIndex: number;
	allowDragging?: boolean;
	moveItem: ( fromIndex: number, toIndex: number ) => void;
	removeItem: ( removeIndex: number ) => void;
	replaceItem: (
		replaceIndex: number,
		media: { id: number } & MediaItem
	) => void;
	setToolBarItem: ( key: string | null ) => void;
	lastChild: boolean;
	MediaUploadComponent: MediaUploadComponentType;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC< ImageGalleryToolbarProps > = ( {
	childIndex,
	allowDragging = true,
	moveItem,
	removeItem,
	replaceItem,
	setToolBarItem,
	lastChild,
	MediaUploadComponent = MediaUpload,
}: ImageGalleryToolbarProps ) => {
	const moveNext = () => {
		moveItem( childIndex, childIndex + 1 );
	};

	const movePrevious = () => {
		moveItem( childIndex, childIndex - 1 );
	};

	const setAsCoverImage = ( coverIndex: number ) => {
		moveItem( coverIndex, 0 );
		setToolBarItem( null );
	};

	const isCoverItem = childIndex === 0;

	return (
		<div className="woocommerce-image-gallery__toolbar">
			<Toolbar
				onClick={ ( e ) => e.stopPropagation() }
				label={ __( 'Options', 'woocommerce' ) }
				id="options-toolbar"
			>
				{ ! isCoverItem && (
					<ToolbarGroup>
						{ allowDragging && (
							<ToolbarButton
								icon={ () => (
									<SortableHandle itemIndex={ childIndex } />
								) }
								label={ __( 'Drag to reorder', 'woocommerce' ) }
							/>
						) }
						<ToolbarButton
							disabled={ childIndex < 2 }
							onClick={ () => movePrevious() }
							icon={ chevronLeft }
							label={ __( 'Move previous', 'woocommerce' ) }
						/>
						<ToolbarButton
							onClick={ () => moveNext() }
							icon={ chevronRight }
							label={ __( 'Move next', 'woocommerce' ) }
							disabled={ lastChild }
						/>
					</ToolbarGroup>
				) }
				{ ! isCoverItem && (
					<ToolbarGroup>
						<ToolbarButton
							onClick={ () => setAsCoverImage( childIndex ) }
							label={ __( 'Set as cover', 'woocommerce' ) }
						>
							{ __( 'Set as cover', 'woocommerce' ) }
						</ToolbarButton>
					</ToolbarGroup>
				) }
				{ isCoverItem && (
					<ToolbarGroup className="woocommerce-image-gallery__toolbar-media">
						<MediaUploadComponent
							onSelect={ ( media ) =>
								replaceItem( childIndex, media as MediaItem )
							}
							allowedTypes={ [ 'image' ] }
							render={ ( { open } ) => (
								<ToolbarButton onClick={ open }>
									{ __( 'Replace', 'woocommerce' ) }
								</ToolbarButton>
							) }
						/>
					</ToolbarGroup>
				) }
				{ isCoverItem && (
					<ToolbarGroup>
						<ToolbarButton
							onClick={ () => removeItem( childIndex ) }
							icon={ trash }
							label={ __( 'Remove', 'woocommerce' ) }
						/>
					</ToolbarGroup>
				) }
				{ ! isCoverItem && (
					<ToolbarGroup>
						<ToolbarItem>
							{ ( toggleProps: {
								'data-toolbar-item': boolean;
								ref: React.ForwardedRef<
									typeof ImageGalleryToolbarDropdown
								>;
							} ) => (
								<ImageGalleryToolbarDropdown
									canRemove={ true }
									onRemove={ () => removeItem( childIndex ) }
									onReplace={ ( media ) =>
										replaceItem( childIndex, media )
									}
									MediaUploadComponent={
										MediaUploadComponent
									}
									{ ...toggleProps }
								/>
							) }
						</ToolbarItem>
					</ToolbarGroup>
				) }
			</Toolbar>
		</div>
	);
};
