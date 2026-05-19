<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Language;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeKind;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\TypeInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Utils\Utils;

/**
 * Utility for efficient AST traversal and modification.
 *
 * `visit()` will walk through an AST using a depth first traversal, calling
 * the visitor's enter function at each node in the traversal, and calling the
 * leave function after visiting that node and all of its child nodes.
 *
 * By returning different values from the `enter` and `leave` functions, the behavior of the visitor can be altered.
 *
 * - no return (`void`) or return `null`: no action
 * - `Visitor::skipNode()`: skips over the subtree at the current node of the AST
 * - `Visitor::stop()`: stop the Visitor completely
 * - `Visitor::removeNode()`: remove the current node
 * - return any other value: replace this node with the returned value
 *
 * When using `visit()` to edit an AST, the original AST will not be modified, and
 * a new version of the AST with the changes applied will be returned from the
 * visit function.
 *
 * ```php
 * $editedAST = Visitor::visit($ast, [
 *     'enter' => function (Node $node, $key, $parent, array $path, array $ancestors) {
 *         // ...
 *     },
 *     'leave' => function (Node $node, $key, $parent, array $path, array $ancestors) {
 *         // ...
 *     }
 * ]);
 * ```
 *
 * Alternatively to providing `enter` and `leave` functions, a visitor can
 * instead provide functions named the same as the [kinds of AST nodes](class-reference.md#graphqllanguageastnodekind),
 * or enter/leave visitors at a named key, leading to four permutations of
 * visitor API:
 *
 * 1. Named visitors triggered when entering a node a specific kind.
 *
 *    ```php
 *    Visitor::visit($ast, [
 *        NodeKind::OBJECT_TYPE_DEFINITION => function (ObjectTypeDefinitionNode $node) {
 *            // enter the "ObjectTypeDefinition" node
 *        }
 *    ]);
 *    ```
 *
 * 2. Named visitors that trigger upon entering and leaving a node of
 *    a specific kind.
 *
 *    ```php
 *    Visitor::visit($ast, [
 *        NodeKind::OBJECT_TYPE_DEFINITION => [
 *            'enter' => function (ObjectTypeDefinitionNode $node) {
 *                // enter the "ObjectTypeDefinition" node
 *            },
 *            'leave' => function (ObjectTypeDefinitionNode $node) {
 *                // leave the "ObjectTypeDefinition" node
 *            }
 *        ]
 *    ]);
 *    ```
 *
 * 3. Generic visitors that trigger upon entering and leaving any node.
 *
 *    ```php
 *    Visitor::visit($ast, [
 *        'enter' => function (Node $node) {
 *            // enter any node
 *        },
 *        'leave' => function (Node $node) {
 *            // leave any node
 *        }
 *    ]);
 *    ```
 *
 * 4. Parallel visitors for entering and leaving nodes of a specific kind.
 *
 *    ```php
 *    Visitor::visit($ast, [
 *        'enter' => [
 *            NodeKind::OBJECT_TYPE_DEFINITION => function (ObjectTypeDefinitionNode $node) {
 *                // enter the "ObjectTypeDefinition" node
 *            }
 *        ],
 *        'leave' => [
 *            NodeKind::OBJECT_TYPE_DEFINITION => function (ObjectTypeDefinitionNode $node) {
 *                // leave the "ObjectTypeDefinition" node
 *            }
 *        ]
 *    ]);
 *    ```
 *
 * @phpstan-type NodeVisitor callable(Node): (VisitorOperation|Node|NodeList<Node>|null|false|void)
 * @phpstan-type VisitorArray array<string, NodeVisitor>|array<string, array<string, NodeVisitor>>
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Language\VisitorTest
 */
class Visitor
{
    public const VISITOR_KEYS = [
        NodeKind::NAME => [],
        NodeKind::DOCUMENT => ['definitions'],
        NodeKind::OPERATION_DEFINITION => ['name', 'variableDefinitions', 'directives', 'selectionSet'],
        NodeKind::VARIABLE_DEFINITION => ['variable', 'type', 'defaultValue', 'directives'],
        NodeKind::VARIABLE => ['name'],
        NodeKind::SELECTION_SET => ['selections'],
        NodeKind::FIELD => ['alias', 'name', 'arguments', 'directives', 'selectionSet'],
        NodeKind::ARGUMENT => ['name', 'value'],
        NodeKind::FRAGMENT_SPREAD => ['name', 'directives'],
        NodeKind::INLINE_FRAGMENT => ['typeCondition', 'directives', 'selectionSet'],
        NodeKind::FRAGMENT_DEFINITION => [
            'name',
            // Note: fragment variable definitions are experimental and may be changed
            // or removed in the future.
            'variableDefinitions',
            'typeCondition',
            'directives',
            'selectionSet',
        ],

        NodeKind::INT => [],
        NodeKind::FLOAT => [],
        NodeKind::STRING => [],
        NodeKind::BOOLEAN => [],
        NodeKind::NULL => [],
        NodeKind::ENUM => [],
        NodeKind::LST => ['values'],
        NodeKind::OBJECT => ['fields'],
        NodeKind::OBJECT_FIELD => ['name', 'value'],
        NodeKind::DIRECTIVE => ['name', 'arguments'],
        NodeKind::NAMED_TYPE => ['name'],
        NodeKind::LIST_TYPE => ['type'],
        NodeKind::NON_NULL_TYPE => ['type'],

        NodeKind::SCHEMA_DEFINITION => ['description', 'directives', 'operationTypes'],
        NodeKind::OPERATION_TYPE_DEFINITION => ['type'],
        NodeKind::SCALAR_TYPE_DEFINITION => ['description', 'name', 'directives'],
        NodeKind::OBJECT_TYPE_DEFINITION => ['description', 'name', 'interfaces', 'directives', 'fields'],
        NodeKind::FIELD_DEFINITION => ['description', 'name', 'arguments', 'type', 'directives'],
        NodeKind::INPUT_VALUE_DEFINITION => ['description', 'name', 'type', 'defaultValue', 'directives'],
        NodeKind::INTERFACE_TYPE_DEFINITION => ['description', 'name', 'interfaces', 'directives', 'fields'],
        NodeKind::UNION_TYPE_DEFINITION => ['description', 'name', 'directives', 'types'],
        NodeKind::ENUM_TYPE_DEFINITION => ['description', 'name', 'directives', 'values'],
        NodeKind::ENUM_VALUE_DEFINITION => ['description', 'name', 'directives'],
        NodeKind::INPUT_OBJECT_TYPE_DEFINITION => ['description', 'name', 'directives', 'fields'],

        NodeKind::SCALAR_TYPE_EXTENSION => ['name', 'directives'],
        NodeKind::OBJECT_TYPE_EXTENSION => ['name', 'interfaces', 'directives', 'fields'],
        NodeKind::INTERFACE_TYPE_EXTENSION => ['name', 'interfaces', 'directives', 'fields'],
        NodeKind::UNION_TYPE_EXTENSION => ['name', 'directives', 'types'],
        NodeKind::ENUM_TYPE_EXTENSION => ['name', 'directives', 'values'],
        NodeKind::INPUT_OBJECT_TYPE_EXTENSION => ['name', 'directives', 'fields'],

        NodeKind::DIRECTIVE_DEFINITION => ['description', 'name', 'arguments', 'locations'],

        NodeKind::SCHEMA_EXTENSION => ['directives', 'operationTypes'],
    ];

    /**
     * Visit the AST (see class description for details).
     *
     * @param NodeList<Node>|Node $root
     * @param VisitorArray $visitor
     * @param array<string, mixed>|null $keyMap
     *
     * @throws \Exception
     *
     * @return mixed
     *
     * @api
     */
    public static function visit(object $root, array $visitor, ?array $keyMap = null)
    {
        $visitorKeys = $keyMap ?? self::VISITOR_KEYS;

        /**
         * @var list<array{
         *   inList: bool,
         *   index: int,
         *   keys: Node|NodeList|mixed,
         *   edits: array<int, array{mixed, mixed}>,
         * }> $stack */
        $stack = [];
        $inList = $root instanceof NodeList;
        $keys = [$root];
        $index = -1;
        $edits = [];
        $parent = null;
        $path = [];
        $ancestors = [];

        do {
            ++$index;
            $isLeaving = $index === count($keys);
            $key = null;
            $node = null;
            $isEdited = $isLeaving && $edits !== [];

            if ($isLeaving) {
                $key = $ancestors === []
                    ? null
                    : $path[count($path) - 1];
                $node = $parent;
                $parent = array_pop($ancestors);
                if ($isEdited) {
                    if ($node instanceof Node || $node instanceof NodeList) {
                        $node = $node->cloneDeep();
                    }

                    $editOffset = 0;
                    foreach ($edits as [$editKey, $editValue]) {
                        if ($inList) {
                            $editKey -= $editOffset;
                        }

                        if ($inList && $editValue === null) {
                            assert($node instanceof NodeList, 'Follows from $inList');
                            $node->splice($editKey, 1);
                            ++$editOffset;
                        } elseif ($node instanceof NodeList) {
                            if ($editValue instanceof NodeList) {
                                $node->splice($editKey, 1, $editValue);
                                $editOffset -= count($editValue) - 1;
                            } elseif ($editValue instanceof Node) {
                                $node[$editKey] = $editValue;
                            } else {
                                $notNodeOrNodeList = Utils::printSafe($editValue);
                                throw new \Exception("Can only add Node or NodeList to NodeList, got: {$notNodeOrNodeList}.");
                            }
                        } else {
                            $node->{$editKey} = $editValue;
                        }
                    }
                }
                // @phpstan-ignore-next-line the stack is guaranteed to be non-empty at this point
                [
                    'index' => $index,
                    'keys' => $keys,
                    'edits' => $edits,
                    'inList' => $inList,
                ] = array_pop($stack);
            } elseif ($parent === null) {
                $node = $root;
            } else {
                $key = $inList
                    ? $index
                    : $keys[$index];
                $node = $parent instanceof NodeList
                    ? $parent[$key]
                    : $parent->{$key};
                if ($node === null) {
                    continue;
                }
                $path[] = $key;
            }

            $result = null;
            if (! $node instanceof NodeList) {
                if (! $node instanceof Node) {
                    $notNode = Utils::printSafe($node);
                    throw new \Exception("Invalid AST Node: {$notNode}.");
                }

                $visitFn = self::extractVisitFn($visitor, $node->kind, $isLeaving);

                if ($visitFn !== null) {
                    $result = $visitFn($node, $key, $parent, $path, $ancestors);

                    if ($result !== null) {
                        if ($result instanceof VisitorStop) {
                            break;
                        }

                        if ($result instanceof VisitorSkipNode) {
                            if (! $isLeaving) {
                                array_pop($path);
                            }
                            continue;
                        }

                        $editValue = $result instanceof VisitorRemoveNode
                            ? null
                            : $result;

                        $edits[] = [$key, $editValue];
                        if (! $isLeaving) {
                            if (! $editValue instanceof Node) {
                                array_pop($path);
                                continue;
                            }

                            $node = $editValue;
                        }
                    }
                }
            }

            if ($result === null && $isEdited) {
                $edits[] = [$key, $node];
            }

            if ($isLeaving) {
                array_pop($path);
            } else {
                $stack[] = [
                    'inList' => $inList,
                    'index' => $index,
                    'keys' => $keys,
                    'edits' => $edits,
                ];
                $inList = $node instanceof NodeList;

                $keys = ($inList ? $node : $visitorKeys[$node->kind]) ?? [];
                $index = -1;
                $edits = [];
                if ($parent !== null) {
                    $ancestors[] = $parent;
                }

                $parent = $node;
            }
        } while ($stack !== []);

        return $edits === []
            ? $root
            : $edits[0][1];
    }

    /**
     * Returns marker for stopping.
     *
     * @api
     */
    public static function stop(): VisitorStop
    {
        static $stop;

        return $stop ??= new VisitorStop();
    }

    /**
     * Returns marker for skipping the subtree at the current node.
     *
     * @api
     */
    public static function skipNode(): VisitorSkipNode
    {
        static $skipNode;

        return $skipNode ??= new VisitorSkipNode();
    }

    /**
     * Returns marker for removing the current node.
     *
     * @api
     */
    public static function removeNode(): VisitorRemoveNode
    {
        static $removeNode;

        return $removeNode ??= new VisitorRemoveNode();
    }

    /**
     * Combines the given visitors to run in parallel.
     *
     * @phpstan-param array<int, VisitorArray> $visitors
     *
     * @return VisitorArray
     */
    public static function visitInParallel(array $visitors): array
    {
        $visitorsCount = count($visitors);
        $skipping = new \SplFixedArray($visitorsCount);

        return [
            'enter' => static function (Node $node) use ($visitors, $skipping, $visitorsCount) {
                for ($i = 0; $i < $visitorsCount; ++$i) {
                    if ($skipping[$i] !== null) {
                        continue;
                    }

                    $fn = self::extractVisitFn(
                        $visitors[$i],
                        $node->kind,
                        false
                    );

                    if ($fn === null) {
                        continue;
                    }

                    $result = $fn(...func_get_args());

                    if ($result === null) {
                        continue;
                    }
                    if ($result instanceof VisitorSkipNode) {
                        $skipping[$i] = $node;
                    } elseif ($result instanceof VisitorStop) {
                        $skipping[$i] = $result;
                    } else {
                        return $result;
                    }
                }

                return null;
            },
            'leave' => static function (Node $node) use ($visitors, $skipping, $visitorsCount) {
                for ($i = 0; $i < $visitorsCount; ++$i) {
                    if ($skipping[$i] === null) {
                        $fn = self::extractVisitFn(
                            $visitors[$i],
                            $node->kind,
                            true
                        );

                        if ($fn !== null) {
                            $result = $fn(...func_get_args());

                            if ($result === null) {
                                continue;
                            }
                            if ($result instanceof VisitorStop) {
                                $skipping[$i] = $result;
                            } elseif ($result instanceof VisitorRemoveNode) {
                                return $result;
                            } else {
                                return $result;
                            }
                        }
                    } elseif ($skipping[$i] === $node) {
                        $skipping[$i] = null;
                    }
                }

                return null;
            },
        ];
    }

    /**
     * Creates a new visitor that updates TypeInfo and delegates to the given visitor.
     *
     * @phpstan-param VisitorArray $visitor
     *
     * @phpstan-return VisitorArray
     */
    public static function visitWithTypeInfo(TypeInfo $typeInfo, array $visitor): array
    {
        return [
            'enter' => static function (Node $node) use ($typeInfo, $visitor) {
                $typeInfo->enter($node);
                $fn = self::extractVisitFn($visitor, $node->kind, false);

                if ($fn === null) {
                    return null;
                }

                $result = $fn(...func_get_args());
                if ($result === null) {
                    return null;
                }

                $typeInfo->leave($node);
                if ($result instanceof Node) {
                    $typeInfo->enter($result);
                }

                return $result;
            },
            'leave' => static function (Node $node) use ($typeInfo, $visitor) {
                $fn = self::extractVisitFn($visitor, $node->kind, true);
                $result = $fn !== null
                    ? $fn(...func_get_args())
                    : null;

                $typeInfo->leave($node);

                return $result;
            },
        ];
    }

    /**
     * @phpstan-param VisitorArray $visitor
     *
     * @return (callable(Node $node, string|int|null $key, Node|NodeList<Node>|null $parent, array<int, int|string> $path, array<int, Node|NodeList<Node>> $ancestors): (VisitorOperation|Node|null))|(callable(Node): (VisitorOperation|Node|NodeList<Node>|void|false|null))|null
     */
    protected static function extractVisitFn(array $visitor, string $kind, bool $isLeaving): ?callable
    {
        $kindVisitor = $visitor[$kind] ?? null;

        if ($kindVisitor !== null) {
            if (is_array($kindVisitor)) {
                return $isLeaving
                    ? $kindVisitor['leave'] ?? null
                    : $kindVisitor['enter'] ?? null;
            }

            if (! $isLeaving) {
                return $kindVisitor;
            }
        }

        $specificVisitor = $isLeaving
            ? $visitor['leave'] ?? null
            : $visitor['enter'] ?? null;

        if ($specificVisitor !== null && is_array($specificVisitor)) {
            return $specificVisitor[$kind] ?? null;
        }

        return $specificVisitor;
    }
}
