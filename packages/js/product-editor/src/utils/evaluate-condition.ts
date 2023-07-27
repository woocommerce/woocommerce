export type Condition = {
	key: string;
	operator: string;
	value: unknown;
};

export type Data = {
	[ key: string ]: unknown;
};

function getValueByString( data: Data, key: string ) {
	const keys = key.split( '.' );
	let value = { ...data };
	for ( let i = 0; i < keys.length; i++ ) {
		if ( ! value.hasOwnProperty( keys[ i ] ) ) {
			return undefined;
		}
		// @ts-ignore
		value = value[ keys[ i ] ];
	}
	return value;
}

export function evaluateCondition( data: Data, condition: Condition ) {
	const { key, operator, value } = condition;
	const dataValue = getValueByString( data, key ) as unknown;
	switch ( operator ) {
		case '>':
			return ( value as number ) > ( dataValue as number );
		case '<':
			return ( value as number ) < ( dataValue as number );
		case '!=':
			return dataValue !== value;
		case '=':
		default:
			return dataValue === value;
	}
}
