/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Toolbar, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { chevronRight, chevronLeft, trash } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { CoverImage } from './icons';
import { SortableHandle } from '../sortable';

// TODO: Dragging by toolbar handle bug
// TODO: hover state for cover button
// TODO: Image outline when toolbar visible
// TODO: Strange behavior with cover image after dragging has happened

export type ImageGalleryToolbarProps = {
	childIndex: number;
	moveItem: ( fromIndex: number, toIndex: number ) => void;
	removeItem: ( removeIndex: number ) => void;
	setAsCoverImage: ( coverIndex: number ) => void;
	lastChild: boolean;
	onDragStart?: () => void;
	onDragEnd?: () => void;
} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC< ImageGalleryToolbarProps > = ( {
	childIndex,
	moveItem,
	removeItem,
	setAsCoverImage,
	lastChild,
	onDragStart,
	onDragEnd,
}: ImageGalleryToolbarProps ) => {
	const moveNext = () => {
		moveItem( childIndex, childIndex + 1 );
	};

	const movePrevious = () => {
		moveItem( childIndex, childIndex - 1 );
	};

	return (
		<div className="woocommerce-image-gallery__toolbar">
			<Toolbar label="Options" id="options-toolbar">
				{ childIndex !== 0 && (
					<ToolbarGroup>
						<ToolbarButton
							icon={ () => (
								<SortableHandle
									onDragStart={ onDragStart }
									onDragEnd={ onDragEnd }
								/>
							) }
							label="Drag"
						/>
						<ToolbarButton
							className="woocommerce-image-gallery__toolbar-previous"
							disabled={ childIndex < 2 }
							onClick={ () => movePrevious() }
							icon={ chevronLeft }
							label="Move previous"
						/>
						<ToolbarButton
							className="woocommerce-image-gallery__toolbar-next"
							onClick={ () => moveNext() }
							icon={ chevronRight }
							label="Move next"
							disabled={ lastChild }
						/>
					</ToolbarGroup>
				) }
				{ childIndex !== 0 && (
					<ToolbarGroup>
						<ToolbarButton
							onClick={ () => setAsCoverImage( childIndex ) }
							icon={ CoverImage }
							label="Set as cover image"
						/>
					</ToolbarGroup>
				) }
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
