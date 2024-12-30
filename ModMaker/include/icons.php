<?php

include_once("include\\lookup.php");
include_once("include\\config.php");
include_once("include\\imageSmoothArc_fast.php");
include_once("include\\imageSmoothLine.php");
include_once("include\\imgex.php");
include_once("include\\drawCommon.php");

$g_icons = [];

function GetIcon(string $localName, array $options = []){
	$defaultOptions = [
		"alpha"      => 1.0, // opaque
	];
	$options    = array_merge($defaultOptions, $options);
	$alpha      = ParseAlpha($options["alpha"]);
	
	$test = $options["alpha"];
	global $g_icons;
	
	$internalName = str_replace(["/", "\\"], "-", trim(strtolower($localName)));
	//printf("Loading %s\n", $internalName);
	
	$key = implode(",", [ $internalName, $alpha ]);
	
	if(isset($g_icons[$key])){
		return $g_icons[$key];
	}
	$path = asDir("media\\img\\icons") . $localName . ".png";
	$gd = imagecreatefrompng($path);
	if(!$gd){
		printf("%s\n", ColorStr("Failed to load canvas " . $path, 255, 128, 128));
		exit(1);
	}
	
	imagealphablending($gd, false);
	imagesavealpha($gd, true);
	
	ChangeImageAlpha($gd, $alpha);
	$g_icons[$key] = $gd;
	
	return $gd;
}
