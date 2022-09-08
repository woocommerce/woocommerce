// Weakly referenced map allows unused ids to be garbage collected.
const weakMap = new WeakMap();

// Incrementing zero-based ID value.
let id = -1;

const prefix = 'link-control-instance';

function getKey( _id: number ) {
	return `${ prefix }-${ _id }`;
}

function useLinkInstanceKey( instance: object ): string | undefined {
	if ( ! instance ) {
		return;
	}
	if ( weakMap.has( instance ) ) {
		return getKey( weakMap.get( instance ) );
	}

	id += 1;

	weakMap.set( instance, id );

	return getKey( id );
}

export default useLinkInstanceKey;
