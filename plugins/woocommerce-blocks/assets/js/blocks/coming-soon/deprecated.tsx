/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { generateStyles } from './styles';
import metadata from './block.json';

const v1 = {
	attributes: metadata.attributes,
	supports: metadata.supports,
	save: ( {
		attributes,
	}: {
		attributes: { color: string; storeOnly: boolean };
	} ) => {
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
	},
};

export default [ v1 ];
