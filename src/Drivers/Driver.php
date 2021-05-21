<?php
/**
 * Driver
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos\Drivers;





/**
 * Base class for esc/pos printer driver
 */
abstract class Driver {
	
	const ESC = "\e";
	const GS = "\x1D";
	const FS = "\x1C";
	
	
		
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