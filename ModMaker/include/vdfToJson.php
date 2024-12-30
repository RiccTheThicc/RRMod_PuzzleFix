<?php

// All credit goes to Luke Zbihlyj:
// https://github.com/lukezbihlyj/vdf-parser

function VdfToJson($input){
	$result = [];
	$input = trim(preg_replace('#^\s*//.+$#m', '', $input));
	
	$inKey = false;
	$inValue = false;
	$inSubArray = false;
	$openBracketCount = 0;

	$buffer = null;
	$key = null;
	$value = null;
	$lastChar = null;

	for ($i = 0; $i < strlen($input); $i++) {
		$char = $input[$i];

		if ($inSubArray) {
			if ($lastChar == '\\') {
				$buffer .= $char;
			} else {
				if ($char == '}' && $openBracketCount == 0) {
					$value = VdfToJson(trim($buffer));
					$buffer = null;
					$inSubArray = false;

					if (!is_null($key)) {
						$result[$key] = $value;
					} else {
						$result = $value;
					}

					$key = null;
					$value = null;
				} elseif ($char == '}') {
					$openBracketCount--;
					$buffer .= $char;
				} elseif ($char == '{') {
					$openBracketCount++;
					$buffer .= $char;
				} else {
					$buffer .= $char;
				}
			}
		} elseif ($inKey) {
			if ($char == '"' && $lastChar !== '\\') {
				$key = $buffer;
				$buffer = null;
				$inKey = false;
			} elseif ($char !== '\\') {
				$buffer .= $char;
			}
		} elseif ($inValue) {
			if ($char == '"' && $lastChar !== '\\') {
				$value = $buffer;
				$buffer = null;
				$inValue = false;
				
				$result[$key] = $value;
				
				$key = null;
				$value = null;
			} elseif ($char !== '\\') {
				$buffer .= $char;
			}
		} else {
			if ($char == '"' && is_null($key)) {
				$inKey = true;
			} elseif ($char == '"' && is_null($value)) {
				$inValue = true;
			} elseif ($char == '{') {
				$inSubArray = true;
				$openBracketCount = 0;
			}
		}

		$lastChar = $char;
	}

	return $result;
}
