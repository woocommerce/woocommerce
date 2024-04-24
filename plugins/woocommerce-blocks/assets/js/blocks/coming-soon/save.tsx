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
	const { color, storeOnly } = attributes;

	if ( storeOnly ) {
		return (
			<div { ...useBlockProps.save() }>
				<InnerBlocks.Content />
				<style>{ `.woocommerce-breadcrumb {display: none;}` }</style>
			</div>
		);
	}

	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
			<style>{ generateStyles( color ) }</style>
		</div>
	);
}
