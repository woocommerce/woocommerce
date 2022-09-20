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

export type ImageGalleryToolbarProps =
	{} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC<
	ImageGalleryToolbarProps
> = ( {}: ImageGalleryToolbarProps ) => {
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
					<ToolbarButton icon={ chevronLeft } label="Move Left" />
					<ToolbarButton icon={ chevronRight } label="Move Right" />
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={ CoverImage }
						label="Set as cover image"
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton>Replace</ToolbarButton>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton icon={ trash } label="Delete" />
				</ToolbarGroup>
			</Toolbar>
		</div>
	);
};
