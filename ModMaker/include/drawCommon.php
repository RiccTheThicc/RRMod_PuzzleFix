<?php

include_once("include\\lookup.php");
include_once("include\\config.php");
include_once("include\\imageSmoothArc_fast.php");
include_once("include\\imageSmoothLine.php");
include_once("include\\imgex.php");
include_once("include\\icons.php");

function imagelinerectangle_deprecated($img, $x1, $y1, $x2, $y2, $color, $width=1) {
	--$x2;
	--$y2;
    imagefilledrectangle($img, $x1-$width, $y1-$width, $x2+$width, $y1-1, $color);
    imagefilledrectangle($img, $x2+1, $y1-$width, $x2+$width, $y2+$width, $color);
    imagefilledrectangle($img, $x1-$width, $y2+1, $x2+$width, $y2+$width, $color);
    imagefilledrectangle($img, $x1-$width, $y1-$width, $x1-1, $y2+$width, $color);
}

function ImageRectangleWithRoundedCorners_deprecated($im, $x1, $y1, $x2, $y2, $radius, $color) {
	printf("%d %d -> %d %d\n", $x1, $y1, $x2, $y2);
	// draw rectangle without corners
	imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
	imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);
	// draw circled corners
	imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
	imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
	imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
	imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
}

// $x1, $y1, $x2, $y2 inclusive, fit into the given box.
function ImageRectangleRoundedCorners($gd, $x1, $y1, $x2, $y2, $radius, $colorCode, $margin = 0) {
	$alpha      = (ColorCodeToRGBA          ($gd, $colorCode))[3];
	$solidColor = (ColorCodeToIndexFullAlpha($gd, $colorCode));
	
	$width  = ($x2 - $x1 + 1);
	$height = ($y2 - $y1 + 1);
	$margin = max(0, $margin);
	$radius = max(0, $radius);
	if($radius > 0){
		$radius = round($radius + 4);
	}
	//$radius = max(0, round($radius));
	//printf("width = %d, height = %d, margin = %d, given radius = %d, ", $width, $height, $margin, $radius);
	$radius = floor(min(min($radius + 1e-4, ($width - 2 * $margin) / 4.0), ($height - 2 * $margin) / 4.0));
	//printf("final radius = %d\n", $radius);
	$tmpGd = CreateBlankImage($width, $height);
	$lx1 = $radius + $margin;
	$ly1 = $radius + $margin;
	$lx2 = $width  - $radius - 1 - $margin;
	$ly2 = $height - $radius - 1 - $margin;
	//imagealphablending($tmpGd, true);
	$safer = 2; //round($radius / 5);
	//$testColor = ColorCodeToIndexFullAlpha($tmpGd, "#FFFFFF00");
	//imagefilledellipse($tmpGd, $lx1, $ly1, $radius * 2, $radius * 2, $solidColor);
	//imagefilledellipse($tmpGd, $lx2, $ly1, $radius * 2, $radius * 2, $solidColor);
	//imagefilledellipse($tmpGd, $lx1, $ly2, $radius * 2, $radius * 2, $solidColor);
	//imagefilledellipse($tmpGd, $lx2, $ly2, $radius * 2, $radius * 2, $solidColor);
	imageSmoothFilledCircle($tmpGd, $lx1 - 1, $ly1 - 1, $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	imageSmoothFilledCircle($tmpGd, $lx2    , $ly1 - 1, $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	imageSmoothFilledCircle($tmpGd, $lx1 - 1, $ly2    , $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	imageSmoothFilledCircle($tmpGd, $lx2    , $ly2    , $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	//imageSmoothFilledCircle($tmpGd, $lx1    , $ly1    , $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	//imageSmoothFilledCircle($tmpGd, $lx2 - 1, $ly1    , $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	//imageSmoothFilledCircle($tmpGd, $lx1    , $ly2 - 1, $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	//imageSmoothFilledCircle($tmpGd, $lx2 - 1, $ly2 - 1, $radius * 2, ColorIndexToRGBA($tmpGd, $solidColor));
	imagefilledrectangle($tmpGd, $lx1 + $safer, $margin, $lx2 - $safer, $height - $margin - 1, $solidColor);
	imagefilledrectangle($tmpGd, $margin, $ly1 + $safer, $width - $margin - 1, $ly2 - $safer,  $solidColor);
	//imagealphablending($tmpGd, false);
	ChangeImageAlpha($tmpGd, $alpha);
	//ScaleImageAlpha($tmpGd, $alpha); // extremely slow and broken
	
	//SaveImageAs($tmpGd, "R:\\Garbage\\test.png");
	OverlayImageSmart($gd, $tmpGd, [ "x" => $x1, "y" => $y1, "alignX" => "left", "alignY" => "top" ]);
	return $gd;
}

function ImageTrimRoundCorners($gd, $radius){
	$radius = intval(round($radius));
	$width  = imagesx($gd);
	$height = imagesy($gd);
	$layer = CreateBlankImage($width, $height);
	ImageRectangleRoundedCorners($layer, 0, 0, $width - 1, $height - 1, $radius, "#00000000");
	for($x = 0; $x < $width; ++$x){
		for($y = 0; $y < $height; ++$y){
			$realColorIndex  = imagecolorat($gd,    $x, $y);
			$layerColorIndex = imagecolorat($layer, $x, $y);
			$realColors  = ColorIndexToRGBA($gd,    $realColorIndex);
			$layerColors = ColorIndexToRGBA($layer, $layerColorIndex);
			$realColors[3] = $layerColors[3];
			$finalColor = ColorRGBAToIndex($gd, $realColors);
			imagesetpixel($gd, $x, $y, $finalColor);
		}
	}
	//OverlayImageSmart($gd, $copy, [ ]);
	return $gd;
}

function DrawLine($gd, $ax, $ay, $bx, $by, $colorCode, $repetition = 1){
	$colorArray = ColorCodeToRGBA($gd, $colorCode);
	for($i = 0; $i < $repetition; ++$i){
		imageSmoothAlphaLine($gd, $ax, $ay, $bx, $by, $colorArray);
	}
}
function GetTextDimensions($text, $fontSize, $fontName){
	// Absolute magic from the internet + precise fixes. Don't fucking touch.
    $bbox = imagettfbbox($fontSize, 0, $fontName, $text);
	$offsetX = 0;
	$offsetY = 0;
	$width   = 0;
	$height  = 0;
    if($bbox[0] >= -1) {
        $offsetX = 1 + abs($bbox[0] + 1) * -1;
    } else {
        $offsetX = 1 + abs($bbox[0] + 2);
    }
    $width = abs($bbox[2] - $bbox[0]);
    if($bbox[0] < -1) {
        $width = abs($bbox[2]) + abs($bbox[0]) - 2;
    }
    $offsetY = abs($bbox[5] + 1) + 1;
    $height = abs($bbox[7]) - abs($bbox[1]) + 1;
    if($bbox[3] > 0) {
        $height = abs($bbox[7] - $bbox[1]);
    }
    return (object)[
		"offsetX" => $offsetX,
		"offsetY" => $offsetY,
		"width"   => $width,
		"height"  => $height,
	];
}

function GetCenteredTextDimensions($text, $fontSize, $fontName){
	// If you intend to display several pieces of text at the same height, individual letters used affect vertical offset/size.
	// This returns the dimensions as if all possible letters were used, so they will all line up.
	$allText = "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~";
	$allText = str_replace(["_"], "", $allText); // except underscore, fuck underscore
	$normalDim   = GetTextDimensions($text,    $fontSize, $fontName);
	$extendedDim = GetTextDimensions($allText, $fontSize, $fontName);
	return (object)[
		"offsetX" => $normalDim->offsetX,
		"offsetY" => $extendedDim->offsetY,
		"width"   => $normalDim->width,
		"height"  => $extendedDim->height,
	];
}

function GetTextBoxSize_DEPRECATED($fontSize, $angle, $fontName, $text){
	$bbox  = imageftbbox($fontSize, $angle, $fontName, $text);
	$maxer = imageftbbox($fontSize, $angle, $fontName, "|");
	$width     = abs($bbox [2] - $bbox [0]);
	$height    = abs($bbox [7] - $bbox [1]);
	$maxHeight = abs($maxer[7] - $maxer[1]);
	$dimensions = (object)[
		"width" =>  $width,
		"height" => max($height, $maxHeight),
	];
	return $dimensions;
}

// A function to display text in all sorts of fancy ways.
// This creates a virtual image to render the text into. This function is very, very slow.
function DrawSmartText($gd, string $text = "sample", array $options = []){
	$defaultOptions = [
		"x"           => imagesx($gd) / 2,
		"y"           => imagesy($gd) / 2,
		"align"       => "center",
		//"angle"       => 0,
		"colorCode"   => "#FFFFFF00",
		"fontSize"    => "16px",
		"fontName"    => "./arial.ttf",
		"centered"    => true,
		"strokeWidth" => 1,
		"strokeColor" => "#00000000", // don't alpha-blend this
		"cardColor"   => "#0000007F",
		"cardMargin"  => 0,
		"cardRadius"  => 3,
		"fitWidth"    => -1,
		"fitHeight"   => -1,
		"maxWidth"    => 1e9,
		"maxHeight"   => 1e9,
	];
	$options = array_merge($defaultOptions, $options);
	$x           = $options["x"];
	$y           = $options["y"];
	$align       = $options["align"];
	//$angle       = $options["angle"];
	$colorCode   = $options["colorCode"];
	$fontSize    = ParseFontSize($options["fontSize"]);
	$fontName    = $options["fontName"];
	$isCentered  = (bool)$options["centered"];
	//$strokeWidth = round(max(0, $options["strokeWidth"]));
	$strokeWidth = Clamp(round($options["strokeWidth"]), 0, 2);
	$strokeColor = $options["strokeColor"];
	$cardColor   = $options["cardColor"];
	$cardMargin  = round(max(0, $options["cardMargin"]));
	$cardRadius  = round(max(0, $options["cardRadius"]));
	$fitWidth    = $options["fitWidth"];  // not implemented
	$fitHeight   = $options["fitHeight"]; // not implemented
	$maxWidth    = $options["maxWidth"];
	$maxHeight   = $options["maxHeight"];
	
	$textColorIndex   = ColorCodeToIndexFullAlpha($gd, $colorCode);
	$strokeColorIndex = ColorCodeToIndexFullAlpha($gd, $strokeColor);
	
	$dimensions = ($isCentered ? GetCenteredTextDimensions($text, $fontSize, $fontName) : GetTextDimensions($text, $fontSize, $fontName));
	$totalWidth  = ($dimensions->width  + $strokeWidth * 2); // + $cardMargin * 2);
	$totalHeight = ($dimensions->height + $strokeWidth * 2); // + $cardMargin * 2);
	
	$scale = min(1.0 * $maxWidth / $totalWidth, 1.0 * $maxHeight / $totalHeight);
	if($scale < 1.0 - 1e-6){
		//printf("Need to scale this text down by %.2f: %s\n", $scale, $text);
		$fontSize    *= $scale;
		$dimensions = ($isCentered ? GetCenteredTextDimensions($text, $fontSize, $fontName) : GetTextDimensions($text, $fontSize, $fontName));
		$totalWidth  = ($dimensions->width  + $strokeWidth * 2); // + $cardMargin * 2);
		$totalHeight = ($dimensions->height + $strokeWidth * 2); // + $cardMargin * 2);
	}
	
	
	$tmpGd = CreateCard($totalWidth, $totalHeight, [ "cardColor" => $cardColor, "cardMargin" => $cardMargin, "cardRadius" => $cardRadius ]);
	
	imagealphablending($tmpGd, true);
	//static $strokeDirs = [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]];
	//static $strokeDirs = [[-1,-1],[-1,1],[1,-1],[1,1]];
	static $strokeDirs = [[-1,0],[1,0],[0,-1],[0,1]];
	//static $strokeDirs = [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1],[-1,0],[1,0],[0,-1],[0,1]];
	for($dist = 1; $dist <= $strokeWidth; ++$dist){
		foreach($strokeDirs as $pair){
			$strokeOffsetX = $dist * $pair[0];
			$strokeOffsetY = $dist * $pair[1];
			imagefttext($tmpGd, $fontSize, 0, $dimensions->offsetX + $strokeWidth + $strokeOffsetX, $dimensions->offsetY + $strokeWidth + $strokeOffsetY, $strokeColorIndex, $fontName, $text);
		}
	}
	imagefttext($tmpGd, $fontSize, 0, $dimensions->offsetX + $strokeWidth, $dimensions->offsetY + $strokeWidth, $textColorIndex, $fontName, $text);
	imagealphablending($tmpGd, false);
	
	OverlayImageSmart($gd, $tmpGd, [ "x" => $x, "y" => $y, "alignX" => $align, "alignY" => "center" ]);
	return $gd;
}

function DrawTextFast($gd, $text, $x, $y, $colorCode, $fontSize, $fontName, int $align = 0, bool $useStroke = true){
	// Simplified, optimized way to draw centered text at x,y coordinates.
	// Used mostly by the map rendering since this is being called thousands of times.
	// Warning: useStroke can double the runtime but produces far better results.
	$myColorIndex = ColorCodeToIndexFullAlpha($gd, $colorCode);
	static $strokeColorIndex = 0;
	$dimensions = GetTextDimensions($text, $fontSize, $fontName);
	$tpx = ($align == 0 ? ceil($x - $dimensions->width  / 2.0 - 1e-4) : ($align == -1 ? round($x) : round($x - $dimensions->width)));
	$tpy = ceil($y - $dimensions->height / 2.0 + $fontSize - 1e-4);
	if($useStroke){
		imagefttext($gd, $fontSize, 0, $tpx + $dimensions->offsetX - 1, $tpy + $dimensions->offsetY    , $strokeColorIndex, $fontName, $text);
		imagefttext($gd, $fontSize, 0, $tpx + $dimensions->offsetX    , $tpy + $dimensions->offsetY - 1, $strokeColorIndex, $fontName, $text);
		imagefttext($gd, $fontSize, 0, $tpx + $dimensions->offsetX + 1, $tpy + $dimensions->offsetY    , $strokeColorIndex, $fontName, $text);
		imagefttext($gd, $fontSize, 0, $tpx + $dimensions->offsetX    , $tpy + $dimensions->offsetY + 1, $strokeColorIndex, $fontName, $text);
	}
	imagefttext($gd, $fontSize, 0, $tpx + $dimensions->offsetX, $tpy + $dimensions->offsetY, $myColorIndex, $fontName, $text);
}
