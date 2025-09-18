<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS;

final class OutputFormat
{
    /**
     * Value format: `"` means double-quote, `'` means single-quote
     *
     * @var non-empty-string
     */
    private $stringQuotingType = '"';

    /**
     * Output RGB colors in hash notation if possible
     *
     * @var bool
     */
    private $usesRgbHashNotation = true;

    /**
     * Declaration format
     *
     * Semicolon after the last rule of a declaration block can be omitted. To do that, set this false.
     *
     * @var bool
     */
    private $renderSemicolonAfterLastRule = true;

    /**
     * Spacing
     * Note that these strings are not sanity-checked: the value should only consist of whitespace
     * Any newline character will be indented according to the current level.
     * The triples (After, Before, Between) can be set using a wildcard
     * (e.g. `$outputFormat->set('Space*Rules', "\n");`)
     *
     * @var string
     */
    private $spaceAfterRuleName = ' ';

    /**
     * @var string
     */
    private $spaceBeforeRules = '';

    /**
     * @var string
     */
    private $spaceAfterRules = '';

    /**
     * @var string
     */
    private $spaceBetweenRules = '';

    /**
     * @var string
     */
    private $spaceBeforeBlocks = '';

    /**
     * @var string
     */
    private $spaceAfterBlocks = '';

    /**
     * @var string
     */
    private $spaceBetweenBlocks = "\n";

    /**
     * Content injected in and around at-rule blocks.
     *
     * @var string
     */
    private $contentBeforeAtRuleBlock = '';

    /**
     * @var string
     */
    private $contentAfterAtRuleBlock = '';

    /**
     * This is what’s printed before and after the comma if a declaration block contains multiple selectors.
     *
     * @var string
     */
    private $spaceBeforeSelectorSeparator = '';

    /**
     * @var string
     */
    private $spaceAfterSelectorSeparator = ' ';

    /**
     * This is what’s inserted before the separator in value lists, by default.
     *
     * @var string
     */
    private $spaceBeforeListArgumentSeparator = '';

    /**
     * Keys are separators (e.g. `,`).  Values are the space sequence to insert, or an empty string.
     *
     * @var array<non-empty-string, string>
     */
    private $spaceBeforeListArgumentSeparators = [];

    /**
     * This is what’s inserted after the separator in value lists, by default.
     *
     * @var string
     */
    private $spaceAfterListArgumentSeparator = '';

    /**
     * Keys are separators (e.g. `,`).  Values are the space sequence to insert, or an empty string.
     *
     * @var array<non-empty-string, string>
     */
    private $spaceAfterListArgumentSeparators = [];

    /**
     * @var string
     */
    private $spaceBeforeOpeningBrace = ' ';

    /**
     * Content injected in and around declaration blocks.
     *
     * @var string
     */
    private $contentBeforeDeclarationBlock = '';

    /**
     * @var string
     */
    private $contentAfterDeclarationBlockSelectors = '';

    /**
     * @var string
     */
    private $contentAfterDeclarationBlock = '';

    /**
     * Indentation character(s) per level. Only applicable if newlines are used in any of the spacing settings.
     *
     * @var string
     */
    private $indentation = "\t";

    /**
     * Output exceptions.
     *
     * @var bool
     */
    private $shouldIgnoreExceptions = false;

    /**
     * Render comments for lists and RuleSets
     *
     * @var bool
     */
    private $shouldRenderComments = false;

    /**
     * @var OutputFormatter|null
     */
    private $outputFormatter;

    /**
     * @var OutputFormat|null
     */
    private $nextLevelFormat;

    /**
     * @var int<0, max>
     */
    private $indentationLevel = 0;

    /**
     * @return non-empty-string
     *
     * @internal
     */
    public function getStringQuotingType(): string
    {
        return $this->stringQuotingType;
    }

    /**
     * @param non-empty-string $quotingType
     *
     * @return $this fluent interface
     */
    public function setStringQuotingType(string $quotingType): self
    {
        $this->stringQuotingType = $quotingType;

        return $this;
    }

    /**
     * @internal
     */
    public function usesRgbHashNotation(): bool
    {
        return $this->usesRgbHashNotation;
    }

    /**
     * @return $this fluent interface
     */
    public function setRGBHashNotation(bool $usesRgbHashNotation): self
    {
        $this->usesRgbHashNotation = $usesRgbHashNotation;

        return $this;
    }

    /**
     * @internal
     */
    public function shouldRenderSemicolonAfterLastRule(): bool
    {
        return $this->renderSemicolonAfterLastRule;
    }

    /**
     * @return $this fluent interface
     */
    public function setSemicolonAfterLastRule(bool $renderSemicolonAfterLastRule): self
    {
        $this->renderSemicolonAfterLastRule = $renderSemicolonAfterLastRule;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceAfterRuleName(): string
    {
        return $this->spaceAfterRuleName;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceAfterRuleName(string $whitespace): self
    {
        $this->spaceAfterRuleName = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBeforeRules(): string
    {
        return $this->spaceBeforeRules;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBeforeRules(string $whitespace): self
    {
        $this->spaceBeforeRules = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceAfterRules(): string
    {
        return $this->spaceAfterRules;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceAfterRules(string $whitespace): self
    {
        $this->spaceAfterRules = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBetweenRules(): string
    {
        return $this->spaceBetweenRules;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBetweenRules(string $whitespace): self
    {
        $this->spaceBetweenRules = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBeforeBlocks(): string
    {
        return $this->spaceBeforeBlocks;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBeforeBlocks(string $whitespace): self
    {
        $this->spaceBeforeBlocks = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceAfterBlocks(): string
    {
        return $this->spaceAfterBlocks;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceAfterBlocks(string $whitespace): self
    {
        $this->spaceAfterBlocks = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBetweenBlocks(): string
    {
        return $this->spaceBetweenBlocks;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBetweenBlocks(string $whitespace): self
    {
        $this->spaceBetweenBlocks = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getContentBeforeAtRuleBlock(): string
    {
        return $this->contentBeforeAtRuleBlock;
    }

    /**
     * @return $this fluent interface
     */
    public function setBeforeAtRuleBlock(string $content): self
    {
        $this->contentBeforeAtRuleBlock = $content;

        return $this;
    }

    /**
     * @internal
     */
    public function getContentAfterAtRuleBlock(): string
    {
        return $this->contentAfterAtRuleBlock;
    }

    /**
     * @return $this fluent interface
     */
    public function setAfterAtRuleBlock(string $content): self
    {
        $this->contentAfterAtRuleBlock = $content;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBeforeSelectorSeparator(): string
    {
        return $this->spaceBeforeSelectorSeparator;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBeforeSelectorSeparator(string $whitespace): self
    {
        $this->spaceBeforeSelectorSeparator = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceAfterSelectorSeparator(): string
    {
        return $this->spaceAfterSelectorSeparator;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceAfterSelectorSeparator(string $whitespace): self
    {
        $this->spaceAfterSelectorSeparator = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBeforeListArgumentSeparator(): string
    {
        return $this->spaceBeforeListArgumentSeparator;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBeforeListArgumentSeparator(string $whitespace): self
    {
        $this->spaceBeforeListArgumentSeparator = $whitespace;

        return $this;
    }

    /**
     * @return array<non-empty-string, string>
     *
     * @internal
     */
    public function getSpaceBeforeListArgumentSeparators(): array
    {
        return $this->spaceBeforeListArgumentSeparators;
    }

    /**
     * @param array<non-empty-string, string> $separatorSpaces
     *
     * @return $this fluent interface
     */
    public function setSpaceBeforeListArgumentSeparators(array $separatorSpaces): self
    {
        $this->spaceBeforeListArgumentSeparators = $separatorSpaces;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceAfterListArgumentSeparator(): string
    {
        return $this->spaceAfterListArgumentSeparator;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceAfterListArgumentSeparator(string $whitespace): self
    {
        $this->spaceAfterListArgumentSeparator = $whitespace;

        return $this;
    }

    /**
     * @return array<non-empty-string, string>
     *
     * @internal
     */
    public function getSpaceAfterListArgumentSeparators(): array
    {
        return $this->spaceAfterListArgumentSeparators;
    }

    /**
     * @param array<non-empty-string, string> $separatorSpaces
     *
     * @return $this fluent interface
     */
    public function setSpaceAfterListArgumentSeparators(array $separatorSpaces): self
    {
        $this->spaceAfterListArgumentSeparators = $separatorSpaces;

        return $this;
    }

    /**
     * @internal
     */
    public function getSpaceBeforeOpeningBrace(): string
    {
        return $this->spaceBeforeOpeningBrace;
    }

    /**
     * @return $this fluent interface
     */
    public function setSpaceBeforeOpeningBrace(string $whitespace): self
    {
        $this->spaceBeforeOpeningBrace = $whitespace;

        return $this;
    }

    /**
     * @internal
     */
    public function getContentBeforeDeclarationBlock(): string
    {
        return $this->contentBeforeDeclarationBlock;
    }

    /**
     * @return $this fluent interface
     */
    public function setBeforeDeclarationBlock(string $content): self
    {
        $this->contentBeforeDeclarationBlock = $content;

        return $this;
    }

    /**
     * @internal
     */
    public function getContentAfterDeclarationBlockSelectors(): string
    {
        return $this->contentAfterDeclarationBlockSelectors;
    }

    /**
     * @return $this fluent interface
     */
    public function setAfterDeclarationBlockSelectors(string $content): self
    {
        $this->contentAfterDeclarationBlockSelectors = $content;

        return $this;
    }

    /**
     * @internal
     */
    public function getContentAfterDeclarationBlock(): string
    {
        return $this->contentAfterDeclarationBlock;
    }

    /**
     * @return $this fluent interface
     */
    public function setAfterDeclarationBlock(string $content): self
    {
        $this->contentAfterDeclarationBlock = $content;

        return $this;
    }

    /**
     * @internal
     */
    public function getIndentation(): string
    {
        return $this->indentation;
    }

    /**
     * @return $this fluent interface
     */
    public function setIndentation(string $indentation): self
    {
        $this->indentation = $indentation;

        return $this;
    }

    /**
     * @internal
     */
    public function shouldIgnoreExceptions(): bool
    {
        return $this->shouldIgnoreExceptions;
    }

    /**
     * @return $this fluent interface
     */
    public function setIgnoreExceptions(bool $ignoreExceptions): self
    {
        $this->shouldIgnoreExceptions = $ignoreExceptions;

        return $this;
    }

    /**
     * @internal
     */
    public function shouldRenderComments(): bool
    {
        return $this->shouldRenderComments;
    }

    /**
     * @return $this fluent interface
     */
    public function setRenderComments(bool $renderComments): self
    {
        $this->shouldRenderComments = $renderComments;

        return $this;
    }

    /**
     * @return int<0, max>
     *
     * @internal
     */
    public function getIndentationLevel(): int
    {
        return $this->indentationLevel;
    }

    /**
     * @param int<1, max> $numberOfTabs
     *
     * @return $this fluent interface
     */
    public function indentWithTabs(int $numberOfTabs = 1): self
    {
        return $this->setIndentation(\str_repeat("\t", $numberOfTabs));
    }

    /**
     * @param int<1, max> $numberOfSpaces
     *
     * @return $this fluent interface
     */
    public function indentWithSpaces(int $numberOfSpaces = 2): self
    {
        return $this->setIndentation(\str_repeat(' ', $numberOfSpaces));
    }

    /**
     * @internal since V8.8.0
     */
    public function nextLevel(): self
    {
        if ($this->nextLevelFormat === null) {
            $this->nextLevelFormat = clone $this;
            $this->nextLevelFormat->indentationLevel++;
            $this->nextLevelFormat->outputFormatter = null;
        }
        return $this->nextLevelFormat;
    }

    public function beLenient(): void
    {
        $this->shouldIgnoreExceptions = true;
    }

    /**
     * @internal since 8.8.0
     */
    public function getFormatter(): OutputFormatter
    {
        if ($this->outputFormatter === null) {
            $this->outputFormatter = new OutputFormatter($this);
        }

        return $this->outputFormatter;
    }

    /**
     * Creates an instance of this class without any particular formatting settings.
     */
    public static function create(): self
    {
        return new OutputFormat();
    }

    /**
     * Creates an instance of this class with a preset for compact formatting.
     */
    public static function createCompact(): self
    {
        $format = self::create();
        $format
            ->setSpaceBeforeRules('')
            ->setSpaceBetweenRules('')
            ->setSpaceAfterRules('')
            ->setSpaceBeforeBlocks('')
            ->setSpaceBetweenBlocks('')
            ->setSpaceAfterBlocks('')
            ->setSpaceAfterRuleName('')
            ->setSpaceBeforeOpeningBrace('')
            ->setSpaceAfterSelectorSeparator('')
            ->setSemicolonAfterLastRule(false)
            ->setRenderComments(false);

        return $format;
    }

    /**
     * Creates an instance of this class with a preset for pretty formatting.
     */
    public static function createPretty(): self
    {
        $format = self::create();
        $format
            ->setSpaceBeforeRules("\n")
            ->setSpaceBetweenRules("\n")
            ->setSpaceAfterRules("\n")
            ->setSpaceBeforeBlocks("\n")
            ->setSpaceBetweenBlocks("\n\n")
            ->setSpaceAfterBlocks("\n")
            ->setSpaceAfterListArgumentSeparators([',' => ' '])
            ->setRenderComments(true);

        return $format;
    }
}
