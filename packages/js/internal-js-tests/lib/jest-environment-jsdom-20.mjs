import BaseEnvironment from '@jest/environment-jsdom-abstract';
import jsdom from 'jsdom';

// Existing suites stub window.location, which jsdom 21 made non-configurable.
export default class JSDOM20Environment extends BaseEnvironment {
	constructor( config, context ) {
		super( config, context, jsdom );
	}
}
