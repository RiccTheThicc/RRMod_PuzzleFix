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
$forceOnlyZone = -1;
$forceDebugGrids = [ ];
$forceSpawnPids = [ ];



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

//if($forceOnlyZone >= 2 && $forceOnlyZone <= 6){
if($forceOnlyZone >= 1 || !empty($forceSpawnPids) || !empty($forceDebugGrids)){
	printf("%s\n", ColorStr("Warning: debug mode enabled. Don't ship the .pak like this.", 255, 128, 128));
	
	foreach($puzzleDatabase->puzzleZoneToGroupNumOverrides as $zoneEnumName => &$ptypeArray_ref){
		//$zoneIndex = ZoneNameToInt($zoneEnumName);
		//if($zoneIndex == $forceOnlyZone){
		//	$ptypeArray_ref["logicGrid"] = 200;
		//	continue;
		//}
		//foreach($ptypeArray_ref as $ptype => &$groupSize_ref){
		//	$groupSize_ref = 0;
		//}unset($groupSize_ref);
		$zoneIndex = ZoneNameToInt($zoneEnumName);
		foreach($ptypeArray_ref as $ptype => &$groupSize_ref){
			if(in_array($ptype, [ "logicGrid", "completeThePattern", "musicGrid", "memoryGrid" ])){// || $zoneIndex != $forceOnlyZone){
				printf("%s\n", ColorStr(sprintf("  Removing group %-18s in %s", $ptype, ZoneToPrettyNoColor($zoneIndex)), 160, 160, 160));
				$groupSize_ref = 0;
			}
		}unset($groupSize_ref);
	}unset($ptypeArray_ref);
	
	$hubPids = GetAllHubPids();
	$zonePids = (IsHubZone($forceOnlyZone) ? (ReduceProfileToZones(GetHubProfile()))[$forceOnlyZone] : []);
	$wrongHubPids = array_diff($hubPids, $zonePids);
	$allRoughBounds = LoadCsvMap("media\\mod\\roughZoneBounds.csv", "zoneIndex");
	$roughBounds = (IsHubZone($forceOnlyZone) ? (object)($allRoughBounds[$forceOnlyZone]) : (object)[ "minX" => 1, "maxX" => 1, "minY" => 1, "maxY" => 1 ]);
	
	foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => &$ser_ref){
		$miniJson = json_decode($ser_ref);
		if($miniJson->PuzzleType == "ghostObject"){
			$miniJson->forceTutorialColors = true;
		}
		//var_dump($miniJson); exit(1);
		$t = UeTransformUnpack($miniJson->ActorTransform);
		if($t->Translation->X >= $roughBounds->minX &&
		   $t->Translation->X <= $roughBounds->maxX &&
		   $t->Translation->Y >= $roughBounds->minY &&
		   $t->Translation->Y <= $roughBounds->maxY &&
		   //!in_array($pid, $wrongHubPids) &&
		   (in_array($pid, GetAllHubPids()) || in_array($pid, GetAllStaticPids()))
			//|| ($miniJson->PuzzleType == "lightPattern" && $miniJson->Zone == 3)
			|| (isset($forceSpawnPids) && !empty($forceSpawnPids) && in_array($pid, $forceSpawnPids))
		   ){
			   $miniJson->SpawnBehaviour = 0;
			   //printf("Enabling puzzle %d %s from %s\n", $pid, $miniJson->PuzzleType, ZoneToPretty(GetPuzzleMap(true)[$pid]->actualZoneIndex));
			   //printf("Enabling puzzle %d %s from %s\n", $pid, $miniJson->PuzzleType, ZoneToPretty($miniJson->Zone));
		}else{
			$miniJson->SpawnBehaviour = 1;
			//$miniJson->Disabled = true; // doesn't work
		}
		$ser_ref = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
	}unset($ser_ref);
	
	$profileHub = GetHubProfile();
	$reduced = ReduceProfileToPtypes($profileHub);
	$gridPids = $reduced["logicGrid"] + $reduced["completeThePattern"] + $reduced["musicGrid"] + $reduced["memoryGrid"];
	shuffle($gridPids);
	$nextPidIndex = 0;
	
	$exports_ref = &$jsonSandboxZones->Exports;
	$zoneContainerMap = [];
	
	foreach($exports_ref as $exportIndex => &$jsonContainer_ref){
		$objectName = $jsonContainer_ref->ObjectName;
		if(!preg_match("/([\w]+)Zone1?/", $objectName, $matches)){
			continue;
		}
		$zoneName = $matches[1];
		if($zoneName == "Sandbox"){
			// Boring empty node.
			continue;
		}
		$zoneIndex = ZoneNameToInt($zoneName);
		//printf("Found %s (%d)\n", $matches[1], $zoneIndex);
		$zoneContainerMap[$zoneIndex] = (object)[];
		
		foreach($jsonContainer_ref->Data as $blobIndex => &$blob_ref){
			if(!isset($blob_ref->Name) || !isset($blob_ref->Value)){
				continue;
			}
			//$name_ref = &$blob_ref->Name;
			//$value_ref = &$blob_ref->Value;
			//if($name_ref == "alwaysSpawnContainerPuzzlesToBeSpawned"){
			//	$zoneContainerMap[$zoneIndex]->alwaysContainers = &$value_ref;
			//}elseif($name_ref == "defaultContainerPuzzlesToBeSpawned"){
			//	$zoneContainerMap[$zoneIndex]->defaultContainers = &$value_ref;
			//}
			//printf("%-18s %-40s %s\n", ZoneToPrettyNoColor($zoneIndex), $name_ref, (is_scalar($value_ref) ? $value_ref : "<node>"));
			$zoneContainerMap[$zoneIndex]->{$blob_ref->Name} = &$blob_ref->Value;
			
			//unset($name_ref);
			//unset($value_ref);
		}unset($blob_ref);
	}unset($jsonContainer_ref);
	ksort($zoneContainerMap);
	
	$runeCount = [];
	foreach($exports_ref as $iii => &$jsonContainer_ref){
		$exportIndex = $iii + 1;
		$localID = "";
		$isRune = false;
		$isHubContainer = false;
		$zoneIndex = -1;
		$isInbounds = false;
		
		$dataIndexSpawnBehaviour = -1;
		$dataIndexOwnerZone = -1;
		$dataIndexSerializedString = -1;
		
		foreach($jsonContainer_ref->Data as $blobIndex => &$blob_ref){
			//var_dump($blob_ref); exit(1);
			if(!isset($blob_ref->Name) || !isset($blob_ref->Value)){
				continue;
			}
			$name_ref = &$blob_ref->Name;
			$value_ref = &$blob_ref->Value;
			//printf("%s = %s\n", $name_ref, json_encode($value_ref));
			if($name_ref == "localID"){
				$localID = $value_ref;
			}elseif($name_ref == "possiblePuzzleTypes"){
				foreach($value_ref as $subValue){
					if($subValue->Value == "logicGrid"){
						$isRune = true;
					}
				}
			}elseif($name_ref == "ownerZone"){
				$zoneIndex = ZoneNameToInt($value_ref);
				$dataIndexOwnerZone = $blobIndex;
				//$isHubContainer = IsHubZone($zoneIndex); // nonono
			}elseif($name_ref == "serializedString"){
				$miniJson = json_decode($value_ref);
				$t = UeTransformUnpack($miniJson->ActorTransform);
				$isInBounds = ($t->Translation->X >= $roughBounds->minX &&
							   $t->Translation->X <= $roughBounds->maxX &&
							   $t->Translation->Y >= $roughBounds->minY &&
							   $t->Translation->Y <= $roughBounds->maxY );
				$isHubContainer = ($miniJson->SpawnBehaviour == 2);
				//printf("Export #%d, localID |%s|, spawnbehaviour is %d, thus it is a %s container, raw serialized string: |%s|\n",
				//		$exportIndex, $localID, $miniJson->SpawnBehaviour, ($isHubContainer ? "hub" : "static"), $value_ref);
				//var_dump($miniJson);
				unset($miniJson);
				$dataIndexSerializedString = $blobIndex;
			}elseif($name_ref == "SpawnBehaviour"){
				$dataIndexSpawnBehaviour = $blobIndex;
			}
			unset($name_ref);
			unset($value_ref);
		}unset($blob_ref);
		if(empty($localID) || !$isRune || !$isHubContainer){
			continue;
		}
		if(!$isInBounds){ continue; }
		
		// Turn this hub grid into a static grid.
		unset($jsonContainer_ref->Data[$dataIndexSpawnBehaviour]);
		//$jsonContainer_ref->Data[$indexSpawnBehaviour]->Value = "ESpawnBehaviour::AlwaysSpawn";
		unset($jsonContainer_ref->Data[$dataIndexOwnerZone]);
		$miniJson = json_decode($jsonContainer_ref->Data[$dataIndexSerializedString]->Value);
		$miniJson->SpawnBehaviour = 0;
		$miniJson->Zone = 0;
		$jsonContainer_ref->Data[$dataIndexSerializedString]->Value = CreateJson($miniJson); //json_encode($miniJson, JSON_PRETTY_PRINT);
		unset($miniJson);
		
		$actualPid = (!isset($forceDebugGrids) || empty($forceDebugGrids) ? $gridPids[$nextPidIndex] : $forceDebugGrids[$nextPidIndex % count($forceDebugGrids)]);
		
		$jsonContainer_ref->Data[] = (object)[
			"\$type" => "UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI",
			"Name" => "desiredKrakenIDOverride",
			"DuplicationIndex" => 0,
			"Value" => $actualPid,
        ];
		//printf("Using pid %d\n", $actualPid);
		
		// krakenIDToPuzzleStatus ?
		$jsonContainer_ref->Data = array_values($jsonContainer_ref->Data);
		$puzzleDatabase->krakenIDToContainedPuzzleData[$actualPid]["Status"] = "dungeon";
		$puzzleDatabase->krakenIDToPuzzleStatus[$actualPid] = "dungeon";
		//$puzzleDatabase->krakenIDToContainedPuzzleData[232]["Status"] = "dungeon";
		//var_dump($jsonContainer_ref); exit(1);
		
		if(!isset($runeCount[$zoneIndex])){
			$runeCount[$zoneIndex] = 0;
		}
		++$runeCount[$zoneIndex];
		
		// Here's a problem though. None of that is enough.
		// We must now turn this rune from a "default spawn" rune to an "always spawn" rune.
		// This gets tricky and messy really fast.
		$isFound = false;
		$internalIndex = -1;
		foreach($zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned as $arrayIndex => &$element_ref){
			if($element_ref->Value == $exportIndex){
				$internalIndex = $arrayIndex;
				//printf("Found exportIndex %d as array index %d for zone %s\n", $exportIndex, $internalIndex, ZoneToPrettyNoColor($zoneIndex));
				$isFound = true;
				break;
			}
		}unset($element_ref);
		if(!$isFound){
			printf("Could not find exportIndex %d as some array index for zone %s\n", $exportIndex, ZoneToPrettyNoColor($zoneIndex));
			var_dump($jsonContainer_ref); exit(1);
		}
		
		$zoneContainerMap[$zoneIndex]->alwaysSpawnContainerPuzzlesToBeSpawned[] = $zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned[$internalIndex];
		unset($zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned[$internalIndex]);
		
		$zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned = array_values($zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned);
		foreach($zoneContainerMap[$zoneIndex]->defaultContainerPuzzlesToBeSpawned as $arrayIndex => &$element_ref){
			$element_ref->Name = (string)$arrayIndex;
		}unset($element_ref);
		
		$zoneContainerMap[$zoneIndex]->alwaysSpawnContainerPuzzlesToBeSpawned = array_values($zoneContainerMap[$zoneIndex]->alwaysSpawnContainerPuzzlesToBeSpawned);
		foreach($zoneContainerMap[$zoneIndex]->alwaysSpawnContainerPuzzlesToBeSpawned as $arrayIndex => &$element_ref){
			$element_ref->Name = (string)$arrayIndex;
		}unset($element_ref);
		
		//printf("\n\n\n");
		++$nextPidIndex;
	}unset($jsonContainer_ref);
	ksort($runeCount);
	//print_r($runeCount);
	
	unset($zoneContainerMap);
	unset($exports_ref);
}
//exit(1);


///////////////////////////////////////////////////////////////////////////////
// Move stuff around.
///////////////////////////////////////////////////////////////////////////////

printf("> Adjusting asset coordinates...\n");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_slab_sockets.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_cluster_runes.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_miscellaneous.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_verdant_glen.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_lucent_waters.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_autumn_falls.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_shady_wildwoods.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_serene_deluge.csv");
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\mod\\adjust_ztemp.csv");



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

// Matchbox slight scale to fit - experimental.
$scaleTest = [
	// Lucent
	"10146/Mesh2Transform/1.21",
	"10164/Mesh1Transform/1.21",
	"10139/Mesh1Transform/1.21",
	"9883/Mesh1Transform/1.33",
	"9905/Mesh1Transform/1.33",
	"10143/Mesh1Transform/1.27",
	"10145/Mesh1Transform/1.27",
	"10138/Mesh1Transform/0.8",
	"10082/Mesh1Transform/1.13",
	"10057/Mesh2Transform/0.85",
	// Autumn
	"6803/Mesh2Transform/1.50",
	// Shsdy
	"8843/Mesh1Transform/0.90",
	"8843/Mesh2Transform/0.85",
	"13727/Mesh1Transform/0.80",
	"13727/Mesh2Transform/0.80",
	"8804/Mesh1Transform/0.93",
	"8817/Mesh1Transform/0.92",
	// Serene
	"17091/Mesh1Transform/1.10",
	"17471/DuplicateTransform-4/0.80",
	"17471/DuplicateTransform-3/0.80",
	"16864/Mesh1Transform/0.90",
];
foreach($scaleTest as $amalgam){
	list($pid, $element, $scale) = explode("/", $amalgam);
	$hadPrettyPrint = str_contains($puzzleDatabase->krakenIDToWorldPuzzleData[$pid], "\n");
	$miniJson = json_decode($puzzleDatabase->krakenIDToWorldPuzzleData[$pid]);
	$isScaleApplied = false;
	foreach((array)($miniJson) as $key => $value){
		if($isScaleApplied){
			break;
		}
		//printf("  Checking key |%s|\n", $key);
		if($key == $element){
			$t = UeTransformUnpack($miniJson->$key);
			$c = json_decode(json_encode($t));
			$t->Scale3D->X *= (float)$scale;
			$t->Scale3D->Y *= (float)$scale;
			$t->Scale3D->Z *= (float)$scale;
			UeTransformPackInto($t, $miniJson->$key);
			$isScaleApplied = true;
			printf("%s\n", ColorStr(sprintf("  Scaled pid %5d, element %-20s, scale %.2f, (%.2f, %.2f, %.2f) -> (%.2f, %.2f, %.2f)",
												$pid, $element, $scale,
												$c->Scale3D->X, $c->Scale3D->Y, $c->Scale3D->Z,
												$t->Scale3D->X, $t->Scale3D->Y, $t->Scale3D->Z),
												160, 160, 160));
			break;
		}
		elseif(is_object($value)){
			foreach((array)($value) as $subKey => $subValue){
				//printf("    Checking subKey |%s| (%s)\n", $subKey, $subValue);
				if($subKey == $element){
					$t = UeTransformUnpack($miniJson->$key->$subKey);
					$c = json_decode(json_encode($t));
					$t->Scale3D->X *= (float)$scale;
					$t->Scale3D->Y *= (float)$scale;
					$t->Scale3D->Z *= (float)$scale;
					UeTransformPackInto($t, $miniJson->$key->$subKey);
					$isScaleApplied = true;
					printf("%s\n", ColorStr(sprintf("  Scaled pid %5d, element %-20s, scale %.2f, (%.2f, %.2f, %.2f) -> (%.2f, %.2f, %.2f)",
														$pid, $element, $scale,
														$c->Scale3D->X, $c->Scale3D->Y, $c->Scale3D->Z,
														$t->Scale3D->X, $t->Scale3D->Y, $t->Scale3D->Z),
														160, 160, 160));
					break;
				}
			}
		}
	}
	if(!$isScaleApplied){
		printf("%s\n", ColorStr(sprintf("Warning: failed to scale pid %5d, element %-20s, scaler %.2f\n", $pid, $element, $scale)));
		continue;
	}
//	$t = UeTransformUnpack($miniJson->$element);
//	$t->Scale3D->X *= (float)$scale;
//	$t->Scale3D->Y *= (float)$scale;
//	$t->Scale3D->Z *= (float)$scale;
//	UeTransformPackInto($t, $miniJson->$element);
	//$puzzleDatabase->krakenIDToWorldPuzzleData[$pid] = json_encode($miniJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	$puzzleDatabase->krakenIDToWorldPuzzleData[$pid] = json_encode($miniJson, JSON_UNESCAPED_SLASHES | ($hadPrettyPrint ? JSON_PRETTY_PRINT : 0x00));
}

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

// Expand a few maze-only slabs slightly so that they could hold larger mazes.
$sandboxZones->Containers["SlabSocket_C--jonat--LFGPAXXJEXVZCYFVLCQSKLMSVMLR--1681748341--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/RiverlandEscarpment/RiverlandPuzzles/RiverlandSlabs.RiverlandSlabs:PersistentLevel.SlabSocket8"]->PuzzleBoxExtent->X = 830;
$sandboxZones->Containers["SlabSocket_C--jonat--LFGPAXXJEXVZCYFVLCQSKLMSVMLR--1681748341--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/RiverlandEscarpment/RiverlandPuzzles/RiverlandSlabs.RiverlandSlabs:PersistentLevel.SlabSocket8"]->PuzzleBoxExtent->Y = 830;
$sandboxZones->Containers["SlabSocket_C--jbard--AVHFOKBQULBYXCGFFSPSBWQFXMEO--1679963602--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/RiverlandEscarpment/RiverlandPuzzles/RiverlandSlabs.RiverlandSlabs:PersistentLevel.SlabSocket5"]->PuzzleBoxExtent->X = 840;
$sandboxZones->Containers["SlabSocket_C--jbard--AVHFOKBQULBYXCGFFSPSBWQFXMEO--1679963602--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/RiverlandEscarpment/RiverlandPuzzles/RiverlandSlabs.RiverlandSlabs:PersistentLevel.SlabSocket5"]->PuzzleBoxExtent->Y = 840;
$sandboxZones->Containers["SlabSocket_C--alyss--FSOTMDRPDCLLGHAETOHLQZUVXHNM--1679313245--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/Mountain/MountainPuzzles/Mountain_Slabs.Mountain_Slabs:PersistentLevel.SlabSocket5"]->PuzzleBoxExtent->X = 760;
$sandboxZones->Containers["SlabSocket_C--alyss--FSOTMDRPDCLLGHAETOHLQZUVXHNM--1679313245--SlabSocket_C " .
	"/Game/ASophia/Maps/MainMapSubmaps/Mountain/MountainPuzzles/Mountain_Slabs.Mountain_Slabs:PersistentLevel.SlabSocket5"]->PuzzleBoxExtent->Y = 760;

// Fix two broken mazes.
$maze25027_ref = &$puzzleDatabase->krakenIDToContainedPuzzleData[25027]["Serialized"]; //var_dump($maze25027_ref);
$maze25027_ref = str_replace("ew0KCSJyYW5kU2VlZCI6ID" . "kwMzUz", "ew0KCSJyYW5kU2VlZCI6ID" . "kwMzUw", $maze25027_ref);
unset($maze25027_ref); // serene broken maze, change randSeed carefully so that it stays in serene

$maze25037_ref = &$puzzleDatabase->krakenIDToContainedPuzzleData[25037]["Serialized"]; //var_dump($maze25037_ref);
$maze25037_ref = str_replace("ew0KCSJyYW5kU2VlZCI6ID" . "Q1NDAx", "ew0KCSJyYW5kU2VlZCI6ID" . "Q1NDA0", $maze25037_ref);
unset($maze25037_ref); // shady broken maze, change randSeed carefully so that it stays in shady

// Change shy aura 17049 type. It's a blue one located on a tree branch that has no collision. No chance of fixing that.
$miniJson = json_decode($puzzleDatabase->krakenIDToWorldPuzzleData[17049]);
$miniJson->ghostType = 5; // 1=green/stop, 2=red/sight, 3=blue/crawl, 5=pink/fly
$puzzleDatabase->krakenIDToWorldPuzzleData[17049] = json_encode($miniJson, JSON_UNESCAPED_SLASHES);

// Fix light motif render distances.
foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => &$ser_ref){
	$miniJson = json_decode($ser_ref);
	if($miniJson->PuzzleType != "lightPattern"){
		continue;
	}
	$t = UeTransformUnpack($miniJson->lightDecalTransform);
	// The "scale" of the decal doesn't change the decal itself, yet affects the distance at which they fade out.
	// Smaller motifs result in (auto-generated) smaller render distances. Some tiny ones are invisible a few steps away.
	$t->Scale3D->X = max(60, $t->Scale3D->X); // pick 36 or 60
	$t->Scale3D->Y = max(20, $t->Scale3D->Y); // pick 12 or 20
	$t->Scale3D->Z = max(20, $t->Scale3D->Z); // pick 12 or 20
	UeTransformPackInto($t, $miniJson->lightDecalTransform);
	$ser_ref = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}unset($ser_ref);



///////////////////////////////////////////////////////////////////////////////
// Graveyard.
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
