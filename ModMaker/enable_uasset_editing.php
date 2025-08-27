<?php

include_once("include\\pjson_parse.php");
include_once("include\\config.php");
include_once("include\\file_io.php");
include_once("include\\puzzleDecode.php");
include_once("include\\ryoanjiDecode.php");
include_once("include\\timex.php");
include_once("include\\stringex.php");
include_once("include\\lookup.php");
include_once("include\\profile.php");
include_once("include\\drawmap.php");
include_once("include\\renderCache.php");
include_once("include\\cluster.php");
include_once("include\\stats.php");
include_once("include\\savefile.php");
include_once("include\\playerCard.php");
include_once("include\\steam.php");
include_once("include\\jsonex.php");
include_once("include\\uassetParse.php");
include_once("include\\uassetHelper.php");

// Hardcore the input path here or pass it as a command-line arg.
$inputPath = "";

if($argc >= 2){
	$inputPath = $argv[1];
}
if(empty($inputPath)){
	printf("Feed me a file path to compress :)\n");
	exit(1);
}
if(!is_file($inputPath)){
	printf("Cannot locate file %s\n", $inputPath);
	exit(1);
}

$json = LoadDecodedUasset($inputPath);
SaveCompressedDecodedUasset($inputPath, $json, [
	"skipArrayIndices" => true,
	"bakeAutoObjectNames" => true,
	"addObjectNamesToNameMap" => true,
	"buildDefaultIndices" => true,
	"simplifyImports" => true,
	//"scalarizeNodes" => [ "ConvexElems", "ResponseArray", "Points" ],
]);
