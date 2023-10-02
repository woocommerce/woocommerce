/**
 * External dependencies
 */
import * as peggy from 'peggy';

const grammar = `
Start = LogicalOr

LogicalOr
	= left:LogicalAnd WhiteSpace+ "||" WhiteSpace+ right:LogicalOr {
		return left || right;
	}
	/ LogicalAnd

LogicalAnd
	= left:Primary WhiteSpace+ "&&" WhiteSpace+ right:LogicalAnd {
		return left && right;
	}
	/ Factor

Factor
	= "!" WhiteSpace* operand:Factor {
		return !operand;
	}
	/ Primary

Primary
	= Variable
	/ "(" logicalOr:LogicalOr ")" {
		return logicalOr;
	}

Variable
	= variable:Identifier accessor:("." Identifier)* {
		const path = variable.split( '.' );
		let result = path.reduce( ( nextObject, propertyName ) => nextObject[ propertyName ], options.context );

		for ( let i = 0; i < accessor.length; i++ ) {
			result = result[ accessor[ i ][ 1 ] ];
		}

		return result;
	}

Identifier
	= identifier:$[a-zA-Z0-9_]+

WhiteSpace
	= " "
	/ "\t"
`;

export const parser = peggy.generate( grammar );
