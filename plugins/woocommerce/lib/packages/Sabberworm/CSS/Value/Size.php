<?php

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * A `Size` consists of a numeric `size` value and a unit.
 */
class Size extends PrimitiveValue
{
    /**
     * vh/vw/vm(ax)/vmin/rem are absolute insofar as they don’t scale to the immediate parent (only the viewport)
     *
     * @var array<int, string>
     *
     * @internal
     */
    const ABSOLUTE_SIZE_UNITS = [
        'px',
        'pt',
        'pc',
        'cm',
        'mm',
        'mozmm',
        'in',
        'vh',
        'dvh',
        'svh',
        'lvh',
        'vw',
        'vmin',
        'vmax',
        'rem',
    ];

    /**
     * @var array<int, string>
     *
     * @internal
     */
    const RELATIVE_SIZE_UNITS = ['%', 'em', 'ex', 'ch', 'fr'];

    /**
     * @var array<int, string>
     *
     * @internal
     */
    const NON_SIZE_UNITS = ['deg', 'grad', 'rad', 's', 'ms', 'turn', 'Hz', 'kHz'];

    /**
     * @var array<int, array<string, string>>|null
     */
    private static $SIZE_UNITS = null;

    /**
     * @var float
     */
    private $fSize;

    /**
     * @var string|null
     */
    private $sUnit;

    /**
     * @var bool
     */
    private $bIsColorComponent;

    /**
     * @param float|int|string $fSize
     * @param string|null $sUnit
     * @param bool $bIsColorComponent
     * @param int $iLineNo
     */
    public function __construct($fSize, $sUnit = null, $bIsColorComponent = false, $iLineNo = 0)
    {
        parent::__construct($iLineNo);
        $this->fSize = (float)$fSize;
        $this->sUnit = $sUnit;
        $this->bIsColorComponent = $bIsColorComponent;
    }

    /**
     * @param bool $bIsColorComponent
     *
     * @return Size
     *
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $oParserState, $bIsColorComponent = false)
    {
        $sSize = '';
        if ($oParserState->comes('-')) {
            $sSize .= $oParserState->consume('-');
        }
        while (is_numeric($oParserState->peek()) || $oParserState->comes('.') || $oParserState->comes('e', true)) {
            if ($oParserState->comes('.')) {
                $sSize .= $oParserState->consume('.');
            } elseif ($oParserState->comes('e', true)) {
                $sLookahead = $oParserState->peek(1, 1);
                if (is_numeric($sLookahead) || $sLookahead === '+' || $sLookahead === '-') {
                    $sSize .= $oParserState->consume(2);
                } else {
                    break; // Reached the unit part of the number like "em" or "ex"
                }
            } else {
                $sSize .= $oParserState->consume(1);
            }
        }

        $sUnit = null;
        $aSizeUnits = self::getSizeUnits();
        foreach ($aSizeUnits as $iLength => &$aValues) {
            $sKey = strtolower($oParserState->peek($iLength));
            if (array_key_exists($sKey, $aValues)) {
                if (($sUnit = $aValues[$sKey]) !== null) {
                    $oParserState->consume($iLength);
                    break;
                }
            }
        }
        return new Size((float)$sSize, $sUnit, $bIsColorComponent, $oParserState->currentLine());
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function getSizeUnits()
    {
        if (!is_array(self::$SIZE_UNITS)) {
            self::$SIZE_UNITS = [];
            foreach (array_merge(self::ABSOLUTE_SIZE_UNITS, self::RELATIVE_SIZE_UNITS, self::NON_SIZE_UNITS) as $val) {
                $iSize = strlen($val);
                if (!isset(self::$SIZE_UNITS[$iSize])) {
                    self::$SIZE_UNITS[$iSize] = [];
                }
                self::$SIZE_UNITS[$iSize][strtolower($val)] = $val;
            }

            krsort(self::$SIZE_UNITS, SORT_NUMERIC);
        }

        return self::$SIZE_UNITS;
    }

    /**
     * @param string $sUnit
     *
     * @return void
     */
    public function setUnit($sUnit)
    {
        $this->sUnit = $sUnit;
    }

    /**
     * @return string|null
     */
    public function getUnit()
    {
        return $this->sUnit;
    }

    /**
     * @param float|int|string $fSize
     */
    public function setSize($fSize)
    {
        $this->fSize = (float)$fSize;
    }

    /**
     * @return float
     */
    public function getSize()
    {
        return $this->fSize;
    }

    /**
     * @return bool
     */
    public function isColorComponent()
    {
        return $this->bIsColorComponent;
    }

    /**
     * Returns whether the number stored in this Size really represents a size (as in a length of something on screen).
     *
     * @return false if the unit an angle, a duration, a frequency or the number is a component in a Color object.
     */
    public function isSize()
    {
        if (in_array($this->sUnit, self::NON_SIZE_UNITS, true)) {
            return false;
        }
        return !$this->isColorComponent();
    }

    /**
     * @return bool
     */
    public function isRelative()
    {
        if (in_array($this->sUnit, self::RELATIVE_SIZE_UNITS, true)) {
            return true;
        }
        if ($this->sUnit === null && $this->fSize != 0) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     *
     * @deprecated in V8.8.0, will be removed in V9.0.0. Use `render` instead.
     */
    public function __toString()
    {
        return $this->render(new OutputFormat());
    }

    /**
     * @param OutputFormat|null $oOutputFormat
     *
     * @return string
     */
    public function render($oOutputFormat)
    {
        $l = localeconv();
        $sPoint = preg_quote($l['decimal_point'], '/');
        $sSize = preg_match("/[\d\.]+e[+-]?\d+/i", (string)$this->fSize)
            ? preg_replace("/$sPoint?0+$/", "", sprintf("%f", $this->fSize)) : (string)$this->fSize;
        return preg_replace(["/$sPoint/", "/^(-?)0\./"], ['.', '$1.'], $sSize)
            . ($this->sUnit === null ? '' : $this->sUnit);
    }
}
