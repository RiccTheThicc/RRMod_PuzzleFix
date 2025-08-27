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
$forceAdjacentAlso = true;
$forceAllFlorbs = false;
$forceDebugGrids = [
];
$forceSpawnPids = [
];



///////////////////////////////////////////////////////////////////////////////
// Load files.
///////////////////////////////////////////////////////////////////////////////

$jsonPuzzleDatabase   = LoadDecodedUasset($inputPuzzleDatabase);
$puzzleDatabase       = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);
$containedRefMap      = &BuildContainedPuzzlesRefMap($jsonPuzzleDatabase);
$externalStatusRefMap = &BuildExternalStatusRefMap($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($inputSandboxZones);
$sandboxZones     = &ParseUassetSandboxZones($jsonSandboxZones);

SaveReadableUassetDataTo($inputReadables, $puzzleDatabase, $sandboxZones);



///////////////////////////////////////////////////////////////////////////////
// Workshop area - test changes go here.
///////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////
// First, add new statics and clusters.
///////////////////////////////////////////////////////////////////////////////

$megaTableIn  = "media\\data\\megatable_v2.csv";
$megaTableOut = "media\\data\\megatable_v2_ext.csv";
$megaTableNew = LoadMegaTableNew($megaTableIn);

function CreateMegaTableEntry(array $data = []){
	// pid,parent,zoneIndex,ptype,category,path,comment,extraData,family,solveValue,coords
	return [
		"pid"        => $data["pid"]        ?? -1,
		"parent"     => $data["parent"]     ?? -1,
		"zoneIndex"  => $data["zoneIndex"]  ?? 7,
		"ptype"      => $data["ptype"]      ?? "unknown",
		"category"   => $data["category"]   ?? "",
		"path"       => $data["path"]       ?? "Unknown Puzzle",
		"comment"    => $data["comment"]    ?? "",
		"extraData"  => $data["extraData"]  ?? (object)[],
		"family"     => $data["family"]     ?? "unknown",
		"solveValue" => $data["solveValue"] ?? 0,
		"coords"     => $data["coords"]     ?? [],
	];
}

$liarsCsvPath = "media\\lostgrids\\embed_liars.csv";
EmbedUassetPuzzleMetadataFrom($liarsCsvPath, $containedRefMap, $externalStatusRefMap);
$liarSpawns = LoadCsvMap($liarsCsvPath, "pid", "\t");

foreach($liarSpawns as $pid => $data){
	if(empty($pid)){
		continue;
	}
	if(!in_array($data["status"], [ "dungeon", "tutorial", "tutorialA", "tutorialB" ])){
		continue;
	}
	// Grids in tutorial pillars are included. Pillars themselves are not.
	//$extraStaticPids[] = intval($pid);
	if($data["status"] == "dungeon"){
		$rune_ref = CreateStaticRune($jsonSandboxZones, [
			"forcedPid"  => $pid,
			"originX"    => 0,
			"originY"    => 0,
			"originZ"    => 0,
			"localID"    => "BP_RuneAnimated_C Liar_pid" . $pid,
		]);
		$megaTableNew[] = CreateMegaTableEntry([
			"pid" => $pid,
			"zoneIndex" => 4,
			"ptype" => "logicGrid",
			"category" => "LogicGrid",
			"path" => "World / Autumn Falls / Quest / Deceptive Whispers / LogicGrid_" . $pid,
			"family" => "static",
		]);
		unset($rune_ref);
	}
}
unset($sandboxZones); $sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_liars.csv");






$wrapLRCsvPath = "media\\lostgrids\\embed_wrapLR.csv";
EmbedUassetPuzzleMetadataFrom($wrapLRCsvPath, $containedRefMap, $externalStatusRefMap);
$wrapLRSpawns = LoadCsvMap($wrapLRCsvPath, "pid", "\t");

$tempLRWrapCounter = 0;
foreach($wrapLRSpawns as $pid => $data){
	if(empty($pid)){
		continue;
	}
	if(!in_array($data["status"], [ "dungeon", "tutorial", "tutorialA", "tutorialB" ])){
		continue;
	}
	// Grids in tutorial pillars are included. Pillars themselves are not.
	//$extraStaticPids[] = intval($pid);
	if($data["status"] == "dungeon"){
		$rune_ref = CreateStaticRune($jsonSandboxZones, [
			"forcedPid"  => $pid,
			"originX"    => 0, //7600 - ($tempLRWrapCounter++) * 100,
			"originY"    => 0, //48600,
			"originZ"    => 0, //8800,
			"localID"    => "BP_RuneAnimated_C WrapLR_pid" . $pid,
		]);
		$megaTableNew[] = CreateMegaTableEntry([
			"pid" => $pid,
			"zoneIndex" => 3,
			"ptype" => "logicGrid",
			"category" => "LogicGrid",
			"path" => "World / Lucent Waters / Enclave / A Warped Perspective / LogicGrid_" . $pid,
			"family" => "static",
		]);
		unset($rune_ref);
	}
}
unset($sandboxZones); $sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);
//AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_wrapLR.csv");



$wrapFourCsvPath = "media\\lostgrids\\embed_wrapFour.csv";
EmbedUassetPuzzleMetadataFrom($wrapFourCsvPath, $containedRefMap, $externalStatusRefMap);
$wrapFourSpawns = LoadCsvMap($wrapFourCsvPath, "pid", "\t");

$tempFourWrapCounter = 0;
foreach($wrapFourSpawns as $pid => $data){
	if(empty($pid)){
		continue;
	}
	if(!in_array($data["status"], [ "dungeon", "tutorial", "tutorialA", "tutorialB" ])){
		continue;
	}
	// Grids in tutorial pillars are included. Pillars themselves are not.
	//$extraStaticPids[] = intval($pid);
	if($data["status"] == "dungeon"){
		$rune_ref = CreateStaticRune($jsonSandboxZones, [
			"forcedPid"  => $pid,
			"originX"    => 0, //7600 - ($tempFourWrapCounter++) * 100,
			"originY"    => 0, //48700,
			"originZ"    => 0, //8800,
			"localID"    => "BP_RuneAnimated_C WrapFour_pid" . $pid,
		]);
		$megaTableNew[] = CreateMegaTableEntry([
			"pid" => $pid,
			"zoneIndex" => 3,
			"ptype" => "logicGrid",
			"category" => "LogicGrid",
			"path" => "World / Lucent Waters / Enclave / A Warped Perspective / LogicGrid_" . $pid,
			"family" => "static",
		]);
		unset($rune_ref);
	}
}
unset($sandboxZones); $sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);
//AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_wrapFour.csv");






EmbedUassetPuzzleMetadataFrom("media\\lostgrids\\embed_butterflies.csv", $containedRefMap, $externalStatusRefMap);
$butterflyMap = [
	(object)[ "pid" => 14056, "tag" => "A1", "x" => -6.0, "y" =>  7.0, "z" => 0.0 ],
	(object)[ "pid" => 14057, "tag" => "A2", "x" =>  6.0, "y" =>  7.0, "z" => 0.0 ],
	(object)[ "pid" => 14064, "tag" => "B1", "x" => -4.5, "y" => -4.5, "z" => 0.0 ],
	(object)[ "pid" => 14065, "tag" => "B2", "x" =>  4.5, "y" => -4.5, "z" => 0.0 ],
	(object)[ "pid" => 14074, "tag" => "C1", "x" => -6.5, "y" =>  3.5, "z" => 0.0 ],
	(object)[ "pid" => 14077, "tag" => "C2", "x" =>  0.0, "y" =>  3.5, "z" => 0.0 ],
	(object)[ "pid" => 14080, "tag" => "C3", "x" =>  6.5, "y" =>  3.5, "z" => 0.0 ],
	(object)[ "pid" => 14082, "tag" => "D1", "x" => -9.0, "y" =>  1.0, "z" => 0.0 ],
	(object)[ "pid" => 14085, "tag" => "D2", "x" =>  0.0, "y" =>  0.0, "z" => 0.0 ],
	(object)[ "pid" => 14088, "tag" => "D3", "x" =>  9.0, "y" =>  1.0, "z" => 0.0 ],
	(object)[ "pid" => 14083, "tag" => "E1", "x" => -9.5, "y" => -2.5, "z" => 0.0 ],
	(object)[ "pid" => 14084, "tag" => "E2", "x" =>  9.5, "y" => -2.5, "z" => 0.0 ],
	(object)[ "pid" => 14098, "tag" => "F1", "x" => -7.5, "y" => -5.0, "z" => 0.0 ],
	(object)[ "pid" => 14099, "tag" => "F2", "x" =>  7.5, "y" => -5.0, "z" => 0.0 ],
	(object)[ "pid" => 14062, "tag" => "E1", "x" => -3.0, "y" =>  8.0, "z" => 0.0 ],
	(object)[ "pid" => 14070, "tag" => "E2", "x" =>  3.0, "y" =>  8.0, "z" => 0.0 ],
	(object)[ "pid" => 14095, "tag" => "U1", "x" => -2.0, "y" => -3.0, "z" => 0.0 ],
	(object)[ "pid" => 14096, "tag" => "U2", "x" =>  2.0, "y" => -3.0, "z" => 0.0 ],
	(object)[ "pid" => 10639, "tag" => "U3", "x" =>  0.0, "y" =>  7.0, "z" => 0.0 ],
];
$butterflyOriginTransform = (object)[
	"x"     => 63900 - 30 + 30 -100 +60,
	"y"     => 65500 - 50 + 30 +100,
	"z"     => 18200 - 100,
	"pitch" => 1.5,
	"yaw"   => -36 - 4,
	"roll"  => 0.5,
	"rot"   => 0, // legacy compat
];
$butterflyOriginScale = (object)[
	"sx" => 58.0,
	"sy" => 58.0,
	"sz" => 58.0,
];
$butterflyLocalTransforms = array_map(function ($bfly) use ($butterflyOriginScale) {
	return sprintf("%.6f,%.6f,%.6f|%.6f,%.6f,%.6f|%.6f,%.6f,%.6f",
		$bfly->x * $butterflyOriginScale->sx,
		$bfly->y * $butterflyOriginScale->sy,
		$bfly->z * $butterflyOriginScale->sz,
		0, 0, 0,
		1, 1, 1);
	}, $butterflyMap);
//var_dump($butterflyLocalTransforms);
$butterflyWorldTransforms = CombineLocalTransform($butterflyOriginTransform, $butterflyLocalTransforms);
//var_dump($butterflyWorldTransforms);
for($i = 0; $i < count($butterflyMap); ++$i){
	$pid = $butterflyMap[$i]->pid;
	//$extraStaticPids[] = intval($pid);
	$worldTransform = $butterflyWorldTransforms[$i];
	$rune_ref = CreateStaticRune($jsonSandboxZones, [
		"parentName" => "MiscZone1",
		"forcedPid"  => $pid,
		"originX"    => $worldTransform->x,
		"originY"    => $worldTransform->y,
		"originZ"    => $worldTransform->z,
		"localID"    => "BP_RuneAnimated_C Butterfly_pid" . $pid,
	]);
	$megaTableNew[] = CreateMegaTableEntry([
		"pid" => $pid,
		"zoneIndex" => 2,
		"ptype" => "logicGrid",
		"category" => "LogicGrid",
		"path" => "World / Verdant Glen / Quest / Tango of the Butterflies / LogicGrid_" . $pid,
		"family" => "static",
	]);
	unset($rune_ref);
}
unset($sandboxZones); $sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);
//AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_butterflies.csv");


$lostgridsMainClusterPath = "media\\lostgrids\\embed_lostgrids_logic.csv";
EmbedUassetPuzzleMetadataFrom($lostgridsMainClusterPath, $containedRefMap, $externalStatusRefMap);
$lostgridsMainClusterPids = array_keys(LoadCsvMap($lostgridsMainClusterPath, "pid", "\t"));

$lostgridsPatternClusterPath = "media\\lostgrids\\embed_lostgrids_pattern.csv";
EmbedUassetPuzzleMetadataFrom($lostgridsPatternClusterPath, $containedRefMap, $externalStatusRefMap);
$lostgridsPatternClusterPids = array_keys(LoadCsvMap($lostgridsPatternClusterPath, "pid", "\t"));

$lostgridsMusicClusterPath = "media\\lostgrids\\embed_lostgrids_music.csv";
EmbedUassetPuzzleMetadataFrom($lostgridsMusicClusterPath, $containedRefMap, $externalStatusRefMap);
$lostgridsMusicClusterPids = array_keys(LoadCsvMap($lostgridsMusicClusterPath, "pid", "\t"));
//$lostgridsMusicClusterPids = [];

$lostgridsClusterAllPids = array_values(array_merge($lostgridsMainClusterPids, $lostgridsPatternClusterPids, $lostgridsMusicClusterPids));
$lostgridsClusterFilteredPids = [];


foreach($lostgridsClusterAllPids as $pid){
	if(empty($pid)){
		continue;
	}
	$zone = ($containedRefMap[$pid]["Serialized"] != null ? json_decode($containedRefMap[$pid]["Serialized"])->PoolName : $containedRefMap[$pid]["Zone"]);
	if(empty($zone) || !str_starts_with($zone, "lostgrids")){
		continue;
	}
	$lostgridTestIndex = count($lostgridsClusterFilteredPids) - 1;
	$row = intval(floor(($lostgridTestIndex / 20 + 1e-4))) + intval(floor(($lostgridTestIndex / 100 + 1e-4)));
	$col = ($lostgridTestIndex % 20);
	$x = 11700 + 150 + $col * 100 - 48654;
	$y = 6200 + $row * 100 + 700 - 6579 - 100;
	$z = 24750 - 200 - 200 + 1598 + 10;
	
	$base64Str = ($containedRefMap[$pid]["Pdata"] ?? ((json_decode($containedRefMap[$pid]["Serialized"]))->BinaryData));
	$grid = GetGridBasics($base64Str);
	$ptype = $grid->mt;
	$prettyPtype = PuzzlePrettyName($ptype);
	
	//CreateStaticRune($jsonSandboxZones, [
	//	"forcedPid"  => $pid,
	//	"originX"    => $x,
	//	"originY"    => $y,
	//	"originZ"    => $z,
	//	"localID"    => "BP_RuneAnimated_C LostGrid_pid" . $pid,
	//]);
	$clusterName = "lostgridsUnknown";
	if(in_array($pid, $lostgridsMainClusterPids)){
		$clusterName = "lostgridsLogic";
	}elseif(in_array($pid, $lostgridsPatternClusterPids)){
		$clusterName = "lostgridsPattern";
	}elseif(in_array($pid, $lostgridsMusicClusterPids)){
		$clusterName = "lostgridsMusic";
	}
	$megaTableNew[] = CreateMegaTableEntry([
		"pid" => $pid,
		"zoneIndex" => 2,
		"ptype" => "logicGrid",
		"category" => "LogicGrid",
		"path" => "World / Verdant Glen / Cluster / Lost Grids / ClusterPuzzle / " . $prettyPtype . "_" . $pid,
		"family" => "cluster",
		"extraData" => (object)[ "cluster" => $clusterName ],
	]);
	
	EnforceGridAssetFormat($pid, false, $containedRefMap); // force non-serialized format
	
	$lostgridsClusterFilteredPids[] = $pid;
}


//define("LOSTGRIDS_CLUSTER_SPAWNS", 30);
//CreateClusterRunes($jsonSandboxZones, [
//	"clusterName" => "lostgrids",
//	"runeCount"   => LOSTGRIDS_CLUSTER_SPAWNS,
//	"originX"     => 7300,
//	"originY"     => 48400,
//	"originZ"     => 8850,
//	"localIDbase" => "LostGrid",
//]);
CreateClusterRunes($jsonSandboxZones, [
	"clusterName" => "lostgridsMusic",
	"runeCount"   => 1,
	"originX"     => 7300,
	"originY"     => 48500,
	"originZ"     => 8850,
	"localIDbase" => "LostgridMusic",
]);
CreateClusterRunes($jsonSandboxZones, [
	"clusterName" => "lostgridsPattern",
	"runeCount"   => 4,
	"originX"     => 7300,
	"originY"     => 48600,
	"originZ"     => 8850,
	"localIDbase" => "LostgridPattern",
]);
CreateClusterRunes($jsonSandboxZones, [
	"clusterName" => "lostgridsLogic",
	"runeCount"   => 25,
	"originX"     => 7300,
	"originY"     => 48400,
	"originZ"     => 8850,
	"localIDbase" => "LostgridLogic",
]);

unset($sandboxZones); $sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_cluster.csv");
printf("Lostgrids main cluster has %d pids.\n", count($lostgridsClusterFilteredPids));


// Move a few extra things to make way for the new grids.
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_world.csv");

// No longer needed.
//$lostgridsClusterSaveStatsCsv = array_map(function($x) { return [ "pid" => $x, "ptype" => "logicGrid", "cluster" => "lostgrids" ]; }, $lostgridsClusterFilteredPids);
//WriteFileSafe("..\\..\\SaveStats\\media\\data\\lostgridsCluster.csv", FormCsv($lostgridsClusterSaveStatsCsv), true);

// Adjust camphor puzzles.
AdjustAssetCoordinates($puzzleDatabase, $sandboxZones, "media\\lostgrids\\adjust_camphor_puzzles.csv");

// Write the intermediate megatable.
SaveMegaTableNew($megaTableNew, $megaTableOut);



///////////////////////////////////////////////////////////////////////////////
// Override spawn behavior to spawn every World puzzle at once - if needed.
///////////////////////////////////////////////////////////////////////////////

//if($forceOnlyZone >= 2 && $forceOnlyZone <= 6){
if($forceOnlyZone >= 1 || !empty($forceSpawnPids) || !empty($forceDebugGrids)){
	printf("%s\n", ColorStr("Warning: debug mode enabled. Don't ship the .pak like this.", 255, 128, 128));
	
	foreach($puzzleDatabase->puzzleZoneToGroupNumOverrides as $zoneEnumName => &$ptypeArray_ref){
		$zoneIndex = ZoneNameToInt($zoneEnumName);
		foreach($ptypeArray_ref as $ptype => &$groupSize_ref){
			if(in_array($ptype, [ "logicGrid", "completeThePattern", "musicGrid", "memoryGrid" ])){// || $zoneIndex != $forceOnlyZone){
				printf("%s\n", ColorStr(sprintf("  Removing group %-18s in %s", $ptype, ZoneToPrettyNoColor($zoneIndex)), 160, 160, 160));
				$groupSize_ref = 0;
			}else{
				$maxCount = (isset(GetHubProfile()[$zoneIndex][$ptype]) ? count(GetHubProfile()[$zoneIndex][$ptype]) : 0);
				$groupSize_ref = ($zoneIndex == $forceOnlyZone ? $maxCount : 0);
				printf("%s\n", ColorStr(sprintf("  Group %-18s in %s set to size %d", $ptype, ZoneToPrettyNoColor($zoneIndex), $groupSize_ref), 160, 160, 160));
			}
		}unset($groupSize_ref);
	}unset($ptypeArray_ref);
	
	$hubPids = GetAllHubPids();
	$zonePids = (IsHubZone($forceOnlyZone) ? (ReduceProfileToZones(GetHubProfile()))[$forceOnlyZone] : []);
	$wrongHubPids = array_diff($hubPids, $zonePids);
	$allRoughBounds = LoadCsvMap("media\\mod\\roughZoneBounds.csv", "zoneIndex");
	$roughBounds = (IsHubZone($forceOnlyZone) ? (object)($allRoughBounds[$forceOnlyZone]) : (object)[ "minX" => 1, "maxX" => 1, "minY" => 1, "maxY" => 1 ]);
	
	if($forceAdjacentAlso){
		foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => &$ser_ref){
			$miniJson = json_decode($ser_ref);
			if($miniJson->PuzzleType == "ghostObject"){
				$miniJson->forceTutorialColors = true;
			}
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
				$miniJson->Disabled = false;
				//$miniJson->AwakenIfAlwaysSpawn = true;
				//$miniJson->CanAwardAutoQuest = true;
				//$miniJson->Map = "BetaCampaign";
				//$miniJson->Zone = 2;
				$puzzleDatabase->krakenIDToPuzzleStatus[$pid] = "live";
				//printf("Enabling puzzle %d %s from %s\n", $pid, $miniJson->PuzzleType, ZoneToPretty(GetPuzzleMap(true)[$pid]->actualZoneIndex));
				////printf("Enabling puzzle %d %s from %s\n", $pid, $miniJson->PuzzleType, ZoneToPretty($miniJson->Zone));
			}else{
				$miniJson->SpawnBehaviour = 1;
				//$miniJson->Disabled = true; // doesn't work
			}
			$ser_ref = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
		}unset($ser_ref);
	}
	
	$profileHub = GetHubProfile();
	$reduced = ReduceProfileToPtypes($profileHub);
	$gridPids = $reduced["logicGrid"] + $reduced["completeThePattern"] + $reduced["musicGrid"] + $reduced["memoryGrid"];
	shuffle($gridPids);
	$nextPidIndex = 0;
	//printf("%s\n", implode(",", $gridPids));
	
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
		if($forceOnlyZone >= 1 && !$isInBounds){ continue; }
		
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

if($forceAllFlorbs){
	$hubProfile = GetHubProfile();
	$florbPids = ReduceProfileToPtypes($hubProfile)["racingBallCourse"];
	foreach($florbPids as $pid){
		$miniJson = json_decode($puzzleDatabase->krakenIDToWorldPuzzleData[$pid]);
		$miniJson->SpawnBehaviour = 0;
		$puzzleDatabase->krakenIDToWorldPuzzleData[$pid] = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
	}
}

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

// Scaling test. This is some legacy shit - original adjuster code could only move/rotate objects.
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
	// Shady
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
	// Camphor
	//"25264/Mesh1Transform/3.00",
	//"25264/Mesh2Transform/2.60",
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

// Fix vanilla grids missing solve paths.
EmbedUassetPuzzleMetadataFrom("media\\mod\\solvePaths_vanilla_fix.csv", $containedRefMap, $externalStatusRefMap);

// Add pattern grid modifier to grids with nPositionalLiars if they don't have it.
// UPD: this is no longer required. Thank you meltdown.
//foreach($puzzleDatabase->krakenIDToContainedPuzzleData as $pid => &$arr_ref){
//	$ser = (isset($arr_ref["Serialized"]) && !empty($arr_ref["Serialized"]) ? json_decode($arr_ref["Serialized"]) : (object)[]);
//	$ptype = ($arr_ref["PuzzleType"] ?? $ser->PuzzleType);
//	if($ptype != "logicGrid"){
//		continue;
//	}
//	$pdata = ($arr_ref["Pdata"] ?? $ser->BinaryData);
//	//printf("%5d %s\n", $pid, $pdata);
//	$grid = GetGridBasics($pdata);
//	if($grid->npl > 0 && !in_array(2, $grid->tm)){
//		//printf("%5d - %d liars\n", $pid, $grid->npl);
//		$bytes = array_map(function ($x) { return ord($x); }, str_split(base64_decode($pdata), 1));
//		//printf("%s\n", implode(" ", $bytes));
//		//$bytes[1]++;
//		$newBytes = array_values(array_merge([ 0, $bytes[1] + 1, 2 ], array_slice($bytes, 2)));
//		//$newBytes = array_values(array_merge([ 0, $bytes[1] + 1, 2 ], array_slice($bytes, 2), [ $grid->npl ]));
//		//printf("%s\n\n", implode(" ", $newBytes));
//		//array_walk($newBytes, function($x) { return chr($x); });
//		//$newPdata = base64_encode(implode("", $newBytes));
//		$newPdata = base64_encode(implode("", array_map(function ($x) { return chr($x); }, $newBytes)));
//		//print_r($arr_ref);
//		if(isset($arr_ref["Serialized"]) && $arr_ref["Serialized"] != null){
//			$arr_ref["Serialized"] = preg_replace("/(\\\"BinaryData\\\":\s*)\\\"(.*?)\\\",/", "\${1}\"" . $newPdata . "\",", $arr_ref["Serialized"]);
//		}else{
//			$arr_ref["Pdata"] = $newPdata;
//		}
//		//print_r($arr_ref);
//		printf("Grid %5d is now a pattern grid due to having %d liars.\n", $pid, $grid->npl);
//		//printf("%s\n%s\n\n", $pdata, $newPdata);
//	}
//}unset($arr_ref);

// Add puzzle tutorial texts to puzzleboxes that spawn wrap-around or nPositionalLiars grids.
foreach($sandboxZones->Containers as $localID => &$container_ref){
	if($container_ref->containerType != "Rune"){
		continue;
	}
	$pid = -1;
	if(isset($container_ref->desiredKrakenIDOverride) && !empty($container_ref->desiredKrakenIDOverride)){
		$pid = intval($container_ref->desiredKrakenIDOverride);
	}
	$ser = json_decode($container_ref->serializedString);
	if(isset($ser->desiredKrakenIDOverride) && !empty($ser->desiredKrakenIDOverride)){
		$pid = intval($ser->desiredKrakenIDOverride);
	}
	if($pid == -1){
		continue;
	}
	if(!isset($puzzleDatabase->krakenIDToContainedPuzzleData[$pid])){
		printf("%s\n", ColorStr(sprintf("Rune %s wants to spawn non-existant puzzle %d!", $localID, $pid), 255, 128, 128));
		exit(1);
	}
	$arr_ref = $puzzleDatabase->krakenIDToContainedPuzzleData[$pid];
	$pdata = ($arr_ref["Pdata"] ?? (json_decode($arr_ref["Serialized"])->BinaryData));
	unset($arr_ref);
	$grid = GetGridBasics($pdata);
	if($grid->w == 0 && $grid->npl <= 0){
		continue;
	}
	if($grid->w > 0 && $grid->npl > 0){
		printf("%s\n", ColorStr(sprintf("Rune %s wants to spawn puzzle %d which has BOTH liars and wrap-around!", $localID, $pid), 255, 128, 128));
		exit(1);
	}
	$text = "";
	if($grid->w == 1){
		//$text = "Left and right edges are connected."; // no localization
		$text = "NSLOCTEXT(\\\"RRMod\\\", \\\"WRAP_LR\\\", \\\"Left and right edges are connected.\\\")";
	}elseif($grid->w == 2){
		//$text = "Top and bottom edges are connected."; // no localization
		$text = "NSLOCTEXT(\\\"RRMod\\\", \\\"WRAP_TB\\\", \\\"Top and bottom edges are connected.\\\")";
	}elseif($grid->w == 3){
		//$text = "All four edges are connected."; // no localization
		$text = "NSLOCTEXT(\\\"RRMod\\\", \\\"WRAP_ALL\\\", \\\"All opposing edges are connected.\\\")";
	}else{
		//$text = sprintf("%d symbol%s lying.", $grid->npl, ($grid->npl == 1 ? " is" : "s are")); // no localization
		//$text = "NSLOCTEXT(\\\"[EB3E14C54D948EFC0FF7F5B25A47CC2E]\\\", \\\"LIARS/LIARS1\\\", \\\"1 symbol is lying or some shit.\\\")"; // fail
		//$text = "NSLOCTEXT(\\\"\\\", \\\"LIARS15\\\", \\\"X symbol is lying or some shit.\\\")"; // fail
		//$text = "NSLOCTEXT(\\\"[LIARS]\\\", \\\"LIARS1\\\", \\\"1 symbol is lying.\\\")"; // fail
		//$text = "NSLOCTEXT(\\\"LIARS\\\", \\\"LIARS1\\\", \\\"1 symbol is lying.\\\")"; // OK
		//$text = "NSLOCTEXT(\\\"\\\", \\\"LIARS15\\\", \\\"15 symbols are lying.\\\")"; // OK
		$keyName = "LIARS" . $grid->npl;
		$helper = ($grid->npl == 1 ? "symbol is" : "symbols are");
		$text = "NSLOCTEXT(\\\"RRMod\\\", \\\"" . $keyName . "\\\", \\\"" . $grid->npl . " " . $helper . " lying.\\\")";
	}
	
	//printf("rune %s, grid %d, pdata %s, text %s:\n", $localID, $pid, $pdata, $text);
	//print_r($container_ref);
	
	$container_ref->serializedString = preg_replace("/\"puzzleTutorialText\":\s*\"\"/", "\"puzzleTutorialText\":\"" . $text . "\"", $container_ref->serializedString);
	if(isset($container_ref->puzzleTutorialText)){
		$container_ref->puzzleTutorialText = $text;
	}
	printf("Added text |%s| to rune |%s| with grid %d.\n", $text, $localID, $pid);
	
}unset($container_ref);


///////////////////////////////////////////////////////////////////////////////
// Puzzle radar stuff (chests).
///////////////////////////////////////////////////////////////////////////////

// Moved to chestmaker.php
//CreateChestBinary("media\\mod\\chest_locations.csv", "media\\mod\\puzzleradar.bin");



///////////////////////////////////////////////////////////////////////////////
// Export files.
///////////////////////////////////////////////////////////////////////////////

if($doExportFiles){
	SaveCompressedDecodedUasset($outputPuzzleDatabase, $jsonPuzzleDatabase, [ "skipArrayIndices" => true, "scalarizeNodes" => [ "Solves" ] ]);
	SaveCompressedDecodedUasset($outputSandboxZones,   $jsonSandboxZones, [ "skipArrayIndices" => false ]);
}else{
	printf("All done! Export omitted.\n");
}

SaveReadableUassetDataTo($outputReadables, $puzzleDatabase, $sandboxZones);
