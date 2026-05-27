<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Source;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;

/**
 * Build instance of @see \Automattic\WooCommerce\Vendor\GraphQL\Type\Schema out of schema language definition (string or parsed AST).
 *
 * See [schema definition language docs](schema-definition-language.md) for details.
 *
 * @phpstan-import-type TypeConfigDecorator from ASTDefinitionBuilder
 * @phpstan-import-type FieldConfigDecorator from ASTDefinitionBuilder
 *
 * @phpstan-type BuildSchemaOptions array{
 *   assumeValid?: bool,
 *   assumeValidSDL?: bool
 * }
 *
 * - assumeValid:
 *   When building a schema from a Automattic\WooCommerce\Vendor\GraphQL service's introspection result, it might be safe to assume the schema is valid.
 *   Set to true to assume the produced schema is valid.
 *   Default: false
 *
 * - assumeValidSDL:
 *   Set to true to assume the SDL is valid.
 *   Default: false
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Utils\BuildSchemaTest
 */
class BuildSchema
{
    private DocumentNode $ast;

    /**
     * @var callable|null
     *
     * @phpstan-var TypeConfigDecorator|null
     */
    private $typeConfigDecorator;

    /**
     * @var callable|null
     *
     * @phpstan-var FieldConfigDecorator|null
     */
    private $fieldConfigDecorator;

    /**
     * @var array<string, bool>
     *
     * @phpstan-var BuildSchemaOptions
     */
    private array $options;

    /**
     * @param array<string, bool> $options
     *
     * @phpstan-param TypeConfigDecorator|null $typeConfigDecorator
     * @phpstan-param BuildSchemaOptions $options
     */
    public function __construct(
        DocumentNode $ast,
        ?callable $typeConfigDecorator = null,
        array $options = [],
        ?callable $fieldConfigDecorator = null
    ) {
        $this->ast = $ast;
        $this->typeConfigDecorator = $typeConfigDecorator;
        $this->options = $options;
        $this->fieldConfigDecorator = $fieldConfigDecorator;
    }

    /**
     * A helper function to build a GraphQLSchema directly from a source
     * document.
     *
     * @param DocumentNode|Source|string $source
     * @param array<string, bool> $options
     *
     * @phpstan-param TypeConfigDecorator|null $typeConfigDecorator
     * @phpstan-param FieldConfigDecorator|null $fieldConfigDecorator
     * @phpstan-param BuildSchemaOptions $options
     *
     * @api
     *
     * @throws \Exception
     * @throws \ReflectionException
     * @throws Error
     * @throws InvariantViolation
     * @throws SyntaxError
     */
    public static function build(
        $source,
        ?callable $typeConfigDecorator = null,
        array $options = [],
        ?callable $fieldConfigDecorator = null
    ): Schema {
        $doc = $source instanceof DocumentNode
            ? $source
            : Parser::parse($source);

        return self::buildAST($doc, $typeConfigDecorator, $options, $fieldConfigDecorator);
    }

    /**
     * This takes the AST of a schema from @see \Automattic\WooCommerce\Vendor\GraphQL\Language\Parser::parse().
     *
     * If no schema definition is provided, then it will look for types named Query and Mutation.
     *
     * Given that AST it constructs a @see \Automattic\WooCommerce\Vendor\GraphQL\Type\Schema. The resulting schema
     * has no resolve methods, so execution will use default resolvers.
     *
     * @param array<string, bool> $options
     *
     * @phpstan-param TypeConfigDecorator|null $typeConfigDecorator
     * @phpstan-param FieldConfigDecorator|null $fieldConfigDecorator
     * @phpstan-param BuildSchemaOptions $options
     *
     * @api
     *
     * @throws \Exception
     * @throws \ReflectionException
     * @throws Error
     * @throws InvariantViolation
     */
    public static function buildAST(
        DocumentNode $ast,
        ?callable $typeConfigDecorator = null,
        array $options = [],
        ?callable $fieldConfigDecorator = null
    ): Schema {
        return (new self($ast, $typeConfigDecorator, $options, $fieldConfigDecorator))->buildSchema();
    }

    /**
     * @throws \Exception
     * @throws \ReflectionException
     * @throws Error
     * @throws InvariantViolation
     */
    public function buildSchema(): Schema
    {
        if (
            ! ($this->options['assumeValid'] ?? false)
            && ! ($this->options['assumeValidSDL'] ?? false)
        ) {
            DocumentValidator::assertValidSDL($this->ast);
        }

        $schemaDef = null;

        /** @var array<string, Node&TypeDefinitionNode> */
        $typeDefinitionsMap = [];

        /** @var array<string, array<int, Node&TypeExtensionNode>> $typeExtensionsMap */
        $typeExtensionsMap = [];

        /** @var array<int, DirectiveDefinitionNode> $directiveDefs */
        $directiveDefs = [];

        foreach ($this->ast->definitions as $definition) {
            switch (true) {
                case $definition instanceof SchemaDefinitionNode:
                    $schemaDef = $definition;
                    break;
                case $definition instanceof TypeDefinitionNode:
                    $name = $definition->getName()->value;
                    $typeDefinitionsMap[$name] = $definition;
                    break;
                case $definition instanceof TypeExtensionNode:
                    $name = $definition->getName()->value;
                    $typeExtensionsMap[$name][] = $definition;
                    break;
                case $definition instanceof DirectiveDefinitionNode:
                    $directiveDefs[] = $definition;
                    break;
            }
        }

        $operationTypes = $schemaDef !== null
            ? $this->getOperationTypes($schemaDef)
            : [
                'query' => 'Query',
                'mutation' => 'Mutation',
                'subscription' => 'Subscription',
            ];

        $definitionBuilder = new ASTDefinitionBuilder(
            $typeDefinitionsMap,
            $typeExtensionsMap,
            static function (string $typeName): Type {
                throw self::unknownType($typeName);
            },
            $this->typeConfigDecorator,
            $this->fieldConfigDecorator
        );

        $directives = array_map(
            [$definitionBuilder, 'buildDirective'],
            $directiveDefs
        );

        $directivesByName = [];
        foreach ($directives as $directive) {
            $directivesByName[$directive->name][] = $directive;
        }

        // If specified directives were not explicitly declared, add them.
        if (! isset($directivesByName['include'])) {
            $directives[] = Directive::includeDirective();
        }
        if (! isset($directivesByName['skip'])) {
            $directives[] = Directive::skipDirective();
        }
        if (! isset($directivesByName['deprecated'])) {
            $directives[] = Directive::deprecatedDirective();
        }
        if (! isset($directivesByName['oneOf'])) {
            $directives[] = Directive::oneOfDirective();
        }

        // Note: While this could make early assertions to get the correctly
        // typed values below, that would throw immediately while type system
        // validation with validateSchema() will produce more actionable results.
        return new Schema(
            (new SchemaConfig())
                ->setDescription($schemaDef->description->value ?? null)
            // @phpstan-ignore-next-line
                ->setQuery(isset($operationTypes['query'])
                    ? $definitionBuilder->maybeBuildType($operationTypes['query'])
                    : null)
            // @phpstan-ignore-next-line
                ->setMutation(isset($operationTypes['mutation'])
                    ? $definitionBuilder->maybeBuildType($operationTypes['mutation'])
                    : null)
            // @phpstan-ignore-next-line
                ->setSubscription(isset($operationTypes['subscription'])
                    ? $definitionBuilder->maybeBuildType($operationTypes['subscription'])
                    : null)
                ->setTypeLoader(static fn (string $name): ?Type => $definitionBuilder->maybeBuildType($name))
                ->setDirectives($directives)
                ->setAstNode($schemaDef)
                ->setTypes(fn (): array => array_map(
                    static fn (TypeDefinitionNode $def): Type => $definitionBuilder->buildType($def->getName()->value),
                    $typeDefinitionsMap,
                ))
        );
    }

    /** @return array<string, string> */
    private function getOperationTypes(SchemaDefinitionNode $schemaDef): array
    {
        /** @var array<string, string> $operationTypes */
        $operationTypes = [];
        foreach ($schemaDef->operationTypes as $operationType) {
            $operationTypes[$operationType->operation] = $operationType->type->name->value;
        }

        return $operationTypes;
    }

    public static function unknownType(string $typeName): Error
    {
        return new Error("Unknown type: \"{$typeName}\".");
    }
}
