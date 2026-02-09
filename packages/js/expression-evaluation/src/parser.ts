/**
 * External dependencies
 */
import * as peggy from 'peggy';

const grammar = `
{{
	function evaluateUnaryExpression( operator, operand ) {
		switch ( operator ) {
			case '!':
				return !operand;
				break;
			case '-':
				return -operand;
				break;
			case '+':
				return +operand;
				break;
			default:
				return undefined;
				break;
		}
	}

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
				case '<=':
					return leftOperand <= rightOperand;
					break;
				case '<':
					return leftOperand < rightOperand;
					break;
				case '>=':
					return leftOperand >= rightOperand;
					break;
				case '>':
					return leftOperand > rightOperand;
					break;
				case '+':
					return leftOperand + rightOperand;
					break;
				case '-':
					return leftOperand - rightOperand;
					break;
				case '*':
					return leftOperand * rightOperand;
					break;
				case '/':
					return leftOperand / rightOperand;
					break;
				case '%':
					return leftOperand % rightOperand;
					break;
				default:
					return undefined;
					break;
			}
		}, head );
	}

	function getPropertyValue( obj, propertyName ) {
		if ( Object.hasOwn( obj, propertyName ) ) {
			return obj[ propertyName ];
		} else if (
			Array.isArray( obj ) &&
			obj.length > 0 &&
			Object.hasOwn( obj[ 0 ], 'key' ) &&
			Object.hasOwn( obj[ 0 ], 'value' )
		) {
			// We likely dealing with an array of objects with key/value pairs (like post meta data)
			const item = obj.find( ( item ) => item.key === propertyName );
			return item?.value;
		}

		return undefined;
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

Comment "comment"
	= MultiLineComment

MultiLineComment
	= "/*" (!"*/" SourceCharacter)* "*/"

__ "skipped"
	= (WhiteSpace / LineTerminatorSequence / Comment)*

IdentifierPath
	= variable:Identifier accessor:(__ "." __ Identifier)* {
		const path = variable.split( '.' );
		let result = path.reduce( getPropertyValue, options.context );

		for ( let i = 0; i < accessor.length; i++ ) {
			result = getPropertyValue( result, accessor[ i ][ 3 ] );
		}

		return result;
	}

Identifier
	= !ReservedWord name:IdentifierName {
		return name;
	}

IdentifierName
	= first:IdentifierStart rest:IdentifierPart* {
		return text();
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
	/ "(" __ expression:Expression __ ")" {
		return expression;
	}

UnaryExpression
	= PrimaryExpression
	/ operator:UnaryOperator __ operand:UnaryExpression {
		return evaluateUnaryExpression( operator, operand );
	}

UnaryOperator
	= "!"
	/ "-"
	/ "+"

MultiplicativeExpression
	= head:UnaryExpression tail:(__ MultiplicativeOperator __ UnaryExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

MultiplicativeOperator
	= "*"
	/ "/"
	/ "%"

AdditiveExpression
	= head:MultiplicativeExpression tail:(__ AdditiveOperator __ MultiplicativeExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

AdditiveOperator
	= "+"
	/ "-"

RelationalExpression
	= head:AdditiveExpression tail:(__ RelationalOperator __ AdditiveExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

RelationalOperator
	= "<="
	/ "<"
	/ ">="
	/ ">"

EqualityExpression
	= head:RelationalExpression tail:(__ EqualityOperator __ RelationalExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

EqualityOperator
	= "==="
	/ "!=="
	/ "=="
	/ "!="

LogicalAndExpression
	= head:EqualityExpression tail:(__ LogicalAndOperator __ EqualityExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

LogicalAndOperator
	= "&&"

LogicalOrExpression
	= head:LogicalAndExpression tail:(__ LogicalOrOperator __ LogicalAndExpression)* {
		return evaluateBinaryExpression( head, tail );
	}

LogicalOrOperator
	= "||"

ConditionalExpression
	= condition:LogicalOrExpression __ ConditionalTrueOperator __ expressionIfTrue:ConditionalExpression __ ConditionalFalseOperator __ expressionIfFalse:ConditionalExpression {
		return condition ? expressionIfTrue : expressionIfFalse;
	}
	/ LogicalOrExpression

ConditionalTrueOperator
	= "?"

ConditionalFalseOperator
	= ":"

Expression
	= __ expression:ConditionalExpression __ {
		return expression;
	}
`;

export const parser = peggy.generate( grammar );
