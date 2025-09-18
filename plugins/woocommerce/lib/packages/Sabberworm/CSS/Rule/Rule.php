<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\RuleValueList;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\Value;

/**
 * `Rule`s just have a string key (the rule) and a 'Value'.
 *
 * In CSS, `Rule`s are expressed as follows: “key: value[0][0] value[0][1], value[1][0] value[1][1];”
 */
class Rule implements Commentable, CSSElement, Positionable
{
    use CommentContainer;
    use Position;

    /**
     * @var non-empty-string
     */
    private $rule;

    /**
     * @var RuleValueList|string|null
     */
    private $value;

    /**
     * @var bool
     */
    private $isImportant = false;

    /**
     * @param non-empty-string $rule
     * @param int<1, max>|null $lineNumber
     * @param int<0, max>|null $columnNumber
     */
    public function __construct(string $rule, ?int $lineNumber = null, ?int $columnNumber = null)
    {
        $this->rule = $rule;
        $this->setPosition($lineNumber, $columnNumber);
    }

    /**
     * @param list<Comment> $commentsBeforeRule
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState, array $commentsBeforeRule = []): Rule
    {
        $comments = \array_merge($commentsBeforeRule, $parserState->consumeWhiteSpace());
        $rule = new Rule(
            $parserState->parseIdentifier(!$parserState->comes('--')),
            $parserState->currentLine(),
            $parserState->currentColumn()
        );
        $rule->setComments($comments);
        $rule->addComments($parserState->consumeWhiteSpace());
        $parserState->consume(':');
        $value = Value::parseValue($parserState, self::listDelimiterForRule($rule->getRule()));
        $rule->setValue($value);
        $parserState->consumeWhiteSpace();
        if ($parserState->comes('!')) {
            $parserState->consume('!');
            $parserState->consumeWhiteSpace();
            $parserState->consume('important');
            $rule->setIsImportant(true);
        }
        $parserState->consumeWhiteSpace();
        while ($parserState->comes(';')) {
            $parserState->consume(';');
        }

        return $rule;
    }

    /**
     * Returns a list of delimiters (or separators).
     * The first item is the innermost separator (or, put another way, the highest-precedence operator).
     * The sequence continues to the outermost separator (or lowest-precedence operator).
     *
     * @param non-empty-string $rule
     *
     * @return list<non-empty-string>
     */
    private static function listDelimiterForRule(string $rule): array
    {
        if (\preg_match('/^font($|-)/', $rule)) {
            return [',', '/', ' '];
        }

        switch ($rule) {
            case 'src':
                return [' ', ','];
            default:
                return [',', ' ', '/'];
        }
    }

    /**
     * @param non-empty-string $rule
     */
    public function setRule(string $rule): void
    {
        $this->rule = $rule;
    }

    /**
     * @return non-empty-string
     */
    public function getRule(): string
    {
        return $this->rule;
    }

    /**
     * @return RuleValueList|string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param RuleValueList|string|null $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Adds a value to the existing value. Value will be appended if a `RuleValueList` exists of the given type.
     * Otherwise, the existing value will be wrapped by one.
     *
     * @param RuleValueList|array<int, RuleValueList> $value
     */
    public function addValue($value, string $type = ' '): void
    {
        if (!\is_array($value)) {
            $value = [$value];
        }
        if (!($this->value instanceof RuleValueList) || $this->value->getListSeparator() !== $type) {
            $currentValue = $this->value;
            $this->value = new RuleValueList($type, $this->getLineNumber());
            if ($currentValue !== null && $currentValue !== '') {
                $this->value->addListComponent($currentValue);
            }
        }
        foreach ($value as $valueItem) {
            $this->value->addListComponent($valueItem);
        }
    }

    public function setIsImportant(bool $isImportant): void
    {
        $this->isImportant = $isImportant;
    }

    public function getIsImportant(): bool
    {
        return $this->isImportant;
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        $formatter = $outputFormat->getFormatter();
        $result = "{$formatter->comments($this)}{$this->rule}:{$formatter->spaceAfterRuleName()}";
        if ($this->value instanceof Value) { // Can also be a ValueList
            $result .= $this->value->render($outputFormat);
        } else {
            $result .= $this->value;
        }
        if ($this->isImportant) {
            $result .= ' !important';
        }
        $result .= ';';
        return $result;
    }
}
