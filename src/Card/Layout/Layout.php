<?php
/**
 * @author luchaos
 */

namespace Completionist\Vanity\Card\Layout;

use Completionist\Vanity\Card\Card;
use Exception;
use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Fill\Gradient\Vertical;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

class Layout
{
    protected $id;
    protected $width;
    protected $height;
    protected $padding;
    protected $backgroundColor;
    protected $textColorMuted;
    protected $textColor;
    protected $textColorHighlight;
    protected $fontNameThin;
    protected $fontName;
    protected $fontNameBold;
    protected $fontSize;
    protected $schemeColor;

    /** @var string $fontPath */
    protected $fontPath;

    /** @var Card $card */
    private $card;

    /** @var Imagine $imagine */
    protected $imagine;

    /** @var Image $image */
    protected $image;

    /**
     * Layout constructor.
     */
    public function __construct()
    {
        $this->imagine = new Imagine;
        $this->image =
            $this->imagine->create(new Box($this->width, $this->height), $this->color($this->backgroundColor));
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param Card $card
     */
    public function card(Card $card)
    {
        $this->card = $card;
    }

    /**
     * @param $fontPath
     */
    public function fonts($fontPath)
    {
        $this->fontPath = rtrim($fontPath, '/');
    }

    /**
     * @param string $key
     * @return array|string|null
     */
    protected function option($key)
    {
        return $this->card->option($key);
    }

    /**
     * @param $key
     * @return array|string|null
     */
    protected function data($key)
    {
        return $this->card->data($key);
    }

    /**
     * @param string $color
     * @param null   $alpha
     * @return \Imagine\Image\Palette\Color\ColorInterface
     */
    protected function color($color, $alpha = null)
    {
        static $palette;
        if (!$palette) {
            $palette = new RGB();
        }
        return $palette->color($color, $alpha);
    }

    /**
     * @param int    $fontSize
     * @param string $textColor
     * @param string $fontName
     * @return \Imagine\Gd\Font|\Imagine\Image\FontInterface
     */
    protected function font($fontSize = null, $textColor = null, $fontName = null)
    {
        return $this->imagine->font(
            $this->fontPath.'/'.($fontName ? $fontName : $this->fontName),
            $fontSize ? $fontSize : $this->fontSize,
            $textColor ? is_object($textColor) ? $textColor : $this->color($textColor) : $this->color($this->textColor)
        );
    }

    /**
     * http://php.net/manual/en/function.imagettfbbox.php
     *
     * 0    lower left corner, X position
     * 1    lower left corner, Y position
     * 2    lower right corner, X position
     * 3    lower right corner, Y position
     * 4    upper right corner, X position
     * 5    upper right corner, Y position
     * 6    upper left corner, X position
     * 7    upper left corner, Y position
     *
     * @param        $text
     * @param int    $fontSize
     * @param string $fontName
     * @return mixed
     */
    protected function textBoundaries($text, $fontSize = null, $fontName = null)
    {
        return imagettfbbox(
            $fontSize ? $fontSize : $this->fontSize, 0, $this->fontPath.'/'.($fontName ? $fontName : $this->fontName),
            $text
        );
    }

    /**
     * @param Image  $target
     * @param string $text
     * @param int    $x
     * @param int    $y
     * @param int    $size
     * @param string $color
     * @param string $font
     */
    protected function addText($target, $text, $x, $y, $size = null, $color = null, $font = null)
    {
        $target->draw()->text(
            $text, $this->font($size, $this->color($this->schemeColor, 60), $font), new Point($x + 1, $y + 1), 0
        );
        $target->draw()->text($text, $this->font($size, $color, $font), new Point($x, $y), 0);
    }

    /**
     * @param Image  $target
     * @param string $url
     * @param int    $x
     * @param int    $y
     * @param int    $size
     * @param bool   $addBackdrop
     */
    protected function addImage($target, $url, $x, $y, $size, $addBackdrop = false)
    {
        try {
            $image = $this->imagine->open($url);
            $aspect = $image->getSize()->getHeight() / $image->getSize()->getWidth();
            $width = $size / $aspect;
            $height = $size;
            $image->resize(new Box($width, $height));

            if ($addBackdrop) {
                $backdrop = $image->copy();
                $backdrop->effects()->gamma(0);
                $target->paste($backdrop, new Point($x + 1, $y + 1));
            }

            $target->paste($image, new Point($x, $y));

        } catch (Exception $e) {
            // could not load image
        }
    }

    /**
     * @param int            $x
     * @param int            $y
     * @param int            $width
     * @param int            $height
     * @param ColorInterface $start
     * @param ColorInterface $end
     * @param bool           $flip
     */
    protected function addGradient($x, $y, $width, $height, $start, $end, $flip = false)
    {
        $gradient = $this->imagine->create(new Box($width, $height));
        $gradient->fill(new Vertical($height, $start, $end));
        if ($flip) {
            $gradient->flipVertically();
        }

        //$white = $this->color('fff');
        //$fill = new Vertical($height, $white->darken(127), $white);
        //$mask = $this->imagine->create(new Box($width, $height))->fill($fill);
        //$gradient->applyMask($mask);

        $this->image->paste($gradient, new Point($x, $y));
    }

    protected function addBackground()
    {
        //$backgroundUrl = $this->option('background.value');
        //$backgroundUrl = $backgroundUrl ? $backgroundUrl : $this->data('background');
        $backgroundUrl = $this->data('background');
        $background = $this->imagine->open($backgroundUrl);
        $sWidth = $background->getSize()->getWidth();
        $sHeight = $background->getSize()->getHeight();
        $sAspect = $sWidth / $sHeight;
        $tAspect = $this->width / $this->height;
        $background->resize(new Box($this->width, $this->width / $sAspect));
        $sHeight = $background->getSize()->getHeight();
        $size = new Box($this->width, $this->height);

        $blur = $this->option('background.blur');
        $darken = $this->option('background.darken');
        $colorize = $this->option('background.colorize');

        if ($darken || $colorize) {
            $background->effects()->gamma(1.2);
        }
        if ($blur) {
            $background->effects()->blur();
        }
        $verticalAlign = $this->option('background.vertical');
        switch ($verticalAlign) {
            case 'bottom':
                $background->crop(new Point(0, (int)(($sHeight - $this->height))), $size);
                break;
            case 'lowercenter':
                $background->crop(new Point(0, (int)(($sHeight - $this->height) / 3 * 2)), $size);
                break;
            case 'center':
                $background->crop(new Point(0, (int)(($sHeight - $this->height) / 2)), $size);
                break;
            case 'uppercenter':
                $background->crop(new Point(0, (int)(($sHeight - $this->height) / 3)), $size);
                break;
            case 'top':
                // default
            default:
                $background->crop(new Point(0, 0), $size);
                break;
        }
        $this->image->paste($background, new Point(0, 0));

        // == background tint overlay

        if ($darken) {
            $this->image->draw()->polygon(
                [
                    new Point(0, 0),
                    new Point($this->width, 0),
                    new Point($this->width, $this->height),
                    new Point(0, $this->height)
                ],
                $this->color('222', 50),
                true
            );
        }
        if ($colorize) {
            $this->image->draw()->polygon(
                [
                    new Point(0, 0),
                    new Point($this->width, 0),
                    new Point($this->width, $this->height),
                    new Point(0, $this->height)
                ],
                $this->color($this->schemeColor, 30),
                true
            );
        }
    }

    /**
     * @param Box    $size
     * @param string $key
     * @return Image|\Imagine\Image\ImageInterface
     */
    protected function renderArea($size, $key)
    {
        $area = $this->createArea($size);
        $elements = $this->option($key);
        if (!count($elements)) {
            return $area;
        }

        $iconFontName = 'fontawesome.ttf';
        $iconFontSize = 10;
        $marginSmall = 2;
        $margin = 4;
        $marginLarge = 6;

        $offsetX = $this->padding;
        foreach ($elements as $element) {
            $type = isset($element['type']) ? $element['type'] : 'text';
            if($type === 'text' && strpos($key, 'areas.top') === 0) {
                $type = 'text-vertical';
            }
            switch ($type) {

                case 'avatar':
                    $imageSize = $size->getHeight() - $marginSmall;
                    $imageUrl = null;
                    //$imageUrl = isset($element['value']) ? $element['value'] : $imageUrl;
                    $imageUrl = isset($element['data']) ? $this->data($element['data']) : $imageUrl;
                    if ($imageUrl) {
                        $this->addImage(
                            $area, $imageUrl, $marginSmall/2,
                            $marginSmall/2, $imageSize, false
                        );
                    }
                    $offsetX += $imageSize + $marginLarge;
                    break;

                case 'image':
                    $imageSize = $size->getHeight() - $this->padding * 2;
                    $imageUrl = null;
                    //$imageUrl = isset($element['value']) ? $element['value'] : $imageUrl;
                    $imageUrl = isset($element['data']) ? $this->data($element['data']) : $imageUrl;
                    if ($imageUrl) {
                        $this->addImage(
                            $area, $imageUrl, $offsetX, (int)(($size->getHeight() - $imageSize) / 2), $imageSize, true
                        );
                    }
                    $offsetX += $imageSize + $marginLarge;
                    break;

                case 'username':
                    $y = (int)(($size->getHeight() - $this->fontSize) / 2);

                    $text = null;
                    $text = isset($element['data']) ? $this->data($element['data']) : $text;
                    //$text = isset($element['value']) ? $element['value'] : $text;
                    $color = isset($element['color']) ? $element['color'] : $this->textColorHighlight;

                    if ($text) {
                        $boundaries = $this->textBoundaries($text, $this->fontSize + 2, $this->fontNameBold);
                        if ($boundaries[2] + $offsetX <= $size->getWidth()) {
                            $this->addText(
                                $area, $text, $offsetX, 9, $this->fontSize + 2, $color,
                                $this->fontNameBold
                            );
                            $offsetX += $boundaries[2] + $marginLarge;
                        }
                    }
                    break;

                case 'text-vertical':

                    $label = isset($element['label']) ? $element['label'] : null;
                    $labelWidth = 0;
                    if ($label) {
                        $y = $size->getHeight() - $this->fontSize - 4;
                        $boundaries = $this->textBoundaries($label, $this->fontSize-2, $this->fontName);
                        $labelWidth = $boundaries[2];
                        if ($boundaries[2] + $offsetX <= $size->getWidth()) {
                            $this->addText($area, $label, $offsetX, $y, $this->fontSize-2, $this->textColorMuted, $this->fontName);
                            $offsetX += $boundaries[2] + $marginLarge;
                        }
                    }

                    $text = null;
                    $text = isset($element['data']) ? $this->data($element['data']) : $text;
                    $text = isset($element['value']) ? $element['value'] : $text;
                    if ($text) {
                        $y = (int)(($size->getHeight() - $this->fontSize) * 0.2);
                        $boundaries = $this->textBoundaries($text, $this->fontSize+2, $this->fontName);
                        $x = $offsetX - $marginLarge - (int)(($labelWidth)/2 + $boundaries[2]*0.5);
                        $color = isset($element['color']) ? $element['color'] : $this->textColor;
                        $this->addText($area, $text, $x, $y, $this->fontSize+2, $color, $this->fontName);
                    }
                    break;

                case 'text':
                    //default

                default:
                    $y = (int)(($size->getHeight() - $this->fontSize) / 2);

                    $label = isset($element['label']) ? $element['label'] : null;
                    if ($label) {
                        $boundaries = $this->textBoundaries($label);
                        if ($boundaries[2] + $offsetX <= $size->getWidth()) {
                            $this->addText($area, $label, $offsetX, $y);
                            $offsetX += $boundaries[2] + $marginLarge;
                        }
                    }

                    $icon = isset($element['icon']) ? $element['icon'] : null;
                    if ($icon) {
                        $iconY = (int)(($size->getHeight() - $iconFontSize) / 2);
                        $boundaries = $this->textBoundaries($icon, $iconFontSize, $iconFontName);
                        if ($boundaries[2] + $offsetX <= $size->getWidth()) {
                            $this->addText($area, $icon, $offsetX, $iconY, $iconFontSize, null, $iconFontName);
                            $offsetX += $boundaries[2] + 4;
                        }
                    }

                    $text = null;
                    $text = isset($element['data']) ? $this->data($element['data']) : $text;
                    $text = isset($element['value']) ? $element['value'] : $text;
                    if ($text) {
                        $boundaries = $this->textBoundaries($text);
                        if ($boundaries[2] + $offsetX <= $size->getWidth()) {
                            $color = isset($element['color']) ? $element['color'] : $this->textColorHighlight;
                            $alpha = isset($element['alpha']) ? (int)$element['alpha'] : 100;
                            $color = $this->color($color, $alpha);
                            $this->addText($area, $text, $offsetX, $y, null, $color);
                            $offsetX += $boundaries[2] + $marginLarge;
                        }
                    }
                    break;
            }
        }
        $offsetX += $this->padding - $marginLarge;
        $area->crop(new Point(0, 0), new Box(max($offsetX, 1), $size->getHeight()));
        return $area;
    }

    /**
     * @param Image $area
     * @param array $items
     * @param int   $rows
     */
    protected function imagesGrid(Image $area, array $items, $rows)
    {
        if (!count($items) || !isset($items[0]) || !isset($items[0]['image'])) {
            return;
        }

        $borderSize = isset($items[0]['color']) ? 1 : 0;
        $margin = 2;

        $itemHeight = min(42, (int)(($area->getSize()->getHeight() - ($margin * 1.5) * ($rows + 1)) / $rows));
        $testImageSize = $this->imagine->open($items[0]['image'])->getSize();
        $testAspect = $testImageSize->getWidth() / $testImageSize->getHeight();
        $itemWidth = $itemHeight * $testAspect;

        $imageHeight = $itemHeight - $borderSize * 2;

        $trimWidth = $itemHeight;
        $trimHeight = $itemHeight;
        $offsetX = 0;
        $offsetY = 0;
        foreach ($items as $item) {

            $item = is_array($item) ? (object)$item : $item;
            if (($offsetX + $itemWidth) > $area->getSize()->getWidth()) {
                $offsetX = 0;
                $offsetY += $itemHeight + $margin;
            }
            if ($offsetY + $itemHeight > $area->getSize()->getHeight()) {
                break;
            }
            $trimHeight = max($trimHeight, $offsetY + $itemHeight);
            $trimWidth = max($trimWidth, $offsetX + $itemWidth);

            // draw border
            if (isset($item->color)) {
                $area->draw()->polygon(
                    [new Point($offsetX, $offsetY),
                     new Point($offsetX + $itemWidth - 1, $offsetY),
                     new Point($offsetX + $itemWidth - 1, $offsetY - 1 + $itemHeight),
                     new Point($offsetX, $offsetY - 1 + $itemHeight)],
                    $this->color($item->color, 60), true
                );
            }

            $this->addImage($area, $item->image, $offsetX + $borderSize, $offsetY + $borderSize, $imageHeight);
            $offsetX += $itemWidth + $margin;
        }

        $area->crop(new Point(0, 0), new Box($trimWidth, $trimHeight));
    }

    protected function createArea($size)
    {
        return $this->imagine->create($size, $this->color('CC0', 0));
    }
}