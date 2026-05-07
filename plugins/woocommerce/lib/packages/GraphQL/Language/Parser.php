<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

use Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ArgumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\BooleanValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DirectiveNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\EnumValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ExecutableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FloatValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentSpreadNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InputValueDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InterfaceTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\IntValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ListValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Location;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NamedTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NameNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NonNullTypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NullValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectFieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ObjectValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ScalarTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SchemaExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeSystemDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\TypeSystemExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\UnionTypeExtensionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\ValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\VariableNode;

/**
 * Parses string containing Automattic\WooCommerce\Vendor\GraphQL query language or [schema definition language](schema-definition-language.md) to Abstract Syntax Tree.
 *
 * @phpstan-type ParserOptions array{
 *   noLocation?: bool,
 *   allowLegacySDLEmptyFields?: bool,
 *   allowLegacySDLImplementsInterfaces?: bool,
 *   experimentalFragmentVariables?: bool
 * }
 *
 * - **noLocation**:
 *   By default, the parser creates AST nodes that know the location in the source.
 *   This configuration flag disables that behavior for performance or testing.
 *
 * - **allowLegacySDLEmptyFields**:
 *   If enabled, the parser will parse empty fields sets in the Schema Definition Language.
 *   Otherwise, the parser will follow the current specification.
 *   This option is provided to ease adoption of the final SDL specification and will be removed in a future major release.
 *
 * - **allowLegacySDLImplementsInterfaces**:
 *   If enabled, the parser will parse implemented interfaces with no `&` character between each interface.
 *   Otherwise, the parser will follow the current specification.
 *   This option is provided to ease adoption of the final SDL specification and will be removed in a future major release.
 *
 * - **experimentalFragmentVariables**:
 *   If enabled, the parser will understand and parse variable definitions contained in a fragment definition.
 *   They'll be represented in the `variableDefinitions` field of the FragmentDefinitionNode.
 *   The syntax is identical to normal, query-defined variables. For example:
 *
 *   ```graphql
 *   fragment A($var: Boolean = false) on T {
 *     ...
 *   }
 *   ```
 *
 *   Note: this feature is experimental and may change or be removed in the future.
 *
 * Those magic functions allow partial parsing:
 *
 * @method static NameNode name(Source|string $source, ParserOptions $options = [])
 * @method static ExecutableDefinitionNode|TypeSystemDefinitionNode definition(Source|string $source, ParserOptions $options = [])
 * @method static ExecutableDefinitionNode executableDefinition(Source|string $source, ParserOptions $options = [])
 * @method static OperationDefinitionNode operationDefinition(Source|string $source, ParserOptions $options = [])
 * @method static string operationType(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<VariableDefinitionNode> variableDefinitions(Source|string $source, ParserOptions $options = [])
 * @method static VariableDefinitionNode variableDefinition(Source|string $source, ParserOptions $options = [])
 * @method static VariableNode variable(Source|string $source, ParserOptions $options = [])
 * @method static SelectionSetNode selectionSet(Source|string $source, ParserOptions $options = [])
 * @method static mixed selection(Source|string $source, ParserOptions $options = [])
 * @method static FieldNode field(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<ArgumentNode> arguments(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<ArgumentNode> constArguments(Source|string $source, ParserOptions $options = [])
 * @method static ArgumentNode argument(Source|string $source, ParserOptions $options = [])
 * @method static ArgumentNode constArgument(Source|string $source, ParserOptions $options = [])
 * @method static FragmentSpreadNode|InlineFragmentNode fragment(Source|string $source, ParserOptions $options = [])
 * @method static FragmentDefinitionNode fragmentDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NameNode fragmentName(Source|string $source, ParserOptions $options = [])
 * @method static BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|ListValueNode|NullValueNode|ObjectValueNode|StringValueNode|VariableNode valueLiteral(Source|string $source, ParserOptions $options = [])
 * @method static BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|ListValueNode|NullValueNode|ObjectValueNode|StringValueNode constValueLiteral(Source|string $source, ParserOptions $options = [])
 * @method static StringValueNode stringLiteral(Source|string $source, ParserOptions $options = [])
 * @method static BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|StringValueNode constValue(Source|string $source, ParserOptions $options = [])
 * @method static BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|ListValueNode|ObjectValueNode|StringValueNode|VariableNode variableValue(Source|string $source, ParserOptions $options = [])
 * @method static ListValueNode array(Source|string $source, ParserOptions $options = [])
 * @method static ListValueNode constArray(Source|string $source, ParserOptions $options = [])
 * @method static ObjectValueNode object(Source|string $source, ParserOptions $options = [])
 * @method static ObjectValueNode constObject(Source|string $source, ParserOptions $options = [])
 * @method static ObjectFieldNode objectField(Source|string $source, ParserOptions $options = [])
 * @method static ObjectFieldNode constObjectField(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<DirectiveNode> directives(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<DirectiveNode> constDirectives(Source|string $source, ParserOptions $options = [])
 * @method static DirectiveNode directive(Source|string $source, ParserOptions $options = [])
 * @method static DirectiveNode constDirective(Source|string $source, ParserOptions $options = [])
 * @method static ListTypeNode|NamedTypeNode|NonNullTypeNode typeReference(Source|string $source, ParserOptions $options = [])
 * @method static NamedTypeNode namedType(Source|string $source, ParserOptions $options = [])
 * @method static TypeSystemDefinitionNode typeSystemDefinition(Source|string $source, ParserOptions $options = [])
 * @method static StringValueNode|null description(Source|string $source, ParserOptions $options = [])
 * @method static SchemaDefinitionNode schemaDefinition(Source|string $source, ParserOptions $options = [])
 * @method static OperationTypeDefinitionNode operationTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static ScalarTypeDefinitionNode scalarTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static ObjectTypeDefinitionNode objectTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<NamedTypeNode> implementsInterfaces(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<FieldDefinitionNode> fieldsDefinition(Source|string $source, ParserOptions $options = [])
 * @method static FieldDefinitionNode fieldDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<InputValueDefinitionNode> argumentsDefinition(Source|string $source, ParserOptions $options = [])
 * @method static InputValueDefinitionNode inputValueDefinition(Source|string $source, ParserOptions $options = [])
 * @method static InterfaceTypeDefinitionNode interfaceTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static UnionTypeDefinitionNode unionTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<NamedTypeNode> unionMemberTypes(Source|string $source, ParserOptions $options = [])
 * @method static EnumTypeDefinitionNode enumTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<EnumValueDefinitionNode> enumValuesDefinition(Source|string $source, ParserOptions $options = [])
 * @method static EnumValueDefinitionNode enumValueDefinition(Source|string $source, ParserOptions $options = [])
 * @method static InputObjectTypeDefinitionNode inputObjectTypeDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<InputValueDefinitionNode> inputFieldsDefinition(Source|string $source, ParserOptions $options = [])
 * @method static TypeExtensionNode typeExtension(Source|string $source, ParserOptions $options = [])
 * @method static SchemaExtensionNode schemaTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static ScalarTypeExtensionNode scalarTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static ObjectTypeExtensionNode objectTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static InterfaceTypeExtensionNode interfaceTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static UnionTypeExtensionNode unionTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static EnumTypeExtensionNode enumTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static InputObjectTypeExtensionNode inputObjectTypeExtension(Source|string $source, ParserOptions $options = [])
 * @method static DirectiveDefinitionNode directiveDefinition(Source|string $source, ParserOptions $options = [])
 * @method static NodeList<NameNode> directiveLocations(Source|string $source, ParserOptions $options = [])
 * @method static NameNode directiveLocation(Source|string $source, ParserOptions $options = [])
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Language\ParserTest
 */
class Parser
{
    /**
     * Given a Automattic\WooCommerce\Vendor\GraphQL source, parses it into a `Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode`.
     *
     * Throws `Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError` if a syntax error is encountered.
     *
     * @param Source|string $source
     *
     * @phpstan-param ParserOptions $options
     *
     * @api
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    public static function parse($source, array $options = []): DocumentNode
    {
        return (new self($source, $options))->parseDocument();
    }

    /**
     * Given a string containing a Automattic\WooCommerce\Vendor\GraphQL value (ex. `[42]`), parse the AST for that value.
     *
     * Throws `Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError` if a syntax error is encountered.
     *
     * This is useful within tools that operate upon Automattic\WooCommerce\Vendor\GraphQL Values directly and
     * in isolation of complete Automattic\WooCommerce\Vendor\GraphQL documents.
     *
     * Consider providing the results to the utility function: `Automattic\WooCommerce\Vendor\GraphQL\Utils\AST::valueFromAST()`.
     *
     * @param Source|string $source
     *
     * @phpstan-param ParserOptions $options
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|ListValueNode|NullValueNode|ObjectValueNode|StringValueNode|VariableNode
     *
     * @api
     */
    public static function parseValue($source, array $options = [])
    {
        $parser = new Parser($source, $options);
        $parser->expect(Token::SOF);
        $value = $parser->parseValueLiteral(false);
        $parser->expect(Token::EOF);

        return $value;
    }

    /**
     * Given a string containing a Automattic\WooCommerce\Vendor\GraphQL Type (ex. `[Int!]`), parse the AST for that type.
     *
     * Throws `Automattic\WooCommerce\Vendor\GraphQL\Error\SyntaxError` if a syntax error is encountered.
     *
     * This is useful within tools that operate upon Automattic\WooCommerce\Vendor\GraphQL Types directly and
     * in isolation of complete Automattic\WooCommerce\Vendor\GraphQL documents.
     *
     * Consider providing the results to the utility function: `Automattic\WooCommerce\Vendor\GraphQL\Utils\AST::typeFromAST()`.
     *
     * @param Source|string $source
     *
     * @phpstan-param ParserOptions $options
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return ListTypeNode|NamedTypeNode|NonNullTypeNode
     *
     * @api
     */
    public static function parseType($source, array $options = [])
    {
        $parser = new Parser($source, $options);
        $parser->expect(Token::SOF);
        $type = $parser->parseTypeReference();
        $parser->expect(Token::EOF);

        return $type;
    }

    /**
     * Parse partial source by delegating calls to the internal parseX methods.
     *
     * @phpstan-param array{string, ParserOptions} $arguments
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return Node|NodeList<Node>
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $parser = new Parser(...$arguments);
        $parser->expect(Token::SOF);

        switch ($name) {
            case 'arguments':
                $parsed = $parser->parseArguments(false);
                break;
            case 'valueLiteral':
                $parsed = $parser->parseValueLiteral(false);
                break;
            case 'array':
                $parsed = $parser->parseArray(false);
                break;
            case 'object':
                $parsed = $parser->parseObject(false);
                break;
            case 'objectField':
                $parsed = $parser->parseObjectField(false);
                break;
            case 'directives':
                $parsed = $parser->parseDirectives(false);
                break;
            case 'directive':
                $parsed = $parser->parseDirective(false);
                break;
            case 'constArguments':
                $parsed = $parser->parseArguments(true);
                break;
            case 'constValueLiteral':
                $parsed = $parser->parseValueLiteral(true);
                break;
            case 'constArray':
                $parsed = $parser->parseArray(true);
                break;
            case 'constObject':
                $parsed = $parser->parseObject(true);
                break;
            case 'constObjectField':
                $parsed = $parser->parseObjectField(true);
                break;
            case 'constDirectives':
                $parsed = $parser->parseDirectives(true);
                break;
            case 'constDirective':
                $parsed = $parser->parseDirective(true);
                break;
            default:
                $parsed = $parser->{'parse' . $name}();
        }

        $parser->expect(Token::EOF);

        return $parsed;
    }

    private Lexer $lexer;

    /**
     * @param Source|string $source
     *
     * @phpstan-param ParserOptions        $options
     */
    public function __construct($source, array $options = [])
    {
        $sourceObj = $source instanceof Source
            ? $source
            : new Source($source);
        $this->lexer = new Lexer($sourceObj, $options);
    }

    /**
     * Returns a location object, used to identify the place in
     * the source that created a given parsed object.
     */
    private function loc(Token $startToken): ?Location
    {
        if (! ($this->lexer->options['noLocation'] ?? false)) {
            return new Location($startToken, $this->lexer->lastToken, $this->lexer->source);
        }

        return null;
    }

    /** Determines if the next token is of a given kind. */
    private function peek(string $kind): bool
    {
        return $this->lexer->token->kind === $kind;
    }

    /**
     * If the next token is of the given kind, return true after advancing
     * the parser. Otherwise, do not change the parser state and return false.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function skip(string $kind): bool
    {
        $match = $this->lexer->token->kind === $kind;

        if ($match) {
            $this->lexer->advance();
        }

        return $match;
    }

    /**
     * If the next token is of the given kind, return that token after advancing
     * the parser. Otherwise, do not change the parser state and return false.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function expect(string $kind): Token
    {
        $token = $this->lexer->token;

        if ($token->kind === $kind) {
            $this->lexer->advance();

            return $token;
        }

        throw new SyntaxError($this->lexer->source, $token->start, "Expected {$kind}, found {$token->getDescription()}");
    }

    /**
     * If the next token is a keyword with the given value, advance the lexer.
     * Otherwise, throw an error.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function expectKeyword(string $value): void
    {
        $token = $this->lexer->token;
        if ($token->kind !== Token::NAME || $token->value !== $value) {
            throw new SyntaxError($this->lexer->source, $token->start, "Expected \"{$value}\", found {$token->getDescription()}");
        }

        $this->lexer->advance();
    }

    /**
     * If the next token is a given keyword, return "true" after advancing
     * the lexer. Otherwise, do not change the parser state and return "false".
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function expectOptionalKeyword(string $value): bool
    {
        $token = $this->lexer->token;
        if ($token->kind === Token::NAME && $token->value === $value) {
            $this->lexer->advance();

            return true;
        }

        return false;
    }

    private function unexpected(?Token $atToken = null): SyntaxError
    {
        $token = $atToken ?? $this->lexer->token;

        return new SyntaxError($this->lexer->source, $token->start, 'Unexpected ' . $token->getDescription());
    }

    /**
     * Returns a possibly empty list of parse nodes, determined by
     * the parseFn. This list begins with a lex token of openKind
     * and ends with a lex token of closeKind. Advances the parser
     * to the next lex token after the closing token.
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<Node>
     */
    private function any(string $openKind, callable $parseFn, string $closeKind): NodeList
    {
        $this->expect($openKind);

        $nodes = [];
        while (! $this->skip($closeKind)) {
            $nodes[] = $parseFn($this);
        }

        return new NodeList($nodes);
    }

    /**
     * Returns a non-empty list of parse nodes, determined by
     * the parseFn. This list begins with a lex token of openKind
     * and ends with a lex token of closeKind. Advances the parser
     * to the next lex token after the closing token.
     *
     * @template TNode of Node
     *
     * @param callable(self): TNode $parseFn
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<TNode>
     */
    private function many(string $openKind, callable $parseFn, string $closeKind): NodeList
    {
        $this->expect($openKind);

        $nodes = [$parseFn($this)];
        while (! $this->skip($closeKind)) {
            $nodes[] = $parseFn($this);
        }

        return new NodeList($nodes);
    }

    /**
     * Converts a name lex token into a name parse node.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseName(): NameNode
    {
        $token = $this->expect(Token::NAME);

        return new NameNode([
            'value' => $token->value,
            'loc' => $this->loc($token),
        ]);
    }

    /**
     * Implements the parsing rules in the Document section.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseDocument(): DocumentNode
    {
        $start = $this->lexer->token;

        return new DocumentNode([
            'definitions' => $this->many(
                Token::SOF,
                fn (): DefinitionNode => $this->parseDefinition(),
                Token::EOF
            ),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return DefinitionNode&Node
     */
    private function parseDefinition(): DefinitionNode
    {
        if ($this->peek(Token::NAME)) {
            switch ($this->lexer->token->value) {
                case 'query':
                case 'mutation':
                case 'subscription':
                case 'fragment':
                    return $this->parseExecutableDefinition();

                    // Note: The schema definition language is an experimental addition.
                case 'schema':
                case 'scalar':
                case 'type':
                case 'interface':
                case 'union':
                case 'enum':
                case 'input':
                case 'directive':
                    // Note: The schema definition language is an experimental addition.
                    return $this->parseTypeSystemDefinition();

                case 'extend':
                    return $this->parseTypeSystemExtension();
            }
        } elseif ($this->peek(Token::BRACE_L)) {
            return $this->parseExecutableDefinition();
        } elseif ($this->peekDescription()) {
            // Note: The schema definition language is an experimental addition.
            return $this->parseTypeSystemDefinition();
        }

        throw $this->unexpected();
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return ExecutableDefinitionNode&Node
     */
    private function parseExecutableDefinition(): ExecutableDefinitionNode
    {
        if ($this->peek(Token::NAME)) {
            switch ($this->lexer->token->value) {
                case 'query':
                case 'mutation':
                case 'subscription':
                    return $this->parseOperationDefinition();

                case 'fragment':
                    return $this->parseFragmentDefinition();
            }
        } elseif ($this->peek(Token::BRACE_L)) {
            return $this->parseOperationDefinition();
        }

        throw $this->unexpected();
    }

    // Implements the parsing rules in the Operations section.

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseOperationDefinition(): OperationDefinitionNode
    {
        $start = $this->lexer->token;
        if ($this->peek(Token::BRACE_L)) {
            return new OperationDefinitionNode([
                'name' => null,
                'operation' => 'query',
                'variableDefinitions' => new NodeList([]),
                'directives' => new NodeList([]),
                'selectionSet' => $this->parseSelectionSet(),
                'loc' => $this->loc($start),
            ]);
        }

        $operation = $this->parseOperationType();

        $name = null;
        if ($this->peek(Token::NAME)) {
            $name = $this->parseName();
        }

        return new OperationDefinitionNode([
            'name' => $name,
            'operation' => $operation,
            'variableDefinitions' => $this->parseVariableDefinitions(),
            'directives' => $this->parseDirectives(false),
            'selectionSet' => $this->parseSelectionSet(),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseOperationType(): string
    {
        $operationToken = $this->expect(Token::NAME);
        switch ($operationToken->value) {
            case 'query':
                return 'query';

            case 'mutation':
                return 'mutation';

            case 'subscription':
                return 'subscription';
        }

        throw $this->unexpected($operationToken);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<VariableDefinitionNode>
     */
    private function parseVariableDefinitions(): NodeList
    {
        return $this->peek(Token::PAREN_L)
            ? $this->many(
                Token::PAREN_L,
                fn (): VariableDefinitionNode => $this->parseVariableDefinition(),
                Token::PAREN_R
            )
            : new NodeList([]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseVariableDefinition(): VariableDefinitionNode
    {
        $start = $this->lexer->token;
        $var = $this->parseVariable();

        $this->expect(Token::COLON);
        $type = $this->parseTypeReference();

        return new VariableDefinitionNode([
            'variable' => $var,
            'type' => $type,
            'defaultValue' => $this->skip(Token::EQUALS)
                ? $this->parseValueLiteral(true)
                : null,
            'directives' => $this->parseDirectives(true),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseVariable(): VariableNode
    {
        $start = $this->lexer->token;
        $this->expect(Token::DOLLAR);

        return new VariableNode([
            'name' => $this->parseName(),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseSelectionSet(): SelectionSetNode
    {
        $start = $this->lexer->token;

        return new SelectionSetNode(
            [
                'selections' => $this->many(
                    Token::BRACE_L,
                    fn (): SelectionNode => $this->parseSelection(),
                    Token::BRACE_R
                ),
                'loc' => $this->loc($start),
            ]
        );
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return SelectionNode&Node
     */
    private function parseSelection(): SelectionNode
    {
        return $this->peek(Token::SPREAD)
            ? $this->parseFragment()
            : $this->parseField();
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseField(): FieldNode
    {
        $start = $this->lexer->token;
        $nameOrAlias = $this->parseName();

        if ($this->skip(Token::COLON)) {
            $alias = $nameOrAlias;
            $name = $this->parseName();
        } else {
            $alias = null;
            $name = $nameOrAlias;
        }

        return new FieldNode([
            'name' => $name,
            'alias' => $alias,
            'arguments' => $this->parseArguments(false),
            'directives' => $this->parseDirectives(false),
            'selectionSet' => $this->peek(Token::BRACE_L) ? $this->parseSelectionSet() : null,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<ArgumentNode>
     */
    private function parseArguments(bool $isConst): NodeList
    {
        $parseFn = $isConst
            ? fn (): ArgumentNode => $this->parseConstArgument()
            : fn (): ArgumentNode => $this->parseArgument();

        return $this->peek(Token::PAREN_L)
            ? $this->many(Token::PAREN_L, $parseFn, Token::PAREN_R)
            : new NodeList([]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseArgument(): ArgumentNode
    {
        $start = $this->lexer->token;
        $name = $this->parseName();

        $this->expect(Token::COLON);
        $value = $this->parseValueLiteral(false);

        return new ArgumentNode([
            'name' => $name,
            'value' => $value,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseConstArgument(): ArgumentNode
    {
        $start = $this->lexer->token;
        $name = $this->parseName();

        $this->expect(Token::COLON);
        $value = $this->parseConstValue();

        return new ArgumentNode([
            'name' => $name,
            'value' => $value,
            'loc' => $this->loc($start),
        ]);
    }

    // Implements the parsing rules in the Fragments section.

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return FragmentSpreadNode|InlineFragmentNode
     */
    private function parseFragment(): SelectionNode
    {
        $start = $this->lexer->token;
        $this->expect(Token::SPREAD);

        $hasTypeCondition = $this->expectOptionalKeyword('on');
        if (! $hasTypeCondition && $this->peek(Token::NAME)) {
            return new FragmentSpreadNode([
                'name' => $this->parseFragmentName(),
                'directives' => $this->parseDirectives(false),
                'loc' => $this->loc($start),
            ]);
        }

        return new InlineFragmentNode([
            'typeCondition' => $hasTypeCondition ? $this->parseNamedType() : null,
            'directives' => $this->parseDirectives(false),
            'selectionSet' => $this->parseSelectionSet(),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseFragmentDefinition(): FragmentDefinitionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('fragment');

        $name = $this->parseFragmentName();

        // Experimental support for defining variables within fragments changes
        // the grammar of FragmentDefinition:
        //   - fragment FragmentName VariableDefinitions? on TypeCondition Directives? SelectionSet
        $variableDefinitions = isset($this->lexer->options['experimentalFragmentVariables'])
            ? $this->parseVariableDefinitions()
            : null;

        $this->expectKeyword('on');
        $typeCondition = $this->parseNamedType();

        return new FragmentDefinitionNode([
            'name' => $name,
            'variableDefinitions' => $variableDefinitions,
            'typeCondition' => $typeCondition,
            'directives' => $this->parseDirectives(false),
            'selectionSet' => $this->parseSelectionSet(),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseFragmentName(): NameNode
    {
        if ($this->lexer->token->value === 'on') {
            throw $this->unexpected();
        }

        return $this->parseName();
    }

    // Implements the parsing rules in the Values section.

    /**
     * Value[Const] :
     *   - [~Const] Variable
     *   - IntValue
     *   - FloatValue
     *   - StringValue
     *   - BooleanValue
     *   - NullValue
     *   - EnumValue
     *   - ListValue[?Const]
     *   - ObjectValue[?Const].
     *
     * BooleanValue : one of `true` `false`
     *
     * NullValue : `null`
     *
     * EnumValue : Name but not `true`, `false` or `null`
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return BooleanValueNode|EnumValueNode|FloatValueNode|IntValueNode|StringValueNode|VariableNode|ListValueNode|ObjectValueNode|NullValueNode
     */
    private function parseValueLiteral(bool $isConst): ValueNode
    {
        $token = $this->lexer->token;
        switch ($token->kind) {
            case Token::BRACKET_L:
                return $this->parseArray($isConst);

            case Token::BRACE_L:
                return $this->parseObject($isConst);

            case Token::INT:
                $this->lexer->advance();

                return new IntValueNode([
                    'value' => $token->value,
                    'loc' => $this->loc($token),
                ]);

            case Token::FLOAT:
                $this->lexer->advance();

                return new FloatValueNode([
                    'value' => $token->value,
                    'loc' => $this->loc($token),
                ]);

            case Token::STRING:
            case Token::BLOCK_STRING:
                return $this->parseStringLiteral();

            case Token::NAME:
                if ($token->value === 'true' || $token->value === 'false') {
                    $this->lexer->advance();

                    return new BooleanValueNode([
                        'value' => $token->value === 'true',
                        'loc' => $this->loc($token),
                    ]);
                }

                if ($token->value === 'null') {
                    $this->lexer->advance();

                    return new NullValueNode([
                        'loc' => $this->loc($token),
                    ]);
                }
                $this->lexer->advance();

                return new EnumValueNode([
                    'value' => $token->value,
                    'loc' => $this->loc($token),
                ]);

            case Token::DOLLAR:
                if (! $isConst) {
                    return $this->parseVariable();
                }

                break;
        }

        throw $this->unexpected();
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseStringLiteral(): StringValueNode
    {
        $token = $this->lexer->token;
        $this->lexer->advance();

        return new StringValueNode([
            'value' => $token->value,
            'block' => $token->kind === Token::BLOCK_STRING,
            'loc' => $this->loc($token),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseConstValue(): ValueNode
    {
        return $this->parseValueLiteral(true);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseVariableValue(): ValueNode
    {
        return $this->parseValueLiteral(false);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseArray(bool $isConst): ListValueNode
    {
        $start = $this->lexer->token;
        $parseFn = $isConst
            ? fn (): ValueNode => $this->parseConstValue()
            : fn (): ValueNode => $this->parseVariableValue();

        return new ListValueNode([
            'values' => $this->any(Token::BRACKET_L, $parseFn, Token::BRACKET_R),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseObject(bool $isConst): ObjectValueNode
    {
        $start = $this->lexer->token;
        $this->expect(Token::BRACE_L);
        $fields = [];
        while (! $this->skip(Token::BRACE_R)) {
            $fields[] = $this->parseObjectField($isConst);
        }

        return new ObjectValueNode([
            'fields' => new NodeList($fields),
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseObjectField(bool $isConst): ObjectFieldNode
    {
        $start = $this->lexer->token;
        $name = $this->parseName();

        $this->expect(Token::COLON);

        return new ObjectFieldNode([
            'name' => $name,
            'value' => $this->parseValueLiteral($isConst),
            'loc' => $this->loc($start),
        ]);
    }

    // Implements the parsing rules in the Directives section.

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<DirectiveNode>
     */
    private function parseDirectives(bool $isConst): NodeList
    {
        $directives = [];
        while ($this->peek(Token::AT)) {
            $directives[] = $this->parseDirective($isConst);
        }

        return new NodeList($directives);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseDirective(bool $isConst): DirectiveNode
    {
        $start = $this->lexer->token;
        $this->expect(Token::AT);

        return new DirectiveNode([
            'name' => $this->parseName(),
            'arguments' => $this->parseArguments($isConst),
            'loc' => $this->loc($start),
        ]);
    }

    // Implements the parsing rules in the Types section.

    /**
     * Handles the Type: TypeName, ListType, and NonNullType parsing rules.
     *
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return ListTypeNode|NamedTypeNode|NonNullTypeNode
     */
    private function parseTypeReference(): TypeNode
    {
        $start = $this->lexer->token;

        if ($this->skip(Token::BRACKET_L)) {
            $type = $this->parseTypeReference();
            $this->expect(Token::BRACKET_R);
            $type = new ListTypeNode([
                'type' => $type,
                'loc' => $this->loc($start),
            ]);
        } else {
            $type = $this->parseNamedType();
        }

        if ($this->skip(Token::BANG)) {
            return new NonNullTypeNode([
                'type' => $type,
                'loc' => $this->loc($start),
            ]);
        }

        return $type;
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseNamedType(): NamedTypeNode
    {
        $start = $this->lexer->token;

        return new NamedTypeNode([
            'name' => $this->parseName(),
            'loc' => $this->loc($start),
        ]);
    }

    // Implements the parsing rules in the Type Definition section.

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return TypeSystemDefinitionNode&Node
     */
    private function parseTypeSystemDefinition(): TypeSystemDefinitionNode
    {
        // Many definitions begin with a description and require a lookahead.
        $keywordToken = $this->peekDescription()
            ? $this->lexer->lookahead()
            : $this->lexer->token;

        if ($keywordToken->kind === Token::NAME) {
            switch ($keywordToken->value) {
                case 'schema':
                    return $this->parseSchemaDefinition();

                case 'scalar':
                    return $this->parseScalarTypeDefinition();

                case 'type':
                    return $this->parseObjectTypeDefinition();

                case 'interface':
                    return $this->parseInterfaceTypeDefinition();

                case 'union':
                    return $this->parseUnionTypeDefinition();

                case 'enum':
                    return $this->parseEnumTypeDefinition();

                case 'input':
                    return $this->parseInputObjectTypeDefinition();

                case 'directive':
                    return $this->parseDirectiveDefinition();
            }
        }

        throw $this->unexpected($keywordToken);
    }

    private function peekDescription(): bool
    {
        return $this->peek(Token::STRING) || $this->peek(Token::BLOCK_STRING);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseDescription(): ?StringValueNode
    {
        if ($this->peekDescription()) {
            return $this->parseStringLiteral();
        }

        return null;
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseSchemaDefinition(): SchemaDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('schema');
        $directives = $this->parseDirectives(true);

        $operationTypes = $this->many(
            Token::BRACE_L,
            fn (): OperationTypeDefinitionNode => $this->parseOperationTypeDefinition(),
            Token::BRACE_R
        );

        return new SchemaDefinitionNode([
            'directives' => $directives,
            'operationTypes' => $operationTypes,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseOperationTypeDefinition(): OperationTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $operation = $this->parseOperationType();
        $this->expect(Token::COLON);
        $type = $this->parseNamedType();

        return new OperationTypeDefinitionNode([
            'operation' => $operation,
            'type' => $type,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseScalarTypeDefinition(): ScalarTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('scalar');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);

        return new ScalarTypeDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseObjectTypeDefinition(): ObjectTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('type');
        $name = $this->parseName();
        $interfaces = $this->parseImplementsInterfaces();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseFieldsDefinition();

        return new ObjectTypeDefinitionNode([
            'name' => $name,
            'interfaces' => $interfaces,
            'directives' => $directives,
            'fields' => $fields,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<NamedTypeNode>
     */
    private function parseImplementsInterfaces(): NodeList
    {
        $types = [];
        if ($this->expectOptionalKeyword('implements')) {
            // Optional leading ampersand
            $this->skip(Token::AMP);
            do {
                $types[] = $this->parseNamedType();
            } while (
                $this->skip(Token::AMP)
                // Legacy support for the SDL?
                || (($this->lexer->options['allowLegacySDLImplementsInterfaces'] ?? false) && $this->peek(Token::NAME))
            );
        }

        return new NodeList($types);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<FieldDefinitionNode>
     */
    private function parseFieldsDefinition(): NodeList
    {
        // Legacy support for the SDL?
        if (
            ($this->lexer->options['allowLegacySDLEmptyFields'] ?? false)
            && $this->peek(Token::BRACE_L)
            && $this->lexer->lookahead()->kind === Token::BRACE_R
        ) {
            $this->lexer->advance();
            $this->lexer->advance();

            /** @phpstan-var NodeList<FieldDefinitionNode> $nodeList */
            $nodeList = new NodeList([]);
        } else {
            /** @phpstan-var NodeList<FieldDefinitionNode> $nodeList */
            $nodeList = $this->peek(Token::BRACE_L)
                ? $this->many(
                    Token::BRACE_L,
                    fn (): FieldDefinitionNode => $this->parseFieldDefinition(),
                    Token::BRACE_R
                )
                : new NodeList([]);
        }

        return $nodeList;
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseFieldDefinition(): FieldDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $name = $this->parseName();
        $args = $this->parseArgumentsDefinition();
        $this->expect(Token::COLON);
        $type = $this->parseTypeReference();
        $directives = $this->parseDirectives(true);

        return new FieldDefinitionNode([
            'name' => $name,
            'arguments' => $args,
            'type' => $type,
            'directives' => $directives,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<InputValueDefinitionNode>
     */
    private function parseArgumentsDefinition(): NodeList
    {
        return $this->peek(Token::PAREN_L)
            ? $this->many(
                Token::PAREN_L,
                fn (): InputValueDefinitionNode => $this->parseInputValueDefinition(),
                Token::PAREN_R
            )
            : new NodeList([]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseInputValueDefinition(): InputValueDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $name = $this->parseName();
        $this->expect(Token::COLON);
        $type = $this->parseTypeReference();
        $defaultValue = null;
        if ($this->skip(Token::EQUALS)) {
            $defaultValue = $this->parseConstValue();
        }

        $directives = $this->parseDirectives(true);

        return new InputValueDefinitionNode([
            'name' => $name,
            'type' => $type,
            'defaultValue' => $defaultValue,
            'directives' => $directives,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseInterfaceTypeDefinition(): InterfaceTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('interface');
        $name = $this->parseName();
        $interfaces = $this->parseImplementsInterfaces();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseFieldsDefinition();

        return new InterfaceTypeDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'interfaces' => $interfaces,
            'fields' => $fields,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * UnionTypeDefinition :
     *   - Description? union Name Directives[Const]? UnionMemberTypes?
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseUnionTypeDefinition(): UnionTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('union');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $types = $this->parseUnionMemberTypes();

        return new UnionTypeDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'types' => $types,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<NamedTypeNode>
     */
    private function parseUnionMemberTypes(): NodeList
    {
        $types = [];
        if ($this->skip(Token::EQUALS)) {
            // Optional leading pipe
            $this->skip(Token::PIPE);
            do {
                $types[] = $this->parseNamedType();
            } while ($this->skip(Token::PIPE));
        }

        return new NodeList($types);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseEnumTypeDefinition(): EnumTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('enum');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $values = $this->parseEnumValuesDefinition();

        return new EnumTypeDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'values' => $values,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<EnumValueDefinitionNode>
     */
    private function parseEnumValuesDefinition(): NodeList
    {
        return $this->peek(Token::BRACE_L)
            ? $this->many(
                Token::BRACE_L,
                fn (): EnumValueDefinitionNode => $this->parseEnumValueDefinition(),
                Token::BRACE_R
            )
            : new NodeList([]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseEnumValueDefinition(): EnumValueDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);

        return new EnumValueDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseInputObjectTypeDefinition(): InputObjectTypeDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('input');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseInputFieldsDefinition();

        return new InputObjectTypeDefinitionNode([
            'name' => $name,
            'directives' => $directives,
            'fields' => $fields,
            'loc' => $this->loc($start),
            'description' => $description,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<InputValueDefinitionNode>
     */
    private function parseInputFieldsDefinition(): NodeList
    {
        return $this->peek(Token::BRACE_L)
            ? $this->many(
                Token::BRACE_L,
                fn (): InputValueDefinitionNode => $this->parseInputValueDefinition(),
                Token::BRACE_R
            )
            : new NodeList([]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return TypeSystemExtensionNode&Node
     */
    private function parseTypeSystemExtension(): TypeSystemExtensionNode
    {
        $keywordToken = $this->lexer->lookahead();

        if ($keywordToken->kind === Token::NAME) {
            switch ($keywordToken->value) {
                case 'schema':
                    return $this->parseSchemaTypeExtension();

                case 'scalar':
                    return $this->parseScalarTypeExtension();

                case 'type':
                    return $this->parseObjectTypeExtension();

                case 'interface':
                    return $this->parseInterfaceTypeExtension();

                case 'union':
                    return $this->parseUnionTypeExtension();

                case 'enum':
                    return $this->parseEnumTypeExtension();

                case 'input':
                    return $this->parseInputObjectTypeExtension();
            }
        }

        throw $this->unexpected($keywordToken);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseSchemaTypeExtension(): SchemaExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('schema');
        $directives = $this->parseDirectives(true);

        $operationTypes = $this->peek(Token::BRACE_L)
            ? $this->many(
                Token::BRACE_L,
                fn (): OperationTypeDefinitionNode => $this->parseOperationTypeDefinition(),
                Token::BRACE_R
            )
            : new NodeList([]);
        if (count($directives) === 0 && count($operationTypes) === 0) {
            $this->unexpected();
        }

        return new SchemaExtensionNode([
            'directives' => $directives,
            'operationTypes' => $operationTypes,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseScalarTypeExtension(): ScalarTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('scalar');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        if (count($directives) === 0) {
            throw $this->unexpected();
        }

        return new ScalarTypeExtensionNode([
            'name' => $name,
            'directives' => $directives,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseObjectTypeExtension(): ObjectTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('type');
        $name = $this->parseName();
        $interfaces = $this->parseImplementsInterfaces();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseFieldsDefinition();

        if (
            count($interfaces) === 0
            && count($directives) === 0
            && count($fields) === 0
        ) {
            throw $this->unexpected();
        }

        return new ObjectTypeExtensionNode([
            'name' => $name,
            'interfaces' => $interfaces,
            'directives' => $directives,
            'fields' => $fields,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseInterfaceTypeExtension(): InterfaceTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('interface');
        $name = $this->parseName();
        $interfaces = $this->parseImplementsInterfaces();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseFieldsDefinition();
        if (
            count($interfaces) === 0
            && count($directives) === 0
            && count($fields) === 0
        ) {
            throw $this->unexpected();
        }

        return new InterfaceTypeExtensionNode([
            'name' => $name,
            'directives' => $directives,
            'interfaces' => $interfaces,
            'fields' => $fields,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * UnionTypeExtension :
     *   - extend union Name Directives[Const]? UnionMemberTypes
     *   - extend union Name Directives[Const].
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseUnionTypeExtension(): UnionTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('union');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $types = $this->parseUnionMemberTypes();
        if (count($directives) === 0 && count($types) === 0) {
            throw $this->unexpected();
        }

        return new UnionTypeExtensionNode([
            'name' => $name,
            'directives' => $directives,
            'types' => $types,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseEnumTypeExtension(): EnumTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('enum');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $values = $this->parseEnumValuesDefinition();
        if (
            count($directives) === 0
            && count($values) === 0
        ) {
            throw $this->unexpected();
        }

        return new EnumTypeExtensionNode([
            'name' => $name,
            'directives' => $directives,
            'values' => $values,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseInputObjectTypeExtension(): InputObjectTypeExtensionNode
    {
        $start = $this->lexer->token;
        $this->expectKeyword('extend');
        $this->expectKeyword('input');
        $name = $this->parseName();
        $directives = $this->parseDirectives(true);
        $fields = $this->parseInputFieldsDefinition();
        if (
            count($directives) === 0
            && count($fields) === 0
        ) {
            throw $this->unexpected();
        }

        return new InputObjectTypeExtensionNode([
            'name' => $name,
            'directives' => $directives,
            'fields' => $fields,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * DirectiveDefinition :
     *   - Description? directive @ Name ArgumentsDefinition? `repeatable`? on DirectiveLocations.
     *
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseDirectiveDefinition(): DirectiveDefinitionNode
    {
        $start = $this->lexer->token;
        $description = $this->parseDescription();
        $this->expectKeyword('directive');
        $this->expect(Token::AT);
        $name = $this->parseName();
        $args = $this->parseArgumentsDefinition();
        $repeatable = $this->expectOptionalKeyword('repeatable');
        $this->expectKeyword('on');
        $locations = $this->parseDirectiveLocations();

        return new DirectiveDefinitionNode([
            'name' => $name,
            'description' => $description,
            'arguments' => $args,
            'repeatable' => $repeatable,
            'locations' => $locations,
            'loc' => $this->loc($start),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     *
     * @return NodeList<NameNode>
     */
    private function parseDirectiveLocations(): NodeList
    {
        // Optional leading pipe
        $this->skip(Token::PIPE);
        $locations = [];
        do {
            $locations[] = $this->parseDirectiveLocation();
        } while ($this->skip(Token::PIPE));

        return new NodeList($locations);
    }

    /**
     * @throws \JsonException
     * @throws SyntaxError
     */
    private function parseDirectiveLocation(): NameNode
    {
        $start = $this->lexer->token;
        $name = $this->parseName();
        if (DirectiveLocation::has($name->value)) {
            return $name;
        }

        throw $this->unexpected($start);
    }
}
