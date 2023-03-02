/* eslint-disable @wordpress/no-unsafe-wp-apis */

/**
 * External dependencies
 */
import { WP_REST_API_Category } from 'wp-types';
import { ProductResponseItem } from '@woocommerce/types';
import {
	__experimentalImageEditingProvider as ImageEditingProvider,
	__experimentalImageEditor as GutenbergImageEditor,
} from '@wordpress/block-editor';
import type { ComponentType, Dispatch, SetStateAction } from 'react';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES, DEFAULT_EDITOR_SIZE } from './constants';
import { EditorBlock } from './types';
import { useBackgroundImage } from './use-background-image';

type MediaAttributes = { mediaId: number; mediaSrc: string };
type MediaSize = { height: number; width: number };

interface WithImageEditorRequiredProps< T > {
	attributes: MediaAttributes & EditorBlock< T >[ 'attributes' ];
	backgroundImageSize: MediaSize;
	setAttributes: ( attrs: Partial< MediaAttributes > ) => void;
	useEditingImage: [ boolean, Dispatch< SetStateAction< boolean > > ];
}

interface WithImageEditorCategoryProps< T >
	extends WithImageEditorRequiredProps< T > {
	category: WP_REST_API_Category;
	product: never;
}

interface WithImageEditorProductProps< T >
	extends WithImageEditorRequiredProps< T > {
	category: never;
	product: ProductResponseItem;
}

type WithImageEditorProps< T extends EditorBlock< T > > =
	| ( T & WithImageEditorCategoryProps< T > )
	| ( T & WithImageEditorProductProps< T > );

interface ImageEditorProps {
	backgroundImageId: number;
	backgroundImageSize: MediaSize;
	backgroundImageSrc: string;
	isEditingImage: boolean;
	setAttributes: ( attrs: MediaAttributes ) => void;
	setIsEditingImage: ( value: boolean ) => void;
}

export const ImageEditor = ( {
	backgroundImageId,
	backgroundImageSize,
	backgroundImageSrc,
	isEditingImage,
	setAttributes,
	setIsEditingImage,
}: ImageEditorProps ) => {
	return (
		<>
			<ImageEditingProvider
				id={ backgroundImageId }
				url={ backgroundImageSrc }
				naturalHeight={
					backgroundImageSize.height || DEFAULT_EDITOR_SIZE.height
				}
				naturalWidth={
					backgroundImageSize.width || DEFAULT_EDITOR_SIZE.width
				}
				onSaveImage={ ( { id, url }: { id: number; url: string } ) => {
					setAttributes( { mediaId: id, mediaSrc: url } );
				} }
				isEditing={ isEditingImage }
				onFinishEditing={ () => setIsEditingImage( false ) }
			>
				<GutenbergImageEditor
					url={ backgroundImageSrc }
					height={
						backgroundImageSize.height || DEFAULT_EDITOR_SIZE.height
					}
					width={
						backgroundImageSize.width || DEFAULT_EDITOR_SIZE.width
					}
				/>
			</ImageEditingProvider>
		</>
	);
};

export const withImageEditor =
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: WithImageEditorProps< T > ) => {
		const [ isEditingImage, setIsEditingImage ] = props.useEditingImage;

		const { attributes, backgroundImageSize, name, setAttributes } = props;
		const { mediaId, mediaSrc } = attributes;
		const item =
			name === BLOCK_NAMES.featuredProduct
				? props.product
				: props.category;

		const { backgroundImageId, backgroundImageSrc } = useBackgroundImage( {
			item,
			mediaId,
			mediaSrc,
			blockName: name,
		} );

		if ( isEditingImage ) {
			return (
				<ImageEditor
					backgroundImageId={ backgroundImageId }
					backgroundImageSize={ backgroundImageSize }
					backgroundImageSrc={ backgroundImageSrc }
					isEditingImage={ isEditingImage }
					setAttributes={ setAttributes }
					setIsEditingImage={ setIsEditingImage }
				/>
			);
		}

		return <Component { ...props } />;
	};
