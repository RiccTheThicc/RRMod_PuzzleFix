<?php

function CreateJson($data, array $options = []){
	$defaultOptions = [
		"spaces"  => 4,
		"tabs" => 1,
		"tabOffset" => 0,
		"useTabs" => true,
		"escapeSlashes" => true,
		"prettyPrint" => true,
		//"onePerLine" => false,
		"flags" => 0x00,
	];
	$options   = array_merge($defaultOptions, $options);
	$spaces    = $options["spaces"];
	$tabs      = $options["tabs"];
	$tabOffset = $options["tabOffset"];
	$useTabs   = (bool)$options["useTabs"];
	$escapeSlashes = (bool)$options["escapeSlashes"];
	$prettyPrint = (bool)$options["prettyPrint"];
	$givenFlags   = $options["flags"];
	
	//$final = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	// Magic (and slow) function that prints a json, except with 2 spaces for tabs instead of 4 (which is what the asset dumper does).
	$char = ($useTabs ? "\t" : " ");
	$padLen = ($useTabs ? $tabs : $spaces);
	$flags = $givenFlags | ($escapeSlashes ? JSON_UNESCAPED_SLASHES : 0x00) | ($prettyPrint ? JSON_PRETTY_PRINT : 0x00);
	$final = preg_replace_callback('/^(?: {4})+/m', function($m) use ($char, $padLen, $tabOffset) {
		return str_repeat($char, $padLen * (max(0, strlen($m[0]) / 4 + $tabOffset)));
		//}, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}, json_encode($data, $flags));
	return $final;
}
