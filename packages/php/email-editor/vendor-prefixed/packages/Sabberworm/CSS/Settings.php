<?php

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS;

/**
 * Parser settings class.
 *
 * Configure parser behaviour here.
 */
class Settings
{
    /**
     * Multi-byte string support.
     *
     * If `true` (`mbstring` extension must be enabled), will use (slower) `mb_strlen`, `mb_convert_case`, `mb_substr`
     * and `mb_strpos` functions. Otherwise, the normal (ASCII-Only) functions will be used.
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bMultibyteSupport;

    /**
     * The default charset for the CSS if no `@charset` declaration is found. Defaults to utf-8.
     *
     * @var string
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $sDefaultCharset = 'utf-8';

    /**
     * Whether the parser silently ignore invalid rules instead of choking on them.
     *
     * @var bool
     *
     * @internal since 8.8.0, will be made private in 9.0.0
     */
    public $bLenientParsing = true;

    private function __construct()
    {
        $this->bMultibyteSupport = extension_loaded('mbstring');
    }

    /**
     * @return self new instance
     */
    public static function create()
    {
        return new Settings();
    }

    /**
     * Enables/disables multi-byte string support.
     *
     * If `true` (`mbstring` extension must be enabled), will use (slower) `mb_strlen`, `mb_convert_case`, `mb_substr`
     * and `mb_strpos` functions. Otherwise, the normal (ASCII-Only) functions will be used.
     *
     * @param bool $bMultibyteSupport
     *
     * @return self fluent interface
     */
    public function withMultibyteSupport($bMultibyteSupport = true)
    {
        $this->bMultibyteSupport = $bMultibyteSupport;
        return $this;
    }

    /**
     * Sets the charset to be used if the CSS does not contain an `@charset` declaration.
     *
     * @param string $sDefaultCharset
     *
     * @return self fluent interface
     */
    public function withDefaultCharset($sDefaultCharset)
    {
        $this->sDefaultCharset = $sDefaultCharset;
        return $this;
    }

    /**
     * Configures whether the parser should silently ignore invalid rules.
     *
     * @param bool $bLenientParsing
     *
     * @return self fluent interface
     */
    public function withLenientParsing($bLenientParsing = true)
    {
        $this->bLenientParsing = $bLenientParsing;
        return $this;
    }

    /**
     * Configures the parser to choke on invalid rules.
     *
     * @return self fluent interface
     */
    public function beStrict()
    {
        return $this->withLenientParsing(false);
    }
}
