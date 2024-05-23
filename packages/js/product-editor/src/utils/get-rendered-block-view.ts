/**
 * External dependencies
 */
import { ComponentType, ReactElement } from 'react';
import { createElement } from '@wordpress/element';
import { Block, getBlockType } from '@wordpress/blocks';
// import { v4 as uuid } from 'uuid';

export function getRenderedBlockView(
	domElement: Element,
	context: unknown
): ReactElement[] {
	const rendered: ReactElement[] = [];

	for ( let i = 0; i < domElement.children.length; i++ ) {
		const child = domElement.children[ i ];
		const blockName = child.getAttribute( 'data-block-name' );
		const children = getRenderedBlockView( child, context );

		if ( blockName ) {
			const blockType = getBlockType( blockName ) as Block & {
				view: ComponentType;
			};
			if ( blockType?.edit ) {
				rendered.push(
					createElement(
						blockType.edit,
						{
							// @ts-ignore No types for dataset yet.
							attributes: child.dataset,
							// @ts-ignore
							context,
							clientId: '@ptodo',
							setAttributes: () => {},
							// clientId: uuid(),
						},
						children
					)
				);
			}
		} else {
			rendered.push(
				createElement( child.tagName, child.attributes, children )
			);
		}
	}

	return rendered;
}
