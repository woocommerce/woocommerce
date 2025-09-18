<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * `Color's can be input in the form #rrggbb, #rgb or schema(val1, val2, …) but are always stored as an array of
 * ('s' => val1, 'c' => val2, 'h' => val3, …) and output in the second form.
 */
class Color extends CSSFunction
{
    /**
     * @param array<non-empty-string, Value|string> $colorValues
     * @param int<1, max>|null $lineNumber
     */
    public function __construct(array $colorValues, ?int $lineNumber = null)
    {
        parent::__construct(\implode('', \array_keys($colorValues)), $colorValues, ',', $lineNumber);
    }

    /**
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $parserState, bool $ignoreCase = false): CSSFunction
    {
        return $parserState->comes('#')
            ? self::parseHexColor($parserState)
            : self::parseColorFunction($parserState);
    }

    /**
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseHexColor(ParserState $parserState): Color
    {
        $parserState->consume('#');
        $hexValue = $parserState->parseIdentifier(false);
        if ($parserState->strlen($hexValue) === 3) {
            $hexValue = $hexValue[0] . $hexValue[0] . $hexValue[1] . $hexValue[1] . $hexValue[2] . $hexValue[2];
        } elseif ($parserState->strlen($hexValue) === 4) {
            $hexValue = $hexValue[0] . $hexValue[0] . $hexValue[1] . $hexValue[1] . $hexValue[2] . $hexValue[2]
                . $hexValue[3] . $hexValue[3];
        }

        if ($parserState->strlen($hexValue) === 8) {
            $colorValues = [
                'r' => new Size(\intval($hexValue[0] . $hexValue[1], 16), null, true, $parserState->currentLine()),
                'g' => new Size(\intval($hexValue[2] . $hexValue[3], 16), null, true, $parserState->currentLine()),
                'b' => new Size(\intval($hexValue[4] . $hexValue[5], 16), null, true, $parserState->currentLine()),
                'a' => new Size(
                    \round(self::mapRange(\intval($hexValue[6] . $hexValue[7], 16), 0, 255, 0, 1), 2),
                    null,
                    true,
                    $parserState->currentLine()
                ),
            ];
        } elseif ($parserState->strlen($hexValue) === 6) {
            $colorValues = [
                'r' => new Size(\intval($hexValue[0] . $hexValue[1], 16), null, true, $parserState->currentLine()),
                'g' => new Size(\intval($hexValue[2] . $hexValue[3], 16), null, true, $parserState->currentLine()),
                'b' => new Size(\intval($hexValue[4] . $hexValue[5], 16), null, true, $parserState->currentLine()),
            ];
        } else {
            throw new UnexpectedTokenException(
                'Invalid hex color value',
                $hexValue,
                'custom',
                $parserState->currentLine()
            );
        }

        return new Color($colorValues, $parserState->currentLine());
    }

    /**
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     */
    private static function parseColorFunction(ParserState $parserState): CSSFunction
    {
        $colorValues = [];

        $colorMode = $parserState->parseIdentifier(true);
        $parserState->consumeWhiteSpace();
        $parserState->consume('(');

        // CSS Color Module Level 4 says that `rgb` and `rgba` are now aliases; likewise `hsl` and `hsla`.
        // So, attempt to parse with the `a`, and allow for it not being there.
        switch ($colorMode) {
            case 'rgb':
                $colorModeForParsing = 'rgba';
                $mayHaveOptionalAlpha = true;
                break;
            case 'hsl':
                $colorModeForParsing = 'hsla';
                $mayHaveOptionalAlpha = true;
                break;
            case 'rgba':
                // This is handled identically to the following case.
            case 'hsla':
                $colorModeForParsing = $colorMode;
                $mayHaveOptionalAlpha = true;
                break;
            default:
                $colorModeForParsing = $colorMode;
                $mayHaveOptionalAlpha = false;
        }

        $containsVar = false;
        $containsNone = false;
        $isLegacySyntax = false;
        $expectedArgumentCount = $parserState->strlen($colorModeForParsing);
        for ($argumentIndex = 0; $argumentIndex < $expectedArgumentCount; ++$argumentIndex) {
            $parserState->consumeWhiteSpace();
            $valueKey = $colorModeForParsing[$argumentIndex];
            if ($parserState->comes('var')) {
                $colorValues[$valueKey] = CSSFunction::parseIdentifierOrFunction($parserState);
                $containsVar = true;
            } elseif (!$isLegacySyntax && $parserState->comes('none')) {
                $colorValues[$valueKey] = $parserState->parseIdentifier();
                $containsNone = true;
            } else {
                $colorValues[$valueKey] = Size::parse($parserState, true);
            }

            // This must be done first, to consume comments as well, so that the `comes` test will work.
            $parserState->consumeWhiteSpace();

            // With a `var` argument, the function can have fewer arguments.
            // And as of CSS Color Module Level 4, the alpha argument is optional.
            $canCloseNow =
                $containsVar
                || ($mayHaveOptionalAlpha && $argumentIndex >= $expectedArgumentCount - 2);
            if ($canCloseNow && $parserState->comes(')')) {
                break;
            }

            // "Legacy" syntax is comma-delimited, and does not allow the `none` keyword.
            // "Modern" syntax is space-delimited, with `/` as alpha delimiter.
            // They cannot be mixed.
            if ($argumentIndex === 0 && !$containsNone) {
                // An immediate closing parenthesis is not valid.
                if ($parserState->comes(')')) {
                    throw new UnexpectedTokenException(
                        'Color function with no arguments',
                        '',
                        'custom',
                        $parserState->currentLine()
                    );
                }
                $isLegacySyntax = $parserState->comes(',');
            }

            if ($isLegacySyntax && $argumentIndex < ($expectedArgumentCount - 1)) {
                $parserState->consume(',');
            }

            // In the "modern" syntax, the alpha value must be delimited with `/`.
            if (!$isLegacySyntax) {
                if ($containsVar) {
                    // If the `var` substitution encompasses more than one argument,
                    // the alpha deliminator may come at any time.
                    if ($parserState->comes('/')) {
                        $parserState->consume('/');
                    }
                } elseif (($colorModeForParsing[$argumentIndex + 1] ?? '') === 'a') {
                    // Alpha value is the next expected argument.
                    // Since a closing parenthesis was not found, a `/` separator is now required.
                    $parserState->consume('/');
                }
            }
        }
        $parserState->consume(')');

        return $containsVar
            ? new CSSFunction($colorMode, \array_values($colorValues), ',', $parserState->currentLine())
            : new Color($colorValues, $parserState->currentLine());
    }

    private static function mapRange(float $value, float $fromMin, float $fromMax, float $toMin, float $toMax): float
    {
        $fromRange = $fromMax - $fromMin;
        $toRange = $toMax - $toMin;
        $multiplier = $toRange / $fromRange;
        $newValue = $value - $fromMin;
        $newValue *= $multiplier;

        return $newValue + $toMin;
    }

    /**
     * @return array<non-empty-string, Value|string>
     */
    public function getColor(): array
    {
        return $this->components;
    }

    /**
     * @param array<non-empty-string, Value|string> $colorValues
     */
    public function setColor(array $colorValues): void
    {
        $this->setName(\implode('', \array_keys($colorValues)));
        $this->components = $colorValues;
    }

    /**
     * @return non-empty-string
     */
    public function getColorDescription(): string
    {
        return $this->getName();
    }

    /**
     * @return non-empty-string
     */
    public function render(OutputFormat $outputFormat): string
    {
        if ($this->shouldRenderAsHex($outputFormat)) {
            return $this->renderAsHex();
        }

        if ($this->shouldRenderInModernSyntax()) {
            return $this->renderInModernSyntax($outputFormat);
        }

        return parent::render($outputFormat);
    }

    private function shouldRenderAsHex(OutputFormat $outputFormat): bool
    {
        return
            $outputFormat->usesRgbHashNotation()
            && $this->getRealName() === 'rgb'
            && $this->allComponentsAreNumbers();
    }

    /**
     * The function name is a concatenation of the array keys of the components, which is passed to the constructor.
     * However, this can be changed by calling {@see CSSFunction::setName},
     * so is not reliable in situations where it's necessary to determine the function name based on the components.
     */
    private function getRealName(): string
    {
        return \implode('', \array_keys($this->components));
    }

    /**
     * Test whether all color components are absolute numbers (CSS type `number`), not percentages or anything else.
     * If any component is not an instance of `Size`, the method will also return `false`.
     */
    private function allComponentsAreNumbers(): bool
    {
        foreach ($this->components as $component) {
            if (!($component instanceof Size) || $component->getUnit() !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Note that this method assumes the following:
     * - The `components` array has keys for `r`, `g` and `b`;
     * - The values in the array are all instances of `Size`.
     *
     * Errors will be triggered or thrown if this is not the case.
     *
     * @return non-empty-string
     */
    private function renderAsHex(): string
    {
        $result = \sprintf(
            '%02x%02x%02x',
            $this->components['r']->getSize(),
            $this->components['g']->getSize(),
            $this->components['b']->getSize()
        );
        $canUseShortVariant = ($result[0] === $result[1]) && ($result[2] === $result[3]) && ($result[4] === $result[5]);

        return '#' . ($canUseShortVariant ? $result[0] . $result[2] . $result[4] : $result);
    }

    /**
     * The "legacy" syntax does not allow RGB colors to have a mixture of `percentage`s and `number`s,
     * and does not allow `none` as any component value.
     *
     * The "legacy" and "modern" monikers are part of the formal W3C syntax.
     * See the following for more information:
     * - {@link
     *     https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/rgb#formal_syntax
     *     Description of the formal syntax for `rgb()` on MDN
     *   };
     * - {@link
     *     https://www.w3.org/TR/css-color-4/#rgb-functions
     *     The same in the CSS Color Module Level 4 W3C Candidate Recommendation Draft
     *   } (as of 13 February 2024, at time of writing).
     */
    private function shouldRenderInModernSyntax(): bool
    {
        if ($this->hasNoneAsComponentValue()) {
            return true;
        }

        if (!$this->colorFunctionMayHaveMixedValueTypes($this->getRealName())) {
            return false;
        }

        $hasPercentage = false;
        $hasNumber = false;
        foreach ($this->components as $key => $value) {
            if ($key === 'a') {
                // Alpha can have units that don't match those of the RGB components in the "legacy" syntax.
                // So it is not necessary to check it.  It's also always last, hence `break` rather than `continue`.
                break;
            }
            if (!($value instanceof Size)) {
                // Unexpected, unknown, or modified via the API
                return false;
            }
            $unit = $value->getUnit();
            // `switch` only does loose comparison
            if ($unit === null) {
                $hasNumber = true;
            } elseif ($unit === '%') {
                $hasPercentage = true;
            } else {
                // Invalid unit
                return false;
            }
        }

        return $hasPercentage && $hasNumber;
    }

    private function hasNoneAsComponentValue(): bool
    {
        return \in_array('none', $this->components, true);
    }

    /**
     * Some color functions, such as `rgb`,
     * may have a mixture of `percentage`, `number`, or possibly other types in their arguments.
     *
     * Note that this excludes the alpha component, which is treated separately.
     */
    private function colorFunctionMayHaveMixedValueTypes(string $function): bool
    {
        $functionsThatMayHaveMixedValueTypes = ['rgb', 'rgba'];

        return \in_array($function, $functionsThatMayHaveMixedValueTypes, true);
    }

    /**
     * @return non-empty-string
     */
    private function renderInModernSyntax(OutputFormat $outputFormat): string
    {
        // Maybe not yet without alpha, but will be...
        $componentsWithoutAlpha = $this->components;
        \end($componentsWithoutAlpha);
        if (\key($componentsWithoutAlpha) === 'a') {
            $alpha = $this->components['a'];
            unset($componentsWithoutAlpha['a']);
        }

        $formatter = $outputFormat->getFormatter();
        $arguments = $formatter->implode(' ', $componentsWithoutAlpha);
        if (isset($alpha)) {
            $separator = $formatter->spaceBeforeListArgumentSeparator('/')
                . '/' . $formatter->spaceAfterListArgumentSeparator('/');
            $arguments = $formatter->implode($separator, [$arguments, $alpha]);
        }

        return $this->getName() . '(' . $arguments . ')';
    }
}
