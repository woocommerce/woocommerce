/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
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
/**
 * Internal dependencies
 */
import type { TextAreaBlockEditProps } from './types';

export function TextAreaBlockEdit( { attributes }: TextAreaBlockEditProps ) {
	// `property` attribute mandatory for this block
	const { property, placeholder } = attributes;
	if ( ! property ) {
		throw new Error( 'Property attribute is missing.' );
	}

	const { label, helpText } = attributes;

	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'core/paragraph' ],
			template: [ [ 'core/paragraph', { placeholder } ] ],
		}
	);
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
