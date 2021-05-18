<?php
/**
 * EscPosHelper
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos;



use Nettools\Core\Helpers\ImagingHelper;




/**
 * Helper class to deal with escpos
 */
class EscPosHelper {
	
	
	const BARCODE_UPCA = 65;
	const BARCODE_UPCE = 66;
	const BARCODE_EAN13 = 67;
	const BARCODE_EAN8 = 68;
	const BARCODE_CODE39 = 69;
	const BARCODE_CODABAR = 71;
	const BARCODE_CODE93 = 72;
	const BARCODE_CODE128 = 73;

	
	
	/**
	 * Print 2D barcode (qrcode)
	 *
	 * @param string $value Qrcode value
	 */
	public static function qrcode($value)
	{
		$l = strlen($value);
		return "\eZ" . chr(1) . chr(1) . chr(2) . chr($l % 256) . chr((int)($l / 256)) . $value;
	}
	
	
	
	/**
	 * Print 1D barcode
	 *
	 * @param string $value Barcode value
	 * @param int $barcode Barcode kind
	 */
	public static function barcode($value, $barcode)
	{
		switch ( $barcode )
		{
			case self::BARCODE_UPCA : 
				if ( !preg_match("/^[0-9]{11,12}$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');
				
				break;
				
				
			case self::BARCODE_UPCE :
				if ( !preg_match("/^([0-9]{6,8}|[0-9]{11,12})$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');
				
				break;
				
				
			case self::BARCODE_EAN13 :
				if ( !preg_match("/^[0-9]{12,13}$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');
				
				break;
				
				
			case self::BARCODE_EAN8	:
				if ( !preg_match("/^[0-9]{7,8}$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');

				break;
				
				
			case self::BARCODE_CODE39 :
				if ( !preg_match("/^([0-9A-Z \$\%\+\-\.\/]+|\*[0-9A-Z \$\%\+\-\.\/]+\*)$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');

				break;
				
				
			case self::BARCODE_CODABAR :
				if ( !preg_match("/^[A-Da-d][0-9\$\+\-\.\/\:]+[A-Da-d]$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');

				break;
				
				
			case self::BARCODE_CODE93 :
				if ( !preg_match("/^[\\x00-\\x7F]+$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');

				break;
				
				
			case self::BARCODE_CODE128 :
				if ( !preg_match("/^\{[A-C][\\x00-\\x7F]+$/", $value) )
					throw new BarcodeFormatException('Barcode value format error');
		
				break;			
		}
		
		
		return "\x1Dk" . chr($barcode) . chr(strlen($value)) . $value;
	}
	
	
	
	/**
	 * Print UPCA barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_upca($value)
	{
		return self::barcode($value, self::BARCODE_UPCA);
	}
	
	
	
	/**
	 * Print UPCE barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_upce($value)
	{
		return self::barcode($value, self::BARCODE_UPCE);
	}
	
	
	
	/**
	 * Print EAN13 barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_ean13($value)
	{
		return self::barcode($value, self::BARCODE_EAN13);
	}
	
	
	
	/**
	 * Print EAN8 barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_ean8($value)
	{
		return self::barcode($value, self::BARCODE_EAN8);
	}
	
	
	
	/**
	 * Print CODE39 barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_code39($value)
	{
		return self::barcode($value, self::BARCODE_CODE39);
	}
	
	
	
	/**
	 * Print CODABAR barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_codabar($value)
	{
		return self::barcode($value, self::BARCODE_CODABAR);
	}
	
	
	
	/**
	 * Print CODE93 barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_code93($value)
	{
		return self::barcode($value, self::BARCODE_CODE93);
	}
	
	
	
	/**
	 * Print CODE128 barcode
	 *
	 * @param string $value Barcode value
	 */
	public static function barcode_code128($value)
	{
		return self::barcode($value, self::BARCODE_CODE128);
	}
	
	
	
	/**
	 * Get data bytes for an image ready to be sent to ESCPOS printer
	 *
	 * @param resource $image
	 * @param int $printerResolution X-resolution of printer
	 * @param float $dither Quantity of dither for black/white conversion
	 * @param bool $useGraphics Use GS(k graphics commands (unsuitable for printers with poor ESC/POS compatibility) ; if false, GSv0 commands will be used (raster bit image)
	 * @return string Return a string to be sent to printer
	 */
	public static function getImageBytes($image, $printerResolution, $dither = 0.8, $useGraphics = false)
	{
		if ( imagesx($image) > $printerResolution )
			$image = ImagingHelper::image_resize($image, imagesx($image), imagesy($image), $printerResolution, NULL);


		// create a gd indexed color converter
		$converter = new \GDIndexedColorConverter();

		// the color palette
		$palette = array(
			array(0, 0, 0),
			array(255, 255, 255)
		);

		// convert the image to indexed color mode
		$new_image = $converter->convertToIndexedColor($image, $palette, $dither);

		// save the new image
		$tmp = tempnam(sys_get_temp_dir(), 'escpos-helper-dither');
		imagepng($new_image, $tmp, 0);

		try
		{
			// create connector and printer
			$connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
			$printer = new \Mike42\Escpos\Printer($connector);

			
			try
			
			{
				// load image with escpos lib 
				$img = \Mike42\Escpos\EscposImage::load($tmp, false);
				
				// render image with graphics new method or bitImage for larger compatibility
				if ( $useGraphics )
					$printer->graphics($img);				
				else
					$printer->bitImage($img);
				
				// get data from connector				
				return $connector->getData();
			}
			finally
			{
				$printer->close();
			}
		}
		finally
		{
			unlink($tmp);
		}
	}
	
}

?>