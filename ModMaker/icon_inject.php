<?php

include_once("include\\pjson_parse.php");
include_once("include\\config.php");
include_once("include\\file_io.php");
include_once("include\\puzzleDecode.php");
include_once("include\\ryoanjiDecode.php");
include_once("include\\timex.php");
include_once("include\\stringex.php");
include_once("include\\lookup.php");
include_once("include\\profile.php");
include_once("include\\drawmap.php");
include_once("include\\renderCache.php");
include_once("include\\cluster.php");
include_once("include\\stats.php");
include_once("include\\savefile.php");
include_once("include\\playerCard.php");
include_once("include\\steam.php");
include_once("include\\jsonex.php");
include_once("include\\uassetParse.php");
include_once("include\\uassetHelper.php");
include_once("include\\chests.php");

function ExportIcon(string $inputAsset, string $outputPngPath, int $sx = 256, int $sy = 256){
	$inputUexpPath = $inputAsset . ".uexp";
	$uexp = file_get_contents($inputUexpPath);
	static $uexpHeaderSize = 285;
	
	$gd = imagecreatetruecolor($sx, $sy);
	imagealphablending($gd, false);
	imagesavealpha($gd, true);
	for($x = 0; $x < $sx; ++$x){
		for($y = 0; $y < $sy; ++$y){
			//$rgb = imagecolorat($gd, $x, $y);
			//$cs = imagecolorsforindex($gd, $rgb);
			$b = ord($uexp[$uexpHeaderSize + ($x * $sy + $y) * 4 + 0]);
			$g = ord($uexp[$uexpHeaderSize + ($x * $sy + $y) * 4 + 1]);
			$r = ord($uexp[$uexpHeaderSize + ($x * $sy + $y) * 4 + 2]);
			$a = ord($uexp[$uexpHeaderSize + ($x * $sy + $y) * 4 + 3]);
			$a = intval((255 - $a) / 2);
			$color = imagecolorallocatealpha($gd, $r, $g, $b, $a);
			imagesetpixel($gd, $y, $x, $color);
		}
	}
	printf("Rendering %s...\n", $outputPngPath);
	SaveImageAs($gd, $outputPngPath);
}

function InjectIcon(string $inputAsset, string $implantPath, string $outputAsset){
	$inputUassetPath = $inputAsset . ".uasset";
	$inputUexpPath   = $inputAsset . ".uexp";
	
	$shortNameInput  = GetFileNameWithoutExtension($inputAsset);
	$shortNameOutput = GetFileNameWithoutExtension($outputAsset);
	if(strlen($shortNameInput) != strlen($shortNameOutput)){
		printf("[ERROR] Can't implant %s + %s -> %s: file name lengths must match (%d vs %d).\n", $inputAsset, $implantPath, $outputAsset, strlen($shortNameInput), strlen($shortNameOutput));
		exit(1);
	}
	
	$uasset  = file_get_contents($inputUassetPath);
	$oldUexp = file_get_contents($inputUexpPath);
	
	// Format is BGRA, 4 bytes per pixel starting at offset 285.
	static $uexpHeaderSize = 285;
	$uexp = substr($oldUexp, 0, $uexpHeaderSize);
	$ptr = $uexpHeaderSize;
	
	$gd = imagecreatefrompng($implantPath);
	imagealphablending($gd, false);
	imagesavealpha($gd, true);
	$sx = imagesx($gd);
	$sy = imagesy($gd);
	printf("Image %s is %dx%d\n", $implantPath, $sx, $sy);
	for($x = 0; $x < $sx; ++$x){
		for($y = 0; $y < $sy; ++$y){
			$rgba = imagecolorat($gd, $y, $x);
			$a = ($rgba >> 24) & 0xFF;
			$r = ($rgba >> 16) & 0xFF;
			$g = ($rgba >>  8) & 0xFF;
			$b = ($rgba >>  0) & 0xFF;
			$newAlpha = ($a == 0 ? 255 : ($a == 127 ? 0 : (127 - $a) * 2));
			
			$uexp .= chr($b) . chr($g) . chr($r) . chr($newAlpha);
			$ptr += 4;
		}
	}
	
	$uexp .= substr($oldUexp, $ptr);
	
	//$rgb = imagecolorat($gd, 128, 128);
	////$cs = imagecolorsforindex($gd, $rgb);
	//printf("R %3d, G %3d, B %3d, alpha %3d\n", $r, $g, $b, $alpha);

	$uasset = str_replace($shortNameInput, $shortNameOutput, $uasset);
	$uexp   = str_replace($shortNameInput, $shortNameOutput, $uexp);
	
	$outputUassetPath = $outputAsset . ".uasset";
	$outputUexpPath   = $outputAsset . ".uexp";
	
	WriteFileSafe($outputUassetPath, $uasset, true);
	WriteFileSafe($outputUexpPath,   $uexp,   true);
}

//ExportIcon("..\\Assets\\MapMarker", "R:\\Garbage\\original.png");
//InjectIcon("..\\Assets\\MapMarker", "..\\Assets\\cirkul.png", "..\\Assets\\EnclaveEx");
//ExportIcon("..\\Assets\\EnclaveEx", "R:\\Garbage\\modified.png");

if($argc < 4){
	printf("Please give me command-line arguments: original icon path, inject pic path, output path\n");
	exit(1);
}

InjectIcon($argv[1], $argv[2], $argv[3]);

