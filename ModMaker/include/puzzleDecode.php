<?php

function RawStringToIntegers(string $raw){
	$bytes = [];
	foreach(str_split($raw) as $byte){
		$bytes[] = ord($byte);
	}
	return $bytes;
}

function ReadFloat32(array $bytes, int &$ptr){
	$s = "";
	for($i = 0; $i < 4; ++$i){
		if($ptr >= count($bytes)){
			printf("Attempting to %s past %d bytes", __FUNCTION__, count($bytes));
			return null;
		}
		$s .= chr($bytes[$ptr++]);
	}
	$float = unpack("f", $s)[1];
	//printf("%s -> %f\n", $s, $float);
	return $float;
}
