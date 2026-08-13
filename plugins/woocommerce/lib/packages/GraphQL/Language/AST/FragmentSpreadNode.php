<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class FragmentSpreadNode extends Node implements SelectionNode
{
    public string $kind = NodeKind::FRAGMENT_SPREAD;

    public NameNode $name;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->directives ??= new NodeList([]);
    }
}
