/**
 * Internal dependencies
 */
import { registeredBlocks } from './registered-blocks-init';

/**
 * Asserts that an option is of the given type. Otherwise, throws an error.
 *
 * @throws Will throw an error if the type of the option doesn't match the expected type.
 * @param {Object} options      Object containing the option to validate.
 * @param {string} optionName   Name of the option to validate.
 * @param {string} expectedType Type expected for the option.
 */
const assertOption = ( options, optionName, expectedType ) => {
	if ( typeof options[ optionName ] !== expectedType ) {
		throw new Error(
			`Incorrect value for the ${ optionName } argument when registering an inner block. It must be a ${ expectedType }.`
		);
	}
};

/**
 * Registers an inner block that can be added as a child of another block.
 *
 * @export
 * @param {Object}   options           Options to use when registering the block.
 * @param {string}   options.main      Name of the parent block.
 * @param {string}   options.blockName Name of the child block being registered.
 * @param {Function} options.component React component used to render the child block.
 */
export function registerInnerBlock( options ) {
	assertOption( options, 'main', 'string' );
	assertOption( options, 'blockName', 'string' );
	assertOption( options, 'component', 'function' );

	const { main, blockName, component } = options;

	if ( ! registeredBlocks[ main ] ) {
		registeredBlocks[ main ] = {};
	}

	registeredBlocks[ main ][ blockName ] = component;
}
