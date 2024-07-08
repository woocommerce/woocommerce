const BLOCK_SELECTOR = "[data-is-parent-block='true'], header, footer";

const getBlockClientId = ( node: HTMLElement ) => {
	while ( node && node.nodeType !== node.ELEMENT_NODE ) {
		node = node.parentNode as HTMLElement;
	}

	if ( ! node ) {
		return;
	}

	const elementNode = node;
	const blockNode = elementNode.closest( BLOCK_SELECTOR );

	if ( ! blockNode ) {
		return;
	}

	return blockNode.id.slice( 'block-'.length );
};

export const selectBlockOnHover = (
	event: MouseEvent,
	{
		selectBlockByClientId,
	}: {
		selectBlockByClientId: (
			clientId: string,
			initialPosition: 0 | -1 | null
		) => void;
		getBlockParents: ( clientId: string ) => string[];
		setBlockEditingMode?: ( clientId: string, mode: string ) => void;
	}
) => {
	const target = event.target as HTMLElement;

	const blockClientId = getBlockClientId( target );

	if ( ! blockClientId ) {
		return;
	}

	selectBlockByClientId( blockClientId, null );

	return blockClientId;
};
