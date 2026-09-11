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
	const id =
		image?.attachmentId ||
		( category && getCategoryImageId( category ) ) ||
		0;
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
			! attributes.className
				?.split( /\s+/ )
				.includes( 'wc-block-featured-category__cover' ) ||
			( ! metadata?.[ IMAGE_KEY ] && ! previousBinding )
		) {
			return;
		}
		const nextBindings = { ...bindings };
		for ( const key of [ 'id', 'url' ] ) {
			if ( nextBindings[ key ]?.source === 'woocommerce/term-image' ) {
				delete nextBindings[ key ];
			}
		}
		const nextMetadata = { ...metadata, bindings: nextBindings };
		if ( ! Object.keys( nextBindings ).length ) {
			delete nextMetadata.bindings;
		}
		const overridden =
			metadata?.[ IMAGE_KEY ] &&
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
			delete nextMetadata[ IMAGE_KEY ];
			__unstableMarkNextChangeAsNotPersistent();
			void updateBlockAttributes( cover.clientId, {
				metadata: nextMetadata,
				className: ( attributes.className || '' ).replace(
					/\s*wc-block-featured-category__no-image\b/g,
					''
				),
			} );
			return;
		}
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
		const className =
			( attributes.className || '' ).replace(
				/\s*wc-block-featured-category__no-image\b/g,
				''
			) +
			( ( ! id ||
				( ( image?.size || image?.attachmentId ) && ! media ) ) &&
			image?.noPlaceholder
				? ' wc-block-featured-category__no-image'
				: '' );
		if (
			previousBinding ||
			attributes.id !== ( id || undefined ) ||
			attributes.url !== url ||
			attributes.className !== className
		) {
			nextMetadata[ IMAGE_KEY ] = { ...image, id, url };
			__unstableMarkNextChangeAsNotPersistent();
			void updateBlockAttributes( cover.clientId, {
				id: id || undefined,
				url,
				className,
				metadata: nextMetadata,
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
