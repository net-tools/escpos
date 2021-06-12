<?php
/**
 * Driver
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos\Drivers;



use \Nettools\Core\Helpers\ImagingHelper;





/**
 * Base class for esc/pos printer driver
 */
abstract class Driver {
	
	const ESC = "\e";
	const GS = "\x1D";
	const FS = "\x1C";
	
	
	/**
	 * Get printer resolution (number of dots per line)
	 *
	 * @return int
	 */
	abstract function getPrinterResolution();
	
	
		
	/**
	 * Get printer resolution (number of dots per line)
	 *
	 * @param string $file Filepath to image file (png)
	 * @return string
	 */
	abstract function getImageBytes($file);
	
	
    
	/**
	 * Get data bytes for a PNG image to send to an ESCPOS printer
	 *
	 * @param string $file Full path to image file
	 * @param float $dither Quantity of dither for black/white conversion
	 * @return string Return a string to be sent to printer
	 */
    public function imageFromPng($file, $dither = 0.8)
    {
        $img = imagecreatefrompng($file);
        return $this->image(img, $dither);
    }
		
    
    
	/**
	 * Get data bytes for a JPEG image to send to an ESCPOS printer
	 *
	 * @param string $file Full path to image file
	 * @param float $dither Quantity of dither for black/white conversion
	 * @return string Return a string to be sent to printer
	 */
    public function imageFromJpeg($file, $dither = 0.8)
    {
        $img = imagecreatefromjpeg($file);
        return $this->image(img, $dither);
    }
		
    
    
	/**
	 * Get data bytes for an image to send to an ESCPOS printer
	 *
	 * @param resource $image
	 * @param float $dither Quantity of dither for black/white conversion
	 * @return string Return a string to be sent to printer
	 */
	public function image($image, $dither = 0.8)
	{
		if ( imagesx($image) > $this->getPrinterResolution() )
			$image = ImagingHelper::image_resize($image, imagesx($image), imagesy($image), $this->getPrinterResolution(), NULL);


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
			// get image bytes
			return $this->getImageBytes($tmp);
		}
		finally
		{
			unlink($tmp);
		}
	}
	
	
	
	/**
	 * Print 2D barcode (qrcode)
	 *
	 * @param string $value Qrcode value
	 * @param int $kind QR code kind 
	 * @param int $size Module size (size of one small square in the qrcode)
	 * @param int $ec Error correction level
	 * @return string Returns ESC/POS string with 2D barcode output
	 */
	abstract function qrcode($value, $kind = NULL, $size = 3, $ec = NULL);
	
	
	
	/**
	 * Print 1D barcode
	 *
	 * @param string $value Barcode value
	 * @param int $barcode Barcode kind
	 * @return string Returns ESC/POS string with 1D barcode output
	 * @throws \Nettools\EscPos\BarcodeFormatException Thrown if value format is wrong according to the barcode kind
	 */
	abstract function barcode($value, $barcode);
}

?>