/**
 * External dependencies
 */
import * as peggy from 'peggy';

const grammar = `
{{
	function evaluateBinaryExpression( head, tail ) {
		return tail.reduce( ( leftOperand, tailElement ) => {
			const operator = tailElement[ 1 ];
			const rightOperand = tailElement[ 3 ];

			switch ( operator ) {
				case '&&':
					return leftOperand && rightOperand;
					break;
				case '||':
					return leftOperand || rightOperand;
					break;
				case '===':
					return leftOperand === rightOperand;
					break;
				case '!==':
					return leftOperand !== rightOperand;
					break;
				case '==':
					return leftOperand == rightOperand;
					break;
				case '!=':
					return leftOperand != rightOperand;
					break;
				default:
					return undefined;
					break;
			}
		}, head );
	}
}}

Start
	= Expression

SourceCharacter
	= .

WhiteSpace
	= " "
	/ "\\t"

LineTerminator
	= "\\n"
	/ "\\r"
	/ "\\u2028"
	/ "\\u2029"

LineTerminatorSequence
	= "\\n"
	/ "\\r\\n"
	/ "\\r"
	/ "\\u2028"
	/ "\\u2029"

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
	/ StringLiteral

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

StringLiteral
	= '"' chars:DoubleQuotedStringCharacter* '"' {
		return chars.join( '' );
	}
	/ "'" chars:SingleQuotedStringCharacter* "'" {
		return chars.join( '' );
	}

DoubleQuotedStringCharacter
	= !('"' / "\\\\" / LineTerminator) SourceCharacter {
		return text();
	}
	/ "\\\\" escapeSequence:EscapeSequence {
		return escapeSequence;
	}
	/ LineContinuation

SingleQuotedStringCharacter
	= !("'" / "\\\\" / LineTerminator) SourceCharacter {
		return text();
	}
	/ "\\\\" escapeSequence:EscapeSequence {
		return escapeSequence;
	}
	/ LineContinuation

LineContinuation
	= "\\\\" LineTerminatorSequence {
		return '';
	}

EscapeSequence
	= CharacterEscapeSequence
	/ "0" !DecimalDigit {
		return "\\0";
	}
	/ HexEscapeSequence
	/ UnicodeEscapeSequence

CharacterEscapeSequence
	= SingleEscapeCharacter
	/ NonEscapeCharacter

SingleEscapeCharacter
	= "'"
	/ '"'
	/ "\\\\"
	/ "b" {
		return "\\b";
	}
	/ "f" {
		return "\\f";
	}
	/ "n" {
		return "\\n";
	}
	/ "r" {
		return "\\r";
	}
	/ "t" {
		return "\\t";
	}
	/ "v" {
		return "\\v";
	}

NonEscapeCharacter
	= (!EscapeCharacter / LineTerminator) SourceCharacter {
		return text();
	}

EscapeCharacter
	= SingleEscapeCharacter
	/ DecimalDigit
	/ "x"
	/ "u"

HexEscapeSequence
	= "x" digits:$(HexDigit HexDigit) {
		return String.fromCharCode( parseInt( digits, 16 ) );
	}

UnicodeEscapeSequence
	= "u" digits:$(HexDigit HexDigit HexDigit HexDigit) {
		return String.fromCharCode( parseInt( digits, 16 ) );
	}

// Tokens

NullToken
	= "null" !IdentifierPart

TrueToken
	= "true" !IdentifierPart

FalseToken
	= "false" !IdentifierPart

// Expressions

PrimaryExpression
	= IdentifierPath
	/ Literal
	/ "(" WhiteSpace* expression:Expression WhiteSpace* ")" {
		return expression;
	}

RelationalExpression
	= PrimaryExpression

EqualityExpression
	= head:RelationalExpression tail:( WhiteSpace* EqualityOperator WhiteSpace* RelationalExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

EqualityOperator
	= "==="
	/ "!=="
	/ "=="
	/ "!="

LogicalAndExpression
	= head:EqualityExpression tail:(WhiteSpace+ LogicalAndOperator WhiteSpace+ EqualityExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

LogicalAndOperator
	= "&&"

Factor
	= "!" WhiteSpace* operand:Factor {
		return !operand;
	}
	/ PrimaryExpression

LogicalOrExpression
	= head:LogicalAndExpression tail:(WhiteSpace+ LogicalOrOperator WhiteSpace+ LogicalAndExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

LogicalOrOperator
	= "||"

Expression
	= LogicalOrExpression
`;

export const parser = peggy.generate( grammar );
