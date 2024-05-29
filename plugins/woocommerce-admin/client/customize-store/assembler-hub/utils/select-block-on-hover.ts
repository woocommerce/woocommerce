const BLOCK_SELECTOR = '.block-editor-block-list__block';

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
		getBlockParents,
		setBlockEditingMode,
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

	const blockParents = getBlockParents( blockClientId );

	if ( ! blockParents || blockParents.length === 0 ) {
		selectBlockByClientId( blockClientId, null );
	} else {
		if ( setBlockEditingMode ) {
			setBlockEditingMode( blockClientId, 'disabled' );
		}
		selectBlockByClientId( blockParents[ 0 ], null );
	}
};
