/** @format */
/**
 * External dependencies
 */
const { map } = require( 'lodash' );

/**
 * Given a string, returns a new string with dash separators converted to
 * camel-case equivalent. This is not as aggressive as `_.camelCase` in
 * converting to uppercase, where Lodash will convert letters following
 * numbers.
 *
 * @param {string} string Input dash-delimited string.
 *
 * @return {string} Camel-cased string.
 */
function camelCaseDash( string ) {
	return string
		.replace( /^([a-z])/g, ( match, letter ) => letter.toUpperCase() )
		.replace( /-([a-z])/g, ( match, letter ) => letter.toUpperCase() );
}

/**
 * Get a formatted description string.
 *
 * @param { string } description Component description as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getDescription( description = '' ) {
	// eslint requires valid jsdoc, but we can remove this because it's implicit.
	return description.replace( '@return { object } -', '' ) + '\n';
}

/**
 * Get a single prop's details formatted for markdown.
 *
 * @param { string } propName Prop name.
 * @param { object } prop Prop details as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getProp( propName, prop ) {
	const lines = [ '### `' + propName + '`\n' ];
	prop.required && lines.push( '- **Required**' );
	lines.push( '- Type: ' + getPropType( prop.type, propName ) );
	lines.push( '- Default: ' + getPropDefaultValue( prop.defaultValue ) );
	lines.push( '\n' );
	lines.push( prop.description );
	lines.push( '\n' );

	return lines.filter( Boolean ).join( '\n' );
}

/**
 * Get a single prop's default value.
 *
 * @param { object } value Default value as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getPropDefaultValue( value ) {
	if ( value && value.value ) {
		return '`' + value.value + '`';
	}
	return 'null';
}

/**
 * Get props and prop details formatted for markdown.
 *
 * @param { object } props Component props as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getProps( props = {} ) {
	const title = 'Props';
	const lines = [ title, stringOfLength( '-', title.length ), '' ];
	Object.keys( props ).map( key => {
		lines.push( getProp( key, props[ key ] ) );
	} );

	return lines.join( '\n' );
}

/**
 * Get a single prop's type.
 *
 * @param { object } type Prop type as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getPropType( type ) {
	if ( ! type ) {
		return;
	}

	const labels = {
		func: 'Function',
		array: 'Array',
		object: 'Object',
		string: 'String',
		number: 'Number',
		bool: 'Boolean',
		node: 'ReactNode',
		element: 'ReactElement',
		any: '*',
		custom: '(custom validator)',
	};

	let value = '';
	switch ( type.name ) {
		case 'arrayOf':
			// replacing "Object" is a hack for shape proptypes.
			value = 'Array \n' + getPropType( type.value ).replace( 'Object \n', '' );
			break;
		case 'objectOf':
			// replacing "Object" is a hack for shape proptypes.
			value = 'Object \n' + getPropType( type.value ).replace( 'Object \n', '' );
			break;
		case 'shape':
			value = map( type.value, ( v, key ) => `\n  - ${ key }: ` + getPropType( v ) ).join( '' );
			value = 'Object \n' + value.replace( /^\n/, '' );
			break;
		case 'enum':
			value = 'One of: ' + type.value.map( v => v.value ).join( ', ' );
			break;
		case 'union':
			value = 'One of type: ' + type.value.map( v => v.name ).join( ', ' );
			break;
		default:
			value = labels[ type.name ] || type.name;
	}

	return value;
}

/**
 * Get a formatted title string.
 *
 * @param { string } name Component title as retrieved from component docs.
 * @return { string } Formatted string.
 */
function getTitle( name ) {
	const title = '`' + name + '` (component)';
	return title + '\n' + stringOfLength( '=', title.length ) + '\n';
}

/**
 * Repeat a string n times. If the string is 1 character long,
 * this will return a string of length n.
 *
 * @param { string } string String to repeat.
 * @param { number } n Number to repeat the string.
 * @return { string } New string.
 */
function stringOfLength( string, n ) {
	let newString = '';
	for ( let i = 0; i < n; i++ ) {
		newString += string;
	}
	return newString;
}

module.exports = {
	camelCaseDash,
	getDescription,
	getProp,
	getPropDefaultValue,
	getProps,
	getPropType,
	getTitle,
	stringOfLength,
};
