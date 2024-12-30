<?php
/**
 * function imageSmoothAlphaLine() - version 1.0
 * Draws a smooth line with alpha-functionality
 *
 * @param   ident    the image to draw on
 * @param   integer  x1
 * @param   integer  y1
 * @param   integer  x2
 * @param   integer  y2
 * @param   integer  red (0 to 255)
 * @param   integer  green (0 to 255)
 * @param   integer  blue (0 to 255)
 * @param   integer  alpha (0 to 127)
 *
 * @access  public
 *
 * @author  DASPRiD <d@sprid.de>
 */
function imageSmoothAlphaLine ($image, $x1, $y1, $x2, $y2, $color_rgb) {
	$icr = $color_rgb[0];
	$icg = $color_rgb[1];
	$icb = $color_rgb[2];
	$alpha = $color_rgb[3];
	$sx = imagesx($image);
	$sy = imagesy($image);
	$dcol = imagecolorallocatealpha($image, $icr, $icg, $icb, $alpha);
	
	// NOTE: this fucking crap doesn't work with orthogonal lines even if it tries
	// Just make lines non-orthogonal, fuck it, can't deal with this shit right now
	if($x1 == $x2){
		++$x1;
	}
	if($y1 == $y2){
		++$y1;
	}

	//  if ($y1 == $y2 || $x1 == $x2) {
	//    //if(!imageline($image, $x1, $y2, $x1, $y2, $dcol)){
	//		//printf("[Warning] Tried drawing an orthogonal line, encountered a bug (%d,%d -> %d,%d)\n", $x1, $y1, $x2, $y2);
	//	}
	//  }
	//  else {
	$m = ($y2 - $y1) / ($x2 - $x1);
	$b = $y1 - $m * $x1;

	$x = min($x1, $x2);
	$endx = max($x1, $x2) + 1;
	$y = min($y1, $y2);
	$endy = max($y1, $y2) + 1;
	if($x < 1 || $endx >= $sx - 1 || $y < 1 || $endy >= $sy - 1){
		return;
	}

	if (abs ($m) < 2) {
		while ($x < $endx) {
			$y = $m * $x + $b;
			$ya = ($y == floor($y) ? 1: $y - floor($y));
			$yb = ceil($y) - $y;

			$trgb = ImageColorAt($image, $x, floor($y));
			$tcr = ($trgb >> 16) & 0xFF;
			$tcg = ($trgb >> 8) & 0xFF;
			$tcb = $trgb & 0xFF;
			imagesetpixel($image, $x, floor($y), imagecolorallocatealpha($image, round($tcr * $ya + $icr * $yb), round($tcg * $ya + $icg * $yb), round($tcb * $ya + $icb * $yb), $alpha));

			$trgb = ImageColorAt($image, $x, ceil($y));
			$tcr = ($trgb >> 16) & 0xFF;
			$tcg = ($trgb >> 8) & 0xFF;
			$tcb = $trgb & 0xFF;
			imagesetpixel($image, $x, ceil($y), imagecolorallocatealpha($image, round($tcr * $yb + $icr * $ya), round($tcg * $yb + $icg * $ya), round($tcb * $yb + $icb * $ya), $alpha));

			$x++;
		}
	} else {
		while ($y < $endy) {
			$x = ($y - $b) / $m;
			$xa = ($x == floor($x) ? 1: $x - floor($x));
			$xb = ceil($x) - $x;

			$trgb = ImageColorAt($image, floor($x), $y);
			$tcr = ($trgb >> 16) & 0xFF;
			$tcg = ($trgb >> 8) & 0xFF;
			$tcb = $trgb & 0xFF;
			imagesetpixel($image, floor($x), $y, imagecolorallocatealpha($image, round($tcr * $xa + $icr * $xb), round($tcg * $xa + $icg * $xb), round($tcb * $xa + $icb * $xb), $alpha));

			$trgb = ImageColorAt($image, ceil($x), $y);
			$tcr = ($trgb >> 16) & 0xFF;
			$tcg = ($trgb >> 8) & 0xFF;
			$tcb = $trgb & 0xFF;
			imagesetpixel ($image, ceil($x), $y, imagecolorallocatealpha($image, round($tcr * $xb + $icr * $xa), round($tcg * $xb + $icg * $xa), round($tcb * $xb + $icb * $xa), $alpha));

			$y++;
		}
	}
}

function imageSmoothAlphaLine2 ($image, $x1, $y1, $x2, $y2, $colorIndex) {
	$arr = imagecolorsforindex($image, $colorIndex);
	//TODO optimize this please
	$pass = [ $arr["red"], $arr["green"], $arr["blue"], $arr["alpha"] ];
	imageSmoothAlphaLine ($image, $x1, $y1, $x2, $y2, $pass);
}
