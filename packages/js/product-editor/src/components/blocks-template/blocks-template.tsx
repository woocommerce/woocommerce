/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { parse, synchronizeBlocksWithTemplate } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useLayoutEffect } from '@wordpress/element';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	useEntityBlockEditor,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	useEntityId,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityRecords,
} from '@wordpress/core-data';

export function BlocksTemplate() {
	const productId = useEntityId( 'postType', 'product' );

	const [ , , onChange ] = useEntityBlockEditor( 'postType', 'product', {
		id: productId,
	} );

	const { records: templates, isResolving: isLoading } = useEntityRecords(
		'postType',
		'wp_template',
		{
			post_type: 'woocommerce_product_editor_template',
			per_page: -1,
		}
	);

	useLayoutEffect( () => {
		if ( isLoading || ! templates ) {
			return;
		}
		const template = templates.find(
			// @ts-ignore
			( t ) => t.id === 'woocommerce/woocommerce//product-editor_simple'
		);
		const parsed = parse( template.content.raw );
		onChange( parsed, {} );
	}, [ templates, isLoading ] );

	return null;
}
