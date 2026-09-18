/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import type { WP_REST_API_Category } from 'wp-types';

/**
 * Internal dependencies
 */
import { getCategoryImageId, getCategoryImageSrc } from './utils';

const IMAGE_KEY = 'woocommerce/featured-category-image';

export function useCoverImage(
	clientId: string,
	category?: WP_REST_API_Category
) {
	const cover = useSelect(
		( select ) =>
			select( blockEditorStore )
				.getBlocks( clientId )
				.find( ( block ) => block.name === 'core/cover' ),
		[ clientId ]
	);
	const { updateBlockAttributes, __unstableMarkNextChangeAsNotPersistent } =
		useDispatch( blockEditorStore );
	const attributes = cover?.attributes;
	const metadata = attributes?.metadata;
	const bindings = metadata?.bindings;
	const image = metadata?.[ IMAGE_KEY ];
	// A preserved custom attachment wins; otherwise follow the category's current image.
	const id =
		image?.attachmentId ||
		( category && getCategoryImageId( category ) ) ||
		0;
	// Fetch attachment details only when we need a particular size or a custom attachment.
	const media = useSelect(
		( select ) =>
			id && ( image?.size || image?.attachmentId )
				? select( coreStore ).getMedia( Number( id ) )
				: undefined,
		[ id, image?.size, image?.attachmentId ]
	);

	useEffect( () => {
		if ( ! cover || ! category || ! image ) {
			return;
		}
		const { [ IMAGE_KEY ]: managedImage, ...nextMetadata } = metadata;
		// Different from our last image means the user replaced it directly in Cover.
		const overridden =
			( attributes.id || 0 ) !== ( managedImage.id || 0 ) ||
			( attributes.url || '' ) !== ( managedImage.url || '' );
		if (
			overridden ||
			attributes.useFeaturedImage ||
			( attributes.backgroundType &&
				attributes.backgroundType !== 'image' ) ||
			bindings?.id ||
			bindings?.url ||
			bindings?.__default
		) {
			__unstableMarkNextChangeAsNotPersistent();
			void updateBlockAttributes( cover.clientId, {
				metadata: nextMetadata,
			} );
			return;
		}
		// Attachment details are still loading. Keep the current image until they arrive.
		if (
			id &&
			( image?.size || image?.attachmentId ) &&
			media === undefined
		) {
			return;
		}
		const url =
			media?.media_details?.sizes?.[ image?.size || 'full' ]
				?.source_url ||
			media?.source_url ||
			( ! image?.attachmentId && getCategoryImageSrc( category ) ) ||
			'';
		if ( attributes.id !== ( id || undefined ) || attributes.url !== url ) {
			__unstableMarkNextChangeAsNotPersistent();
			void updateBlockAttributes( cover.clientId, {
				id: id || undefined,
				url,
				metadata: {
					...nextMetadata,
					[ IMAGE_KEY ]: { ...image, id, url },
				},
			} );
		}
	}, [
		cover,
		category,
		metadata,
		bindings,
		attributes,
		image,
		id,
		media,
		updateBlockAttributes,
		__unstableMarkNextChangeAsNotPersistent,
	] );
}
