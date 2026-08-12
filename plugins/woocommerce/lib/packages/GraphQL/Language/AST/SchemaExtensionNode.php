<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language\AST;

class SchemaExtensionNode extends Node implements TypeSystemExtensionNode
{
    public string $kind = NodeKind::SCHEMA_EXTENSION;

    /** @var NodeList<DirectiveNode> */
    public NodeList $directives;

    /** @var NodeList<OperationTypeDefinitionNode> */
    public NodeList $operationTypes;
}
