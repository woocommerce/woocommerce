/**
 * External dependencies
 */
import { ComponentType, ReactElement } from 'react';
import { createElement } from '@wordpress/element';
import { v4 as uuid } from 'uuid';
import { Block, getBlockType } from '@wordpress/blocks';

export function getRenderedBlockView( domElement: Element ): ReactElement[] {
	const rendered: ReactElement[] = [];

	for ( let i = 0; i < domElement.children.length; i++ ) {
		const child = domElement.children[ i ];
		const blockName = child.getAttribute( 'data-block-name' );
		const children = getRenderedBlockView( child );

		if ( blockName ) {
			const blockType = getBlockType( blockName ) as Block & {
				view: ComponentType;
			};
			if ( blockType?.view ) {
				rendered.push(
					createElement(
						blockType.view,
						{
							// @ts-ignore No types for dataset yet.
							attributes: child.dataset,
							context: {},
							clientId: uuid(),
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
