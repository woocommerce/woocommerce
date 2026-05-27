<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\SDLValidationContext;

/**
 * Lone schema definition.
 *
 * A Automattic\WooCommerce\Vendor\GraphQL document is only valid if it contains only one schema definition.
 */
class LoneSchemaDefinition extends ValidationRule
{
    public static function schemaDefinitionNotAloneMessage(): string
    {
        return 'Must provide only one schema definition.';
    }

    public static function canNotDefineSchemaWithinExtensionMessage(): string
    {
        return 'Cannot define a new schema within a schema extension.';
    }

    public function getSDLVisitor(SDLValidationContext $context): array
    {
        $oldSchema = $context->getSchema();
        $alreadyDefined = $oldSchema === null
            ? false
            : (
                $oldSchema->astNode !== null
                || $oldSchema->getQueryType() !== null
                || $oldSchema->getMutationType() !== null
                || $oldSchema->getSubscriptionType() !== null
            );

        $schemaDefinitionsCount = 0;

        return [
            NodeKind::SCHEMA_DEFINITION => static function (SchemaDefinitionNode $node) use ($alreadyDefined, $context, &$schemaDefinitionsCount): void {
                if ($alreadyDefined) {
                    $context->reportError(new Error(static::canNotDefineSchemaWithinExtensionMessage(), $node));

                    return;
                }

                if ($schemaDefinitionsCount > 0) {
                    $context->reportError(new Error(static::schemaDefinitionNotAloneMessage(), $node));
                }

                ++$schemaDefinitionsCount;
            },
        ];
    }
}
