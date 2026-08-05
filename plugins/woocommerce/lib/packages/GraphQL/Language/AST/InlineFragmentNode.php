<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class InlineFragmentNode extends Node implements SelectionNode
{
    public string $kind = NodeKind::INLINE_FRAGMENT;

    public ?NamedTypeNode $typeCondition = null;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public SelectionSetNode $selectionSet;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }
}
