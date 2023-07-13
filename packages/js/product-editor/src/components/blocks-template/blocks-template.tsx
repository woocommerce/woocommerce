/**
 * External dependencies
 */
import { synchronizeBlocksWithTemplate } from '@wordpress/blocks';
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

	const [ blocks, , onChange ] = useEntityBlockEditor(
		'postType',
		'product',
		{
			id: productId,
		}
	);

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
		onChange(
			synchronizeBlocksWithTemplate( blocks, template.content.raw ),
			{}
		);
	}, [ templates, isLoading ] );

	return null;
}
