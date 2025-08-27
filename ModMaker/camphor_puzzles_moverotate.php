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
include_once("include\\chests.php");

$pdbIn  = "..\\OutputJsons\\PuzzleDatabase.json";
$pdbOut = "..\\OutputJsons\\PuzzleDatabase.json";
$sbzIn  = "..\\OutputJsons\\SandboxZones.json";
$sbzOut = "..\\OutputJsons\\SandboxZones.json";

$ROTATOR = UeDefaultTransform();
$ROTATOR->Translation->X = 18223.021484375;
$ROTATOR->Translation->Y = 42844.50390625;
$ROTATOR->Translation->Z = 1371.434814453125;
$ROTATOR->Rotation->X = 0;
$ROTATOR->Rotation->Y = 30;
$ROTATOR->Rotation->Z = 0;

$MOVER = UeDefaultTransform();
$MOVER->Translation->X =  32300 +  8660;
$MOVER->Translation->Y = -48000 - 15000;
$MOVER->Translation->Z = -10000;

$camphorWorldPuzzles = [
	25257,25258,25259,25260, // archways
	25263,25264,25265,25266,25267,25268,25269,25270,25271,25272, // matchboxes
	25273,25274,25275,25276,25277,25278,25279,25280,25281,25282,25283,25284,25285,25286,25287,25288,25289, // unused matchboxes?
	25290,25291,25292, // gliderings
	25293,25294, // unused gliderings?
];

$jsonPuzzleDatabase = LoadDecodedUasset($pdbIn);
$puzzleDatabase     = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($sbzIn);
$sandboxZones     = &ParseUassetSandboxZones($jsonSandboxZones);

foreach($camphorWorldPuzzles as $pid){
	if(!isset($puzzleDatabase->krakenIDToWorldPuzzleData[$pid])){
		printf("[WARNING] Puzzle %d doesn't exist, ignoring.\n", $pid);
		continue;
	}
	$ser_ref = &$puzzleDatabase->krakenIDToWorldPuzzleData[$pid];
	$miniJson = json_decode($ser_ref);
	//if(!in_array($miniJson->PuzzleClass, [ "BP_ArchwayFind_C", "BP_Matchbox_C" ])) { var_dump($miniJson); exit(1); }
	//var_dump($miniJson);
	foreach($miniJson as $key => &$value_ref){
		static $adjustTheseFields = [ "ActorTransform", "StartingPlatformTransform" ];
		if(in_array($key, $adjustTheseFields)){
			//printf("%s\n", $value_ref);
			$t = UeTransformUnpack($value_ref);
			$t = UeTransformRotateAround($t, $ROTATOR);
			$t = UeTransformAdd($t, $MOVER);
			UeTransformPackInto($t, $value_ref);
			unset($t);
			//printf("%s\n", $value_ref);
		}elseif(preg_match("/^SERIALIZEDSUBCOMP_PuzzleBounds\-\d*$/", $key)){
			$t = UeTransformUnpack($value_ref->WorldTransform);
			$t = UeTransformRotateAround($t, $ROTATOR);
			$t = UeTransformAdd($t, $MOVER);
			UeTransformPackInto($t, $value_ref->WorldTransform);
			unset($t);
			
			$box = UeBoxUnpack($value_ref->Box);
			$box = UeBoxRotateAround($box, $ROTATOR);
			$box = UeBoxAdd($box, $MOVER);
			UeBoxPackInto($box, $value_ref->Box);
			unset($box);
		}
	}unset($value_ref);
	$ser_ref = json_encode($miniJson);
	printf("[DEBUG] Moved puzzle %d (%s)\n", $pid, $miniJson->PuzzleClass);
	//var_dump($miniJson);
	unset($ser_ref);
}

foreach($sandboxZones->Containers as $localID => &$container_ref){
	if(!preg_match("/BP_RuneAnimated_C Wrap(?:Four|LR)_pid\d+/", $localID)){
		continue;
	}
	
	//printf("%s\n\n", $container_ref->serializedString);
	$container_ref->ownerZone = "EMainMapZoneName::Central";
	$miniJson = json_decode($container_ref->serializedString);
	$t = UeTransformUnpack($miniJson->ActorTransform);
	$t = UeTransformRotateAround($t, $ROTATOR);
	$t = UeTransformAdd($t, $MOVER);
	UeTransformPackInto($t, $miniJson->ActorTransform);
	$container_ref->serializedString = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
	//printf("%s\n\n", $container_ref->serializedString);
	
	printf("[DEBUG] Moved wrap rune %s (grid %d)\n", $localID, $miniJson->desiredKrakenIDOverride);
}unset($container_ref);

SaveCompressedDecodedUasset($pdbOut, $jsonPuzzleDatabase, [
	"skipArrayIndices" => true,
	"bakeAllIndices" => true,
	"scalarizeNodes" => [ "Solves" ],
]);

SaveCompressedDecodedUasset($sbzOut, $jsonSandboxZones, [
	"skipArrayIndices" => false,
	"bakeAllIndices" => true,
]);
