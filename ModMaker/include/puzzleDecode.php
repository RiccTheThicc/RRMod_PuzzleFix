<?php

function RawStringToIntegers(string $raw){
	$bytes = [];
	foreach(str_split($raw) as $byte){
		$bytes[] = ord($byte);
	}
	return $bytes;
}

function ReadValue32(array $bytes, int &$ptr, string $type){
	$s = "";
	for($i = 0; $i < 4; ++$i){
		if($ptr >= count($bytes)){
			printf("Attempting to %s past %d bytes", __FUNCTION__, count($bytes));
			return null;
		}
		$s .= chr($bytes[$ptr++]);
	}
	$float = unpack($type, $s)[1];
	//printf("%s -> %f\n", $s, $float);
	return $float;
}

function ReadFloat32(array $bytes, int &$ptr){
	return ReadValue32($bytes, $ptr, "f");
}

function ReadInt32(array $bytes, int &$ptr){
	return ReadValue32($bytes, $ptr, "l");
}

function WriteBinaryFloat32(string &$output, float $f){
	$a = pack("f", $f);
	$output .= $a;
	return $output;
}

function WriteBinaryInt32(string &$output, int $v){
	$a = pack("l", $v);
	$output .= $a;
	return $output;
}

function ReadVarintSimple($bytes, &$ptr){
	$val = $bytes[$ptr++];
	if($val >= 128){
		$val2 = $bytes[$ptr++];
		return (($val - 128) * 128 + $val2);
	}
	return $val;
}

function GetGridBasics(string $base64){
	// There will be no comments on this piece of code.
	$decoded = base64_decode($base64);
	if($decoded == FALSE){
		printf("Failed to decode grid data: %s\n", $base64);
		exit(1);
	}
	$isnpl = 0;
	$grid = (object)[
		"rn" => 0,
		"cn" => 0,
		"tm" => [],
		"gm" => [],
		//"r" => [],
		"npl" => -1,
		"w" => 0,
		"g" => [0 => [], 1 => []],
		"mt" => "logicGrid",
	];
	$bytes = array_map(function ($x) { return ord($x); }, str_split($decoded, 1));
//	$arr = [ 0 => [], 1 => [] ];
	$ptr = 1;
	$tmc = ReadVarintSimple($bytes, $ptr);
	for($i = 0; $i < $tmc; ++$i){
		$v = ReadVarintSimple($bytes, $ptr);
		$grid->tm[] = $v;
		static $gridModifierIntentifiers = [ 2 => "completeThePattern", 4 => "musicGrid", 12 => "memoryGrid" ];
		if(isset($gridModifierIntentifiers[$v])){
			$grid->mt = $gridModifierIntentifiers[$v];
		}
		if($v == 11){
			//$grid->npl = ReadVarintSimple($bytes, $ptr);
			$isnpl = 1;
		}elseif($v == 4){
			ReadVarintSimple($bytes, $ptr);
			ReadVarintSimple($bytes, $ptr);
			ReadVarintSimple($bytes, $ptr);
			$ic = ReadVarintSimple($bytes, $ptr);
			for($j = 0; $j < $ic; ++$j){
				$inl = ReadVarintSimple($bytes, $ptr);
				$ptr += $inl;
			}
		}
	}
	$grid->w = ReadVarintSimple($bytes, $ptr);
	$grid->rn = ReadVarintSimple($bytes, $ptr);
	$grid->cn = ReadVarintSimple($bytes, $ptr);
	$gmc = ReadVarintSimple($bytes, $ptr);
	for($i = 0; $i < $gmc; ++$i){
		$grid->gm[] = ReadVarintSimple($bytes, $ptr);
		$c = ReadVarintSimple($bytes, $ptr);
		for($j = 0; $j < $c; ++$j){
			ReadVarintSimple($bytes, $ptr);
		}
	}
	$rc = ReadVarintSimple($bytes, $ptr);
	for($i = 0; $i < min(2, $rc); ++$i){
		$rt = ReadVarintSimple($bytes, $ptr);
		//$grid->r[] = $rt;
		if(!in_array($rt, [ 0, 1 ])){
			break;
		}
		$c = ReadVarintSimple($bytes, $ptr);
		for($j = 0; $j < $c; ++$j){
			//$arr[$rt][] = ReadVarintSimple($bytes, $ptr);
			$grid->g[$rt][] = ReadVarintSimple($bytes, $ptr);
		}
	}
	if($isnpl){
		$grid->npl = $bytes[count($bytes) - 1];
	}
	return $grid;
}


// Simpler functions, basically the same but don't move any pointers
function StringToInt32(string $raw, int $offset){
	$s = substr($raw, $offset, 4);
	$result = unpack("l", $s)[1];
	return $result;
}
function StringToFloat(string $raw, int $offset){
	$s = substr($raw, $offset, 4);
	$result = unpack("f", $s)[1];
	return $result;
}
function Int32IntoString(string &$output, int $offset, int $value){
	$bin = str_pad(pack("l", $value), 4, "\0");
	for($i = 0; $i < 4; ++$i){
		$output[$offset + $i] = $bin[$i];
	}
}
function FloatIntoString(string &$output, int $offset, float $value){
	$bin = str_pad(pack("f", $value), 4, "\0");
	for($i = 0; $i < 4; ++$i){
		$output[$offset + $i] = $bin[$i];
	}
}
