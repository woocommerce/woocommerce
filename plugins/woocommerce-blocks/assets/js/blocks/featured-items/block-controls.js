/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls as BlockControlsWrapper,
	MediaReplaceFlow,
} from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { crop } from '@wordpress/icons';
import TextToolbarButton from '@woocommerce/editor-components/text-toolbar-button';

/**
 * Internal dependencies
 */
import { useBackgroundImage } from './use-background-image';

export const BlockControls = ( {
	backgroundImageId,
	backgroundImageSrc,
	contentAlign,
	cropLabel,
	editLabel,
	editMode,
	isEditingImage,
	mediaSrc,
	setAttributes,
	setIsEditingImage,
} ) => {
	return (
		<BlockControlsWrapper>
			<AlignmentToolbar
				value={ contentAlign }
				onChange={ ( nextAlign ) => {
					setAttributes( { contentAlign: nextAlign } );
				} }
			/>
			<ToolbarGroup>
				{ backgroundImageSrc && ! isEditingImage && (
					<ToolbarButton
						onClick={ () => setIsEditingImage( true ) }
						icon={ crop }
						label={ cropLabel }
					/>
				) }
				<MediaReplaceFlow
					mediaId={ backgroundImageId }
					mediaURL={ mediaSrc }
					accept="image/*"
					onSelect={ ( media ) => {
						setAttributes( {
							mediaId: media.id,
							mediaSrc: media.url,
						} );
					} }
					allowedTypes={ [ 'image' ] }
				/>
				{ backgroundImageId && mediaSrc ? (
					<TextToolbarButton
						onClick={ () =>
							setAttributes( { mediaId: 0, mediaSrc: '' } )
						}
					>
						{ __( 'Reset', 'woo-gutenberg-products-block' ) }
					</TextToolbarButton>
				) : null }
			</ToolbarGroup>
			<ToolbarGroup
				controls={ [
					{
						icon: 'edit',
						title: editLabel,
						onClick: () =>
							setAttributes( { editMode: ! editMode } ),
						isActive: editMode,
					},
				] }
			/>
		</BlockControlsWrapper>
	);
};

export const withBlockControls = ( { cropLabel, editLabel } ) => (
	Component
) => ( props ) => {
	const [ isEditingImage, setIsEditingImage ] = props.useEditingImage;
	const { attributes, category, name, product, setAttributes } = props;
	const { contentAlign, editMode, mediaId, mediaSrc } = attributes;
	const item = category || product;

	const { backgroundImageId, backgroundImageSrc } = useBackgroundImage( {
		item,
		mediaId,
		mediaSrc,
		blockName: name,
	} );

	return (
		<>
			<BlockControls
				backgroundImageId={ backgroundImageId }
				backgroundImageSrc={ backgroundImageSrc }
				contentAlign={ contentAlign }
				cropLabel={ cropLabel }
				editLabel={ editLabel }
				editMode={ editMode }
				isEditingImage={ isEditingImage }
				mediaSrc={ mediaSrc }
				setAttributes={ setAttributes }
				setIsEditingImage={ setIsEditingImage }
			/>
			<Component { ...props } />
		</>
	);
};
