<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment\CommentContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\CSSList;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\CSSListItem;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList\KeyFrame;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\OutputException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\KeyframeSelector;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule\Rule;

/**
 * This class represents a `RuleSet` constrained by a `Selector`.
 *
 * It contains an array of selector objects (comma-separated in the CSS) as well as the rules to be applied to the
 * matching elements.
 *
 * Declaration blocks usually appear directly inside a `Document` or another `CSSList` (mostly a `MediaQuery`).
 *
 * Note that `CSSListItem` extends both `Commentable` and `Renderable`, so those interfaces must also be implemented.
 */
class DeclarationBlock implements CSSElement, CSSListItem, Positionable, RuleContainer
{
    use CommentContainer;
    use Position;

    /**
     * @var array<Selector|string>
     */
    private $selectors = [];

    /**
     * @var RuleSet
     */
    private $ruleSet;

    /**
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(?int $lineNumber = null)
    {
        $this->ruleSet = new RuleSet($lineNumber);
        $this->setPosition($lineNumber);
    }

    /**
     * @throws UnexpectedTokenException
     * @throws UnexpectedEOFException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState, ?CSSList $list = null): ?DeclarationBlock
    {
        $comments = [];
        $result = new DeclarationBlock($parserState->currentLine());
        try {
            $selectors = [];
            $selectorParts = [];
            $stringWrapperCharacter = null;
            $functionNestingLevel = 0;
            $consumedNextCharacter = false;
            static $stopCharacters = ['{', '}', '\'', '"', '(', ')', ','];
            do {
                if (!$consumedNextCharacter) {
                    $selectorParts[] = $parserState->consume(1);
                }
                $selectorParts[] = $parserState->consumeUntil($stopCharacters, false, false, $comments);
                $nextCharacter = $parserState->peek();
                $consumedNextCharacter = false;
                switch ($nextCharacter) {
                    case '\'':
                        // The fallthrough is intentional.
                    case '"':
                        if (!\is_string($stringWrapperCharacter)) {
                            $stringWrapperCharacter = $nextCharacter;
                        } elseif ($stringWrapperCharacter === $nextCharacter) {
                            if (\substr(\end($selectorParts), -1) !== '\\') {
                                $stringWrapperCharacter = null;
                            }
                        }
                        break;
                    case '(':
                        if (!\is_string($stringWrapperCharacter)) {
                            ++$functionNestingLevel;
                        }
                        break;
                    case ')':
                        if (!\is_string($stringWrapperCharacter)) {
                            if ($functionNestingLevel <= 0) {
                                throw new UnexpectedTokenException('anything but', ')');
                            }
                            --$functionNestingLevel;
                        }
                        break;
                    case ',':
                        if (!\is_string($stringWrapperCharacter) && $functionNestingLevel === 0) {
                            $selectors[] = \implode('', $selectorParts);
                            $selectorParts = [];
                            $parserState->consume(1);
                            $consumedNextCharacter = true;
                        }
                        break;
                }
            } while (!\in_array($nextCharacter, ['{', '}'], true) || \is_string($stringWrapperCharacter));
            if ($functionNestingLevel !== 0) {
                throw new UnexpectedTokenException(')', $nextCharacter);
            }
            $selectors[] = \implode('', $selectorParts); // add final or only selector
            $result->setSelectors($selectors, $list);
            if ($parserState->comes('{')) {
                $parserState->consume(1);
            }
        } catch (UnexpectedTokenException $e) {
            if ($parserState->getSettings()->usesLenientParsing()) {
                if (!$parserState->comes('}')) {
                    $parserState->consumeUntil('}', false, true);
                }
                return null;
            } else {
                throw $e;
            }
        }
        $result->setComments($comments);

        RuleSet::parseRuleSet($parserState, $result->getRuleSet());

        return $result;
    }

    /**
     * @param array<Selector|string>|string $selectors
     *
     * @throws UnexpectedTokenException
     */
    public function setSelectors($selectors, ?CSSList $list = null): void
    {
        if (\is_array($selectors)) {
            $this->selectors = $selectors;
        } else {
            $this->selectors = \explode(',', $selectors);
        }
        foreach ($this->selectors as $key => $selector) {
            if (!($selector instanceof Selector)) {
                if ($list === null || !($list instanceof KeyFrame)) {
                    if (!Selector::isValid($selector)) {
                        throw new UnexpectedTokenException(
                            "Selector did not match '" . Selector::SELECTOR_VALIDATION_RX . "'.",
                            $selector,
                            'custom'
                        );
                    }
                    $this->selectors[$key] = new Selector($selector);
                } else {
                    if (!KeyframeSelector::isValid($selector)) {
                        throw new UnexpectedTokenException(
                            "Selector did not match '" . KeyframeSelector::SELECTOR_VALIDATION_RX . "'.",
                            $selector,
                            'custom'
                        );
                    }
                    $this->selectors[$key] = new KeyframeSelector($selector);
                }
            }
        }
    }

    /**
     * Remove one of the selectors of the block.
     *
     * @param Selector|string $selectorToRemove
     */
    public function removeSelector($selectorToRemove): bool
    {
        if ($selectorToRemove instanceof Selector) {
            $selectorToRemove = $selectorToRemove->getSelector();
        }
        foreach ($this->selectors as $key => $selector) {
            if ($selector->getSelector() === $selectorToRemove) {
                unset($this->selectors[$key]);
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<Selector>
     */
    public function getSelectors(): array
    {
        return $this->selectors;
    }

    public function getRuleSet(): RuleSet
    {
        return $this->ruleSet;
    }

    /**
     * @see RuleSet::addRule()
     */
    public function addRule(Rule $ruleToAdd, ?Rule $sibling = null): void
    {
        $this->ruleSet->addRule($ruleToAdd, $sibling);
    }

    /**
     * @see RuleSet::getRules()
     *
     * @return array<int<0, max>, Rule>
     */
    public function getRules(?string $searchPattern = null): array
    {
        return $this->ruleSet->getRules($searchPattern);
    }

    /**
     * @see RuleSet::setRules()
     *
     * @param array<Rule> $rules
     */
    public function setRules(array $rules): void
    {
        $this->ruleSet->setRules($rules);
    }

    /**
     * @see RuleSet::getRulesAssoc()
     *
     * @return array<string, Rule>
     */
    public function getRulesAssoc(?string $searchPattern = null): array
    {
        return $this->ruleSet->getRulesAssoc($searchPattern);
    }

    /**
     * @see RuleSet::removeRule()
     */
    public function removeRule(Rule $ruleToRemove): void
    {
        $this->ruleSet->removeRule($ruleToRemove);
    }

    /**
     * @see RuleSet::removeMatchingRules()
     */
    public function removeMatchingRules(string $searchPattern): void
    {
        $this->ruleSet->removeMatchingRules($searchPattern);
    }

    /**
     * @see RuleSet::removeAllRules()
     */
    public function removeAllRules(): void
    {
        $this->ruleSet->removeAllRules();
    }

    /**
     * @return non-empty-string
     *
     * @throws OutputException
     */
    public function render(OutputFormat $outputFormat): string
    {
        $formatter = $outputFormat->getFormatter();
        $result = $formatter->comments($this);
        if (\count($this->selectors) === 0) {
            // If all the selectors have been removed, this declaration block becomes invalid
            throw new OutputException(
                'Attempt to print declaration block with missing selector',
                $this->getLineNumber()
            );
        }
        $result .= $outputFormat->getContentBeforeDeclarationBlock();
        $result .= $formatter->implode(
            $formatter->spaceBeforeSelectorSeparator() . ',' . $formatter->spaceAfterSelectorSeparator(),
            $this->selectors
        );
        $result .= $outputFormat->getContentAfterDeclarationBlockSelectors();
        $result .= $formatter->spaceBeforeOpeningBrace() . '{';
        $result .= $this->ruleSet->render($outputFormat);
        $result .= '}';
        $result .= $outputFormat->getContentAfterDeclarationBlock();

        return $result;
    }
}
