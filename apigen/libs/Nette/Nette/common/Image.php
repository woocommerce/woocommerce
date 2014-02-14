<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette;

use Nette;



/**
 * Basic manipulation with images.
 *
 * <code>
 * $image = Image::fromFile('nette.jpg');
 * $image->resize(150, 100);
 * $image->sharpen();
 * $image->send();
 * </code>
 *
 * @author     David Grudl
 *
 * @method void alphaBlending(bool $on)
 * @method void antialias(bool $on)
 * @method void arc($x, $y, $w, $h, $s, $e, $color)
 * @method void char($font, $x, $y, $char, $color)
 * @method void charUp($font, $x, $y, $char, $color)
 * @method int colorAllocate($red, $green, $blue)
 * @method int colorAllocateAlpha($red, $green, $blue, $alpha)
 * @method int colorAt($x, $y)
 * @method int colorClosest($red, $green, $blue)
 * @method int colorClosestAlpha($red, $green, $blue, $alpha)
 * @method int colorClosestHWB($red, $green, $blue)
 * @method void colorDeallocate($color)
 * @method int colorExact($red, $green, $blue)
 * @method int colorExactAlpha($red, $green, $blue, $alpha)
 * @method void colorMatch(Image $image2)
 * @method int colorResolve($red, $green, $blue)
 * @method int colorResolveAlpha($red, $green, $blue, $alpha)
 * @method void colorSet($index, $red, $green, $blue)
 * @method array colorsForIndex($index)
 * @method int colorsTotal()
 * @method int colorTransparent([$color])
 * @method void convolution(array $matrix, float $div, float $offset)
 * @method void copy(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH)
 * @method void copyMerge(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $opacity)
 * @method void copyMergeGray(Image $src, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $opacity)
 * @method void copyResampled(Image $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH)
 * @method void copyResized(Image $src, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH)
 * @method void dashedLine($x1, $y1, $x2, $y2, $color)
 * @method void ellipse($cx, $cy, $w, $h, $color)
 * @method void fill($x, $y, $color)
 * @method void filledArc($cx, $cy, $w, $h, $s, $e, $color, $style)
 * @method void filledEllipse($cx, $cy, $w, $h, $color)
 * @method void filledPolygon(array $points, $numPoints, $color)
 * @method void filledRectangle($x1, $y1, $x2, $y2, $color)
 * @method void fillToBorder($x, $y, $border, $color)
 * @method void filter($filtertype [, ...])
 * @method int fontHeight($font)
 * @method int fontWidth($font)
 * @method array ftBBox($size, $angle, string $fontFile, string $text [, array $extrainfo])
 * @method array ftText($size, $angle, $x, $y, $col, string $fontFile, string $text [, array $extrainfo])
 * @method void gammaCorrect(float $inputgamma, float $outputgamma)
 * @method int interlace([$interlace])
 * @method bool isTrueColor()
 * @method void layerEffect($effect)
 * @method void line($x1, $y1, $x2, $y2, $color)
 * @method int loadFont(string $file)
 * @method void paletteCopy(Image $source)
 * @method void polygon(array $points, $numPoints, $color)
 * @method array psBBox(string $text, $font, $size [, $space] [, $tightness] [, float $angle])
 * @method void psEncodeFont($fontIndex, string $encodingfile)
 * @method void psExtendFont($fontIndex, float $extend)
 * @method void psFreeFont($fontindex)
 * @method resource psLoadFont(string $filename)
 * @method void psSlantFont($fontIndex, float $slant)
 * @method array psText(string $text, $font, $size, $color, $backgroundColor, $x, $y [, $space] [, $tightness] [, float $angle] [, $antialiasSteps])
 * @method void rectangle($x1, $y1, $x2, $y2, $col)
 * @method Image rotate(float $angle, $backgroundColor)
 * @method void saveAlpha(bool $saveflag)
 * @method void setBrush(Image $brush)
 * @method void setPixel($x, $y, $color)
 * @method void setStyle(array $style)
 * @method void setThickness($thickness)
 * @method void setTile(Image $tile)
 * @method void string($font, $x, $y, string $s, $col)
 * @method void stringUp($font, $x, $y, string $s, $col)
 * @method void trueColorToPalette(bool $dither, $ncolors)
 * @method array ttfBBox($size, $angle, string $fontfile, string $text)
 * @method array ttfText($size, $angle, $x, $y, $color, string $fontfile, string $text)
 * @method int types()
 * @property-read int $width
 * @property-read int $height
 * @property-read resource $imageResource
 */
class Image extends Object
{
	/** {@link resize()} only shrinks images */
	const SHRINK_ONLY = 1;

	/** {@link resize()} will ignore aspect ratio */
	const STRETCH = 2;

	/** {@link resize()} fits in given area so its dimensions are less than or equal to the required dimensions */
	const FIT = 0;

	/** {@link resize()} fills given area so its dimensions are greater than or equal to the required dimensions */
	const FILL = 4;

	/** {@link resize()} fills given area exactly */
	const EXACT = 8;

	/** @int image types {@link send()} */
	const JPEG = IMAGETYPE_JPEG,
		PNG = IMAGETYPE_PNG,
		GIF = IMAGETYPE_GIF;

	const EMPTY_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

	/** @deprecated */
	const ENLARGE = 0;

	/** @var resource */
	private $image;



	/**
	 * Returns RGB color.
	 * @param  int  red 0..255
	 * @param  int  green 0..255
	 * @param  int  blue 0..255
	 * @param  int  transparency 0..127
	 * @return array
	 */
	public static function rgb($red, $green, $blue, $transparency = 0)
	{
		return array(
			'red' => max(0, min(255, (int) $red)),
			'green' => max(0, min(255, (int) $green)),
			'blue' => max(0, min(255, (int) $blue)),
			'alpha' => max(0, min(127, (int) $transparency)),
		);
	}



	/**
	 * Opens image from file.
	 * @param  string
	 * @param  mixed  detected image format
	 * @return Image
	 */
	public static function fromFile($file, & $format = NULL)
	{
		if (!extension_loaded('gd')) {
			throw new NotSupportedException("PHP extension GD is not loaded.");
		}

		$info = @getimagesize($file); // @ - files smaller than 12 bytes causes read error

		switch ($format = $info[2]) {
		case self::JPEG:
			return new static(imagecreatefromjpeg($file));

		case self::PNG:
			return new static(imagecreatefrompng($file));

		case self::GIF:
			return new static(imagecreatefromgif($file));

		default:
			throw new UnknownImageFileException("Unknown image type or file '$file' not found.");
		}
	}



	/**
	 * Get format from the image stream in the string.
	 * @param  string
	 * @return mixed  detected image format
	 */
	public static function getFormatFromString($s)
	{
		$types = array('image/jpeg' => self::JPEG, 'image/gif' => self::GIF, 'image/png' => self::PNG);
		$type = Utils\MimeTypeDetector::fromString($s);
		return isset($types[$type]) ? $types[$type] : NULL;
	}



	/**
	 * Create a new image from the image stream in the string.
	 * @param  string
	 * @param  mixed  detected image format
	 * @return Image
	 */
	public static function fromString($s, & $format = NULL)
	{
		if (!extension_loaded('gd')) {
			throw new NotSupportedException("PHP extension GD is not loaded.");
		}

		$format = static::getFormatFromString($s);

		return new static(imagecreatefromstring($s));
	}



	/**
	 * Creates blank image.
	 * @param  int
	 * @param  int
	 * @param  array
	 * @return Image
	 */
	public static function fromBlank($width, $height, $color = NULL)
	{
		if (!extension_loaded('gd')) {
			throw new NotSupportedException("PHP extension GD is not loaded.");
		}

		$width = (int) $width;
		$height = (int) $height;
		if ($width < 1 || $height < 1) {
			throw new InvalidArgumentException('Image width and height must be greater than zero.');
		}

		$image = imagecreatetruecolor($width, $height);
		if (is_array($color)) {
			$color += array('alpha' => 0);
			$color = imagecolorallocatealpha($image, $color['red'], $color['green'], $color['blue'], $color['alpha']);
			imagealphablending($image, FALSE);
			imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
			imagealphablending($image, TRUE);
		}
		return new static($image);
	}



	/**
	 * Wraps GD image.
	 * @param  resource
	 */
	public function __construct($image)
	{
		$this->setImageResource($image);
		imagesavealpha($image, TRUE);
	}



	/**
	 * Returns image width.
	 * @return int
	 */
	public function getWidth()
	{
		return imagesx($this->image);
	}



	/**
	 * Returns image height.
	 * @return int
	 */
	public function getHeight()
	{
		return imagesy($this->image);
	}



	/**
	 * Sets image resource.
	 * @param  resource
	 * @return Image  provides a fluent interface
	 */
	protected function setImageResource($image)
	{
		if (!is_resource($image) || get_resource_type($image) !== 'gd') {
			throw new InvalidArgumentException('Image is not valid.');
		}
		$this->image = $image;
		return $this;
	}



	/**
	 * Returns image GD resource.
	 * @return resource
	 */
	public function getImageResource()
	{
		return $this->image;
	}



	/**
	 * Resizes image.
	 * @param  mixed  width in pixels or percent
	 * @param  mixed  height in pixels or percent
	 * @param  int    flags
	 * @return Image  provides a fluent interface
	 */
	public function resize($width, $height, $flags = self::FIT)
	{
		if ($flags & self::EXACT) {
			return $this->resize($width, $height, self::FILL)->crop('50%', '50%', $width, $height);
		}

		list($newWidth, $newHeight) = static::calculateSize($this->getWidth(), $this->getHeight(), $width, $height, $flags);

		if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) { // resize
			$newImage = static::fromBlank($newWidth, $newHeight, self::RGB(0, 0, 0, 127))->getImageResource();
			imagecopyresampled(
				$newImage, $this->getImageResource(),
				0, 0, 0, 0,
				$newWidth, $newHeight, $this->getWidth(), $this->getHeight()
			);
			$this->image = $newImage;
		}

		if ($width < 0 || $height < 0) { // flip is processed in two steps for better quality
			$newImage = static::fromBlank($newWidth, $newHeight, self::RGB(0, 0, 0, 127))->getImageResource();
			imagecopyresampled(
				$newImage, $this->getImageResource(),
				0, 0, $width < 0 ? $newWidth - 1 : 0, $height < 0 ? $newHeight - 1 : 0,
				$newWidth, $newHeight, $width < 0 ? -$newWidth : $newWidth, $height < 0 ? -$newHeight : $newHeight
			);
			$this->image = $newImage;
		}
		return $this;
	}



	/**
	 * Calculates dimensions of resized image.
	 * @param  mixed  source width
	 * @param  mixed  source height
	 * @param  mixed  width in pixels or percent
	 * @param  mixed  height in pixels or percent
	 * @param  int    flags
	 * @return array
	 */
	public static function calculateSize($srcWidth, $srcHeight, $newWidth, $newHeight, $flags = self::FIT)
	{
		if (substr($newWidth, -1) === '%') {
			$newWidth = round($srcWidth / 100 * abs($newWidth));
			$percents = TRUE;
		} else {
			$newWidth = (int) abs($newWidth);
		}

		if (substr($newHeight, -1) === '%') {
			$newHeight = round($srcHeight / 100 * abs($newHeight));
			$flags |= empty($percents) ? 0 : self::STRETCH;
		} else {
			$newHeight = (int) abs($newHeight);
		}

		if ($flags & self::STRETCH) { // non-proportional
			if (empty($newWidth) || empty($newHeight)) {
				throw new InvalidArgumentException('For stretching must be both width and height specified.');
			}

			if ($flags & self::SHRINK_ONLY) {
				$newWidth = round($srcWidth * min(1, $newWidth / $srcWidth));
				$newHeight = round($srcHeight * min(1, $newHeight / $srcHeight));
			}

		} else {  // proportional
			if (empty($newWidth) && empty($newHeight)) {
				throw new InvalidArgumentException('At least width or height must be specified.');
			}

			$scale = array();
			if ($newWidth > 0) { // fit width
				$scale[] = $newWidth / $srcWidth;
			}

			if ($newHeight > 0) { // fit height
				$scale[] = $newHeight / $srcHeight;
			}

			if ($flags & self::FILL) {
				$scale = array(max($scale));
			}

			if ($flags & self::SHRINK_ONLY) {
				$scale[] = 1;
			}

			$scale = min($scale);
			$newWidth = round($srcWidth * $scale);
			$newHeight = round($srcHeight * $scale);
		}

		return array(max((int) $newWidth, 1), max((int) $newHeight, 1));
	}



	/**
	 * Crops image.
	 * @param  mixed  x-offset in pixels or percent
	 * @param  mixed  y-offset in pixels or percent
	 * @param  mixed  width in pixels or percent
	 * @param  mixed  height in pixels or percent
	 * @return Image  provides a fluent interface
	 */
	public function crop($left, $top, $width, $height)
	{
		list($left, $top, $width, $height) = static::calculateCutout($this->getWidth(), $this->getHeight(), $left, $top, $width, $height);
		$newImage = static::fromBlank($width, $height, self::RGB(0, 0, 0, 127))->getImageResource();
		imagecopy($newImage, $this->getImageResource(), 0, 0, $left, $top, $width, $height);
		$this->image = $newImage;
		return $this;
	}



	/**
	 * Calculates dimensions of cutout in image.
	 * @param  mixed  source width
	 * @param  mixed  source height
	 * @param  mixed  x-offset in pixels or percent
	 * @param  mixed  y-offset in pixels or percent
	 * @param  mixed  width in pixels or percent
	 * @param  mixed  height in pixels or percent
	 * @return array
	 */
	public static function calculateCutout($srcWidth, $srcHeight, $left, $top, $newWidth, $newHeight)
	{
		if (substr($newWidth, -1) === '%') {
			$newWidth = round($srcWidth / 100 * $newWidth);
		}
		if (substr($newHeight, -1) === '%') {
			$newHeight = round($srcHeight / 100 * $newHeight);
		}
		if (substr($left, -1) === '%') {
			$left = round(($srcWidth - $newWidth) / 100 * $left);
		}
		if (substr($top, -1) === '%') {
			$top = round(($srcHeight - $newHeight) / 100 * $top);
		}
		if ($left < 0) {
			$newWidth += $left; $left = 0;
		}
		if ($top < 0) {
			$newHeight += $top; $top = 0;
		}
		$newWidth = min((int) $newWidth, $srcWidth - $left);
		$newHeight = min((int) $newHeight, $srcHeight - $top);
		return array($left, $top, $newWidth, $newHeight);
	}



	/**
	 * Sharpen image.
	 * @return Image  provides a fluent interface
	 */
	public function sharpen()
	{
		imageconvolution($this->getImageResource(), array( // my magic numbers ;)
			array( -1, -1, -1 ),
			array( -1, 24, -1 ),
			array( -1, -1, -1 ),
		), 16, 0);
		return $this;
	}



	/**
	 * Puts another image into this image.
	 * @param  Image
	 * @param  mixed  x-coordinate in pixels or percent
	 * @param  mixed  y-coordinate in pixels or percent
	 * @param  int  opacity 0..100
	 * @return Image  provides a fluent interface
	 */
	public function place(Image $image, $left = 0, $top = 0, $opacity = 100)
	{
		$opacity = max(0, min(100, (int) $opacity));

		if (substr($left, -1) === '%') {
			$left = round(($this->getWidth() - $image->getWidth()) / 100 * $left);
		}

		if (substr($top, -1) === '%') {
			$top = round(($this->getHeight() - $image->getHeight()) / 100 * $top);
		}

		if ($opacity === 100) {
			imagecopy(
				$this->getImageResource(), $image->getImageResource(),
				$left, $top, 0, 0, $image->getWidth(), $image->getHeight()
			);

		} elseif ($opacity <> 0) {
			imagecopymerge(
				$this->getImageResource(), $image->getImageResource(),
				$left, $top, 0, 0, $image->getWidth(), $image->getHeight(),
				$opacity
			);
		}
		return $this;
	}



	/**
	 * Saves image to the file.
	 * @param  string  filename
	 * @param  int  quality 0..100 (for JPEG and PNG)
	 * @param  int  optional image type
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function save($file = NULL, $quality = NULL, $type = NULL)
	{
		if ($type === NULL) {
			switch (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
			case 'jpg':
			case 'jpeg':
				$type = self::JPEG;
				break;
			case 'png':
				$type = self::PNG;
				break;
			case 'gif':
				$type = self::GIF;
			}
		}

		switch ($type) {
		case self::JPEG:
			$quality = $quality === NULL ? 85 : max(0, min(100, (int) $quality));
			return imagejpeg($this->getImageResource(), $file, $quality);

		case self::PNG:
			$quality = $quality === NULL ? 9 : max(0, min(9, (int) $quality));
			return imagepng($this->getImageResource(), $file, $quality);

		case self::GIF:
			return $file === NULL ? imagegif($this->getImageResource()) : imagegif($this->getImageResource(), $file); // PHP bug #44591

		default:
			throw new InvalidArgumentException("Unsupported image type.");
		}
	}



	/**
	 * Outputs image to string.
	 * @param  int  image type
	 * @param  int  quality 0..100 (for JPEG and PNG)
	 * @return string
	 */
	public function toString($type = self::JPEG, $quality = NULL)
	{
		ob_start();
		$this->save(NULL, $quality, $type);
		return ob_get_clean();
	}



	/**
	 * Outputs image to string.
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->toString();

		} catch (\Exception $e) {
			Diagnostics\Debugger::toStringException($e);
		}
	}



	/**
	 * Outputs image to browser.
	 * @param  int  image type
	 * @param  int  quality 0..100 (for JPEG and PNG)
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function send($type = self::JPEG, $quality = NULL)
	{
		if ($type !== self::GIF && $type !== self::PNG && $type !== self::JPEG) {
			throw new InvalidArgumentException("Unsupported image type.");
		}
		header('Content-Type: ' . image_type_to_mime_type($type));
		return $this->save(NULL, $quality, $type);
	}



	/**
	 * Call to undefined method.
	 *
	 * @param  string  method name
	 * @param  array   arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public function __call($name, $args)
	{
		$function = 'image' . $name;
		if (function_exists($function)) {
			foreach ($args as $key => $value) {
				if ($value instanceof self) {
					$args[$key] = $value->getImageResource();

				} elseif (is_array($value) && isset($value['red'])) { // rgb
					$args[$key] = imagecolorallocatealpha(
						$this->getImageResource(),
						$value['red'], $value['green'], $value['blue'], $value['alpha']
					);
				}
			}
			array_unshift($args, $this->getImageResource());

			$res = call_user_func_array($function, $args);
			return is_resource($res) && get_resource_type($res) === 'gd' ? $this->setImageResource($res) : $res;
		}

		return parent::__call($name, $args);
	}

}



/**
 * The exception that indicates invalid image file.
 */
class UnknownImageFileException extends \Exception
{
}
