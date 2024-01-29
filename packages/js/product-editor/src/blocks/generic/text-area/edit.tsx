/**
 * External dependencies
 */
import { createElement, useEffect } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useDispatch, useSelect } from '@wordpress/data';
import type { BlockInstance } from '@wordpress/blocks';
import {
	// @ts-expect-error no exported member.
	useBaseControlProps,
	BaseControl,
} from '@wordpress/components';
/**
 * External dependencies
 */
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import type { TextAreaBlockEditProps } from './types';

export function TextAreaBlockEdit( {
	attributes,
	clientId,
}: TextAreaBlockEditProps ) {
	// `property` attribute mandatory for this block
	const { property } = attributes;
	if ( ! property ) {
		throw new Error( 'Property attribute is missing.' );
	}

	const { label, helpText, placeholder } = attributes;

	// Data handling for the block property
	const [ textAreaContent, setTextAreaContent ] = useEntityProp(
		'postType',
		'product',
		property
	);

	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'core/paragraph' ],
			template: [
				[
					'core/paragraph',
					{
						placeholder,
					},
				],
			],
		}
	);

	/*
	 * Block content is provided by the `content` attribute
	 * of the core/paragraph inner block.
	 */
	const block = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlock( clientId );
		},
		[ clientId ]
	);

	// Get the first (and the only) paragraph
	const paragraphClientId = block?.innerBlocks.find(
		( blq: BlockInstance ) => blq.name === 'core/paragraph'
	)?.clientId;

	// Pick the paragraph content
	const content = useSelect(
		( select ) =>
			paragraphClientId
				? select( 'core/block-editor' ).getBlockAttributes(
						paragraphClientId
				  )?.content
				: '',
		[ paragraphClientId ]
	);

	/*
	 * Populate paragraph block content from the entity prop
	 * when the block is render.
	 */
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	useEffect( () => {
		if ( ! paragraphClientId ) {
			return;
		}
		updateBlockAttributes( paragraphClientId, {
			content: textAreaContent,
		} );
	}, [ paragraphClientId, updateBlockAttributes ] ); // eslint-disable-line

	/*
	 * Follow the content changes of the paragraph block,
	 * and update the entity prop accordingly.
	 */
	useEffect( () => {
		setTextAreaContent( content );
	}, [ content, setTextAreaContent ] );

	const { baseControlProps } = useBaseControlProps( {
		label,
		help: helpText,
	} );

	const blockProps = useWooBlockProps( attributes, {
		className: 'wp-block-woocommerce-product-text-area-field',
	} );

	return (
		<div { ...blockProps }>
			<BaseControl { ...baseControlProps }>
				<div { ...innerBlockProps } />
			</BaseControl>
		</div>
	);
}
