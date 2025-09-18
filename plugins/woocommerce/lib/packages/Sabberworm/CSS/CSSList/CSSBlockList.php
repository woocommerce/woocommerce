<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule\Rule;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\RuleContainer;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet\RuleSet;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\CSSFunction;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\Value;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value\ValueList;

/**
 * A `CSSBlockList` is a `CSSList` whose `DeclarationBlock`s are guaranteed to contain valid declaration blocks or
 * at-rules.
 *
 * Most `CSSList`s conform to this category but some at-rules (such as `@keyframes`) do not.
 */
abstract class CSSBlockList extends CSSList
{
    /**
     * Gets all `DeclarationBlock` objects recursively, no matter how deeply nested the selectors are.
     *
     * @return list<DeclarationBlock>
     */
    public function getAllDeclarationBlocks(): array
    {
        $result = [];

        foreach ($this->contents as $item) {
            if ($item instanceof DeclarationBlock) {
                $result[] = $item;
            } elseif ($item instanceof CSSBlockList) {
                $result = \array_merge($result, $item->getAllDeclarationBlocks());
            }
        }

        return $result;
    }

    /**
     * Returns all `RuleSet` objects recursively found in the tree, no matter how deeply nested the rule sets are.
     *
     * @return list<RuleSet>
     */
    public function getAllRuleSets(): array
    {
        $result = [];

        foreach ($this->contents as $item) {
            if ($item instanceof RuleSet) {
                $result[] = $item;
            } elseif ($item instanceof CSSBlockList) {
                $result = \array_merge($result, $item->getAllRuleSets());
            } elseif ($item instanceof DeclarationBlock) {
                $result[] = $item->getRuleSet();
            }
        }

        return $result;
    }

    /**
     * Returns all `Value` objects found recursively in `Rule`s in the tree.
     *
     * @param CSSElement|null $element
     *        This is the `CSSList` or `RuleSet` to start the search from (defaults to the whole document).
     * @param string|null $ruleSearchPattern
     *        This allows filtering rules by property name
     *        (e.g. if "color" is passed, only `Value`s from `color` properties will be returned,
     *        or if "font-" is provided, `Value`s from all font rules, like `font-size`, and including `font` itself,
     *        will be returned).
     * @param bool $searchInFunctionArguments whether to also return `Value` objects used as `CSSFunction` arguments.
     *
     * @return list<Value>
     *
     * @see RuleSet->getRules()
     */
    public function getAllValues(
        ?CSSElement $element = null,
        ?string $ruleSearchPattern = null,
        bool $searchInFunctionArguments = false
    ): array {
        $element = $element ?? $this;

        $result = [];
        if ($element instanceof CSSBlockList) {
            foreach ($element->getContents() as $contentItem) {
                // Statement at-rules are skipped since they do not contain values.
                if ($contentItem instanceof CSSElement) {
                    $result = \array_merge(
                        $result,
                        $this->getAllValues($contentItem, $ruleSearchPattern, $searchInFunctionArguments)
                    );
                }
            }
        } elseif ($element instanceof RuleContainer) {
            foreach ($element->getRules($ruleSearchPattern) as $rule) {
                $result = \array_merge(
                    $result,
                    $this->getAllValues($rule, $ruleSearchPattern, $searchInFunctionArguments)
                );
            }
        } elseif ($element instanceof Rule) {
            $value = $element->getValue();
            // `string` values are discarded.
            if ($value instanceof CSSElement) {
                $result = \array_merge(
                    $result,
                    $this->getAllValues($value, $ruleSearchPattern, $searchInFunctionArguments)
                );
            }
        } elseif ($element instanceof ValueList) {
            if ($searchInFunctionArguments || !($element instanceof CSSFunction)) {
                foreach ($element->getListComponents() as $component) {
                    // `string` components are discarded.
                    if ($component instanceof CSSElement) {
                        $result = \array_merge(
                            $result,
                            $this->getAllValues($component, $ruleSearchPattern, $searchInFunctionArguments)
                        );
                    }
                }
            }
        } elseif ($element instanceof Value) {
            $result[] = $element;
        }

        return $result;
    }

    /**
     * @return list<Selector>
     */
    protected function getAllSelectors(?string $specificitySearch = null): array
    {
        $result = [];

        foreach ($this->getAllDeclarationBlocks() as $declarationBlock) {
            foreach ($declarationBlock->getSelectors() as $selector) {
                if ($specificitySearch === null) {
                    $result[] = $selector;
                } else {
                    $comparator = '===';
                    $expressionParts = \explode(' ', $specificitySearch);
                    $targetSpecificity = $expressionParts[0];
                    if (\count($expressionParts) > 1) {
                        $comparator = $expressionParts[0];
                        $targetSpecificity = $expressionParts[1];
                    }
                    $targetSpecificity = (int) $targetSpecificity;
                    $selectorSpecificity = $selector->getSpecificity();
                    $comparatorMatched = false;
                    switch ($comparator) {
                        case '<=':
                            $comparatorMatched = $selectorSpecificity <= $targetSpecificity;
                            break;
                        case '<':
                            $comparatorMatched = $selectorSpecificity < $targetSpecificity;
                            break;
                        case '>=':
                            $comparatorMatched = $selectorSpecificity >= $targetSpecificity;
                            break;
                        case '>':
                            $comparatorMatched = $selectorSpecificity > $targetSpecificity;
                            break;
                        default:
                            $comparatorMatched = $selectorSpecificity === $targetSpecificity;
                            break;
                    }
                    if ($comparatorMatched) {
                        $result[] = $selector;
                    }
                }
            }
        }

        return $result;
    }
}
