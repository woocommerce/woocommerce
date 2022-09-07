/**
 * External dependencies
 */
import { Toolbar, ToolbarButton } from '@wordpress/components';
import { formatBold, formatItalic, link } from '@wordpress/icons';
import { createElement } from '@wordpress/element';

export type ImageGalleryToolbarProps =
	{} & React.HTMLAttributes< HTMLDivElement >;

export const ImageGalleryToolbar: React.FC<
	ImageGalleryToolbarProps
> = ( {}: ImageGalleryToolbarProps ) => {
	return (
		<div className="woocommerce-image-gallery__toolbar">
			<Toolbar label="Options" id="options-toolbar">
				<ToolbarButton>Text</ToolbarButton>
				<ToolbarButton icon={ formatBold } label="Bold" isPressed />
				<ToolbarButton icon={ formatItalic } label="Italic" />
				<ToolbarButton icon={ link } label="Link" />
			</Toolbar>
		</div>
	);
};
