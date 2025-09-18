<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\CSSListItem;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule\Rule;

/**
 * This class is a container for individual 'Rule's.
 *
 * The most common form of a rule set is one constrained by a selector, i.e., a `DeclarationBlock`.
 * However, unknown `AtRule`s (like `@font-face`) are rule sets as well.
 *
 * If you want to manipulate a `RuleSet`, use the methods `addRule(Rule $rule)`, `getRules()` and `removeRule($rule)`
 * (which accepts either a `Rule` or a rule name; optionally suffixed by a dash to remove all related rules).
 *
 * Note that `CSSListItem` extends both `Commentable` and `Renderable`, so those interfaces must also be implemented.
 */
class RuleSet implements CSSElement, CSSListItem, Positionable, RuleContainer
{
    use CommentContainer;
    use Position;

    /**
     * the rules in this rule set, using the property name as the key,
     * with potentially multiple rules per property name.
     *
     * @var array<string, array<int<0, max>, Rule>>
     */
    private $rules = [];

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        $this->setPosition($lineNumber);
    }

    /**
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parseRuleSet(ParserState $parserState, RuleSet $ruleSet): void
    {
        while ($parserState->comes(';')) {
            $parserState->consume(';');
        }
        while (true) {
            $commentsBeforeRule = $parserState->consumeWhiteSpace();
            if ($parserState->comes('}')) {
                break;
            }
            $rule = null;
            if ($parserState->getSettings()->usesLenientParsing()) {
                try {
                    $rule = Rule::parse($parserState, $commentsBeforeRule);
                } catch (UnexpectedTokenException $e) {
                    try {
                        $consumedText = $parserState->consumeUntil(["\n", ';', '}'], true);
                        // We need to “unfind” the matches to the end of the ruleSet as this will be matched later
                        if ($parserState->streql(\substr($consumedText, -1), '}')) {
                            $parserState->backtrack(1);
                        } else {
                            while ($parserState->comes(';')) {
                                $parserState->consume(';');
                            }
                        }
                    } catch (UnexpectedTokenException $e) {
                        // We’ve reached the end of the document. Just close the RuleSet.
                        return;
                    }
                }
            } else {
                $rule = Rule::parse($parserState, $commentsBeforeRule);
            }
            if ($rule instanceof Rule) {
                $ruleSet->addRule($rule);
            }
        }
        $parserState->consume('}');
    }

    /**
     * @throws \UnexpectedValueException
     *         if the last `Rule` is needed as a basis for setting position, but does not have a valid position,
     *         which should never happen
     */
    public function addRule(Rule $ruleToAdd, ?Rule $sibling = null): void
    {
        $propertyName = $ruleToAdd->getRule();
        if (!isset($this->rules[$propertyName])) {
            $this->rules[$propertyName] = [];
        }

        $position = \count($this->rules[$propertyName]);

        if ($sibling !== null) {
            $siblingIsInSet = false;
            $siblingPosition = \array_search($sibling, $this->rules[$propertyName], true);
            if ($siblingPosition !== false) {
                $siblingIsInSet = true;
                $position = $siblingPosition;
            } else {
                $siblingIsInSet = $this->hasRule($sibling);
                if ($siblingIsInSet) {
                    // Maintain ordering within `$this->rules[$propertyName]`
                    // by inserting before first `Rule` with a same-or-later position than the sibling.
                    foreach ($this->rules[$propertyName] as $index => $rule) {
                        if (self::comparePositionable($rule, $sibling) >= 0) {
                            $position = $index;
                            break;
                        }
                    }
                }
            }
            if ($siblingIsInSet) {
                // Increment column number of all existing rules on same line, starting at sibling
                $siblingLineNumber = $sibling->getLineNumber();
                $siblingColumnNumber = $sibling->getColumnNumber();
                foreach ($this->rules as $rulesForAProperty) {
                    foreach ($rulesForAProperty as $rule) {
                        if (
                            $rule->getLineNumber() === $siblingLineNumber &&
                            $rule->getColumnNumber() >= $siblingColumnNumber
                        ) {
                            $rule->setPosition($siblingLineNumber, $rule->getColumnNumber() + 1);
                        }
                    }
                }
                $ruleToAdd->setPosition($siblingLineNumber, $siblingColumnNumber);
            }
        }

        if ($ruleToAdd->getLineNumber() === null) {
            //this node is added manually, give it the next best line
            $columnNumber = $ruleToAdd->getColumnNumber() ?? 0;
            $rules = $this->getRules();
            $rulesCount = \count($rules);
            if ($rulesCount > 0) {
                $last = $rules[$rulesCount - 1];
                $lastsLineNumber = $last->getLineNumber();
                if (!\is_int($lastsLineNumber)) {
                    throw new \UnexpectedValueException(
                        'A Rule without a line number was found during addRule',
                        1750718399
                    );
                }
                $ruleToAdd->setPosition($lastsLineNumber + 1, $columnNumber);
            } else {
                $ruleToAdd->setPosition(1, $columnNumber);
            }
        } elseif ($ruleToAdd->getColumnNumber() === null) {
            $ruleToAdd->setPosition($ruleToAdd->getLineNumber(), 0);
        }

        \array_splice($this->rules[$propertyName], $position, 0, [$ruleToAdd]);
    }

    /**
     * Returns all rules matching the given rule name
     *
     * @example $ruleSet->getRules('font') // returns array(0 => $rule, …) or array().
     *
     * @example $ruleSet->getRules('font-')
     *          //returns an array of all rules either beginning with font- or matching font.
     *
     * @param string|null $searchPattern
     *        Pattern to search for. If null, returns all rules.
     *        If the pattern ends with a dash, all rules starting with the pattern are returned
     *        as well as one matching the pattern with the dash excluded.
     *
     * @return array<int<0, max>, Rule>
     */
    public function getRules(?string $searchPattern = null): array
    {
        $result = [];
        foreach ($this->rules as $propertyName => $rules) {
            // Either no search rule is given or the search rule matches the found rule exactly
            // or the search rule ends in “-” and the found rule starts with the search rule.
            if (
                $searchPattern === null || $propertyName === $searchPattern
                || (
                    \strrpos($searchPattern, '-') === \strlen($searchPattern) - \strlen('-')
                    && (\strpos($propertyName, $searchPattern) === 0
                        || $propertyName === \substr($searchPattern, 0, -1))
                )
            ) {
                $result = \array_merge($result, $rules);
            }
        }
        \usort($result, [self::class, 'comparePositionable']);

        return $result;
    }

    /**
     * Overrides all the rules of this set.
     *
     * @param array<Rule> $rules The rules to override with.
     */
    public function setRules(array $rules): void
    {
        $this->rules = [];
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Returns all rules matching the given pattern and returns them in an associative array with the rule’s name
     * as keys. This method exists mainly for backwards-compatibility and is really only partially useful.
     *
     * Note: This method loses some information: Calling this (with an argument of `background-`) on a declaration block
     * like `{ background-color: green; background-color; rgba(0, 127, 0, 0.7); }` will only yield an associative array
     * containing the rgba-valued rule while `getRules()` would yield an indexed array containing both.
     *
     * @param string|null $searchPattern
     *        Pattern to search for. If null, returns all rules. If the pattern ends with a dash,
     *        all rules starting with the pattern are returned as well as one matching the pattern with the dash
     *        excluded.
     *
     * @return array<string, Rule>
     */
    public function getRulesAssoc(?string $searchPattern = null): array
    {
        /** @var array<string, Rule> $result */
        $result = [];
        foreach ($this->getRules($searchPattern) as $rule) {
            $result[$rule->getRule()] = $rule;
        }

        return $result;
    }

    /**
     * Removes a `Rule` from this `RuleSet` by identity.
     */
    public function removeRule(Rule $ruleToRemove): void
    {
        $nameOfPropertyToRemove = $ruleToRemove->getRule();
        if (!isset($this->rules[$nameOfPropertyToRemove])) {
            return;
        }
        foreach ($this->rules[$nameOfPropertyToRemove] as $key => $rule) {
            if ($rule === $ruleToRemove) {
                unset($this->rules[$nameOfPropertyToRemove][$key]);
            }
        }
    }

    /**
     * Removes rules by property name or search pattern.
     *
     * @param string $searchPattern
     *        pattern to remove.
     *        If the pattern ends in a dash,
     *        all rules starting with the pattern are removed as well as one matching the pattern with the dash
     *        excluded.
     */
    public function removeMatchingRules(string $searchPattern): void
    {
        foreach ($this->rules as $propertyName => $rules) {
            // Either the search rule matches the found rule exactly
            // or the search rule ends in “-” and the found rule starts with the search rule or equals it
            // (without the trailing dash).
            if (
                $propertyName === $searchPattern
                || (\strrpos($searchPattern, '-') === \strlen($searchPattern) - \strlen('-')
                    && (\strpos($propertyName, $searchPattern) === 0
                        || $propertyName === \substr($searchPattern, 0, -1)))
            ) {
                unset($this->rules[$propertyName]);
            }
        }
    }

    public function removeAllRules(): void
    {
        $this->rules = [];
    }

    /**
     * @internal
     */
    public function render(OutputFormat $outputFormat): string
    {
        return $this->renderRules($outputFormat);
    }

    protected function renderRules(OutputFormat $outputFormat): string
    {
        $result = '';
        $isFirst = true;
        $nextLevelFormat = $outputFormat->nextLevel();
        foreach ($this->getRules() as $rule) {
            $nextLevelFormatter = $nextLevelFormat->getFormatter();
            $renderedRule = $nextLevelFormatter->safely(static function () use ($rule, $nextLevelFormat): string {
                return $rule->render($nextLevelFormat);
            });
            if ($renderedRule === null) {
                continue;
            }
            if ($isFirst) {
                $isFirst = false;
                $result .= $nextLevelFormatter->spaceBeforeRules();
            } else {
                $result .= $nextLevelFormatter->spaceBetweenRules();
            }
            $result .= $renderedRule;
        }

        $formatter = $outputFormat->getFormatter();
        if (!$isFirst) {
            // Had some output
            $result .= $formatter->spaceAfterRules();
        }

        return $formatter->removeLastSemicolon($result);
    }

    /**
     * @return int negative if `$first` is before `$second`; zero if they have the same position; positive otherwise
     *
     * @throws \UnexpectedValueException if either argument does not have a valid position, which should never happen
     */
    private static function comparePositionable(Positionable $first, Positionable $second): int
    {
        $firstsLineNumber = $first->getLineNumber();
        $secondsLineNumber = $second->getLineNumber();
        if (!\is_int($firstsLineNumber) || !\is_int($secondsLineNumber)) {
            throw new \UnexpectedValueException(
                'A Rule without a line number was passed to comparePositionable',
                1750637683
            );
        }

        if ($firstsLineNumber === $secondsLineNumber) {
            $firstsColumnNumber = $first->getColumnNumber();
            $secondsColumnNumber = $second->getColumnNumber();
            if (!\is_int($firstsColumnNumber) || !\is_int($secondsColumnNumber)) {
                throw new \UnexpectedValueException(
                    'A Rule without a column number was passed to comparePositionable',
                    1750637761
                );
            }
            return $firstsColumnNumber - $secondsColumnNumber;
        }

        return $firstsLineNumber - $secondsLineNumber;
    }

    private function hasRule(Rule $rule): bool
    {
        foreach ($this->rules as $rulesForAProperty) {
            if (\in_array($rule, $rulesForAProperty, true)) {
                return true;
            }
        }

        return false;
    }
}
