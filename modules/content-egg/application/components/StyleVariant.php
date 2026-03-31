<?php

namespace ContentEgg\application\components;

use ContentEgg\application\admin\GeneralConfig;
use Generator;



defined('\ABSPATH') || exit;

/**
 * StyleVariant abstract class file
 *
 * @author keywordrush.com <support@keywordrush.com>
 * @link https://www.keywordrush.com
 * @copyright Copyright &copy; 2025 keywordrush.com
 */

class StyleVariant
{
    private $theme;
    private $variant;
    private $background;
    private $backgroundRgb;
    private $border;
    private $color;
    private $hoverBackground;
    private $hoverBorder;
    private $activeBackground;
    private $activeBorder;

    public function __construct($theme = 'light', $variant = null, $background = null)
    {
        $this->setTheme($theme);
        if ($variant && $background)
            $this->setVariant($variant, $background);
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function setVariant($variant, $background)
    {
        $this->variant = $variant;
        $this->background = $background;
        $this->backgroundRgb = self::hexToRgb($this->background);
        $this->border = self::shadeColor($this->background, 10);
        $this->color = self::colorContrast($this->background);
        $this->hoverBackground = $this->getShadeOrTint($this->background, 10);
        $this->hoverBorder = $this->getShadeOrTint($this->border, 10);
        $this->activeBackground = $this->getShadeOrTint($this->background, 20);
        $this->activeBorder = $this->getShadeOrTint($this->border, 20);
    }

    public function generateVariantCss()
    {
        $css = $this->generateRootCss();
        $css .= $this->generateBadgeCss();
        $css .= $this->generateButtonCss();
        return $css;
    }

    public function generateRootCss()
    {
        $vars = array(
            '--cegg-' . $this->variant => $this->background,
            '--cegg-' . $this->variant . '-rgb' => $this->backgroundRgb,
        );

        $css = ":root {";
        foreach ($vars as $key => $value)
        {
            $css .= $key . ":" . $value . ";";
        }
        $css .= "}";
        return $css;
    }

    public function generateBadgeCss()
    {
        $css = '.cegg5-container .bg-' . $this->variant . '{--cegg-badge-color:' . $this->color . ';}';
        $css .= '.cegg5-container .text-bg-' . $this->variant . '{color: ' . $this->color . ' !important;}';

        return $css;
    }

    public function generateButtonCss()
    {
        $css = ".cegg5-container .btn-{$this->variant}{";
        $css .= "--cegg-btn-color: {$this->color};";
        $css .= "--cegg-btn-bg: {$this->background};";
        $css .= "--cegg-btn-border-color: {$this->border};";
        $css .= "--cegg-btn-hover-color: {$this->color};";
        $css .= "--cegg-btn-hover-bg: {$this->hoverBackground};";
        $css .= "--cegg-btn-hover-border-color: {$this->hoverBorder};";
        $css .= "--cegg-btn-active-color: {$this->color};";
        $css .= "--cegg-btn-active-bg: {$this->activeBackground};";
        $css .= "--cegg-btn-active-border-color: {$this->activeBorder};";
        $css .= "}";

        // btn-outline
        $color = $this->background;
        $colorHover = self::colorContrast($color);
        $activeBackground = $color;
        $activeBorder = $color;
        $activeColor = self::colorContrast($activeBackground);

        $css .= ".cegg5-container .btn-outline-{$this->variant}{";
        $css .= "--cegg-btn-color: {$color};";
        $css .= "--cegg-btn-border-color: {$color};";
        $css .= "--cegg-btn-hover-color: {$colorHover};";
        $css .= "--cegg-btn-hover-bg: {$activeBackground};";
        $css .= "--cegg-btn-hover-border-color: {$activeBorder};";
        $css .= "--cegg-btn-active-color: {$activeColor};";
        $css .= "--cegg-btn-active-bg: {$activeBackground};";
        $css .= "--cegg-btn-active-border-color: {$activeBorder};";
        $css .= "}";

        return $css;
    }

    public static function colorContrast($background)
    {
        list($r, $g, $b) = self::hexToRgb($background, true);

        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($brightness > 128) ? '#000000' : '#FFFFFF';
    }

    public  function getShadeOrTint($color, $percent)
    {
        if ($this->theme == 'light')
            return self::shadeColor($color, $percent);
        else
            return self::tintColor($color, $percent);
    }

    public static function shadeColor($color, $percent)
    {
        list($r, $g, $b) = self::hexToRgb($color, true);

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        $newHex = sprintf("#%02x%02x%02x", round($r), round($g), round($b));
        return $newHex;
    }

    public static function tintColor($color, $percent)
    {
        return self::shadeColor($color, $percent * -1);
    }

    public static function hexToRgb($hex, $return_array = false)
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) == 3)
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        if ($return_array)
            return array($r, $g, $b);
        else
            return "$r, $g, $b";
    }
}
