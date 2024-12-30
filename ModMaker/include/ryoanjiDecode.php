<?php

include_once("include\\puzzleDecode.php");

// full parsing / rendering not implemented here, but

function GetRyoanjiSize(string $base64){
	$decoded = base64_decode($base64);
	$bytes = RawStringToIntegers($decoded);
	$ptr = 8;
	$width  = ReadFloat32($bytes, $ptr);
	$height = ReadFloat32($bytes, $ptr);
	if(abs($width - $height) > 1e-6){
		printf("%s: what kind of a ryoanji has dimensions such as %.2f x %.2f?\n", __FUNCTION__, $width, $height);
		exit(1);
	}
	return $width;
}

