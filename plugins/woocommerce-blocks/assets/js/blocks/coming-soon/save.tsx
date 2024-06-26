/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { generateStyles } from './styles';

export default function Save( { attributes } ) {
	const { color, storeOnly } = attributes;
	const blockProps = { ...useBlockProps.save() };

	if ( storeOnly ) {
		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
				<style>{ `.woocommerce-breadcrumb {display: none;}` }</style>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
			<style>{ generateStyles( color ) }</style>
		</div>
	);
}
