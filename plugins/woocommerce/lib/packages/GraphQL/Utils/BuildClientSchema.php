<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Utils;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Parser;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CustomScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Directive;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\EnumType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\FieldDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectField;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\InterfaceType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ListOfType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NamedType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\NonNull;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\OutputType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ScalarType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\Type;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\UnionType;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Introspection;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Type\SchemaConfig;
use Automattic\WooCommerce\Vendor\GraphQL\Type\TypeKind;

/**
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type UnnamedInputObjectFieldConfig from InputObjectField
 *
 * @phpstan-type Options array{
 *   assumeValid?: bool
 * }
 *
 *    - assumeValid:
 *          When building a schema from a Automattic\WooCommerce\Vendor\GraphQL service's introspection result, it
 *          might be safe to assume the schema is valid. Set to true to assume the
 *          produced schema is valid.
 *
 *          Default: false
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Utils\BuildClientSchemaTest
 */
class BuildClientSchema
{
    /** @var array<string, mixed> */
    private array $introspection;

    /**
     * @var array<string, bool>
     *
     * @phpstan-var Options
     */
    private array $options;

    /** @var array<string, NamedType&Type> */
    private array $typeMap = [];

    /**
     * @param array<string, mixed> $introspectionQuery
     * @param array<string, bool> $options
     *
     * @phpstan-param Options    $options
     */
    public function __construct(array $introspectionQuery, array $options = [])
    {
        $this->introspection = $introspectionQuery;
        $this->options = $options;
    }

    /**
     * Build a schema for use by client tools.
     *
     * Given the result of a client running the introspection query, creates and
     * returns a \Automattic\WooCommerce\Vendor\GraphQL\Type\Schema instance which can be then used with all graphql-php
     * tools, but cannot be used to execute a query, as introspection does not
     * represent the "resolver", "parse" or "serialize" functions or any other
     * server-internal mechanisms.
     *
     * This function expects a complete introspection result. Don't forget to check
     * the "errors" field of a server response before calling this function.
     *
     * @param array<string, mixed> $introspectionQuery
     * @param array<string, bool> $options
     *
     * @phpstan-param Options $options
     *
     * @api
     *
     * @throws \Exception
     * @throws InvariantViolation
     */
    public static function build(array $introspectionQuery, array $options = []): Schema
    {
        return (new self($introspectionQuery, $options))->buildSchema();
    }

    /**
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function buildSchema(): Schema
    {
        if (! array_key_exists('__schema', $this->introspection)) {
            $missingSchemaIntrospection = Utils::printSafeJson($this->introspection);
            throw new InvariantViolation("Invalid or incomplete introspection result. Ensure that you are passing \"data\" property of introspection response and no \"errors\" was returned alongside: {$missingSchemaIntrospection}.");
        }

        $schemaIntrospection = $this->introspection['__schema'];

        $builtInTypes = array_merge(
            Type::builtInScalars(),
            Introspection::getTypes()
        );

        foreach ($schemaIntrospection['types'] as $typeIntrospection) {
            if (! isset($typeIntrospection['name'])) {
                throw self::invalidOrIncompleteIntrospectionResult($typeIntrospection);
            }

            $name = $typeIntrospection['name'];
            if (! is_string($name)) {
                throw self::invalidOrIncompleteIntrospectionResult($typeIntrospection);
            }

            // Use the built-in singleton types to avoid reconstruction
            $this->typeMap[$name] = $builtInTypes[$name]
                ?? $this->buildType($typeIntrospection);
        }

        $description = isset($schemaIntrospection['description'])
            ? $schemaIntrospection['description']
            : null;

        $queryType = isset($schemaIntrospection['queryType'])
            ? $this->getObjectType($schemaIntrospection['queryType'])
            : null;

        $mutationType = isset($schemaIntrospection['mutationType'])
            ? $this->getObjectType($schemaIntrospection['mutationType'])
            : null;

        $subscriptionType = isset($schemaIntrospection['subscriptionType'])
            ? $this->getObjectType($schemaIntrospection['subscriptionType'])
            : null;

        $directives = isset($schemaIntrospection['directives'])
            ? array_map(
                [$this, 'buildDirective'],
                $schemaIntrospection['directives']
            )
            : [];

        return new Schema(
            (new SchemaConfig())
                ->setDescription($description)
                ->setQuery($queryType)
                ->setMutation($mutationType)
                ->setSubscription($subscriptionType)
                ->setTypes($this->typeMap)
                ->setDirectives($directives)
                ->setAssumeValid($this->options['assumeValid'] ?? false)
        );
    }

    /**
     * @param array<string, mixed> $typeRef
     *
     * @throws InvariantViolation
     */
    private function getType(array $typeRef): Type
    {
        if (isset($typeRef['kind'])) {
            if ($typeRef['kind'] === TypeKind::LIST) {
                if (! isset($typeRef['ofType'])) {
                    throw new InvariantViolation('Decorated type deeper than introspection query.');
                }

                return new ListOfType($this->getType($typeRef['ofType']));
            }

            if ($typeRef['kind'] === TypeKind::NON_NULL) {
                if (! isset($typeRef['ofType'])) {
                    throw new InvariantViolation('Decorated type deeper than introspection query.');
                }

                // @phpstan-ignore-next-line if the type is not a nullable type, schema validation will catch it
                return new NonNull($this->getType($typeRef['ofType']));
            }
        }

        if (! isset($typeRef['name'])) {
            $unknownTypeRef = Utils::printSafeJson($typeRef);
            throw new InvariantViolation("Unknown type reference: {$unknownTypeRef}.");
        }

        return $this->getNamedType($typeRef['name']);
    }

    /**
     * @throws InvariantViolation
     *
     * @return NamedType&Type
     */
    private function getNamedType(string $typeName): NamedType
    {
        if (! isset($this->typeMap[$typeName])) {
            throw new InvariantViolation("Invalid or incomplete schema, unknown type: {$typeName}. Ensure that a full introspection query is used in order to build a client schema.");
        }

        return $this->typeMap[$typeName];
    }

    /** @param array<mixed> $type */
    public static function invalidOrIncompleteIntrospectionResult(array $type): InvariantViolation
    {
        $incompleteType = Utils::printSafeJson($type);

        return new InvariantViolation("Invalid or incomplete introspection result. Ensure that a full introspection query is used in order to build a client schema: {$incompleteType}.");
    }

    /**
     * @param array<string, mixed> $typeRef
     *
     * @throws InvariantViolation
     *
     * @return Type&InputType
     */
    private function getInputType(array $typeRef): InputType
    {
        $type = $this->getType($typeRef);

        if ($type instanceof InputType) {
            return $type;
        }

        $notInputType = Utils::printSafe($type);
        throw new InvariantViolation("Introspection must provide input type for arguments, but received: {$notInputType}.");
    }

    /**
     * @param array<string, mixed> $typeRef
     *
     * @throws InvariantViolation
     */
    private function getOutputType(array $typeRef): OutputType
    {
        $type = $this->getType($typeRef);

        if ($type instanceof OutputType) {
            return $type;
        }

        $notInputType = Utils::printSafe($type);
        throw new InvariantViolation("Introspection must provide output type for fields, but received: {$notInputType}.");
    }

    /**
     * @param array<string, mixed> $typeRef
     *
     * @throws InvariantViolation
     */
    private function getObjectType(array $typeRef): ObjectType
    {
        $type = $this->getType($typeRef);

        return ObjectType::assertObjectType($type);
    }

    /**
     * @param array<string, mixed> $typeRef
     *
     * @throws InvariantViolation
     */
    public function getInterfaceType(array $typeRef): InterfaceType
    {
        $type = $this->getType($typeRef);

        return InterfaceType::assertInterfaceType($type);
    }

    /**
     * @param array<string, mixed> $type
     *
     * @throws InvariantViolation
     *
     * @return Type&NamedType
     */
    private function buildType(array $type): NamedType
    {
        if (! array_key_exists('kind', $type)) {
            throw self::invalidOrIncompleteIntrospectionResult($type);
        }

        switch ($type['kind']) {
            case TypeKind::SCALAR:
                return $this->buildScalarDef($type);
            case TypeKind::OBJECT:
                return $this->buildObjectDef($type);
            case TypeKind::INTERFACE:
                return $this->buildInterfaceDef($type);
            case TypeKind::UNION:
                return $this->buildUnionDef($type);
            case TypeKind::ENUM:
                return $this->buildEnumDef($type);
            case TypeKind::INPUT_OBJECT:
                return $this->buildInputObjectDef($type);
            default:
                $unknownKindType = Utils::printSafeJson($type);
                throw new InvariantViolation("Invalid or incomplete introspection result. Received type with unknown kind: {$unknownKindType}.");
        }
    }

    /**
     * @param array<string, string> $scalar
     *
     * @throws InvariantViolation
     */
    private function buildScalarDef(array $scalar): ScalarType
    {
        return new CustomScalarType([
            'name' => $scalar['name'],
            'description' => $scalar['description'],
            'serialize' => static fn ($value) => $value,
        ]);
    }

    /**
     * @param array<string, mixed> $implementingIntrospection
     *
     * @throws InvariantViolation
     *
     * @return array<int, InterfaceType>
     */
    private function buildImplementationsList(array $implementingIntrospection): array
    {
        // TODO: Temporary workaround until Automattic\WooCommerce\Vendor\GraphQL ecosystem will fully support 'interfaces' on interface types.
        if (
            array_key_exists('interfaces', $implementingIntrospection)
            && $implementingIntrospection['interfaces'] === null
            && $implementingIntrospection['kind'] === TypeKind::INTERFACE
        ) {
            return [];
        }

        if (! array_key_exists('interfaces', $implementingIntrospection)) {
            $safeIntrospection = Utils::printSafeJson($implementingIntrospection);
            throw new InvariantViolation("Introspection result missing interfaces: {$safeIntrospection}.");
        }

        return array_map(
            [$this, 'getInterfaceType'],
            $implementingIntrospection['interfaces']
        );
    }

    /**
     * @param array<string, mixed> $object
     *
     * @throws InvariantViolation
     */
    private function buildObjectDef(array $object): ObjectType
    {
        return new ObjectType([
            'name' => $object['name'],
            'description' => $object['description'],
            'interfaces' => fn (): array => $this->buildImplementationsList($object),
            'fields' => fn (): array => $this->buildFieldDefMap($object),
        ]);
    }

    /**
     * @param array<string, mixed> $interface
     *
     * @throws InvariantViolation
     */
    private function buildInterfaceDef(array $interface): InterfaceType
    {
        return new InterfaceType([
            'name' => $interface['name'],
            'description' => $interface['description'],
            'fields' => fn (): array => $this->buildFieldDefMap($interface),
            'interfaces' => fn (): array => $this->buildImplementationsList($interface),
        ]);
    }

    /**
     * @param array<string, mixed> $union
     *
     * @throws InvariantViolation
     */
    private function buildUnionDef(array $union): UnionType
    {
        if (! array_key_exists('possibleTypes', $union)) {
            $safeUnion = Utils::printSafeJson($union);
            throw new InvariantViolation("Introspection result missing possibleTypes: {$safeUnion}.");
        }

        return new UnionType([
            'name' => $union['name'],
            'description' => $union['description'],
            'types' => fn (): array => array_map(
                [$this, 'getObjectType'],
                $union['possibleTypes']
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $enum
     *
     * @throws InvariantViolation
     */
    private function buildEnumDef(array $enum): EnumType
    {
        if (! array_key_exists('enumValues', $enum)) {
            $safeEnum = Utils::printSafeJson($enum);
            throw new InvariantViolation("Introspection result missing enumValues: {$safeEnum}.");
        }

        $values = [];
        foreach ($enum['enumValues'] as $value) {
            $values[$value['name']] = [
                'description' => $value['description'],
                'deprecationReason' => $value['deprecationReason'],
            ];
        }

        return new EnumType([
            'name' => $enum['name'],
            'description' => $enum['description'],
            'values' => $values,
        ]);
    }

    /**
     * @param array<string, mixed> $inputObject
     *
     * @throws InvariantViolation
     */
    private function buildInputObjectDef(array $inputObject): InputObjectType
    {
        if (! array_key_exists('inputFields', $inputObject)) {
            $safeInputObject = Utils::printSafeJson($inputObject);
            throw new InvariantViolation("Introspection result missing inputFields: {$safeInputObject}.");
        }

        return new InputObjectType([
            'name' => $inputObject['name'],
            'description' => $inputObject['description'],
            'fields' => fn (): array => $this->buildInputValueDefMap($inputObject['inputFields']),
        ]);
    }

    /**
     * @param array<string, mixed> $typeIntrospection
     *
     * @throws \Exception
     * @throws InvariantViolation
     *
     * @return array<string, UnnamedFieldDefinitionConfig>
     */
    private function buildFieldDefMap(array $typeIntrospection): array
    {
        if (! array_key_exists('fields', $typeIntrospection)) {
            $safeType = Utils::printSafeJson($typeIntrospection);
            throw new InvariantViolation("Introspection result missing fields: {$safeType}.");
        }

        /** @var array<string, UnnamedFieldDefinitionConfig> $map */
        $map = [];
        foreach ($typeIntrospection['fields'] as $field) {
            if (! array_key_exists('args', $field)) {
                $safeField = Utils::printSafeJson($field);
                throw new InvariantViolation("Introspection result missing field args: {$safeField}.");
            }

            $map[$field['name']] = [
                'description' => $field['description'],
                'deprecationReason' => $field['deprecationReason'],
                'type' => $this->getOutputType($field['type']),
                'args' => $this->buildInputValueDefMap($field['args']),
            ];
        }

        // @phpstan-ignore-next-line unless the returned name was numeric, this works
        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $inputValueIntrospections
     *
     * @throws \Exception
     *
     * @return array<string, UnnamedInputObjectFieldConfig>
     */
    private function buildInputValueDefMap(array $inputValueIntrospections): array
    {
        /** @var array<string, UnnamedInputObjectFieldConfig> $map */
        $map = [];
        foreach ($inputValueIntrospections as $value) {
            $map[$value['name']] = $this->buildInputValue($value);
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $inputValueIntrospection
     *
     * @throws \Exception
     * @throws SyntaxError
     *
     * @return UnnamedInputObjectFieldConfig
     */
    public function buildInputValue(array $inputValueIntrospection): array
    {
        $type = $this->getInputType($inputValueIntrospection['type']);

        $inputValue = [
            'description' => $inputValueIntrospection['description'],
            'type' => $type,
        ];

        if (isset($inputValueIntrospection['defaultValue'])) {
            $inputValue['defaultValue'] = AST::valueFromAST(
                Parser::parseValue($inputValueIntrospection['defaultValue']),
                $type
            );
        }

        return $inputValue;
    }

    /**
     * @param array<string, mixed> $directive
     *
     * @throws \Exception
     * @throws InvariantViolation
     */
    public function buildDirective(array $directive): Directive
    {
        if (! array_key_exists('args', $directive)) {
            $safeDirective = Utils::printSafeJson($directive);
            throw new InvariantViolation("Introspection result missing directive args: {$safeDirective}.");
        }

        if (! array_key_exists('locations', $directive)) {
            $safeDirective = Utils::printSafeJson($directive);
            throw new InvariantViolation("Introspection result missing directive locations: {$safeDirective}.");
        }

        return new Directive([
            'name' => $directive['name'],
            'description' => $directive['description'],
            'args' => $this->buildInputValueDefMap($directive['args']),
            'isRepeatable' => $directive['isRepeatable'] ?? false,
            'locations' => $directive['locations'],
        ]);
    }
}
