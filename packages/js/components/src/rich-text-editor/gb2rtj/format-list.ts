/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { TextNode } from '../types/rtj-format';

type ListState = {
	currentIndent: number;
	listItems: TextNode[];
	currentFormatting: {
		bold?: boolean;
		italic?: boolean;
		linkURL?: string;
	};
};

export const parseHTMLList = (
	listStyle: 'numbered' | 'bulleted',
	htmlNodes: Node[],
	listState: ListState = {
		currentIndent: 1,
		listItems: [],
		currentFormatting: {},
	}
) => {
	htmlNodes.forEach( ( node ) => {
		switch ( node.nodeName ) {
			case 'LI': {
				const lastItem =
					listState.listItems[ listState.listItems.length - 1 ];
				if ( lastItem ) {
					lastItem.text += '\n';
				}
				break;
			}
			case 'A':
				listState.currentFormatting.linkURL = (
					node as HTMLAnchorElement
				 ).href;
				break;
			case 'STRONG':
				listState.currentFormatting.bold = true;
				break;
			case 'EM':
				listState.currentFormatting.italic = true;
				break;
			case 'UL':
			case 'OL':
				listState.currentIndent += 1;
				break;
			case '#text': {
				const text = node.textContent || '';
				listState.listItems.push( {
					attributes: {
						line: {
							listStyle,
							indentLevel: listState.currentIndent,
						},
						...listState.currentFormatting,
					},
					text,
				} );
				listState.currentFormatting = {};
				break;
			}
		}

		if ( node.childNodes.length ) {
			parseHTMLList(
				listStyle,
				Array.from( node.childNodes ),
				listState
			);

			// as we close an item, depth goes shallower
			if (
				listState.currentIndent > 1 &&
				( node.nodeName === 'UL' || node.nodeName === 'OL' )
			) {
				listState.currentIndent -= 1;
			}
		}
	} );

	return listState.listItems;
};

export const convertGBListToRTJNodes = ( block: BlockInstance ) => {
	const listNodes = new DOMParser()
		.parseFromString( block.attributes.values, 'text/html' )
		.querySelector( 'body' )?.childNodes;

	const list = parseHTMLList(
		block.attributes.ordered ? 'numbered' : 'bulleted',
		listNodes ? Array.from( listNodes ) : []
	);

	if ( list.length > 0 && list[ list.length - 1 ].text ) {
		list[ list.length - 1 ].text += '\n';
	}

	return list.map( ( item ) => {
		item.text = item.text.replace( /\n\n$/g, '\n' );
		return item;
	} );
};
