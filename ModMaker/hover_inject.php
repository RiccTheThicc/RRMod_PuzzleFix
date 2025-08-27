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

$inputPath   = "C:\\222\\RiccAssets\\IslandsofInsight\\Content\\ASophia\\UI\\HUD\\Map\\T_Map_Zones_Hover.uexp";
$implantPath = "X:\\Dropbox\\Modding\\IslandsOfInsight\\OfflineRestoredMod\\ModMaker\\_map\\edited\\implant_Hover.png";
$outputPath  = "R:\\Garbage\\T_Map_Zones_Hover.uexp";
//$outputPath  = "X:\\Dropbox\\Modding\\IslandsOfInsight\\OfflineRestoredMod\\UnrealPakMini\\IslandsofInsight\\Content\\ASophia\\UI\\HUD\\Map\\T_Map_Zones_DF.uexp";

$implantStartX = 0;
$implantStartY = 260;
$implantSizeX  = 4096;
$implantSizeY  = 3830;

$shitOffsetX = 0; // -24;
$shitOffsetY = 0; //   0;

printf("Loading %s\n", $inputPath);
$file = file_get_contents($inputPath);
$bytes = array_map(function($x){ return ord($x); }, str_split($file, 1));

$UEXP_HEADER_SIZE = 426;
$TEXTURE_HEADER_SIZE = 0;
$UEXP_SUFFIX_SIZE = 4;

$rawBytes = array_slice($bytes, $UEXP_HEADER_SIZE + $TEXTURE_HEADER_SIZE, -$UEXP_SUFFIX_SIZE);

$pixelCount = count($rawBytes);
$imgWidth  = 0;
$imgHeight = 0;

// Better to parse the texture header of course but who cares?
$imgWidth = intval(round(sqrt($pixelCount)));
$imgHeight = $imgWidth;

printf("Image is %d x %d (%d pixels)\n", $imgWidth, $imgHeight, $pixelCount);
printf("\n");

//$gd = imagecreatetruecolor($imgWidth, $imgHeight);
//for($x = 0; $x < $imgWidth; ++$x){
//	printf("Processing col %d\n", $x);
//	for($y = 0; $y < $imgHeight; ++$y){
//		$pixelIndex = $y * $imgWidth + $x;
//		$pixelColor = $rawBytes[$pixelIndex];
//		$clr = imagecolorallocatealpha($gd, $pixelColor, $pixelColor, $pixelColor, 0);
//		imagesetpixel($gd, $x, $y, $clr);
//	}
//}
//imagepng($gd, "R:\\Garbage\\p.png");

$gd = imagecreatefrompng($implantPath);
$implantCount = 0;
for($x = $implantStartX; $x < $implantStartX + $implantSizeX; ++$x){
	printf("Processing col %d\n", $x);
	for($y = $implantStartY; $y < $implantStartY + $implantSizeY; ++$y){
		$rgb = imagecolorat($gd, $x, $y);
		//$alpha = ($rgb >> 24) & 0xFF;
		////if($alpha == 0x7F){
		////	continue;
		////}
		//
		//$r = ($rgb >> 16) & 0xFF;
		//$g = ($rgb >>  8) & 0xFF;
		//$b = ($rgb >>  0) & 0xFF;
		$cs = imagecolorsforindex($gd, $rgb);
		
		$val = intval(round(((float)($cs["red"] + $cs["green"] + $cs["blue"])) / 3.0));
		$val = max(0, min(255, $val));
		if($val != 255 && $val % 8 != 0){
			printf("[ERROR] Implant's pixel %d,%d has bad color %d,%d,%d (%X)\n", $x, $y, $r, $g, $b, $rgb);
			exit(1);
		}
		
		$startByte = $UEXP_HEADER_SIZE + $TEXTURE_HEADER_SIZE + (($y + $shitOffsetY) * $imgWidth + ($x + $shitOffsetX)) * 1;
		$bytes[$startByte] = $val;
		
		++$implantCount;
	}
}

printf("Implanted %d pixels, saving...\n", $implantCount);
$final = implode("", array_map(function($x){ return chr($x); }, $bytes));
WriteFileSafe($outputPath, $final, true);
