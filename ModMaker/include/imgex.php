<?php

define("DEFAULT_INTERPOLATION", IMG_BILINEAR_FIXED);

function ColorCodeToRGBA($gd, string $colorCode){
	$result = [255, 255, 255, 0];
	if(!str_starts_with($colorCode, "#") || !in_array(strlen($colorCode), [ 7, 9 ])){
		printf("%s\n", ColorStr("Bad color code: \"" . $colorCode . "\", expected format: \"#AABBCC7F\"", 255, 128, 128));
		exit(1);
	}
	$scan = sscanf($colorCode, "#%02x%02x%02x%02x");
	if(!in_array(count($scan), [3, 4])){
		return $result;
	}
	for($i = 0; $i < count($scan); ++$i){
		$result[$i] = $scan[$i]; // maybe with alpha, maybe without
	}
	return $result;
}

function ColorCodeToIndex($gd, string $colorCode){
	$rgba = ColorCodeToRGBA($gd, $colorCode);
	$colorIndex = imagecolorexactalpha($gd, $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
	return $colorIndex;
}

function ColorCodeToIndexFullAlpha($gd, string $colorCode){
	$rgba = ColorCodeToRGBA($gd, $colorCode);
	$colorIndex = imagecolorexactalpha($gd, $rgba[0], $rgba[1], $rgba[2], 0);
	return $colorIndex;
}

function ColorIndexToRGBA($gd, int $colorIndex){
	return array_values(imagecolorsforindex($gd, $colorIndex));
}

function ColorRGBAToIndex($gd, array $rgba){
	return imagecolorexactalpha($gd, $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
}

function ParseFontSize(mixed $fontSize){
	if(is_float($fontSize) || is_int($fontSize)){
		// Assume points.
		return $fontSize;
	}
	if(is_string($fontSize)){
		$value = floatval($fontSize);
		if(str_ends_with($fontSize, "pt")){
			return $value;
		}
		if(str_ends_with($fontSize, "px")){
			// Convert to points.
			return ($value / 4.0 * 3);
		}
		// Assume points.
		return $value;
	}
	printf("%s\n", ColorStr(__FUNCTION__ . ": can't recognize font size " . json_encode($fontSize), 255, 128, 128));
	exit(1);
	return $fontSize;
}

function ParseImageFormat(string $path){
	$path = trim(strtolower($path));
	if(str_ends_with($path, ".png")){
		return "png";
	}
	if(str_ends_with($path, ".jpg") || str_ends_with($path, ".jpeg")){
		return "jpg";
	}
	return "unknown";
}

function LoadImage(string $path){
	$format = ParseImageFormat($path);
	switch($format){
		case "png":{
			$gd = imagecreatefrompng($path);
			break;
		}
		case "jpg":
		case "jpeg":{
			$gd = imagecreatefromjpeg($path);
			break;
		}
		default:{
			printf("%s: Unknown extension %s\n", __FUNCTION__, (empty($format) ? "<none>" : $format));
			exit(1);
		}
	}
	return $gd;
}

function SaveImageAs($gd, string $path, int $jpgQuality = 98){
	$format = ParseImageFormat($path);
	//printf("Saving %s...\n", $path);
	switch($format){
		case "png":{
			imagepng($gd, $path);
			break;
		}
		case "jpg":
		case "jpeg":{
			imagejpeg($gd, $path, $jpgQuality);
			break;
		}
		default:{
			printf("%s: Unknown extension %s\n", __FUNCTION__, (empty($format) ? "<none>" : $format));
			exit(1);
		}
	}
	//imagedestroy($gd); // ?
}

// Simple blank image, fully transparent.
function CreateBlankImage(int|float $width, int|float $height){
	$width  = intval(round($width));
	$height = intval(round($height));
	$gd = imagecreatetruecolor($width, $height);
	//imagecolortransparent($gd, imagecolorallocate($gd, 0, 0, 0));
	imagefill($gd, 0, 0, imagecolorallocatealpha($gd, 0, 0, 0, 127));
	imagesavealpha($gd, true);
	imagealphablending($gd, false);
	return $gd;
}

// A card is a blank image with a customizable rounded rectangle.
function CreateCard(int|float $width, int|float $height, array $options = []){
	$width  = intval(round($width));
	$height = intval(round($height));
	$defaultOptions = [
		"backColor"  => "#0000007F",
		"cardColor"  => "#00000070",
		"cardMargin" => 0,
		"cardRadius" => 4,
	];
	$options = array_merge($defaultOptions, $options);
	$backColor  = $options["backColor"];
	$cardColor  = $options["cardColor"];
	$cardMargin = round(max(0, $options["cardMargin"]));
	$cardRadius = round(max(0, $options["cardRadius"]));
	
	$gd = imagecreatetruecolor($width, $height);
	imagefill($gd, 0, 0, ColorCodeToIndex($gd, $backColor));
	imagesavealpha($gd, true);
	imagealphablending($gd, false);
	
	ImageRectangleRoundedCorners($gd, 0, 0, $width - 1, $height - 1, $cardRadius, $cardColor, $cardMargin);
	return $gd;
}

function OverlayImageSmart($canvas, $overlay, array $options = []){
	$defaultOptions = [
		"x"         => imagesx($canvas) / 2,
		"y"         => imagesy($canvas) / 2,
		"alignX"    => "center",
		"alignY"    => "center",
		"size"      => -1,
		"sizeX"     => -1,
		"sizeY"     => -1,
		"scale"     => 1.0,
		"blend"     => true,
	];
	$options = array_merge($defaultOptions, $options);
	$x         = $options["x"];
	$y         = $options["y"];
	$alignX    = $options["alignX"];
	$alignY    = $options["alignY"];
	$size      = $options["size"];
	$sizeX     = $options["sizeX"];
	$sizeY     = $options["sizeY"];
	$doBlend   = (bool)$options["blend"];
	$scale     = max((float)$options["scale"], 0.0);
	
	$scaledGd = $overlay;
	if(abs($scale - 1.0) < 1e-6){
		$finalSizeX = max(1, round(($sizeX > 0 ? $sizeX : ($size > 0 ? $size : imagesx($overlay)))));
		$finalSizeY = max(1, round(($sizeY > 0 ? $sizeY : ($size > 0 ? $size : imagesy($overlay)))));
		if($finalSizeX != imagesx($overlay) || $finalSizeY != imagesy($overlay)){
			$scaledGd = imagescale($overlay, $finalSizeX, $finalSizeY, DEFAULT_INTERPOLATION);
		}
	}else{
		$finalSizeX = max(1, round($scale * imagesx($overlay)));
		$finalSizeY = max(1, round($scale * imagesy($overlay)));
		$scaledGd = imagescale($overlay, $finalSizeX, $finalSizeY, DEFAULT_INTERPOLATION);
	}
	
	$opx = round($x - $finalSizeX / 2);
	$opy = round($y - $finalSizeY / 2);
	if($alignX == "left"){ 
		$opx = round($x);
	}elseif($alignX == "right"){
		$opx = round($x - $finalSizeX - 1);
	}
	if($alignY == "top"){
		$opy = round($y);
	}elseif($alignY == "bottom"){
		$opy = round($y - $finalSizeY - 1);
	}
	
	if($doBlend){ imagealphablending($canvas, true); }
	imagecopy($canvas, $scaledGd, $opx, $opy, 0, 0, $finalSizeX, $finalSizeY);
	if($doBlend){imagealphablending($canvas, false); }
	return $canvas;
}

function TileImages(array $gdArray, array $options = []){
	$defaultOptions = [
		"spacer"     => 0,
		"margin"     => 0,
		"mode"       => "vertical",
		"align"      => "center",
		"backColor"  => "#0000007F",
		"cardColor"  => "#0000007F",
		"cardMargin" => 0,
		"cardRadius" => 0,
		"blend"      => true,
	];
	$options    = array_merge($defaultOptions, $options);
	$spacer     = max(0, round($options["spacer"]));
	$margin     = max(0, round($options["margin"]));
	$isVertical = (in_array(strtolower($options["mode"]), [ "vertical", "ver", "vert", "v" ])); // horizontal otherwise
	$align      = $options["align"];
	$backColor  = $options["backColor"];
	$cardColor  = $options["cardColor"];
	$cardMargin = $options["cardMargin"];
	$cardRadius = $options["cardRadius"];
	$doBlend    = $options["blend"];
	
	$spacerCount = count($gdArray) - 1;
	
	if($isVertical){
		$totalWidth  = max      (array_map("imagesx", $gdArray)) + 0                      + $margin * 2;
		$totalHeight = array_sum(array_map("imagesy", $gdArray)) + $spacerCount * $spacer + $margin * 2;
	}else{
		$totalWidth  = array_sum(array_map("imagesx", $gdArray)) + $spacerCount * $spacer + $margin * 2;
		$totalHeight = max      (array_map("imagesy", $gdArray)) + 0                      + $margin * 2;
	}
	
	//$gd = CreateBlankImage($totalWidth, $totalHeight);
	$gd = CreateCard($totalWidth, $totalHeight, [ "backColor" => $backColor, "cardColor" => $cardColor, "cardMargin" => $cardMargin, "cardRadius" => $cardRadius ]);
	
	$px = $margin;
	$py = $margin;
	for($index = 0; $index < count($gdArray); ++$index){
		$item = $gdArray[$index];
		if($isVertical){
			if($align == "left"){
				OverlayImageSmart($gd, $item, [ "x" => $px,                   "y" => $py,                    "alignX" => "left",   "alignY" => "top",    "blend" => $doBlend ]);
			}elseif($align == "right"){
				OverlayImageSmart($gd, $item, [ "x" => $totalWidth - $margin, "y" => $py,                    "alignX" => "right",  "alignY" => "top",    "blend" => $doBlend ]);
			}else{
				OverlayImageSmart($gd, $item, [                               "y" => $py,                    "alignX" => "center", "alignY" => "top",    "blend" => $doBlend ]);
			}
			$py += imagesy($item) + $spacer;
		}else{
			if($align == "top"){
				OverlayImageSmart($gd, $item, [ "x" => $px,                   "y" => $py,                    "alignX" => "left",   "alignY" => "top",    "blend" => $doBlend ]);
			}elseif($align == "bottom"){
				OverlayImageSmart($gd, $item, [ "x" => $px,                   "y" => $totalHeight - $margin, "alignX" => "left",   "alignY" => "bottom", "blend" => $doBlend ]);
			}else{
				OverlayImageSmart($gd, $item, [ "x" => $px,                                                  "alignX" => "left",   "alignY" => "center", "blend" => $doBlend ]);
			}
			$px += imagesx($item) + $spacer;
		}
	}
	
	return $gd;
}

function ParseAlpha(mixed $alpha){
	if(is_float($alpha)){
		$alpha = intval(Clamp(1.0 - $alpha, 0.0, 1.0) * 127);
	}
	$alpha = intval(Clamp($alpha, 0, 127));
	return $alpha;
}

function ChangeImageAlpha($gd, mixed $alpha){
	$alpha = ParseAlpha($alpha);
	imagefilter($gd, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);
	//printf("Alpha changed to %3d\n", $alpha);
	return $gd;
}

function ScaleImageAlpha($gd, mixed $alpha){
	$alpha = ParseAlpha($alpha);
	//imagefilter($gd, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);
	$width  = imagesx($gd);
	$height = imagesy($gd);
	for($x = 0; $x < $width; ++$x){
		for($y = 0; $y < $height; ++$y){
			$realColorIndex  = imagecolorat($gd,    $x, $y);
			$realColors      = ColorIndexToRGBA($gd,    $realColorIndex);
			$realColors[3]   = Clamp(round($realColors[3] * ($alpha / 127.0)), 0, 127);
			$finalColor = ColorRGBAToIndex($gd, $realColors);
			imagesetpixel($gd, $x, $y, $finalColor);
		}
	}
	return $gd;
}

function ImageTrimToSquare($gd){
	$width  = imagesx($gd);
	$height = imagesy($gd);
	$size = min($width, $height);
	
	if($width > $size){
		$chop = intval(round(($width - $size) / 2.0));
		$gd = imagecrop($gd, ['x' => $chop, 'y' => 0, 'width' => $size, 'height' => $size]);
	}
	if($height > $size){
		$chop = intval(round(($height - $size) / 2.0));
		$gd = imagecrop($gd, ['x' => 0, 'y' => $chop, 'width' => $size, 'height' => $size]);
	}
	
	return $gd;
}
