/**
 * External dependencies
 */
import { createElement, useLayoutEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { synchronizeBlocksWithTemplate } from '@wordpress/blocks';
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

type Template = {
	id: string;
	title: {
		raw: string;
		rendered: string;
	};
};

export function BlocksTemplate() {
	const [ selectedTemplateId, setSelectedTemplateId ] = useState(
		'woocommerce/woocommerce//product-editor_simple'
	);
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
			( t ) => t.id === selectedTemplateId
		);
		onChange(
			synchronizeBlocksWithTemplate( [], template.content.raw ),
			{}
		);
	}, [ templates, isLoading, selectedTemplateId ] );

	if ( ! templates ) {
		return null;
	}

	return (
		<SelectControl
			className="woocommerce-template-switcher"
			options={ templates.map( ( template: Template ) => ( {
				label: template.title.rendered,
				value: template.id,
			} ) ) }
			onChange={ ( templateId ) =>
				setSelectedTemplateId( templateId as string )
			}
		/>
	);
}
