<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class DirectiveNode extends Node
{
    public string $kind = NodeKind::DIRECTIVE;

    public NameNode $name;

    /** @var NodeList<ArgumentNode> */
    public NodeList $arguments;

    public function __construct(array $vars)
    {
        parent::__construct($vars);
        $this->arguments ??= new NodeList([]);
    }
}
