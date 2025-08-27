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

$pixelColorToF16 = [
	"0x0000","0x1C05","0x2005","0x2207","0x2405","0x2506","0x2607","0x2708","0x2805","0x2885","0x2906","0x2986","0x2A07","0x2A87","0x2B08","0x2B88",
	"0x2C05","0x2C45","0x2C85","0x2CC5","0x2D06","0x2D46","0x2D86","0x2DC6","0x2E07","0x2E47","0x2E87","0x2EC7","0x2F08","0x2F48","0x2F88","0x2FC8",
	"0x3005","0x3025","0x3045","0x3065","0x3085","0x30A5","0x30C5","0x30E5","0x3106","0x3126","0x3146","0x3166","0x3186","0x31A6","0x31C6","0x31E6",
	"0x3207","0x3227","0x3247","0x3267","0x3287","0x32A7","0x32C7","0x32E7","0x3308","0x3328","0x3348","0x3368","0x3388","0x33A8","0x33C8","0x33E8",
	"0x3405","0x3415","0x3425","0x3435","0x3445","0x3455","0x3465","0x3475","0x3485","0x3495","0x34A5","0x34B5","0x34C5","0x34D5","0x34E5","0x34F5",
	"0x3506","0x3516","0x3526","0x3536","0x3546","0x3556","0x3566","0x3576","0x3586","0x3596","0x35A6","0x35B6","0x35C6","0x35D6","0x35E6","0x35F6",
	"0x3607","0x3617","0x3627","0x3637","0x3647","0x3657","0x3667","0x3677","0x3687","0x3697","0x36A7","0x36B7","0x36C7","0x36D7","0x36E7","0x36F7",
	"0x3708","0x3718","0x3728","0x3738","0x3748","0x3758","0x3768","0x3778","0x3788","0x3798","0x37A8","0x37B8","0x37C8","0x37D8","0x37E8","0x37F8",
	"0x3805","0x380D","0x3815","0x381D","0x3825","0x382D","0x3835","0x383D","0x3845","0x384D","0x3855","0x385D","0x3865","0x386D","0x3875","0x387D",
	"0x3885","0x388D","0x3895","0x389D","0x38A5","0x38AD","0x38B5","0x38BD","0x38C5","0x38CD","0x38D5","0x38DD","0x38E5","0x38ED","0x38F5","0x38FD",
	"0x3906","0x390E","0x3916","0x391E","0x3926","0x392E","0x3936","0x393E","0x3946","0x394E","0x3956","0x395E","0x3966","0x396E","0x3976","0x397E",
	"0x3986","0x398E","0x3996","0x399E","0x39A6","0x39AE","0x39B6","0x39BE","0x39C6","0x39CE","0x39D6","0x39DE","0x39E6","0x39EE","0x39F6","0x39FE",
	"0x3A07","0x3A0F","0x3A17","0x3A1F","0x3A27","0x3A2F","0x3A37","0x3A3F","0x3A47","0x3A4F","0x3A57","0x3A5F","0x3A67","0x3A6F","0x3A77","0x3A7F",
	"0x3A87","0x3A8F","0x3A97","0x3A9F","0x3AA7","0x3AAF","0x3AB7","0x3ABF","0x3AC7","0x3ACF","0x3AD7","0x3ADF","0x3AE7","0x3AEF","0x3AF7","0x3AFF",
	"0x3B08","0x3B10","0x3B18","0x3B20","0x3B28","0x3B30","0x3B38","0x3B40","0x3B48","0x3B50","0x3B58","0x3B60","0x3B68","0x3B70","0x3B78","0x3B80",
	"0x3B88","0x3B90","0x3B98","0x3BA0","0x3BA8","0x3BB0","0x3BB8","0x3BC0","0x3BC8","0x3BD0","0x3BD8","0x3BE0","0x3BE8","0x3BF0","0x3BF8","0x3C00",
];

function r16f_to_r32f(array $encoded){
    static $EXPONENT_LENGTH = 5;
    static $SIGNIFICAND_LENGTH = 10;
    static $EXPONENT_OFFSET = 15;
	static $MAX_EXPONENT = 16;
	
	$msb = ($encoded[0]);
	$lsb = ($encoded[1]);

	// Get the components of the double
	$sign = ($msb >> 7) & 0b1;                  // Sign is the first bit
	$exponent = ($msb >> 2) & 0b11111;          // Next 5 are the exponent
	$significand = $lsb | (($msb & 0b11) << 8); // Final 10 are the significand

	// Convert the significand to a float
	$decimal = 0;
	for ($i = 9; $i >= 0; $i--)	{
		if (($significand >> ($i)) & 0b1){
			$decimal += pow(2, -1 * (10 - $i));
		}
	}

	if ($exponent == 0 && $significand == 0){
		// If it's a signed zero
		return 0.0;
	}else if ($exponent == 0){
		// It's a subnormal
		$double = pow(-1, $sign) * pow(2, 1 - $EXPONENT_OFFSET) * $decimal;
	}else if ($exponent == $MAX_EXPONENT){
		// Signed infinity
		if ($significand == 0){
			if ($sign == 0){
				return INF;
			}
			return -INF;
		}
		return NAN;
	}else{
		// Regular float
		$double = pow(-1, $sign) * pow(2, $exponent - $EXPONENT_OFFSET) * (1 + $decimal);
	}

	return $double;
}

$inputPath   = "C:\\222\\RiccAssets\\IslandsofInsight\\Content\\ASophia\\UI\\HUD\\Map\\T_Map_Zones_DF.uexp";
$implantPath = "X:\\Dropbox\\Modding\\IslandsOfInsight\\OfflineRestoredMod\\ModMaker\\_map\\edited\\implant_DF.png";
//$outputPath  = "R:\\Garbage\\1.uexp";
$outputPath  = "X:\\Dropbox\\Modding\\IslandsOfInsight\\OfflineRestoredMod\\UnrealPakMini\\IslandsofInsight\\Content\\ASophia\\UI\\HUD\\Map\\T_Map_Zones_DF.uexp";

$implantStartX = 1500;
$implantStartY = 600;
$implantSizeX  = 200;
$implantSizeY  = 200;

$shitOffsetX =   0; // -12
$shitOffsetY =   0;

printf("Loading %s\n", $inputPath);
$file = file_get_contents($inputPath);
$bytes = array_map(function($x){ return ord($x); }, str_split($file, 1));

$UEXP_HEADER_SIZE = 223;
$TEXTURE_HEADER_SIZE = 108;
$UEXP_SUFFIX_SIZE = 4;

$rawBytes = array_slice($bytes, $UEXP_HEADER_SIZE + $TEXTURE_HEADER_SIZE, -$UEXP_SUFFIX_SIZE);

$pixelCount = count($rawBytes) / 2;
$imgWidth  = 0;
$imgHeight = 0;

// Better to parse the texture header of course but who cares?
$imgWidth = intval(round(sqrt($pixelCount)));
$imgHeight = $imgWidth;

printf("Image is %d x %d\n", $imgWidth, $imgHeight);
printf("\n");

$gd = imagecreatefrompng($implantPath);
$implantCount = 0;
for($x = $implantStartX; $x < $implantStartX + $implantSizeX; ++$x){
	for($y = $implantStartY; $y < $implantStartY + $implantSizeY; ++$y){
		$rgb = imagecolorat($gd, $x, $y);
		$alpha = ($rgb >> 24) & 0xFF;
		//if($alpha == 0x7F){
		//	continue;
		//}
		
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >>  8) & 0xFF;
		$b = ($rgb >>  0) & 0xFF;
		
		$val = intval(round(((float)($r + $g + $b)) / 3.0));
		
		$val = min(255, $val);
		
		sscanf($pixelColorToF16[$val], "0x%02X%02X", $aa, $bb);
		$startByte = $UEXP_HEADER_SIZE + $TEXTURE_HEADER_SIZE + (($y + $shitOffsetY) * $imgWidth + ($x + $shitOffsetX)) * 2;
		
		//if($bytes[$startByte + 0] == $bb && $bytes[$startByte + 1] == $aa){
		//	printf("WHAT THE FUCK?!\n"); exit(1);
		//}
		
		$bytes[$startByte + 0] = $bb;
		$bytes[$startByte + 1] = $aa;
		
		++$implantCount;
		
		//printf("%3d ", $r);
		//$g = ($rgb >> 8) & 0xFF;
		//$b = $rgb & 0xFF;
	}
	//printf("\n");
}

printf("Implanted %d pixels, saving...\n", $implantCount);
$final = implode("", array_map(function($x){ return chr($x); }, $bytes));
WriteFileSafe($outputPath, $final, true);

//for($x = 0; $x < imagesy($gd); ++$x){
//	for($y = 0; $y < imagesx($gd); ++$y){
//		$rgb = imagecolorat($gd, $x, $y);
//		$alpha = ($rgb >> 24) & 0xFF;
//		if($alpha == 0x7F){
//			continue;
//		}
//		printf("%d,%d\n", $x, $y);
//	}
//}





exit(1);

$gd = imagecreatetruecolor($imgWidth, $imgHeight);
$duoBytes = array_chunk($rawBytes, 2);
$duoBytesSwapped = array_map(function($pair){ return [ $pair[1], $pair[0] ];}, $duoBytes);
$textDuoBytesSwapped = array_map(function($pair){ return sprintf("0x%02X%02X", $pair[1], $pair[0]); }, array_chunk($rawBytes, 2));

for($x = 0; $x < $imgWidth; ++$x){
	//printf("Processing row %d / %d\n", $row + 1, $imgHeight);
	for($y = 0; $y < $imgHeight; ++$y){
		$pixelIndex = $y * $imgWidth + $x;
		$f = r16f_to_r32f($duoBytesSwapped[$pixelIndex]);
		$pixelColor = max(0, min(255, intval(round($f * 255.0))));
		$clr = imagecolorallocatealpha($gd, $pixelColor, $pixelColor, $pixelColor, 0);
		imagesetpixel($gd, $x, $y, $clr);
	}
}
imagepng($gd, "R:\\Garbage\\p.png");












//file_put_contents($outputPath, implode("\n", $rawBytes));
//$duoBytes = array_map(function($pair){ return sprintf("0x%02X%02X", $pair[0], $pair[1]); }, array_chunk($rawBytes, 2));
//$duoBytes = array_map(function($pair){ return sprintf("%02X%02X", $pair[0], $pair[1]); }, array_chunk($rawBytes, 2));
//WriteFileSafe($outputPath, implode("\n", $duoBytes), true);
//$u = array_unique($duoBytes);
//sort($u);
////printf("Unique: %d\n", count($u));
//printf("%s\n", implode("\n", $u));
//$c = array_count_values($duoBytes);
//arsort($c);
//$c = array_filter($c, function($x){ return ($x > 100);});
//printf("%s\n", json_encode($c, 0xc0));
//$boba = array_map(function($x){ return ($x == "0000" ? "----" : $x); }, $duoBytes);
//$text = implode("\n", array_map(function($line){ return implode(" ", $line); }, array_chunk($boba, $imgWidth)));
//$boba = array_map(function($x){ return ($x == "0000" ? "-" : "#"); }, $duoBytes);
//$text = implode("\n", array_map(function($line){ return implode("", $line); }, array_chunk($boba, $imgWidth)));
//WriteFileSafe($outputPath, $text, true);


//printf("Converting byte values...\n");
//$duoBytes = array_chunk($rawBytes, 2);
//$duoBytesSwapped = array_map(function($pair){ return [ $pair[1], $pair[0] ];}, $duoBytes);
//$textDuoBytesSwapped = array_map(function($pair){ return sprintf("0x%02X%02X", $pair[1], $pair[0]); }, array_chunk($rawBytes, 2));
//
//printf("Processing pixels...\n");
//$map = [];
//$maxo = -1e10;
//$mino = 1e10;
//for($row = 0; $row < $imgHeight; ++$row){
//	//printf("Processing row %d / %d\n", $row + 1, $imgHeight);
//	for($col = 0; $col < $imgWidth; ++$col){
//		$pixelIndex = $row * $imgWidth + $col;
//		$f = r16f_to_r32f($duoBytesSwapped[$pixelIndex]);
//		$maxo = max($maxo, $f);
//		$mino = min($mino, $f);
//		if(!isset($map[$textDuoBytesSwapped[$pixelIndex]])){
//			$map[$textDuoBytesSwapped[$pixelIndex]] = $f * 255.0;
//		}
//	}
//}
//
//printf("MIN: %f\n", $mino);
//printf("MAX: %f\n", $maxo);
//
//asort($map);
//printf("%s\n", json_encode($map, 0xc0));






//printf("Testing...\n");
//$u = array_unique($duoBytesSwapped);
//sort($u);
//$us = array_map(function($x){ return r16f_to_r32f($x); }, $duoBytesSwapped);
//printf("%s\n", implode("\n", $us));
////printf("Unique: %d\n", count($u));


//for($i = 384018; $i < 384018 + 10; ++$i){
//	printf("Pixel %d: %s (%s)\n", $i, implode(",", $duoBytesSwapped[$i]), $textDuoBytesSwapped[$i] );
//	printf("%s\n", r16f_to_r32f($duoBytesSwapped[$i]));
//	
//	printf("\n");
//}
//// 384018


//$trailMap   = array_fill(0, 256, 1.11);
//$reverseMap = array_fill(0, 256, "");
//for($aa = 0; $aa <= 255; ++$aa){
//	for($bb = 0; $bb <= 255; ++$bb){
//		$encoded = [ $aa, $bb ];
//		$f = r16f_to_r32f($encoded);
//		if($f < 0 || $f > 1.0 + 1e-4){
//			continue;
//		}
//		$f *= 255.0;
//		$val = intval(floor($f));
//		$trail = $f - floor($f);
//		if($trail < $trailMap[$val]){
//			$trailMap[$val] = $trail;
//			$reverseMap[$val] = sprintf("0x%02X%02X", $aa, $bb);
//		}
//	}
//}
////printf("%s\n", json_encode($reverseMap, 0xc0));
//foreach($reverseMap as $grayColor => $s){
//	sscanf($s, "0x%02X%02X", $aa, $bb);
//	$f = r16f_to_r32f([$aa, $bb]);
//	//printf("%3d: %8.1f [%d,%d]\n", $grayColor, $f * 255.0, $aa, $bb);
//	printf("%s\n", $s);
//}
//exit(1);