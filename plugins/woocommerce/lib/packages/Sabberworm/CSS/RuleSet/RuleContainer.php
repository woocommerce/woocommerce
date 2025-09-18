<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\RuleSet;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Rule\Rule;

/**
 * Represents a CSS item that contains `Rules`, defining the methods to manipulate them.
 */
interface RuleContainer
{
    public function addRule(Rule $ruleToAdd, ?Rule $sibling = null): void;

    public function removeRule(Rule $ruleToRemove): void;

    public function removeMatchingRules(string $searchPattern): void;

    public function removeAllRules(): void;

    /**
     * @param array<Rule> $rules
     */
    public function setRules(array $rules): void;

    /**
     * @return array<int<0, max>, Rule>
     */
    public function getRules(?string $searchPattern = null): array;

    /**
     * @return array<string, Rule>
     */
    public function getRulesAssoc(?string $searchPattern = null): array;
}
