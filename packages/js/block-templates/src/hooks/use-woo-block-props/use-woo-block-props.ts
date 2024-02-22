/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

interface TemplateBlockAttributes {
	_templateBlockId?: string;
	_templateBlockOrder?: number;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	[ key: string ]: any;
}

export const useWooBlockProps = (
	attributes: TemplateBlockAttributes,
	props: Record< string, unknown > = {}
) => {
	const additionalProps = {
		'data-template-block-id': attributes._templateBlockId,
		'data-template-block-order': attributes._templateBlockOrder,
		tabIndex: -1,
		...props,
	};
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore the type definitions are slightly wrong. It should be possible to pass the tabIndex attribute.
	return useBlockProps( additionalProps );
};
