<?php
/**
 * Epson_TM_P80
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\EscPos\Drivers;





/**
 * Driver class for Epson TM-P80
 */
class Epson_TM_P80 extends EscPosCompliant {
	
	const QRCODE_MODEL1 = 49;
	const QRCODE_MODEL2 = 50;
	
	const QRCODE_EC_L = 48;
	const QRCODE_EC_M = 49;
	const QRCODE_EC_Q = 50;
	const QRCODE_EC_H = 51;
	
	
	
	
	/** 
	 * Do send job with appropriate escpos command, depending of printer driver capabilities
	 *
	 * @param \Mike42\Escpos\EscposImage $img Image object
	 * @param \Mike42\Escpos\Printer $printer Printer object to send through
	 */
	function printImageWithMike42EscPosPrinter(\Mike42\Escpos\EscposImage $img, \Mike42\Escpos\Printer $printer)
	{
		$printer->graphics($img);
	}
	
	

	/**
	 * Print 2D barcode (qrcode)
	 *
	 * @param string $value Qrcode value
	 * @param int $kind QR code kind (49 for model 1, 50 for model 2)
	 * @param int $size Module size (size of one small square in the qrcode)
	 * @param int $ec Error correction level
	 * @return string Returns ESC/POS string with 2D barcode output
	 */
	public function qrcode($value, $kind = NULL, $size = 3, $ec = NULL)
	{
		if ( is_null($kind) )
			$kind = self::QRCODE_MODEL2;
		if ( is_null($ec) )
			$ec = self::QRCODE_EC_L;
        if ( is_null($size) )
            $size = 3;
		
		
		// nb : "1" char = ascii code 49 ; '0' char = ascii code 48
		
		// function 165 : select QR code model 
		$s = self::GS . '(k' . chr(4) . chr(0) . "1" . chr(65) . chr($kind) . chr(0);
		
		// function 167 : set size
		$s .= self::GS . '(k' . chr(3) . chr(0) . "1" . chr(67) . chr($size);
		
		// function 169 : set EC
		$s .= self::GS . '(k' . chr(3) . chr(0) . "1" . chr(69) . chr($ec);

		// function 180 : store qrcode data
		$l = strlen($value) + 3;
		$s .= self::GS . '(k' . chr($l % 256) . chr((int)($l / 256)) . "1" . chr(80) . '0' . $value;
		
		// function 181 : output qrcode
		$s .= self::GS . '(k' . chr(3) . chr(0) . "1" . chr(81) . chr(48);

		return $s;
	}
}

?>