/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
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
	const previousBinding =
		bindings?.url?.source === 'woocommerce/term-image'
			? bindings.url
			: undefined;
	const image = metadata?.[ IMAGE_KEY ] || previousBinding?.args;
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
		if (
			! cover ||
			! category ||
			( ! metadata?.[ IMAGE_KEY ] && ! previousBinding )
		) {
			return;
		}
		const {
			bindings: savedBindings,
			[ IMAGE_KEY ]: managedImage,
			...otherMetadata
		} = metadata;
		const {
			id: idBinding,
			url: urlBinding,
			...otherBindings
		} = savedBindings || {};
		const nextBindings = {
			...otherBindings,
			...( idBinding && idBinding.source !== 'woocommerce/term-image'
				? { id: idBinding }
				: {} ),
			...( urlBinding && urlBinding.source !== 'woocommerce/term-image'
				? { url: urlBinding }
				: {} ),
		};
		const nextMetadata = {
			...otherMetadata,
			...( Object.keys( nextBindings ).length
				? { bindings: nextBindings }
				: {} ),
		};
		// Different from our last image means the user replaced it directly in Cover.
		const overridden =
			managedImage &&
			( ( attributes.id || 0 ) !== ( image.id || 0 ) ||
				( attributes.url || '' ) !== ( image.url || '' ) );
		if (
			overridden ||
			attributes.useFeaturedImage ||
			( attributes.backgroundType &&
				attributes.backgroundType !== 'image' ) ||
			nextBindings.id ||
			nextBindings.url ||
			nextBindings.__default
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
			getSetting< string >( 'placeholderImgSrcFullSize', '' );
		if (
			previousBinding ||
			attributes.id !== ( id || undefined ) ||
			attributes.url !== url
		) {
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
		previousBinding,
		bindings,
		attributes,
		image,
		id,
		media,
		updateBlockAttributes,
		__unstableMarkNextChangeAsNotPersistent,
	] );
}
