<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\PhpDoc;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * @phpstan-import-type PartialEnumValueConfig from EnumType
 */
class PhpEnumType extends EnumType
{
    public const MULTIPLE_DESCRIPTIONS_DISALLOWED = 'Using more than 1 Description attribute is not supported.';
    public const MULTIPLE_DEPRECATIONS_DISALLOWED = 'Using more than 1 Deprecated attribute is not supported.';

    /** @var class-string<\UnitEnum> */
    protected string $enumClass;

    /**
     * @param class-string<\UnitEnum> $enumClass The fully qualified class name of a native PHP enum
     * @param string|null $name The name the enum will have in the schema, defaults to the basename of the given class
     * @param string|null $description The description the enum will have in the schema, defaults to PHPDoc of the given class
     * @param array<EnumTypeExtensionNode>|null $extensionASTNodes
     *
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function __construct(
        string $enumClass,
        ?string $name = null,
        ?string $description = null,
        ?EnumTypeDefinitionNode $astNode = null,
        ?array $extensionASTNodes = null
    ) {
        $this->enumClass = $enumClass;
        $reflection = new \ReflectionEnum($enumClass);

        /**
         * @var array<string, PartialEnumValueConfig> $enumDefinitions
         */
        $enumDefinitions = [];
        foreach ($reflection->getCases() as $case) {
            $enumDefinitions[$case->name] = [
                'value' => $case->getValue(),
                'description' => $this->extractDescription($case),
                'deprecationReason' => $this->deprecationReason($case),
            ];
        }

        parent::__construct([
            'name' => $name ?? $this->baseName($enumClass),
            'values' => $enumDefinitions,
            'description' => $description ?? $this->extractDescription($reflection),
            'astNode' => $astNode,
            'extensionASTNodes' => $extensionASTNodes,
        ]);
    }

    public function serialize($value): string
    {
        if ($value instanceof $this->enumClass) {
            return $value->name;
        }

        if (is_a($this->enumClass, \BackedEnum::class, true)) {
            try {
                $instance = $this->enumClass::from($value);
            } catch (\ValueError|\TypeError $error) {
                $notEnumInstanceOrValue = Utils::printSafe($value);
                throw new SerializationError("Cannot serialize value as enum: {$notEnumInstanceOrValue}, expected instance or valid value of {$this->enumClass}.", $error->getCode(), $error);
            }

            return $instance->name;
        }

        $notEnum = Utils::printSafe($value);
        throw new SerializationError("Cannot serialize value as enum: {$notEnum}, expected instance of {$this->enumClass}.");
    }

    public function parseValue($value)
    {
        // Can happen when variable values undergo a serialization cycle before execution
        if ($value instanceof $this->enumClass) {
            return $value;
        }

        return parent::parseValue($value);
    }

    /** @param class-string $class */
    protected function baseName(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }

    /**
     * @param \ReflectionClassConstant|\ReflectionClass<\UnitEnum> $reflection
     *
     * @throws \Exception
     */
    protected function extractDescription(\ReflectionClassConstant|\ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(Description::class);

        if (count($attributes) === 1) {
            return $attributes[0]->newInstance()->description;
        }

        if (count($attributes) > 1) {
            throw new \Exception(self::MULTIPLE_DESCRIPTIONS_DISALLOWED);
        }

        $comment = $reflection->getDocComment();
        $unpadded = PhpDoc::unpad($comment);

        return PhpDoc::unwrap($unpadded);
    }

    /** @throws \Exception */
    protected function deprecationReason(\ReflectionClassConstant $reflection): ?string
    {
        $attributes = $reflection->getAttributes(Deprecated::class);

        if (count($attributes) === 1) {
            return $attributes[0]->newInstance()->reason;
        }

        if (count($attributes) > 1) {
            throw new \Exception(self::MULTIPLE_DEPRECATIONS_DISALLOWED);
        }

        return null;
    }
}
