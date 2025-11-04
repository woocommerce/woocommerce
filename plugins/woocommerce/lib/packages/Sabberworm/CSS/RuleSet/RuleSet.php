<?php

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\Comment;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\Commentable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Renderable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule\Rule;

/**
 * This class is a container for individual 'Rule's.
 *
 * The most common form of a rule set is one constrained by a selector, i.e., a `DeclarationBlock`.
 * However, unknown `AtRule`s (like `@font-face`) are rule sets as well.
 *
 * If you want to manipulate a `RuleSet`, use the methods `addRule(Rule $rule)`, `getRules()` and `removeRule($rule)`
 * (which accepts either a `Rule` or a rule name; optionally suffixed by a dash to remove all related rules).
 */
abstract class RuleSet implements CSSElement, Commentable, Positionable
{
    use Position;

    /**
     * the rules in this rule set, using the property name as the key,
     * with potentially multiple rules per property name.
     *
     * @var array<string, array<int<0, max>, Rule>>
     */
    private $aRules;

    /**
     * @var array<array-key, Comment>
     *
     * @internal since 8.8.0
     */
    protected $aComments;

    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        $this->aRules = [];
        $this->setPosition($iLineNo);
        $this->aComments = [];
    }

    /**
     * @return void
     *
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parseRuleSet(ParserState $oParserState, RuleSet $oRuleSet)
    {
        while ($oParserState->comes(';')) {
            $oParserState->consume(';');
        }
        while (true) {
            $commentsBeforeRule = $oParserState->consumeWhiteSpace();
            if ($oParserState->comes('}')) {
                break;
            }
            $oRule = null;
            if ($oParserState->getSettings()->bLenientParsing) {
                try {
                    $oRule = Rule::parse($oParserState, $commentsBeforeRule);
                } catch (UnexpectedTokenException $e) {
                    try {
                        $sConsume = $oParserState->consumeUntil(["\n", ";", '}'], true);
                        // We need to “unfind” the matches to the end of the ruleSet as this will be matched later
                        if ($oParserState->streql(substr($sConsume, -1), '}')) {
                            $oParserState->backtrack(1);
                        } else {
                            while ($oParserState->comes(';')) {
                                $oParserState->consume(';');
                            }
                        }
                    } catch (UnexpectedTokenException $e) {
                        // We’ve reached the end of the document. Just close the RuleSet.
                        return;
                    }
                }
            } else {
                $oRule = Rule::parse($oParserState, $commentsBeforeRule);
            }
            if ($oRule) {
                $oRuleSet->addRule($oRule);
            }
        }
        $oParserState->consume('}');
    }

    /**
     * @param Rule|null $oSibling
     *
     * @return void
     */
    public function addRule(Rule $oRule, $oSibling = null)
    {
        $sRule = $oRule->getRule();
        if (!isset($this->aRules[$sRule])) {
            $this->aRules[$sRule] = [];
        }

        $iPosition = count($this->aRules[$sRule]);

        if ($oSibling !== null) {
            $iSiblingPos = array_search($oSibling, $this->aRules[$sRule], true);
            if ($iSiblingPos !== false) {
                $iPosition = $iSiblingPos;
                $oRule->setPosition($oSibling->getLineNo(), $oSibling->getColNo() - 1);
            }
        }
        if ($oRule->getLineNumber() === null) {
            //this node is added manually, give it the next best line
            $columnNumber = $oRule->getColNo();
            $rules = $this->getRules();
            $pos = count($rules);
            if ($pos > 0) {
                $last = $rules[$pos - 1];
                $oRule->setPosition($last->getLineNo() + 1, $columnNumber);
            } else {
                $oRule->setPosition(1, $columnNumber);
            }
        } elseif ($oRule->getColumnNumber() === null) {
            $oRule->setPosition($oRule->getLineNumber(), 0);
        }

        array_splice($this->aRules[$sRule], $iPosition, 0, [$oRule]);
    }

    /**
     * Returns all rules matching the given rule name
     *
     * @example $oRuleSet->getRules('font') // returns array(0 => $oRule, …) or array().
     *
     * @example $oRuleSet->getRules('font-')
     *          //returns an array of all rules either beginning with font- or matching font.
     *
     * @param Rule|string|null $mRule
     *        Pattern to search for. If null, returns all rules.
     *        If the pattern ends with a dash, all rules starting with the pattern are returned
     *        as well as one matching the pattern with the dash excluded.
     *        Passing a `Rule` for this parameter is deprecated in version 8.9.0, and will not work from v9.0.
     *        Call `getRules($rule->getRule())` instead.
     *
     * @return array<int, Rule>
     */
    public function getRules($mRule = null)
    {
        if ($mRule instanceof Rule) {
            $mRule = $mRule->getRule();
        }
        /** @var array<int, Rule> $aResult */
        $aResult = [];
        foreach ($this->aRules as $sName => $aRules) {
            // Either no search rule is given or the search rule matches the found rule exactly
            // or the search rule ends in “-” and the found rule starts with the search rule.
            if (
                !$mRule || $sName === $mRule
                || (
                    strrpos($mRule, '-') === strlen($mRule) - strlen('-')
                    && (strpos($sName, $mRule) === 0 || $sName === substr($mRule, 0, -1))
                )
            ) {
                $aResult = array_merge($aResult, $aRules);
            }
        }
        usort($aResult, function (Rule $first, Rule $second) {
            if ($first->getLineNo() === $second->getLineNo()) {
                return $first->getColNo() - $second->getColNo();
            }
            return $first->getLineNo() - $second->getLineNo();
        });
        return $aResult;
    }

    /**
     * Overrides all the rules of this set.
     *
     * @param array<array-key, Rule> $aRules The rules to override with.
     *
     * @return void
     */
    public function setRules(array $aRules)
    {
        $this->aRules = [];
        foreach ($aRules as $rule) {
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
     * @param Rule|string|null $mRule $mRule
     *        Pattern to search for. If null, returns all rules. If the pattern ends with a dash,
     *        all rules starting with the pattern are returned as well as one matching the pattern with the dash
     *        excluded.
     *        Passing a `Rule` for this parameter is deprecated in version 8.9.0, and will not work from v9.0.
     *        Call `getRulesAssoc($rule->getRule())` instead.
     *
     * @return array<string, Rule>
     */
    public function getRulesAssoc($mRule = null)
    {
        /** @var array<string, Rule> $aResult */
        $aResult = [];
        foreach ($this->getRules($mRule) as $oRule) {
            $aResult[$oRule->getRule()] = $oRule;
        }
        return $aResult;
    }

    /**
     * Removes a `Rule` from this `RuleSet` by identity.
     *
     * @param Rule|string|null $mRule
     *        `Rule` to remove.
     *        Passing a `string` or `null` is deprecated in version 8.9.0, and will no longer work from v9.0.
     *        Use `removeMatchingRules()` or `removeAllRules()` instead.
     */
    public function removeRule($mRule)
    {
        if ($mRule instanceof Rule) {
            $sRule = $mRule->getRule();
            if (!isset($this->aRules[$sRule])) {
                return;
            }
            foreach ($this->aRules[$sRule] as $iKey => $oRule) {
                if ($oRule === $mRule) {
                    unset($this->aRules[$sRule][$iKey]);
                }
            }
        } elseif ($mRule !== null) {
            $this->removeMatchingRules($mRule);
        } else {
            $this->removeAllRules();
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
    public function removeMatchingRules($searchPattern)
    {
        foreach ($this->aRules as $propertyName => $rules) {
            // Either the search rule matches the found rule exactly
            // or the search rule ends in “-” and the found rule starts with the search rule or equals it
            // (without the trailing dash).
            if (
                $propertyName === $searchPattern
                || (\strrpos($searchPattern, '-') === \strlen($searchPattern) - \strlen('-')
                    && (\strpos($propertyName, $searchPattern) === 0
                        || $propertyName === \substr($searchPattern, 0, -1)))
            ) {
                unset($this->aRules[$propertyName]);
            }
        }
    }

    public function removeAllRules()
    {
        $this->aRules = [];
    }

    /**
     * @return string
     *
     * @deprecated in V8.8.0, will be removed in V9.0.0. Use `render` instead.
     */
    public function __toString()
    {
        return $this->render(new OutputFormat());
    }

    /**
     * @return string
     */
    protected function renderRules(OutputFormat $oOutputFormat)
    {
        $sResult = '';
        $bIsFirst = true;
        $oNextLevel = $oOutputFormat->nextLevel();
        foreach ($this->getRules() as $oRule) {
            $sRendered = $oNextLevel->safely(function () use ($oRule, $oNextLevel) {
                return $oRule->render($oNextLevel);
            });
            if ($sRendered === null) {
                continue;
            }
            if ($bIsFirst) {
                $bIsFirst = false;
                $sResult .= $oNextLevel->spaceBeforeRules();
            } else {
                $sResult .= $oNextLevel->spaceBetweenRules();
            }
            $sResult .= $sRendered;
        }

        if (!$bIsFirst) {
            // Had some output
            $sResult .= $oOutputFormat->spaceAfterRules();
        }

        return $oOutputFormat->removeLastSemicolon($sResult);
    }

    /**
     * @param array<string, Comment> $aComments
     *
     * @return void
     */
    public function addComments(array $aComments)
    {
        $this->aComments = array_merge($this->aComments, $aComments);
    }

    /**
     * @return array<string, Comment>
     */
    public function getComments()
    {
        return $this->aComments;
    }

    /**
     * @param array<string, Comment> $aComments
     *
     * @return void
     */
    public function setComments(array $aComments)
    {
        $this->aComments = $aComments;
    }
}
