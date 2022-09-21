/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Toolbar, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { chevronRight, chevronLeft, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { DraggableIcon } from '../sortable/draggable-icon';
import { CoverImage } from './icons';
import { SortableHandle } from '../sortable';

// TODO: - Dragging by toolbar handle bug
// - Drag current item to current index immediately bug (goes to next index)
// - hover state for cover button
// - Image outline when toolbar visible

export type ImageGalleryToolbarProps = {
	childIndex: number;
	moveItem: ( fromIndex: number, toIndex: number ) => void;
	removeItem: ( removeIndex: number ) => void;
	setAsCoverImage: ( coverIndex: number ) => void;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC< ImageGalleryToolbarProps > = ( {
	childIndex,
	moveItem,
	removeItem,
	setAsCoverImage,
}: ImageGalleryToolbarProps ) => {
	const moveNext = () => {
		moveItem( childIndex, childIndex + 1 );
	};
	const movePrevious = () => {
		if ( childIndex < 1 ) {
			return;
		}
		moveItem( childIndex, childIndex - 1 );
	};

	return (
		<div className="woocommerce-image-gallery__toolbar">
			<Toolbar label="Options" id="options-toolbar">
				<ToolbarGroup>
					<ToolbarButton
						icon={ () => (
							<SortableHandle>
								<DraggableIcon />
							</SortableHandle>
						) }
						label="Drag"
					/>
					<ToolbarButton
						className="woocommerce-image-gallery__toolbar-previous"
						onClick={ () => movePrevious() }
						icon={ chevronLeft }
						label="Move previous"
					/>
					<ToolbarButton
						className="woocommerce-image-gallery__toolbar-next"
						onClick={ () => moveNext() }
						icon={ chevronRight }
						label="Move next"
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ () => setAsCoverImage( childIndex ) }
						icon={ CoverImage }
						label="Set as cover image"
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton>Replace</ToolbarButton>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ () => removeItem( childIndex ) }
						icon={ trash }
						label="Delete"
					/>
				</ToolbarGroup>
			</Toolbar>
		</div>
	);
};
