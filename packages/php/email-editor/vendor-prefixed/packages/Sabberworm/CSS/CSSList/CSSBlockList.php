<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSElement;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\Selector;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Rule\Rule;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\RuleSet\RuleSet;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\CSSFunction;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\Value;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\ValueList;

/**
 * A `CSSBlockList` is a `CSSList` whose `DeclarationBlock`s are guaranteed to contain valid declaration blocks or
 * at-rules.
 *
 * Most `CSSList`s conform to this category but some at-rules (such as `@keyframes`) do not.
 */
abstract class CSSBlockList extends CSSList
{
    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        parent::__construct($iLineNo);
    }

    /**
     * @param array<int, DeclarationBlock> $aResult
     *
     * @return void
     */
    protected function allDeclarationBlocks(array &$aResult)
    {
        foreach ($this->aContents as $mContent) {
            if ($mContent instanceof DeclarationBlock) {
                $aResult[] = $mContent;
            } elseif ($mContent instanceof CSSBlockList) {
                $mContent->allDeclarationBlocks($aResult);
            }
        }
    }

    /**
     * @param array<int, RuleSet> $aResult
     *
     * @return void
     */
    protected function allRuleSets(array &$aResult)
    {
        foreach ($this->aContents as $mContent) {
            if ($mContent instanceof RuleSet) {
                $aResult[] = $mContent;
            } elseif ($mContent instanceof CSSBlockList) {
                $mContent->allRuleSets($aResult);
            }
        }
    }

    /**
     * Returns all `Value` objects found recursively in `Rule`s in the tree.
     *
     * @param CSSElement|string|null $element
     *        This is the `CSSList` or `RuleSet` to start the search from (defaults to the whole document).
     *        If a string is given, it is used as a rule name filter.
     *        Passing a string for this parameter is deprecated in version 8.9.0, and will not work from v9.0;
     *        use the following parameter to pass a rule name filter instead.
     * @param string|bool|null $ruleSearchPatternOrSearchInFunctionArguments
     *        This allows filtering rules by property name
     *        (e.g. if "color" is passed, only `Value`s from `color` properties will be returned,
     *        or if "font-" is provided, `Value`s from all font rules, like `font-size`, and including `font` itself,
     *        will be returned).
     *        If a Boolean is provided, it is treated as the `$searchInFunctionArguments` argument.
     *        Passing a Boolean for this parameter is deprecated in version 8.9.0, and will not work from v9.0;
     *        use the `$searchInFunctionArguments` parameter instead.
     * @param bool $searchInFunctionArguments whether to also return Value objects used as Function arguments.
     *
     * @return array<int, Value>
     *
     * @see RuleSet->getRules()
     */
    public function getAllValues(
        $element = null,
        $ruleSearchPatternOrSearchInFunctionArguments = null,
        $searchInFunctionArguments = false
    ) {
        if (\is_bool($ruleSearchPatternOrSearchInFunctionArguments)) {
            $searchInFunctionArguments = $ruleSearchPatternOrSearchInFunctionArguments;
            $searchString = null;
        } else {
            $searchString = $ruleSearchPatternOrSearchInFunctionArguments;
        }

        if ($element === null) {
            $element = $this;
        } elseif (\is_string($element)) {
            $searchString = $element;
            $element = $this;
        }

        $result = [];
        $this->allValues($element, $result, $searchString, $searchInFunctionArguments);
        return $result;
    }

    /**
     * @param CSSElement|string $oElement
     * @param array<int, Value> $aResult
     * @param string|null $sSearchString
     * @param bool $bSearchInFunctionArguments
     *
     * @return void
     */
    protected function allValues($oElement, array &$aResult, $sSearchString = null, $bSearchInFunctionArguments = false)
    {
        if ($oElement instanceof CSSBlockList) {
            foreach ($oElement->getContents() as $oContent) {
                $this->allValues($oContent, $aResult, $sSearchString, $bSearchInFunctionArguments);
            }
        } elseif ($oElement instanceof RuleSet) {
            foreach ($oElement->getRules($sSearchString) as $oRule) {
                $this->allValues($oRule, $aResult, $sSearchString, $bSearchInFunctionArguments);
            }
        } elseif ($oElement instanceof Rule) {
            $this->allValues($oElement->getValue(), $aResult, $sSearchString, $bSearchInFunctionArguments);
        } elseif ($oElement instanceof ValueList) {
            if ($bSearchInFunctionArguments || !($oElement instanceof CSSFunction)) {
                foreach ($oElement->getListComponents() as $mComponent) {
                    $this->allValues($mComponent, $aResult, $sSearchString, $bSearchInFunctionArguments);
                }
            }
        } else {
            // Non-List `Value` or `CSSString` (CSS identifier)
            $aResult[] = $oElement;
        }
    }

    /**
     * @param array<int, Selector> $aResult
     * @param string|null $sSpecificitySearch
     *
     * @return void
     */
    protected function allSelectors(array &$aResult, $sSpecificitySearch = null)
    {
        /** @var array<int, DeclarationBlock> $aDeclarationBlocks */
        $aDeclarationBlocks = [];
        $this->allDeclarationBlocks($aDeclarationBlocks);
        foreach ($aDeclarationBlocks as $oBlock) {
            foreach ($oBlock->getSelectors() as $oSelector) {
                if ($sSpecificitySearch === null) {
                    $aResult[] = $oSelector;
                } else {
                    $sComparator = '===';
                    $aSpecificitySearch = explode(' ', $sSpecificitySearch);
                    $iTargetSpecificity = $aSpecificitySearch[0];
                    if (count($aSpecificitySearch) > 1) {
                        $sComparator = $aSpecificitySearch[0];
                        $iTargetSpecificity = $aSpecificitySearch[1];
                    }
                    $iTargetSpecificity = (int)$iTargetSpecificity;
                    $iSelectorSpecificity = $oSelector->getSpecificity();
                    $bMatches = false;
                    switch ($sComparator) {
                        case '<=':
                            $bMatches = $iSelectorSpecificity <= $iTargetSpecificity;
                            break;
                        case '<':
                            $bMatches = $iSelectorSpecificity < $iTargetSpecificity;
                            break;
                        case '>=':
                            $bMatches = $iSelectorSpecificity >= $iTargetSpecificity;
                            break;
                        case '>':
                            $bMatches = $iSelectorSpecificity > $iTargetSpecificity;
                            break;
                        default:
                            $bMatches = $iSelectorSpecificity === $iTargetSpecificity;
                            break;
                    }
                    if ($bMatches) {
                        $aResult[] = $oSelector;
                    }
                }
            }
        }
    }
}
