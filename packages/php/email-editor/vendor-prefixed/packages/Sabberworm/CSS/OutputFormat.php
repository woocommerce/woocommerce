<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS;

/**
 * Extending this class is deprecated in version 8.8.0; it will be made `final` in version 9.0.0.
 *
 * @method OutputFormat setSemicolonAfterLastRule(bool $bSemicolonAfterLastRule) Set whether semicolons are added after
 *     last rule.
 */
class OutputFormat
{
    /**
     * Value format: `"` means double-quote, `'` means single-quote
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sStringQuotingType = '"';

    /**
     * Output RGB colors in hash notation if possible
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bRGBHashNotation = true;

    /**
     * Declaration format
     *
     * Semicolon after the last rule of a declaration block can be omitted. To do that, set this false.
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bSemicolonAfterLastRule = true;

    /**
     * Spacing
     * Note that these strings are not sanity-checked: the value should only consist of whitespace
     * Any newline character will be indented according to the current level.
     * The triples (After, Before, Between) can be set using a wildcard (e.g. `$oFormat->set('Space*Rules', "\n");`)
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceAfterRuleName = ' ';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBeforeRules = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceAfterRules = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBetweenRules = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBeforeBlocks = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceAfterBlocks = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBetweenBlocks = "\n";

    /**
     * Content injected in and around at-rule blocks.
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sBeforeAtRuleBlock = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sAfterAtRuleBlock = '';

    /**
     * This is what’s printed before and after the comma if a declaration block contains multiple selectors.
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBeforeSelectorSeparator = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceAfterSelectorSeparator = ' ';

    /**
     * This is what’s inserted before the separator in value lists, by default.
     *
     * `array` is deprecated in version 8.8.0, and will be removed in version 9.0.0.
     * To set the spacing for specific separators, use {@see $aSpaceBeforeListArgumentSeparators} instead.
     *
     * @var string|array<non-empty-string, string>
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBeforeListArgumentSeparator = '';

    /**
     * Keys are separators (e.g. `,`).  Values are the space sequence to insert, or an empty string.
     *
     * @var array<non-empty-string, string>
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $aSpaceBeforeListArgumentSeparators = [];

    /**
     * This is what’s inserted after the separator in value lists, by default.
     *
     * `array` is deprecated in version 8.8.0, and will be removed in version 9.0.0.
     * To set the spacing for specific separators, use {@see $aSpaceAfterListArgumentSeparators} instead.
     *
     * @var string|array<non-empty-string, string>
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceAfterListArgumentSeparator = '';

    /**
     * Keys are separators (e.g. `,`).  Values are the space sequence to insert, or an empty string.
     *
     * @var array<non-empty-string, string>
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $aSpaceAfterListArgumentSeparators = [];

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sSpaceBeforeOpeningBrace = ' ';

    /**
     * Content injected in and around declaration blocks.
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sBeforeDeclarationBlock = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sAfterDeclarationBlockSelectors = '';

    /**
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sAfterDeclarationBlock = '';

    /**
     * Indentation character(s) per level. Only applicable if newlines are used in any of the spacing settings.
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sIndentation = "\t";

    /**
     * Output exceptions.
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bIgnoreExceptions = false;

    /**
     * Render comments for lists and RuleSets
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bRenderComments = false;

    /**
     * @var OutputFormatter|null
     */
    private $oFormatter = null;

    /**
     * @var OutputFormat|null
     */
    private $oNextLevelFormat = null;

    /**
     * @var int
     */
    private $iIndentationLevel = 0;

    /**
     * @internal since V8.8.0. Use the factory methods `create()`, `createCompact()`, or `createPretty()` instead.
     */
    public function __construct()
    {
    }

    /**
     * @param string $sName
     *
     * @return string|null
     *
     * @deprecated since 8.8.0, will be removed in 9.0.0. Use specific getters instead.
     */
    public function get($sName)
    {
        $aVarPrefixes = ['a', 's', 'm', 'b', 'f', 'o', 'c', 'i'];
        foreach ($aVarPrefixes as $sPrefix) {
            $sFieldName = $sPrefix . ucfirst($sName);
            if (isset($this->$sFieldName)) {
                return $this->$sFieldName;
            }
        }
        return null;
    }

    /**
     * @param array<array-key, string>|string $aNames
     * @param mixed $mValue
     *
     * @return self|false
     *
     * @deprecated since 8.8.0, will be removed in 9.0.0. Use specific setters instead.
     */
    public function set($aNames, $mValue)
    {
        $aVarPrefixes = ['a', 's', 'm', 'b', 'f', 'o', 'c', 'i'];
        if (is_string($aNames) && strpos($aNames, '*') !== false) {
            $aNames =
                [
                    str_replace('*', 'Before', $aNames),
                    str_replace('*', 'Between', $aNames),
                    str_replace('*', 'After', $aNames),
                ];
        } elseif (!is_array($aNames)) {
            $aNames = [$aNames];
        }
        foreach ($aVarPrefixes as $sPrefix) {
            $bDidReplace = false;
            foreach ($aNames as $sName) {
                $sFieldName = $sPrefix . ucfirst($sName);
                if (isset($this->$sFieldName)) {
                    $this->$sFieldName = $mValue;
                    $bDidReplace = true;
                }
            }
            if ($bDidReplace) {
                return $this;
            }
        }
        // Break the chain so the user knows this option is invalid
        return false;
    }

    /**
     * @param string $sMethodName
     * @param array<array-key, mixed> $aArguments
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($sMethodName, array $aArguments)
    {
        if (strpos($sMethodName, 'set') === 0) {
            return $this->set(substr($sMethodName, 3), $aArguments[0]);
        } elseif (strpos($sMethodName, 'get') === 0) {
            return $this->get(substr($sMethodName, 3));
        } elseif (method_exists(OutputFormatter::class, $sMethodName)) {
            // @deprecated since 8.8.0, will be removed in 9.0.0. Call the method on the formatter directly instead.
            return call_user_func_array([$this->getFormatter(), $sMethodName], $aArguments);
        } else {
            throw new \Exception('Unknown OutputFormat method called: ' . $sMethodName);
        }
    }

    /**
     * @param int $iNumber
     *
     * @return self
     */
    public function indentWithTabs($iNumber = 1)
    {
        return $this->setIndentation(str_repeat("\t", $iNumber));
    }

    /**
     * @param int $iNumber
     *
     * @return self
     */
    public function indentWithSpaces($iNumber = 2)
    {
        return $this->setIndentation(str_repeat(" ", $iNumber));
    }

    /**
     * @return OutputFormat
     *
     * @internal since V8.8.0
     */
    public function nextLevel()
    {
        if ($this->oNextLevelFormat === null) {
            $this->oNextLevelFormat = clone $this;
            $this->oNextLevelFormat->iIndentationLevel++;
            $this->oNextLevelFormat->oFormatter = null;
        }
        return $this->oNextLevelFormat;
    }

    /**
     * @return void
     */
    public function beLenient()
    {
        $this->bIgnoreExceptions = true;
    }

    /**
     * @return OutputFormatter
     *
     * @internal since 8.8.0
     */
    public function getFormatter()
    {
        if ($this->oFormatter === null) {
            $this->oFormatter = new OutputFormatter($this);
        }

        return $this->oFormatter;
    }

    /**
     * @return int
     *
     * @deprecated #869 since version V8.8.0, will be removed in V9.0.0. Use `getIndentationLevel()` instead.
     */
    public function level()
    {
        return $this->iIndentationLevel;
    }

    /**
     * Creates an instance of this class without any particular formatting settings.
     *
     * @return self
     */
    public static function create()
    {
        return new OutputFormat();
    }

    /**
     * Creates an instance of this class with a preset for compact formatting.
     *
     * @return self
     */
    public static function createCompact()
    {
        $format = self::create();
        $format->set('Space*Rules', "")
            ->set('Space*Blocks', "")
            ->setSpaceAfterRuleName('')
            ->setSpaceBeforeOpeningBrace('')
            ->setSpaceAfterSelectorSeparator('')
            ->setRenderComments(false);
        return $format;
    }

    /**
     * Creates an instance of this class with a preset for pretty formatting.
     *
     * @return self
     */
    public static function createPretty()
    {
        $format = self::create();
        $format->set('Space*Rules', "\n")
            ->set('Space*Blocks', "\n")
            ->setSpaceBetweenBlocks("\n\n")
            ->set('SpaceAfterListArgumentSeparators', [',' => ' '])
            ->setRenderComments(true);
        return $format;
    }
}
