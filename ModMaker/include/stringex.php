<?php

function ColorStr(string $str, int $r, int $g, int $b){
	$r = max(0, min($r, 255));
	$g = max(0, min($g, 255));
	$b = max(0, min($b, 255));
	return sprintf("\e[38;2;%03d;%03d;%03dm%s\e[0m", $r, $g, $b, $str);
}

function PctStr(int $value, int $maximum, int $width = null, int $pctPrecision = 1){
	// Returns a string such as " 53 / 300 (17.7%)"
	$pctPrecision = max(0, $pctPrecision);
	$intWidthStr = "%" . ($width == null ? "" : $width) . "d";
	$floatPrecStr = ($pctPrecision == 0 ? "%3.0f" : "%" . (string)($pctPrecision + 4) . "." . (string)($pctPrecision) . "f");
	$format = $intWidthStr . " / " . $intWidthStr . " (" . $floatPrecStr . "%%)";
	$finalStr = sprintf($format, $value, $maximum, 100.0 * $value / $maximum);
	//printf("|%s| |%s| |%s| |%s|\n", $intWidthStr, $floatPrecStr, $format, $finalStr);
	return $finalStr;
}

function BoolStr(mixed $data){
	return (((bool)$data) ? "true" : "false");
}