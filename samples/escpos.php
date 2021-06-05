<?php


// composer autoload
if ( !class_exists('\Nettools\EscPos\EscPosHelper') )
    if ( file_exists(__DIR__ . '/../../../autoload.php') )
        include_once __DIR__ . '/../../../autoload.php';
    else
        die('Composer autoload is not found in ' . realpath(__DIR__ . '/../../../'));



use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\CapabilityProfile;




function output($escpos)
{
	$html = "<a href=\"rawbt:base64," . base64_encode($escpos) . "\">Send to ESC/POS printer</a><br>";
	
	echo $html;
}




?>
<html>
    <head>
        <title>ESC/POS Helper</title>
    </head>
<body>
<?php
	
	
	
// ****** UPDATE THIS LINE WITH APPROPRIATE DRIVER *****
//$driver = new \Nettools\EscPos\Drivers\Epson_TM_P80();
$driver = new \Nettools\EscPos\Drivers\Hoin_HOP_E300();
// ****** UPDATE THIS LINE WITH APPROPRIATE DRIVER *****


// create helper object	
$escpos_helper = new \Nettools\EscPos\EscPosHelper($driver);
	
	
try
{
	if ( $f = $_FILES['image'] )
	{
		if ( $f['error'] == UPLOAD_ERR_OK )
		{
			try
			{
				// take file extension
				$ext = strtolower(substr(strrchr(strtolower($f['name']), '.'), 1));

				// read image with appropriate function
				switch ( $ext )
				{
					case 'gif' :
						$image = imagecreatefromgif($f['tmp_name']);
						break;

					case 'jpg' :
						$image = imagecreatefromjpeg($f['tmp_name']);
						break;

					case 'png' :
						$image = imagecreatefrompng($f['tmp_name']);
						break;

					default:
						echo "<h1 style=\"color:red; font-weight:bold\">Unsupported image file type</h1>";
						break;					
				}


				// dither image to black & white
				$escpos = $escpos_helper->image($image, 0.75);
				output($escpos);
			}
			finally
			{
				unlink($f['tmp_name']);
			}
		}
	}



	// output a char map
	else if ( isset($_REQUEST['charmap']) )
	{
		function compactCharTable($printer, $start = 4, $header = false)
		{
			/* Output a compact character table for the current encoding */
			$chars = str_repeat(' ', 256);
			for ($i = 0; $i < 255; $i++) {
				$chars[$i] = ($i > 32 && $i != 127) ? chr($i) : ' ';
			}
			if ($header) {
				$printer -> setEmphasis(true);
				$printer -> textRaw("  0123456789ABCDEF0123456789ABCDEF\n");
				$printer -> setEmphasis(false);
			}
			for ($y = $start; $y < 8; $y++) {
				$printer -> setEmphasis(true);
				$printer -> textRaw(strtoupper(dechex($y * 2)) . " ");
				$printer -> setEmphasis(false);
				$printer -> textRaw(substr($chars, $y * 32, 32) . "\n");
			}
		}




		/**
		 * This demo prints out supported code pages on your printer. This is intended
		 * for debugging character-encoding issues: If your printer does not work with
		 * a built-in capability profile, you need to check its documentation for
		 * supported code pages.
		 *
		 * These are then loaded into a capability profile, which maps code page
		 * numbers to iconv encoding names on your particular printer. This script
		 * will print all configured code pages, so that you can check that the chosen
		 * iconv encoding name matches the actual code page contents.
		 *
		 * If this is correctly set up for your printer, then the driver will try its
		 * best to map UTF-8 text into these code pages for you, allowing you to accept
		 * arbitrary input from a database, without worrying about encoding it for the printer.
		 */

		// Enter connector and capability profile (to match your printer)
		$connector = new DummyPrintConnector();

		try
		{
			$profile = CapabilityProfile::load("default");

			/* Print a series of receipts containing i18n example strings - Code below shouldn't need changing */
			$printer = new Mike42\Escpos\Printer($connector, $profile);
			$codePages = $profile -> getCodePages();

			$table = $_REQUEST['charmap'];
			$page = $codePages[$table];


			/* Change printer code page */
			$printer -> selectCharacterTable(255);
			$printer -> selectCharacterTable($table);
			/* Select & print a label for it */
			$label = $page -> getId();
			/*if (!$page -> isEncodable()) {
				$label= " (not supported)";
			}*/
			$printer -> setEmphasis(true);
			$printer -> textRaw("Table $table: $label\n");
			$printer -> setEmphasis(false);
			/*if (!$page -> isEncodable() && !$verbose) {
				continue; // Skip non-recognised
			}*/
			/* Print a table of available characters (first table is larger than subsequent ones */
			compactCharTable($printer, 1, true);


			// save output before finalizing connector
			$escpos = $connector->getData();

			// output
			output($escpos);

			// cut
			$printer -> cut();
		}
		finally
		{
			$printer -> close();
		}
	}




	// output a barcode
	else if ( isset($_REQUEST['barcode']) )
	{
		$escpos = $escpos_helper->barcode($_REQUEST['value'], (int)$_REQUEST['barcode']);

		// output
		output($escpos);
	}




	// output a qrcode
	else if ( isset($_REQUEST['qrcode']) )
	{
		$escpos = $escpos_helper->qrcode($_REQUEST['qrcode'], (int)($_REQUEST['version']), (int)($_REQUEST['size']), (int)($_REQUEST['ec']));

		// output
		output($escpos);
	}

	
	// output
	print_r("<div style=\"padding:5px; background-color:lightgray;\">" . $html . "</div>");
}


// catching other exceptions
catch (Throwable $e)
{
    echo "<h1 style=\"color:red; font-weight:bold\">" . get_class($e) . "</h1>";
    echo "<pre style=\"padding:5px; background-color:lightgray;\">" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}
?>
	
<p><em>Printer driver : <?php echo substr(strrchr(get_class($driver), '\\'),1); ?></em></p>

	
<hr>


<form method="post" enctype="multipart/form-data" action="escpos.php">
	<p><label>Image : <input type="file" accept="image/*" name="image"></label><input name="submit" type="submit"></p>
</form>
	
	
<hr>
	
	
<p>Samples from <em>mike42/escpos-php</em></p>
<form method="post" action="escpos.php">
	<select name="charmap">
		<option></option>
		<?php
		// compute list of code pages
		$profile = CapabilityProfile::load("default");
		$codePages = $profile -> getCodePages();


		foreach ( $codePages as $code => $page )
			echo "<option value=\"$code\">{$page->getId()}</option>";
		?>
	</select>
	<input type="submit" value="Print this characters map">
</form>

	
<hr>
	
	
<form method="post" action="escpos.php">
	<select name="barcode">
		<option value="65">UPC-A (11-12 digits)</option>
		<option value="66">UPC-E (11-12 digits)</option>
		<option value="67">EAN13 (12-13 digits)</option>
		<option value="68">EAN8 (7-8 digits)</option>
		<option value="69">CODE39 (1-255 chars)</option>
		<option value="71">CODABAR (1-255 digits)</option>
		<option value="72">CODE93 (1-255 digits)</option>
		<option value="73">CODE128 (2-255 digits)</option>
	</select>
	<input type="text" name="value">
	<input type="submit" name="submit" value="Print barcode">
</form>

	
<hr>

	
<form method="post" action="escpos.php">
	<label>Value :<input type="text" name="qrcode"></label><br>
	<label>Version/model :<input type="text" name="version" placeholder="1-19 (Hoin) or 49-50 (Epson Model 1 or 2)"></label><br>
	<label>Size : <input type="text" name="size" value="3"></label><br>
	<label>Error correction : <input type="text" name="ec" value="" placeholder="1-4 (Hoin) or 48-51 (Epson)"></label><br>
	<input type="submit" name="submit" value="Print qrcode">
</form>