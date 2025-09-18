<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

class CalcFunction extends CSSFunction
{
    /**
     * @var int
     */
    private const T_OPERAND = 1;

    /**
     * @var int
     */
    private const T_OPERATOR = 2;

    /**
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState, bool $ignoreCase = false): CSSFunction
    {
        $operators = ['+', '-', '*', '/'];
        $function = $parserState->parseIdentifier();
        if ($parserState->peek() !== '(') {
            // Found ; or end of line before an opening bracket
            throw new UnexpectedTokenException('(', $parserState->peek(), 'literal', $parserState->currentLine());
        } elseif ($function !== 'calc') {
            // Found invalid calc definition. Example calc (...
            throw new UnexpectedTokenException('calc', $function, 'literal', $parserState->currentLine());
        }
        $parserState->consume('(');
        $calcRuleValueList = new CalcRuleValueList($parserState->currentLine());
        $list = new RuleValueList(',', $parserState->currentLine());
        $nestingLevel = 0;
        $lastComponentType = null;
        while (!$parserState->comes(')') || $nestingLevel > 0) {
            if ($parserState->isEnd() && $nestingLevel === 0) {
                break;
            }

            $parserState->consumeWhiteSpace();
            if ($parserState->comes('(')) {
                $nestingLevel++;
                $calcRuleValueList->addListComponent($parserState->consume(1));
                $parserState->consumeWhiteSpace();
                continue;
            } elseif ($parserState->comes(')')) {
                $nestingLevel--;
                $calcRuleValueList->addListComponent($parserState->consume(1));
                $parserState->consumeWhiteSpace();
                continue;
            }
            if ($lastComponentType !== CalcFunction::T_OPERAND) {
                $value = Value::parsePrimitiveValue($parserState);
                $calcRuleValueList->addListComponent($value);
                $lastComponentType = CalcFunction::T_OPERAND;
            } else {
                if (\in_array($parserState->peek(), $operators, true)) {
                    if (($parserState->comes('-') || $parserState->comes('+'))) {
                        if (
                            $parserState->peek(1, -1) !== ' '
                            || !($parserState->comes('- ')
                                || $parserState->comes('+ '))
                        ) {
                            throw new UnexpectedTokenException(
                                " {$parserState->peek()} ",
                                $parserState->peek(1, -1) . $parserState->peek(2),
                                'literal',
                                $parserState->currentLine()
                            );
                        }
                    }
                    $calcRuleValueList->addListComponent($parserState->consume(1));
                    $lastComponentType = CalcFunction::T_OPERATOR;
                } else {
                    throw new UnexpectedTokenException(
                        \sprintf(
                            'Next token was expected to be an operand of type %s. Instead "%s" was found.',
                            \implode(', ', $operators),
                            $parserState->peek()
                        ),
                        '',
                        'custom',
                        $parserState->currentLine()
                    );
                }
            }
            $parserState->consumeWhiteSpace();
        }
        $list->addListComponent($calcRuleValueList);
        if (!$parserState->isEnd()) {
            $parserState->consume(')');
        }
        return new CalcFunction($function, $list, ',', $parserState->currentLine());
    }
}
