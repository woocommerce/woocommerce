/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { generateStyles } from './styles';

export default function Save( { attributes } ) {
	const { color } = attributes;
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
			<style>{ generateCSS( color ) }</style>
		</div>
	);
}
