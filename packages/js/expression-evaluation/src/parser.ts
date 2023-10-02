/**
 * External dependencies
 */
import * as peggy from 'peggy';

const grammar = `
start = logicalOr

logicalOr
	= left:logicalAnd whitespace+ "||" whitespace+ right:logicalOr {
		return left || right;
	}
	/ logicalAnd

logicalAnd
	= left:primary whitespace+ "&&" whitespace+ right:logicalAnd {
		return left && right;
	}
	/ factor

factor
	= "!" whitespace* operand:factor {
		return !operand;
	}
	/ primary

primary
	= variable
	/ "(" logicalOr:logicalOr ")" {
		return logicalOr;
	}

variable
	= variable:identifier accessor:("." identifier)* {
		const path = variable.split( '.' );
		let result = path.reduce( ( nextObject, propertyName ) => nextObject[ propertyName ], options.context );

		for ( let i = 0; i < accessor.length; i++ ) {
			result = result[ accessor[ i ][ 1 ] ];
		}

		return result;
	}

identifier
	= identifier:$[a-zA-Z0-9_]+

whitespace
	= [ \t]
`;

export const parser = peggy.generate( grammar );
