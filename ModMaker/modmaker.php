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



///////////////////////////////////////////////////////////////////////////////
// Setup paths.
///////////////////////////////////////////////////////////////////////////////

$inputPuzzleDatabase  = "..\\BaseJsons\\PuzzleDatabase.json";
$inputSandboxZones    = "..\\BaseJsons\\SandboxZones.json";
$inputReadables       = "..\\BaseReadable\\";

$outputPuzzleDatabase = "..\\OutputJsons\\PuzzleDatabase.json";
$outputSandboxZones   = "..\\OutputJsons\\SandboxZones.json";
$outputReadables      = "..\\OutputReadable\\";

$doExportFiles = true;
$spawnBehaviourOverride = false;



///////////////////////////////////////////////////////////////////////////////
// Load files.
///////////////////////////////////////////////////////////////////////////////

$jsonPuzzleDatabase = LoadDecodedUasset($inputPuzzleDatabase);
$puzzleDatabase = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($inputSandboxZones);
$sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);

SaveReadableUassetDataTo($inputReadables, $puzzleDatabase, $sandboxZones);



///////////////////////////////////////////////////////////////////////////////
// Workshop area - test changes go here.
///////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////
// Override spawn behavior to spawn every World puzzle at once - if needed.
///////////////////////////////////////////////////////////////////////////////

if($spawnBehaviourOverride){
	// Spawn everything.
	foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => &$ser_ref){
		$miniJson = json_decode($ser_ref);
		$miniJson->SpawnBehaviour = 0;
		$ser_ref = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
	}unset($ser_ref);
}



///////////////////////////////////////////////////////////////////////////////
// Move stuff around.
///////////////////////////////////////////////////////////////////////////////

printf("> Adjusting asset coordinates...\n");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjuster3.csv");



///////////////////////////////////////////////////////////////////////////////
// Remove incompatible kraken ids and yeet all bounds.
///////////////////////////////////////////////////////////////////////////////

// Add non-blocking bounds to non-enclave armillaries. This prevents them from generating blocking bounds.
foreach($puzzleDatabase->krakenIDToContainedPuzzleData as $pid => &$arr_ref){
	if(isset($arr_ref["Serialized"])){
		$ptype = json_decode($arr_ref["Serialized"])->PuzzleType;
		if($ptype == "gyroRing" && in_array((int)$pid, GetTempleArmillaries())){
			$miniJson = json_decode($arr_ref["Serialized"]);
			static $testIndex = 1;
			$x = (float)$testIndex;
			$y = (float)$testIndex;
			$z = 0;
			$miniJson->{'SERIALIZEDSUBCOMP_PuzzleBounds-0'} = (object)[
				"RelativeTransform"            => sprintf("%.1f,%.1f,%.1f|0.000000,0.000000,0.000000|1.000000,1.000000,1.000000", $x, $y, $z - 1e7),
				"bUseForDungeonIdentification" => false, // careful with this one
				"acceptAllByDefault"           => true,
				"bBlockSpawning"               => false,
				"rejectedTypes"                => "",
				"WorldTransform"               => sprintf("%.1f,%.1f,%.1f|0.000000,0.000000,0.000000|1.000000,1.000000,1.000000", $x, $y, $z),
				"Box"                          => sprintf("Min=X=%.1f Y=%.1f Z=%.1f|Max=X=%.1f Y=%.1f Z=%.1f", $x - 0.1, $y - 0.1, $z - 0.1 - 1e7, $x + 0.1, $y + 0.1, $z + 0.1 - 1e7),
			];
			++$testIndex;
			//object(stdClass)#923508 (7) { // sample
			//  ["RelativeTransform"]            => string(82) "0.000000,0.000000,295.000000|0.000000,0.000000,0.000000|2.000000,2.000000,2.000000"
			//  ["bUseForDungeonIdentification"] => bool(true)
			//  ["acceptAllByDefault"]           => bool(false)
			//  ["bBlockSpawning"]               => bool(true)
			//  ["acceptedTypes"]                => string(0) ""
			//  ["WorldTransform"]               => string(95) "-22065.705078,-16037.631836,39663.035156|0.000000,98.410042,0.000000|2.000000,2.000000,2.000000"
			//  ["Box"]                          => string(83) "Min=X=-22129.705 Y=-16101.632 Z=39599.035|Max=X=-22001.705 Y=-15973.632 Z=39727.035"
			$arr_ref["Serialized"] = json_encode($miniJson);
		}
	}
}unset($arr_ref);

printf("> Removing incompatibles and yeeting blocking bounds...\n");
foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => &$ser_ref){
	DisableSerializedIncompatibles($ser_ref);
	DisableSerializedBounds($ser_ref);
	ShrinkSerializedBounds($ser_ref);
}unset($ser_ref);

foreach($puzzleDatabase->krakenIDToContainedPuzzleData as $pid => &$arr_ref){
	// Logic (and other) grids use Pdata key for puzzle contents. Other contained puzzles use Serialized key for it.
	if(isset($arr_ref["Serialized"])){
		$ptype = json_decode($arr_ref["Serialized"])->PuzzleType;
		// Do not modify Serialized strings of floor-slab puzzles!
		// UPD: turns out you can't modify wall slabs either.
		if(!in_array($ptype, [ "ryoanji", "rollingCube", "mirrorMaze", "match3", "klotski", "lockpick", "fractalMatch" ])){
			DisableSerializedIncompatibles($arr_ref["Serialized"]);
			DisableSerializedBounds($arr_ref["Serialized"]);
			ShrinkSerializedBounds($arr_ref["Serialized"]);
		}
	}
}unset($arr_ref);

foreach($sandboxZones->Containers as $localID => &$container_ref){
	$containerType = $container_ref->containerType; // "Rune", "Monument", "SlabSocket", or "GyroSpawn".
	
	if(isset($container_ref->serializedString)){
		DisableSerializedIncompatibles($container_ref->serializedString);
		DisableSerializedBounds($container_ref->serializedString);
		ShrinkSerializedBounds($container_ref->serializedString);
	}
	if(isset($container_ref->puzzleBoundsTransforms)){
		YeetBoundsTransform($container_ref->puzzleBoundsTransforms);
	}
	if(isset($container_ref->puzzleBoundsBoxes)){
		YeetBoundsBox($container_ref->puzzleBoundsBoxes);
	}
}unset($container_ref);



///////////////////////////////////////////////////////////////////////////////
// Swap ryoanji data.
///////////////////////////////////////////////////////////////////////////////

printf("> Fixing broken sentinel stones...\n");
$ryoanjiSwapPath = "media\\mod\\ryoanji_swap.csv";
$ryoanjiSwapCsv = LoadCsv($ryoanjiSwapPath);

// Sanity check
$ryoanjiParticipants = array_values(array_unique(array_merge(array_column($ryoanjiSwapCsv, "pid"), array_column($ryoanjiSwapCsv, "takeFrom"))));
if(count($ryoanjiParticipants) != count($ryoanjiSwapCsv) * 2){
	printf("Ryoanji swap error: duplicate pids detected (or malformed input) - %d/%d unique pids on the list\n", count($ryoanjiParticipants), count($ryoanjiSwapCsv) * 2);
	exit(1);
}

foreach($ryoanjiSwapCsv as $entry){
	$pid             = $entry["pid"];
	$takeFrom        = $entry["takeFrom"];
	
	$originalSer_ref = &$puzzleDatabase->krakenIDToContainedPuzzleData[$pid]["Serialized"];
	$providerSer_ref = &$puzzleDatabase->krakenIDToContainedPuzzleData[$takeFrom]["Serialized"];
	
	// Warning: unlike everything else, serialized container puzzles must not be re-encoded. Hence this regex fuckery.
	preg_match("/\"BinaryData\":\s*\"(.*?)\"/", $originalSer_ref, $tempA);
	preg_match("/\"BinaryData\":\s*\"(.*?)\"/", $providerSer_ref, $tempB);
	$originalPuzzle  = $tempA[1]; //printf("%s\n\n", $originalPuzzle);
	$replacerPuzzle  = $tempB[1]; //printf("%s\n\n", $replacerPuzzle);
	$oldSize         = GetRyoanjiSize($originalPuzzle);
	$newSize         = GetRyoanjiSize($replacerPuzzle);
	
	$originalSer_ref = preg_replace("/\"BinaryData\": \"(.*?)\"/", "\"BinaryData\": \"" . $replacerPuzzle . "\"", $originalSer_ref);
	$providerSer_ref = preg_replace("/\"BinaryData\": \"(.*?)\"/", "\"BinaryData\": \"" . $originalPuzzle . "\"", $providerSer_ref);
	
	//printf("hub ryoanji %d (%4dx%4d) <-> deprecated ryoanji %d (%4dx%4d)\n", $pid, $oldSize, $oldSize, $takeFrom, $newSize, $newSize);
	unset($originalSer_ref);
	unset($providerSer_ref);
}



///////////////////////////////////////////////////////////////////////////////
// Fix to make ryoanji-only floor slabs allow very large puzzles.
///////////////////////////////////////////////////////////////////////////////

foreach($sandboxZones->Containers as $localID => &$container_ref){
	if($container_ref->containerType == "SlabSocket"   &&
	    str_starts_with($localID, "SlabSocket")        &&
		!is_array($container_ref->possiblePuzzleTypes) && 
		$container_ref->possiblePuzzleTypes == "ryoanji"){
			$container_ref->PuzzleBoxExtent->X = 6001.0;
			$container_ref->PuzzleBoxExtent->Y = 6001.0;
	}
}unset($container_ref);



///////////////////////////////////////////////////////////////////////////////
// Final touches....
///////////////////////////////////////////////////////////////////////////////

// Fix baby monuments in autumn.
foreach($sandboxZones->Containers as $localID => &$container_ref){
	if($container_ref->containerType == "Monument" && isset($container_ref->monumentTransform)){
		$t = UeTransformUnpack($container_ref->monumentTransform);
		static $minMonumentScale = 0.80;
		$t->Scale3D->X = max($minMonumentScale, $t->Scale3D->X);
		$t->Scale3D->Y = max($minMonumentScale, $t->Scale3D->Y);
		$t->Scale3D->Z = max($minMonumentScale, $t->Scale3D->Z);
		UeTransformPackInto($t, $container_ref->monumentTransform);
	}
}unset($container_ref);



///////////////////////////////////////////////////////////////////////////////
// Extras - currently unused.
///////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////
// Export files.
///////////////////////////////////////////////////////////////////////////////

if($doExportFiles){
	SaveCompressedDecodedUasset($outputPuzzleDatabase, $jsonPuzzleDatabase);
	SaveCompressedDecodedUasset($outputSandboxZones,   $jsonSandboxZones);
}else{
	printf("All done! Export omitted.\n");
}

SaveReadableUassetDataTo($outputReadables, $puzzleDatabase, $sandboxZones);
