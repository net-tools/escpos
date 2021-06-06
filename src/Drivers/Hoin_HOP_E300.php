<?php
/**
 * Hoin_HOP_E300
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos\Drivers;





/**
 * Driver class for Hoin HOP-E300
 */
class Hoin_HOP_E300 extends EscPosCompliant {
	
	const QRCODE_EC_L = 1;
	const QRCODE_EC_M = 2;
	const QRCODE_EC_Q = 3;
	const QRCODE_EC_H = 4;
	
	
	
	
	/** 
	 * Do send job with appropriate escpos command, depending of printer driver capabilities
	 *
	 * @param \Mike42\Escpos\EscposImage $img Image object
	 * @param \Mike42\Escpos\Printer $printer Printer object to send through
	 */
	function printImageWithMike42EscPosPrinter(\Mike42\Escpos\EscposImage $img, \Mike42\Escpos\Printer $printer)
	{
		$printer->bitImageColumnFormat($img);
	}
	
	

	/**
	 * Print 2D barcode (qrcode)
	 *
	 * @param string $value Qrcode value
	 * @param int $kind QR code version (1 to 19)
	 * @param int $size Module size (size of one small square in the qrcode)
	 * @param int $ec Error correction level
	 * @return string Returns ESC/POS string with 2D barcode output
	 */
	public function qrcode($value, $kind = NULL, $size = 3, $ec = NULL)
	{
		if ( is_null($kind) )
			throw new \Nettools\EscPos\BarcodeFormatException('QRcode version (1-19) is mandatory');
		if ( is_null($ec) )
			$ec = self::QRCODE_EC_L;
        if ( is_null($size) )
            $size = 3;
	
		
		$l = strlen($value);
		return "\eZ" . chr($kind) . chr($ec) . chr($size) . chr($l % 256) . chr((int)($l / 256)) . $value;
	}
}

?>