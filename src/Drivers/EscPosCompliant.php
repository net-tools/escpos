<?php
/**
 * EscPosCompliant
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos\Drivers;




/**
 * Class for esc/pos compliant printer driver
 */
abstract class EscPosCompliant extends Driver {
	
	
	/**
	 * Get printer resolution (number of dots per line)
	 *
	 * @return int
	 */
	function getPrinterResolution()
	{
		return 576;
	}
	
	
	
	/** 
	 * Do send job with appropriate escpos command, depending of printer driver capabilities
	 *
	 * @param \Mike42\Escpos\EscposImage $img Image object
	 * @param \Mike42\Escpos\Printer $printer Printer object to send through
	 */
	abstract function printImageWithMike42EscPosPrinter(\Mike42\Escpos\EscposImage $img, \Mike42\Escpos\Printer $printer);
	
	

	/**
	 * Get printer resolution (number of dots per line)
	 *
	 * @param string $file Filepath to image file (png)
	 * @return string
	 */
	function getImageBytes($file)
	{
		// create connector and printer
		$connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
		$printer = new \Mike42\Escpos\Printer($connector);


		try
		{
			// load image with escpos lib 
			$img = \Mike42\Escpos\EscposImage::load($file, false);


			// get image bytes to print
			$this->printImageWithMike42EscPosPrinter($img, $printer);


			// get data from connector				
			return $connector->getData();
		}
		finally
		{
			$printer->close();
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
	public function barcode($value, $barcode)
	{
		return "\x1Dk" . chr($barcode) . chr(strlen($value)) . $value;
	}		
}

?>