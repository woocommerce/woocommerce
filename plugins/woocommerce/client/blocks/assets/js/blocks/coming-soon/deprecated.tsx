/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { generateStyles } from './styles';

const v1Metadata = {
	attributes: {
		color: {
			type: 'string',
		},
		storeOnly: {
			type: 'boolean',
			default: false,
		},
	},
	supports: {
		color: {
			background: false,
			text: true,
		},
	},
};

const v1 = {
	...v1Metadata,
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
