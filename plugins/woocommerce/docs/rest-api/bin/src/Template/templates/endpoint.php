<article class="endpoint-doc">
    <header class="endpoint-header">
        <div class="endpoint-verbs">
            <?php foreach ($descriptor->verbs as $verb): ?>
                <span class="verb verb-<?php $engine->e(strtolower($verb)); ?>"><?php $engine->e($verb); ?></span>
            <?php endforeach; ?>
            <?php if ($descriptor->public): ?>
                <span class="badge badge-public" title="This endpoint does not require authentication">Unauthenticated</span>
            <?php endif; ?>
        </div>
        <h1 class="endpoint-route"><?php $engine->e($displayRoute); ?></h1>
    </header>

    <nav class="section-nav">
        <?php
        $hasDescription = !empty(trim($descriptor->description));
        $hasRouteParams = count($pathParams) > 0;
        $hasQueryParams = count($parameters) > 0;
        ?>
        <?php if ($hasDescription): ?>
        <span class="section-nav-item">
            <a href="#description">Description</a>
            <button class="copy-link-btn" data-section="description" title="Copy link">&#128279;</button>
        </span>
        <?php endif; ?>
        <?php if ($hasRouteParams): ?>
        <span class="section-nav-item">
            <a href="#route-parameters">Route Parameters</a>
            <button class="copy-link-btn" data-section="route-parameters" title="Copy link">&#128279;</button>
        </span>
        <?php endif; ?>
        <?php if ($hasQueryParams): ?>
        <span class="section-nav-item">
            <a href="#query-parameters">Parameters</a>
            <button class="copy-link-btn" data-section="query-parameters" title="Copy link">&#128279;</button>
        </span>
        <?php endif; ?>
        <span class="section-nav-item">
            <a href="#response">Response</a>
            <button class="copy-link-btn" data-section="response" title="Copy link">&#128279;</button>
        </span>
        <span class="section-nav-item">
            <a href="#examples">Examples</a>
            <button class="copy-link-btn" data-section="examples" title="Copy link">&#128279;</button>
        </span>
    </nav>

    <section class="endpoint-summary">
        <h2><?php $engine->e($descriptor->name); ?></h2>
    </section>

    <?php if (!empty(trim($descriptor->description))): ?>
    <section id="description" class="endpoint-description-section collapsible-section expanded">
        <h2 class="section-toggle">
            <span class="toggle-icon">&#9660;</span>
            Description
        </h2>
        <div class="section-content">
            <div class="endpoint-description">
                <?php echo $engine->markdown($descriptor->description); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // =========================================================================
    // Helper functions - defined here so they're available for all sections
    // =========================================================================

    // Helper function to get array type display (handles nested arrays)
    if (!function_exists('getArrayTypeDisplay')) {
        function getArrayTypeDisplay(array $items): string {
            $itemType = $items['type'] ?? null;

            // Handle nullable item types
            if (is_array($itemType)) {
                $itemType = array_filter($itemType, fn($t) => $t !== 'null');
                $itemType = count($itemType) === 1 ? reset($itemType) : implode('|', $itemType);
            }

            if ($itemType === 'array' && isset($items['items'])) {
                // Nested array - recurse
                return '[' . getArrayTypeDisplay($items['items']) . ']';
            } elseif ($itemType) {
                return '[' . $itemType . ']';
            } else {
                return '[]';
            }
        }
    }

    // Helper function to format parameter type
    if (!function_exists('formatParamType')) {
        function formatParamType(array $param, $engine): array {
            // Check for oneOf/anyOf first (at top level)
            if (isset($param['oneOf'])) {
                return [
                    'typeDisplay' => 'one of',
                    'typeHtml' => '<span class="type-variant" title="Exactly one of the listed options">one of</span>',
                ];
            }
            if (isset($param['anyOf'])) {
                return [
                    'typeDisplay' => 'any of',
                    'typeHtml' => '<span class="type-variant" title="One or more of the listed options">any of</span>',
                ];
            }
            // Check for oneOf/anyOf in array items
            if (($param['type'] ?? '') === 'array' && isset($param['items'])) {
                if (isset($param['items']['oneOf'])) {
                    return [
                        'typeDisplay' => '[one of]',
                        'typeHtml' => '<span class="type-variant" title="Array where each item is exactly one of the listed options">[one of]</span>',
                    ];
                }
                if (isset($param['items']['anyOf'])) {
                    return [
                        'typeDisplay' => '[any of]',
                        'typeHtml' => '<span class="type-variant" title="Array where each item matches one or more of the listed options">[any of]</span>',
                    ];
                }
            }

            $type = $param['type'] ?? 'string';
            $isNullable = false;
            $pattern = $param['pattern'] ?? null;
            $format = $param['format'] ?? null;

            // Handle array of types and extract nullable
            if (is_array($type)) {
                if (in_array('null', $type)) {
                    $isNullable = true;
                    $type = array_filter($type, fn($t) => $t !== 'null');
                    $type = array_values($type);
                }
                $type = count($type) === 1 ? $type[0] : implode('|', $type);
            } elseif (str_contains($type, '|null') || str_contains($type, 'null|')) {
                $isNullable = true;
                $type = str_replace(['|null', 'null|'], '', $type);
            }

            // Determine display type: format > array notation > base type
            $typeDisplay = $type;

            if ($format !== null) {
                $typeDisplay = $format;
            } elseif ($type === 'array' && isset($param['items'])) {
                // For arrays, show as [itemType] with nested support
                $typeDisplay = getArrayTypeDisplay($param['items']);
            }

            // Build nullable indicator
            $nullableHtml = $isNullable ? ' <span class="nullable-icon" title="Nullable">?</span>' : '';

            return [
                'typeDisplay' => $typeDisplay,
                'typeHtml' => $engine->escape($typeDisplay) . $nullableHtml,
            ];
        }
    }

    // Helper function to build constraints as array
    if (!function_exists('buildConstraints')) {
        function buildConstraints(array $param): array {
            $constraints = [];

            // String length constraints
            $minLength = $param['minLength'] ?? null;
            $maxLength = $param['maxLength'] ?? null;
            if ($minLength !== null && $maxLength !== null) {
                $constraints[] = "Length: {$minLength}-{$maxLength}";
            } elseif ($minLength !== null) {
                $constraints[] = "Min length: {$minLength}";
            } elseif ($maxLength !== null) {
                $constraints[] = "Max length: {$maxLength}";
            }

            // Numeric constraints
            $minimum = $param['minimum'] ?? null;
            $maximum = $param['maximum'] ?? null;
            $exclusiveMin = $param['exclusiveMinimum'] ?? false;
            $exclusiveMax = $param['exclusiveMaximum'] ?? false;

            if ($minimum !== null && $maximum !== null) {
                $minOp = $exclusiveMin ? '>' : '>=';
                $maxOp = $exclusiveMax ? '<' : '<=';
                $constraints[] = "Range: {$minOp} {$minimum}, {$maxOp} {$maximum}";
            } elseif ($minimum !== null) {
                $op = $exclusiveMin ? '>' : '>=';
                $constraints[] = "Range: {$op} {$minimum}";
            } elseif ($maximum !== null) {
                $op = $exclusiveMax ? '<' : '<=';
                $constraints[] = "Range: {$op} {$maximum}";
            }

            // Multiple of
            $multipleOf = $param['multipleOf'] ?? null;
            if ($multipleOf !== null) {
                $constraints[] = "Multiple of: {$multipleOf}";
            }

            // Array constraints
            $minItems = $param['minItems'] ?? null;
            $maxItems = $param['maxItems'] ?? null;
            if ($minItems !== null && $maxItems !== null) {
                $constraints[] = "Items: {$minItems}-{$maxItems}";
            } elseif ($minItems !== null) {
                $constraints[] = "Min items: {$minItems}";
            } elseif ($maxItems !== null) {
                $constraints[] = "Max items: {$maxItems}";
            }

            $uniqueItems = $param['uniqueItems'] ?? false;
            if ($uniqueItems === true) {
                $constraints[] = "Unique items required";
            }

            return $constraints;
        }
    }

    // Helper function to render description with constraints
    if (!function_exists('renderDescription')) {
        function renderDescription(string $description, array $constraints, ?string $pattern, $engine, bool $showPlaceholder = true): string {
            // Show placeholder if description is empty
            if (trim($description) === '') {
                $html = $showPlaceholder ? '<em class="no-description">No description available</em>' : '';
            } else {
                $html = $engine->escape($description);
            }

            $hasConstraints = !empty($constraints) || $pattern !== null;

            if ($hasConstraints) {
                $html .= '<ul class="param-constraints">';

                // Add pattern first if present
                if ($pattern !== null) {
                    $escapedPattern = $engine->escape($pattern);
                    $html .= '<li class="pattern-constraint">' .
                        'Pattern: <code>' . $escapedPattern . '</code> ' .
                        '<button class="pattern-copy-btn" data-pattern="' . $escapedPattern . '" title="Copy pattern">&#128203;</button>' .
                        '</li>';
                }

                foreach ($constraints as $constraint) {
                    $html .= '<li>' . $engine->escape($constraint) . '</li>';
                }
                $html .= '</ul>';
            }

            return $html;
        }
    }

    // Helper function to render oneOf/anyOf options
    if (!function_exists('renderVariantOptions')) {
        function renderVariantOptions(array $options, $engine, string $prefix = '') {
            echo '<div class="variant-options">';
            foreach ($options as $index => $option) {
                $title = $option['title'] ?? null;
                $description = $option['description'] ?? '';
                $type = $option['type'] ?? 'mixed';
                $typeIsHtml = false;

                // Check for oneOf/anyOf in the option itself or its array items
                $hasOptionOneOf = isset($option['oneOf']);
                $hasOptionAnyOf = isset($option['anyOf']);
                $hasItemsOneOf = isset($option['items']['oneOf']);
                $hasItemsAnyOf = isset($option['items']['anyOf']);

                if ($hasOptionOneOf) {
                    $type = '<span class="type-variant" title="Exactly one of the listed options">one of</span>';
                    $typeIsHtml = true;
                } elseif ($hasOptionAnyOf) {
                    $type = '<span class="type-variant" title="One or more of the listed options">any of</span>';
                    $typeIsHtml = true;
                } elseif ($hasItemsOneOf) {
                    $type = '<span class="type-variant" title="Array where each item is exactly one of the listed options">[one of]</span>';
                    $typeIsHtml = true;
                } elseif ($hasItemsAnyOf) {
                    $type = '<span class="type-variant" title="Array where each item matches one or more of the listed options">[any of]</span>';
                    $typeIsHtml = true;
                } else {
                    // Get display type
                    if (is_array($type)) {
                        $type = implode('|', $type);
                    }
                    if ($type === 'array' && isset($option['items'])) {
                        $type = getArrayTypeDisplay($option['items']);
                    }
                }

                $hasVariants = $hasOptionOneOf || $hasOptionAnyOf || $hasItemsOneOf || $hasItemsAnyOf;
                $hasProperties = !$hasVariants && ($type === 'object' || $type === 'array') &&
                                 (isset($option['properties']) || isset($option['items']['properties']));

                echo '<div class="variant-option">';
                echo '<div class="variant-option-header">';
                if ($title) {
                    echo '<strong class="variant-title">' . $engine->escape($title) . '</strong>';
                    echo ' <span class="variant-type">(' . ($typeIsHtml ? $type : $engine->escape($type)) . ')</span>';
                } else {
                    echo '<strong class="variant-type">' . ($typeIsHtml ? $type : $engine->escape($type)) . '</strong>';
                }
                if ($description) {
                    echo '<p class="variant-description">' . $engine->escape($description) . '</p>';
                }
                echo '</div>';

                // Render oneOf/anyOf options recursively
                if ($hasOptionOneOf) {
                    echo '<div class="variant-properties">';
                    echo '<p class="variant-label"><em>Exactly one of:</em></p>';
                    renderVariantOptions($option['oneOf'], $engine, $prefix);
                    echo '</div>';
                } elseif ($hasOptionAnyOf) {
                    echo '<div class="variant-properties">';
                    echo '<p class="variant-label"><em>Any of:</em></p>';
                    renderVariantOptions($option['anyOf'], $engine, $prefix);
                    echo '</div>';
                } elseif ($hasItemsOneOf) {
                    echo '<div class="variant-properties">';
                    echo '<p class="variant-label"><em>Array items - exactly one of:</em></p>';
                    renderVariantOptions($option['items']['oneOf'], $engine, $prefix . '[]');
                    echo '</div>';
                } elseif ($hasItemsAnyOf) {
                    echo '<div class="variant-properties">';
                    echo '<p class="variant-label"><em>Array items - any of:</em></p>';
                    renderVariantOptions($option['items']['anyOf'], $engine, $prefix . '[]');
                    echo '</div>';
                }
                // Render properties if it's an object or array with properties
                elseif (isset($option['properties'])) {
                    echo '<div class="variant-properties">';
                    renderSchemaTable($option, $engine, $prefix, 0, false);
                    echo '</div>';
                } elseif (isset($option['items']['properties'])) {
                    echo '<div class="variant-properties">';
                    echo '<p class="nested-array-label"><em>Array items:</em></p>';
                    renderSchemaTable($option['items'], $engine, $prefix . '[]', 0, false);
                    echo '</div>';
                }

                echo '</div>';
            }
            echo '</div>';
        }
    }

    // Helper function to render nested schema table (used for both input params and response)
    if (!function_exists('renderSchemaTable')) {
        function renderSchemaTable($schema, $engine, $prefix = '', $depth = 0, $isRoot = true) {
            $properties = $schema['properties'] ?? [];
            $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

            if (empty($properties)) {
                // Maybe it's an array type with items
                if (($schema['type'] ?? '') === 'array' && isset($schema['items']['properties'])) {
                    echo '<p class="schema-note">Array of objects with the following properties:</p>';
                    renderSchemaTable($schema['items'], $engine, '', $depth, true);
                } else {
                    echo '<p class="schema-note">No schema properties available.</p>';
                }
                return;
            }

            echo '<table class="params-table schema-table">';
            echo '<thead><tr><th>Property</th><th>Type</th><th>Description</th></tr></thead>';
            echo '<tbody>';

            foreach ($properties as $name => $prop) {
                $fullName = $prefix ? $prefix . '.' . $name : $name;

                // Check for oneOf/anyOf (top level or in array items)
                $hasOneOf = isset($prop['oneOf']);
                $hasAnyOf = isset($prop['anyOf']);
                $hasItemsOneOf = isset($prop['items']['oneOf']);
                $hasItemsAnyOf = isset($prop['items']['anyOf']);
                $hasVariants = $hasOneOf || $hasAnyOf || $hasItemsOneOf || $hasItemsAnyOf;

                $type = $prop['type'] ?? 'mixed';
                $typeDisplay = $type;
                $typeIsHtml = false;
                $nullableIndicator = '';

                if ($hasOneOf) {
                    $typeDisplay = '<span class="type-variant" title="Exactly one of the listed options">one of</span>';
                    $typeIsHtml = true;
                } elseif ($hasAnyOf) {
                    $typeDisplay = '<span class="type-variant" title="One or more of the listed options">any of</span>';
                    $typeIsHtml = true;
                } elseif ($hasItemsOneOf) {
                    $typeDisplay = '<span class="type-variant" title="Array where each item is exactly one of the listed options">[one of]</span>';
                    $typeIsHtml = true;
                } elseif ($hasItemsAnyOf) {
                    $typeDisplay = '<span class="type-variant" title="Array where each item matches one or more of the listed options">[any of]</span>';
                    $typeIsHtml = true;
                } else {
                    // Check for nullable types and extract
                    $isNullable = false;
                    if (is_array($type)) {
                        if (in_array('null', $type)) {
                            $isNullable = true;
                            $type = array_filter($type, fn($t) => $t !== 'null');
                            $type = array_values($type);
                        }
                        $type = count($type) === 1 ? $type[0] : implode('|', $type);
                    } elseif (str_contains($type, '|null')) {
                        $isNullable = true;
                        $type = str_replace(['|null', 'null|'], '', $type);
                    }

                    // For arrays, show as [itemType] with nested array support
                    $typeDisplay = $type;
                    if ($type === 'array' && isset($prop['items'])) {
                        $typeDisplay = getArrayTypeDisplay($prop['items']);
                    }

                    // Add nullable indicator
                    $nullableIndicator = $isNullable ? ' <span class="nullable-icon" title="Nullable">?</span>' : '';
                }

                $isRequired = in_array($name, $required);
                $readonly = ($prop['readonly'] ?? false) ? ' <span class="readonly-icon" title="Read-only">🔒</span>' : '';

                // Check if this property has nested schema
                $hasNestedObject = !$hasVariants && ($type === 'object' && isset($prop['properties']));
                $hasNestedArray = !$hasVariants && ($type === 'array' && isset($prop['items']['properties']));
                $hasNested = $hasNestedObject || $hasNestedArray || $hasVariants;

                // Build description with constraints (same as input parameters)
                $constraints = buildConstraints($prop);
                $baseDescription = $prop['description'] ?? '';
                $pattern = $prop['pattern'] ?? null;
                $format = $prop['format'] ?? null;
                // Only show pattern in constraints if there's no format
                $showPattern = $pattern !== null && $format === null;

                // Render description with constraints
                $description = renderDescription($baseDescription, $constraints, $showPattern ? $pattern : null, $engine);

                // Add enum values to description if present (HTML added after escaping)
                if (isset($prop['enum']) && is_array($prop['enum'])) {
                    $enumValues = array_map(fn($v) => '<code>' . $engine->escape((string)$v) . '</code>', $prop['enum']);
                    $description .= '<div class="enum-values">Possible values: ' . implode(', ', $enumValues) . '</div>';
                }

                $nameDisplay = $engine->escape($name);

                // Add expand toggle for nested schemas
                $expandToggle = $hasNested ? '<button class="schema-expand-btn" aria-expanded="false" title="Expand properties">▶</button>' : '';

                echo '<tr' . ($hasNested ? ' class="has-nested-schema"' : '') . '>';
                echo '<td><span class="prop-name-wrap">' . $expandToggle . '<code>' . $nameDisplay . '</code></span>' . ($isRequired ? ' <span class="badge badge-required">required</span>' : '') . $readonly . '</td>';
                echo '<td>' . ($typeIsHtml ? $typeDisplay : $engine->escape($typeDisplay)) . $nullableIndicator . '</td>';
                echo '<td>' . $description . '</td>';
                echo '</tr>';

                // Render oneOf options (collapsed by default)
                if ($hasOneOf) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    echo '<p class="variant-label"><em>Exactly one of:</em></p>';
                    renderVariantOptions($prop['oneOf'], $engine, $fullName);
                    echo '</div></td></tr>';
                }
                // Render anyOf options (collapsed by default)
                elseif ($hasAnyOf) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    echo '<p class="variant-label"><em>Any of:</em></p>';
                    renderVariantOptions($prop['anyOf'], $engine, $fullName);
                    echo '</div></td></tr>';
                }
                // Render array items oneOf options (collapsed by default)
                elseif ($hasItemsOneOf) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    echo '<p class="variant-label"><em>Array items - exactly one of:</em></p>';
                    renderVariantOptions($prop['items']['oneOf'], $engine, $fullName . '[]');
                    echo '</div></td></tr>';
                }
                // Render array items anyOf options (collapsed by default)
                elseif ($hasItemsAnyOf) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    echo '<p class="variant-label"><em>Array items - any of:</em></p>';
                    renderVariantOptions($prop['items']['anyOf'], $engine, $fullName . '[]');
                    echo '</div></td></tr>';
                }
                // Render nested object properties (collapsed by default)
                elseif ($hasNestedObject) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    renderSchemaTable($prop, $engine, $fullName, $depth + 1, false);
                    echo '</div></td></tr>';
                }
                // Render array item properties (collapsed by default)
                elseif ($hasNestedArray) {
                    echo '<tr class="nested-schema-row collapsed">';
                    echo '<td colspan="3" class="nested-schema-cell">';
                    echo '<div class="nested-schema-content">';
                    echo '<p class="nested-array-label"><em>Array items:</em></p>';
                    renderSchemaTable($prop['items'], $engine, $fullName . '[]', $depth + 1, false);
                    echo '</div></td></tr>';
                }
            }

            echo '</tbody></table>';
        }
    }
    ?>

    <?php if (count($pathParams) > 0): ?>
    <section id="route-parameters" class="endpoint-parameters collapsible-section expanded">
        <h2 class="section-toggle">
            <span class="toggle-icon">&#9660;</span>
            Route Parameters
        </h2>
        <div class="section-content">
            <div class="response-tabs">
                <button class="tab-btn active" data-tab="route-params-table">Table</button>
                <button class="tab-btn" data-tab="route-params-json">JSON</button>
            </div>
            <div class="tab-content active" id="route-params-table">
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pathParams as $name): ?>
                        <?php
                            $paramSchema = $routeParamsSchema[$name] ?? null;
                            $patternInfo = $routeParamPatterns[$name] ?? null;

                            // Build type display with regex copy button
                            $inferredType = $patternInfo['type'] ?? 'string';
                            $regexPattern = $patternInfo['pattern'] ?? '';
                            $isExotic = $patternInfo['isExotic'] ?? false;

                            // If we have schema info, prefer schema type over inferred
                            // For exotic patterns, always use "regex" as the type
                            if ($isExotic) {
                                $typeDisplay = 'regex';
                            } elseif ($paramSchema) {
                                $typeInfo = formatParamType($paramSchema, $engine);
                                $typeDisplay = $typeInfo['typeHtml'];
                            } else {
                                $typeDisplay = $engine->escape($inferredType);
                            }

                            // Always add regex copy button
                            $escapedRegex = $engine->escape($regexPattern);
                            $typeHtml = $typeDisplay . ' <button class="regex-copy-btn" data-pattern="' . $escapedRegex . '" title="Copy regex">&#128203;</button>';

                            // For exotic patterns, also show inline pattern
                            if ($isExotic && $regexPattern) {
                                $typeHtml .= '<div class="exotic-pattern"><code>' . $escapedRegex . '</code></div>';
                            }

                            // Build description
                            if ($paramSchema) {
                                $constraints = buildConstraints($paramSchema);
                                $description = $paramSchema['description'] ?? '';
                                $pattern = $paramSchema['pattern'] ?? null;
                                $showPattern = $pattern !== null && ($paramSchema['format'] ?? null) === null;
                                $descriptionHtml = renderDescription($description, $constraints, $showPattern ? $pattern : null, $engine);
                            } else {
                                $descriptionHtml = '<em>No schema information available</em>';
                            }
                        ?>
                        <tr>
                            <td><code><?php $engine->e($name); ?></code> <span class="required-icon" title="Required">*</span></td>
                            <td><?php echo $typeHtml; ?></td>
                            <td><?php echo $descriptionHtml; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tab-content" id="route-params-json">
                <div class="code-block">
                    <button class="copy-btn" data-target="route-params-json-code" title="Copy to clipboard">Copy</button>
                    <pre id="route-params-json-code"><code class="language-json"><?php $engine->e(json_encode($routeParamsSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (count($parameters) > 0): ?>
    <?php
        // Determine header based on HTTP verbs
        $isGetOnly = count($descriptor->verbs) === 1 && strtoupper($descriptor->verbs[0]) === 'GET';
        $queryParamsHeader = $isGetOnly ? 'Query String Parameters' : 'Query String / Body Parameters';
    ?>
    <section id="query-parameters" class="endpoint-parameters collapsible-section expanded">
        <h2 class="section-toggle">
            <span class="toggle-icon">&#9660;</span>
            <?php $engine->e($queryParamsHeader); ?>
        </h2>
        <div class="section-content">
            <div class="response-tabs">
                <button class="tab-btn active" data-tab="query-params-table">Table</button>
                <button class="tab-btn" data-tab="query-params-json">JSON</button>
            </div>
            <div class="tab-content active" id="query-params-table">
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parameters as $name => $param): ?>
                        <?php
                            $isRequired = $param['required'] ?? false;
                            $typeInfo = formatParamType($param, $engine);
                            $constraints = buildConstraints($param);
                            $description = $param['description'] ?? '';
                            $pattern = $param['pattern'] ?? null;
                            // Only show pattern in constraints if there's no format
                            $showPattern = $pattern !== null && ($param['format'] ?? null) === null;
                            $descriptionHtml = renderDescription($description, $constraints, $showPattern ? $pattern : null, $engine);

                            // Add enum values to description if present
                            if (isset($param['enum']) && is_array($param['enum'])) {
                                $enumValues = array_map(fn($v) => '<code>' . $engine->escape((string)$v) . '</code>', $param['enum']);
                                $descriptionHtml .= '<div class="enum-values">Possible values: ' . implode(', ', $enumValues) . '</div>';
                            }

                            // Check for oneOf/anyOf (top level or in array items)
                            $hasOneOf = isset($param['oneOf']);
                            $hasAnyOf = isset($param['anyOf']);
                            $hasItemsOneOf = isset($param['items']['oneOf']);
                            $hasItemsAnyOf = isset($param['items']['anyOf']);
                            $hasVariants = $hasOneOf || $hasAnyOf || $hasItemsOneOf || $hasItemsAnyOf;

                            // Check for nested schema (object with properties or array with item properties)
                            $paramType = $param['type'] ?? 'string';
                            if (is_array($paramType)) {
                                $paramType = in_array('object', $paramType) ? 'object' : (in_array('array', $paramType) ? 'array' : $paramType[0]);
                            }
                            $hasNestedObject = ($paramType === 'object' && isset($param['properties']));
                            $hasNestedArray = ($paramType === 'array' && isset($param['items']['properties']));
                            $hasNested = $hasNestedObject || $hasNestedArray || $hasVariants;
                            $expandToggle = $hasNested ? '<button class="schema-expand-btn" aria-expanded="false" title="Expand properties">▶</button>' : '';
                        ?>
                        <tr<?php echo $hasNested ? ' class="has-nested-schema"' : ''; ?>>
                            <td><span class="prop-name-wrap"><?php echo $expandToggle; ?><code><?php $engine->e($name); ?></code></span><?php if ($isRequired): ?> <span class="required-icon" title="Required">*</span><?php endif; ?></td>
                            <td><?php echo $typeInfo['typeHtml']; ?></td>
                            <td><?php echo $descriptionHtml; ?></td>
                        </tr>
                        <?php if ($hasOneOf): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <p class="variant-label"><em>Exactly one of:</em></p>
                                    <?php renderVariantOptions($param['oneOf'], $engine, $name); ?>
                                </div>
                            </td>
                        </tr>
                        <?php elseif ($hasAnyOf): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <p class="variant-label"><em>Any of:</em></p>
                                    <?php renderVariantOptions($param['anyOf'], $engine, $name); ?>
                                </div>
                            </td>
                        </tr>
                        <?php elseif ($hasItemsOneOf): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <p class="variant-label"><em>Array items - exactly one of:</em></p>
                                    <?php renderVariantOptions($param['items']['oneOf'], $engine, $name . '[]'); ?>
                                </div>
                            </td>
                        </tr>
                        <?php elseif ($hasItemsAnyOf): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <p class="variant-label"><em>Array items - any of:</em></p>
                                    <?php renderVariantOptions($param['items']['anyOf'], $engine, $name . '[]'); ?>
                                </div>
                            </td>
                        </tr>
                        <?php elseif ($hasNestedObject): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <?php renderSchemaTable($param, $engine, $name, 0, false); ?>
                                </div>
                            </td>
                        </tr>
                        <?php elseif ($hasNestedArray): ?>
                        <tr class="nested-schema-row collapsed">
                            <td colspan="3" class="nested-schema-cell">
                                <div class="nested-schema-content">
                                    <p class="nested-array-label"><em>Array items:</em></p>
                                    <?php renderSchemaTable($param['items'], $engine, $name . '[]', 0, false); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tab-content" id="query-params-json">
                <div class="code-block">
                    <button class="copy-btn" data-target="query-params-json-code" title="Copy to clipboard">Copy</button>
                    <pre id="query-params-json-code"><code class="language-json"><?php $engine->e(json_encode($parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="response" class="endpoint-response collapsible-section expanded">
        <h2 class="section-toggle">
            <span class="toggle-icon">&#9660;</span>
            Response
        </h2>
        <div class="section-content">
            <?php if ($responseSchema !== null): ?>
            <div class="response-tabs">
                <button class="tab-btn active" data-tab="response-table">Table</button>
                <button class="tab-btn" data-tab="response-json">JSON</button>
            </div>
            <div class="tab-content active" id="response-table">
            <?php
            if (!function_exists('renderSchemaTable')) {
                function renderSchemaTable($schema, $engine, $prefix = '', $depth = 0, $isRoot = true) {
                    $properties = $schema['properties'] ?? [];
                    $required = $schema['required'] ?? [];

                    if (empty($properties)) {
                        // Maybe it's an array type with items
                        if (($schema['type'] ?? '') === 'array' && isset($schema['items']['properties'])) {
                            echo '<p class="schema-note">Array of objects with the following properties:</p>';
                            renderSchemaTable($schema['items'], $engine, '', $depth, true);
                        } else {
                            echo '<p class="schema-note">No schema properties available.</p>';
                        }
                        return;
                    }

                    echo '<table class="params-table schema-table">';
                    echo '<thead><tr><th>Property</th><th>Type</th><th>Description</th></tr></thead>';
                    echo '<tbody>';

                    foreach ($properties as $name => $prop) {
                        $fullName = $prefix ? $prefix . '.' . $name : $name;
                        $type = $prop['type'] ?? 'mixed';

                        // Check for nullable types and extract
                        $isNullable = false;
                        if (is_array($type)) {
                            if (in_array('null', $type)) {
                                $isNullable = true;
                                $type = array_filter($type, fn($t) => $t !== 'null');
                                $type = array_values($type);
                            }
                            $type = count($type) === 1 ? $type[0] : implode('|', $type);
                        } elseif (str_contains($type, '|null')) {
                            $isNullable = true;
                            $type = str_replace(['|null', 'null|'], '', $type);
                        }

                        // For arrays, show as [itemType] with nested array support
                        $typeDisplay = $type;
                        if ($type === 'array' && isset($prop['items'])) {
                            $typeDisplay = getArrayTypeDisplay($prop['items']);
                        }

                        // Add nullable indicator
                        $nullableIndicator = $isNullable ? ' <span class="nullable-icon" title="Nullable">?</span>' : '';

                        $isRequired = in_array($name, $required);
                        $readonly = ($prop['readonly'] ?? false) ? ' <span class="readonly-icon" title="Read-only">🔒</span>' : '';

                        // Check if this property has nested schema
                        $hasNestedObject = ($type === 'object' && isset($prop['properties']));
                        $hasNestedArray = ($type === 'array' && isset($prop['items']['properties']));
                        $hasNested = $hasNestedObject || $hasNestedArray;

                        // Build description with constraints
                        $constraints = buildConstraints($prop);
                        $baseDescription = $prop['description'] ?? '';
                        $pattern = $prop['pattern'] ?? null;
                        $format = $prop['format'] ?? null;
                        // Only show pattern in constraints if there's no format
                        $showPattern = $pattern !== null && $format === null;

                        // Render description with constraints
                        $description = renderDescription($baseDescription, $constraints, $showPattern ? $pattern : null, $engine);

                        // Add enum values to description if present (HTML added after escaping)
                        if (isset($prop['enum']) && is_array($prop['enum'])) {
                            $enumValues = array_map(fn($v) => '<code>' . $engine->escape((string)$v) . '</code>', $prop['enum']);
                            $description .= '<div class="enum-values">Possible values: ' . implode(', ', $enumValues) . '</div>';
                        }

                        $nameDisplay = $engine->escape($name);

                        // Add expand toggle for nested schemas
                        $expandToggle = $hasNested ? '<button class="schema-expand-btn" aria-expanded="false" title="Expand properties">▶</button>' : '';

                        echo '<tr' . ($hasNested ? ' class="has-nested-schema"' : '') . '>';
                        echo '<td><span class="prop-name-wrap">' . $expandToggle . '<code>' . $nameDisplay . '</code></span>' . ($isRequired ? ' <span class="badge badge-required">required</span>' : '') . $readonly . '</td>';
                        echo '<td>' . $engine->escape($typeDisplay) . $nullableIndicator . '</td>';
                        echo '<td>' . $description . '</td>';
                        echo '</tr>';

                        // Render nested object properties (collapsed by default)
                        if ($hasNestedObject) {
                            echo '<tr class="nested-schema-row collapsed">';
                            echo '<td colspan="3" class="nested-schema-cell">';
                            echo '<div class="nested-schema-content">';
                            renderSchemaTable($prop, $engine, $fullName, $depth + 1, false);
                            echo '</div></td></tr>';
                        }
                        // Render array item properties (collapsed by default)
                        if ($hasNestedArray) {
                            echo '<tr class="nested-schema-row collapsed">';
                            echo '<td colspan="3" class="nested-schema-cell">';
                            echo '<div class="nested-schema-content">';
                            echo '<p class="nested-array-label"><em>Array items:</em></p>';
                            renderSchemaTable($prop['items'], $engine, $fullName . '[]', $depth + 1, false);
                            echo '</div></td></tr>';
                        }
                    }

                    echo '</tbody></table>';
                }
            }
            renderSchemaTable($responseSchema, $engine);
            ?>
        </div>
            <div class="tab-content" id="response-json">
                <div class="code-block">
                    <button class="copy-btn" data-target="response-json-code" title="Copy to clipboard">Copy</button>
                    <pre id="response-json-code"><code class="language-json"><?php $engine->e(json_encode($responseSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
                </div>
            </div>
            <?php else: ?>
            <p class="schema-note">No response schema available for this endpoint.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php $hasResponseExample = !empty($examples['response']); ?>
    <section id="examples" class="endpoint-examples collapsible-section expanded">
        <h2 class="section-toggle">
            <span class="toggle-icon">&#9660;</span>
            Examples
        </h2>
        <div class="section-content">
            <div class="examples-tabs">
                <button class="tab-btn active" data-tab="example-curl">curl</button>
                <button class="tab-btn" data-tab="example-nodejs">Node.js</button>
                <button class="tab-btn" data-tab="example-php">PHP</button>
                <button class="tab-btn" data-tab="example-python">Python</button>
                <button class="tab-btn" data-tab="example-ruby">Ruby</button>
                <?php if ($hasResponseExample): ?>
                <button class="tab-btn" data-tab="example-response">Response</button>
                <?php endif; ?>
            </div>
            <div class="tab-content active" id="example-curl">
                <div class="code-block">
                    <button class="copy-btn" data-target="curl-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="curl-example-code"><code class="language-bash"><?php $engine->e($examples['curl']); ?></code></pre>
                </div>
            </div>
            <div class="tab-content" id="example-nodejs">
                <div class="code-block">
                    <button class="copy-btn" data-target="nodejs-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="nodejs-example-code"><code class="language-javascript"><?php $engine->e($examples['nodejs']); ?></code></pre>
                </div>
            </div>
            <div class="tab-content" id="example-php">
                <div class="code-block">
                    <button class="copy-btn" data-target="php-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="php-example-code"><code class="language-php"><?php $engine->e($examples['php']); ?></code></pre>
                </div>
            </div>
            <div class="tab-content" id="example-python">
                <div class="code-block">
                    <button class="copy-btn" data-target="python-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="python-example-code"><code class="language-python"><?php $engine->e($examples['python']); ?></code></pre>
                </div>
            </div>
            <div class="tab-content" id="example-ruby">
                <div class="code-block">
                    <button class="copy-btn" data-target="ruby-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="ruby-example-code"><code class="language-ruby"><?php $engine->e($examples['ruby']); ?></code></pre>
                </div>
            </div>
            <?php if ($hasResponseExample): ?>
            <div class="tab-content" id="example-response">
                <div class="code-block">
                    <button class="copy-btn" data-target="response-example-code" title="Copy to clipboard">Copy</button>
                    <pre id="response-example-code"><code class="language-json"><?php $engine->e($examples['response']); ?></code></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</article>
