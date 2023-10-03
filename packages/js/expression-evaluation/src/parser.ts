/**
 * External dependencies
 */
import * as peggy from 'peggy';

const grammar = `
Start
	= LogicalOr

WhiteSpace
	= " "
	/ "\t"

IdentifierPath
	= variable:Identifier accessor:("." Identifier)* {
		const path = variable.split( '.' );
		let result = path.reduce( ( nextObject, propertyName ) => nextObject[ propertyName ], options.context );

		for ( let i = 0; i < accessor.length; i++ ) {
			result = result[ accessor[ i ][ 1 ] ];
		}

		return result;
	}

Identifier
	= !ReservedWord name:IdentifierName {
		return name;
	}

IdentifierName
	= first:IdentifierStart rest:IdentifierPart* {
		return first + rest.join( '' );
	}

IdentifierStart
	= [a-zA-Z]
	/ "_"
	/ "$"

IdentifierPart
	= IdentifierStart

ReservedWord
	= NullLiteral
	/ BooleanLiteral

// Literals

Literal
	= NullLiteral
	/ BooleanLiteral
	/ NumericLiteral

NullLiteral
	= NullToken { return null; }

BooleanLiteral
	= "true" { return true; }
	/ "false" { return false; }

NumericLiteral
	= literal:HexIntegerLiteral !(IdentifierStart / DecimalDigit) {
		return literal;
	}
	/ literal:DecimalLiteral !(IdentifierStart / DecimalDigit) {
		return literal;
	}

HexIntegerLiteral
	= "0x"i digits:$HexDigit+ {
		return parseInt( digits, 16 );
	}

HexDigit
	= [0-9a-f]i

DecimalLiteral
	= DecimalIntegerLiteral "." DecimalDigit* ExponentPart? {
		return parseFloat( text() );
	}
	/ "." DecimalDigit+ ExponentPart? {
		return parseFloat( text() );
	}
	/ DecimalIntegerLiteral ExponentPart? {
		return parseFloat( text() );
	}

DecimalIntegerLiteral
	= "0"
	/ NonZeroDigit DecimalDigit*

DecimalDigit
	= [0-9]

NonZeroDigit
	= [1-9]

ExponentPart
	= ExponentIndicator SignedInteger

ExponentIndicator
	= "e"i

SignedInteger
	= [+-]? DecimalDigit+

// Tokens

NullToken
	= "null" !IdentifierPart

TrueToken
	= "true" !IdentifierPart

FalseToken
	= "false" !IdentifierPart

// Logical Expressions

LogicalOr
	= left:LogicalAnd WhiteSpace+ "||" WhiteSpace+ right:LogicalOr {
		return left || right;
	}
	/ LogicalAnd

LogicalAnd
	= left:PrimaryExpression WhiteSpace+ "&&" WhiteSpace+ right:LogicalAnd {
		return left && right;
	}
	/ Factor

Factor
	= "!" WhiteSpace* operand:Factor {
		return !operand;
	}
	/ PrimaryExpression

PrimaryExpression
	= IdentifierPath
	/ Literal
	/ "(" WhiteSpace* expression:LogicalOr WhiteSpace* ")" {
		return expression;
	}
`;

export const parser = peggy.generate( grammar );
