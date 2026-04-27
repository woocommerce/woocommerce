<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Validator;

use Automattic\WooCommerce\Vendor\GraphQL\Error\Error;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\TypeInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\DisableIntrospection;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ExecutableDefinitions;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\FieldsOnCorrectType;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\FragmentsOnCompositeTypes;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\KnownArgumentNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\KnownArgumentNamesOnDirectives;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\KnownDirectives;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\KnownFragmentNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\KnownTypeNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\LoneAnonymousOperation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\LoneSchemaDefinition;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\NoFragmentCycles;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\NoUndefinedVariables;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\NoUnusedFragments;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\NoUnusedVariables;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\OneOfInputObjectsRule;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\PossibleFragmentSpreads;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\PossibleTypeExtensions;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ProvidedRequiredArguments;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ProvidedRequiredArgumentsOnDirectives;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryComplexity;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryDepth;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QuerySecurityRule;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ScalarLeafs;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\SingleFieldSubscription;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueArgumentDefinitionNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueArgumentNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueDirectiveNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueDirectivesPerLocation;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueEnumValueNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueFieldDefinitionNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueFragmentNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueInputFieldNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueOperationNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueOperationTypes;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueTypeNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\UniqueVariableNames;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ValidationRule;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ValuesOfCorrectType;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\VariablesAreInputTypes;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\VariablesInAllowedPosition;

/**
 * Implements the "Validation" section of the spec.
 *
 * Validation runs synchronously, returning an array of encountered errors, or
 * an empty array if no errors were encountered and the document is valid.
 *
 * A list of specific validation rules may be provided. If not provided, the
 * default list of rules defined by the Automattic\WooCommerce\Vendor\GraphQL specification will be used.
 *
 * Each validation rule is an instance of Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\ValidationRule
 * which returns a visitor (see the [Automattic\WooCommerce\Vendor\GraphQL\Language\Visitor API](class-reference.md#graphqllanguagevisitor)).
 *
 * Visitor methods are expected to return an instance of [Automattic\WooCommerce\Vendor\GraphQL\Error\Error](class-reference.md#graphqlerrorerror),
 * or array of such instances when invalid.
 *
 * Optionally a custom TypeInfo instance may be provided. If not provided, one
 * will be created from the provided schema.
 */
class DocumentValidator
{
    /** @var array<string, ValidationRule> */
    private static array $rules = [];

    /** @var array<class-string<ValidationRule>, ValidationRule> */
    private static array $defaultRules;

    /** @var array<class-string<QuerySecurityRule>, QuerySecurityRule> */
    private static array $securityRules;

    /** @var array<class-string<ValidationRule>, ValidationRule> */
    private static array $sdlRules;

    private static bool $initRules = false;

    /**
     * Validate a Automattic\WooCommerce\Vendor\GraphQL query against a schema.
     *
     * @param array<ValidationRule>|null $rules Defaults to using all available rules
     *
     * @throws \Exception
     *
     * @return list<Error>
     *
     * @api
     */
    public static function validate(
        Schema $schema,
        DocumentNode $ast,
        ?array $rules = null,
        ?TypeInfo $typeInfo = null
    ): array {
        $rules ??= static::allRules();

        if ($rules === []) {
            return [];
        }

        $typeInfo ??= new TypeInfo($schema);

        $context = new QueryValidationContext($schema, $ast, $typeInfo);

        $visitors = [];
        foreach ($rules as $rule) {
            $visitors[] = $rule->getVisitor($context);
        }

        Visitor::visit(
            $ast,
            Visitor::visitWithTypeInfo(
                $typeInfo,
                Visitor::visitInParallel($visitors)
            )
        );

        return $context->getErrors();
    }

    /**
     * Returns all global validation rules.
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, ValidationRule>
     *
     * @api
     */
    public static function allRules(): array
    {
        if (! self::$initRules) {
            self::$rules = array_merge(
                static::defaultRules(),
                self::securityRules(),
                self::$rules
            );
            self::$initRules = true;
        }

        return self::$rules;
    }

    /** @return array<class-string<ValidationRule>, ValidationRule> */
    public static function defaultRules(): array
    {
        return self::$defaultRules ??= [
            ExecutableDefinitions::class => new ExecutableDefinitions(),
            UniqueOperationNames::class => new UniqueOperationNames(),
            LoneAnonymousOperation::class => new LoneAnonymousOperation(),
            SingleFieldSubscription::class => new SingleFieldSubscription(),
            KnownTypeNames::class => new KnownTypeNames(),
            FragmentsOnCompositeTypes::class => new FragmentsOnCompositeTypes(),
            VariablesAreInputTypes::class => new VariablesAreInputTypes(),
            ScalarLeafs::class => new ScalarLeafs(),
            FieldsOnCorrectType::class => new FieldsOnCorrectType(),
            UniqueFragmentNames::class => new UniqueFragmentNames(),
            KnownFragmentNames::class => new KnownFragmentNames(),
            NoUnusedFragments::class => new NoUnusedFragments(),
            PossibleFragmentSpreads::class => new PossibleFragmentSpreads(),
            NoFragmentCycles::class => new NoFragmentCycles(),
            UniqueVariableNames::class => new UniqueVariableNames(),
            NoUndefinedVariables::class => new NoUndefinedVariables(),
            NoUnusedVariables::class => new NoUnusedVariables(),
            KnownDirectives::class => new KnownDirectives(),
            UniqueDirectivesPerLocation::class => new UniqueDirectivesPerLocation(),
            KnownArgumentNames::class => new KnownArgumentNames(),
            UniqueArgumentNames::class => new UniqueArgumentNames(),
            ValuesOfCorrectType::class => new ValuesOfCorrectType(),
            ProvidedRequiredArguments::class => new ProvidedRequiredArguments(),
            VariablesInAllowedPosition::class => new VariablesInAllowedPosition(),
            OverlappingFieldsCanBeMerged::class => new OverlappingFieldsCanBeMerged(),
            UniqueInputFieldNames::class => new UniqueInputFieldNames(),
            OneOfInputObjectsRule::class => new OneOfInputObjectsRule(),
        ];
    }

    /**
     * @deprecated just add rules via @see DocumentValidator::addRule()
     *
     * @throws \InvalidArgumentException
     *
     * @return array<class-string<QuerySecurityRule>, QuerySecurityRule>
     */
    public static function securityRules(): array
    {
        return self::$securityRules ??= [
            DisableIntrospection::class => new DisableIntrospection(DisableIntrospection::DISABLED),
            QueryDepth::class => new QueryDepth(QueryDepth::DISABLED),
            QueryComplexity::class => new QueryComplexity(QueryComplexity::DISABLED),
        ];
    }

    /** @return array<class-string<ValidationRule>, ValidationRule> */
    public static function sdlRules(): array
    {
        return self::$sdlRules ??= [
            LoneSchemaDefinition::class => new LoneSchemaDefinition(),
            UniqueOperationTypes::class => new UniqueOperationTypes(),
            UniqueTypeNames::class => new UniqueTypeNames(),
            UniqueEnumValueNames::class => new UniqueEnumValueNames(),
            UniqueFieldDefinitionNames::class => new UniqueFieldDefinitionNames(),
            UniqueArgumentDefinitionNames::class => new UniqueArgumentDefinitionNames(),
            UniqueDirectiveNames::class => new UniqueDirectiveNames(),
            KnownTypeNames::class => new KnownTypeNames(),
            KnownDirectives::class => new KnownDirectives(),
            UniqueDirectivesPerLocation::class => new UniqueDirectivesPerLocation(),
            PossibleTypeExtensions::class => new PossibleTypeExtensions(),
            KnownArgumentNamesOnDirectives::class => new KnownArgumentNamesOnDirectives(),
            UniqueArgumentNames::class => new UniqueArgumentNames(),
            UniqueInputFieldNames::class => new UniqueInputFieldNames(),
            ProvidedRequiredArgumentsOnDirectives::class => new ProvidedRequiredArgumentsOnDirectives(),
        ];
    }

    /**
     * Returns global validation rule by name.
     *
     * Standard rules are named by class name, so example usage for such rules:
     *
     * @example DocumentValidator::getRule(Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryComplexity::class);
     *
     * @api
     *
     * @throws \InvalidArgumentException
     */
    public static function getRule(string $name): ?ValidationRule
    {
        return static::allRules()[$name] ?? null;
    }

    /**
     * Add rule to list of global validation rules.
     *
     * @api
     */
    public static function addRule(ValidationRule $rule): void
    {
        self::$rules[$rule->getName()] = $rule;
    }

    /**
     * Remove rule from list of global validation rules.
     *
     * @api
     */
    public static function removeRule(ValidationRule $rule): void
    {
        unset(self::$rules[$rule->getName()]);
    }

    /**
     * Validate a Automattic\WooCommerce\Vendor\GraphQL document defined through schema definition language.
     *
     * @param array<ValidationRule>|null $rules
     *
     * @throws \Exception
     *
     * @return list<Error>
     */
    public static function validateSDL(
        DocumentNode $documentAST,
        ?Schema $schemaToExtend = null,
        ?array $rules = null
    ): array {
        $rules ??= self::sdlRules();

        if ($rules === []) {
            return [];
        }

        $context = new SDLValidationContext($documentAST, $schemaToExtend);

        $visitors = [];
        foreach ($rules as $rule) {
            $visitors[] = $rule->getSDLVisitor($context);
        }

        Visitor::visit(
            $documentAST,
            Visitor::visitInParallel($visitors)
        );

        return $context->getErrors();
    }

    /**
     * @throws \Exception
     * @throws Error
     */
    public static function assertValidSDL(DocumentNode $documentAST): void
    {
        $errors = self::validateSDL($documentAST);
        if ($errors !== []) {
            throw new Error(self::combineErrorMessages($errors));
        }
    }

    /**
     * @throws \Exception
     * @throws Error
     */
    public static function assertValidSDLExtension(DocumentNode $documentAST, Schema $schema): void
    {
        $errors = self::validateSDL($documentAST, $schema);
        if ($errors !== []) {
            throw new Error(self::combineErrorMessages($errors));
        }
    }

    /** @param array<Error> $errors */
    private static function combineErrorMessages(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getMessage();
        }

        return implode("\n\n", $messages);
    }
}
