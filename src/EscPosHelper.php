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
	
	/**
	 * Get data bytes for an image ready to be sent to ESCPOS printer
	 *
	 * @param resource $image
	 * @param int $printerResolution X-resolution of printer
	 * @param float $dither Quantity of dither for black/white conversion
	 * @return string Return a string to be sent to printer
	 */
	public static function getImageBytes($image, $printerResolution, $dither = 0.8)
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
			$connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
			$printer = new \Mike42\Escpos\Printer($connector);

			try
			{
				$img = \Mike42\Escpos\EscposImage::load($tmp, false);
				$printer->graphics($img);				
				return $connector->getData();
			}
			finally
			{
				$connector->finalize();
			}
		}
		finally
		{
			unlink($tmp);
		}
	}
	
}

?>