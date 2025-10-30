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
$sbzIn  = "..\\OutputJsons\\SandboxZones.json";
$mtIn   = "media\\data\\megatable_v2_ext.csv";
$mtOut  = "media\\data\\megatable_v3.csv";
$mtCopy    = "..\\..\\SaveStats\\media\\data\\megatable_v3.csv";
$spawnsOut = "..\\..\\SaveStats\\media\\data\\spawns.csv";

$jsonPuzzleDatabase   = LoadDecodedUasset($pdbIn);
$puzzleDatabase       = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($sbzIn);
$sandboxZones     = &ParseUassetSandboxZones($jsonSandboxZones);

$megaTable = LoadMegaTableNew($mtIn);
$CAMPHOR_DUNGEON_PID = 25261;
$CAMPHOR_ZONE = 2;

// Add all puzzle totems and their grids directly from output jsons.
$totemList = [
	// path, objName, zoneIndex, parent
	[ "..\\OutputJsons\\Maps\\CamphorCorridorTemple.json", "BP_PuzzleTotem_WrapLR",   2, -1, "World / Lucent Waters / Enclave / A Warped Perspective" ],
	[ "..\\OutputJsons\\Maps\\CamphorCorridorTemple.json", "BP_PuzzleTotem_WrapFour", 2, -1, "World / Lucent Waters / Enclave / A Warped Perspective" ],
	[ "..\\OutputJsons\\Maps\\RRMod_Objects.json",         "BP_PuzzleTotem_LiarA",    4, -1, "World / Autumn Falls / Quest / Deceptive Whispers" ],
	[ "..\\OutputJsons\\Maps\\RRMod_Objects.json",         "BP_PuzzleTotem_LiarB",    4, -1, "World / Autumn Falls / Quest / Deceptive Whispers" ],
];
foreach($totemList as $tuple){
	list($path, $objName, $zoneIndex, $parent, $basePath) = $tuple;
	$zoneName = ZoneToPrettyNoColor($zoneIndex);
	$json = LoadDecodedUasset($path);
	$totem = FetchObjectByName($json, $objName);
	if(empty($totem)){
		printf("[ERROR] Failed to find object %s in %s\n", $objName, $path);
		exit(1);
	}

	$pillarPidNode = FetchObjectField($totem, "KrakenId");
	$pillarPid = intval($pillarPidNode);
	
	$pillarRoot = FetchObjectField($totem, "RootComponent");
	$rootObj = FetchObjectByIntegerIndex($json, intval($pillarRoot));
	$locationNode = FetchObjectField($rootObj, "RelativeLocation");
	$t = UeTransformUnpack($locationNode[0]->Value);
	$coord = [ "x" => $t->Translation->X, "y" => $t->Translation->Y, "z" => $t->Translation->Z, "pitch" => 0, "yaw" => 0, "roll" => 0 ];
	
	$megaTable[$pillarPid] = (object)[
		"pid"        => $pillarPid,
		"parent"     => $parent,
		"zoneIndex"  => $zoneIndex,
		"ptype"      => "puzzleTotem",
		"category"   => "PillarOfInsight",
		//"path"       => "World / " . $zoneName . " / StaticPuzzle / PillarOfInsight_" . $pillarPid,
		"path"       => $basePath . " / PillarOfInsight_" . $pillarPid,
		"comment"    => "",
		"extraData"  => (object)[],
		"family"     => "internal",
		"solveValue" => 0,
		"coords"     => [ $coord ],
	];
	$myPidsNode = FetchObjectField($totem, "GridKrakenIDs");
	foreach($myPidsNode as $subNode){
		$pid = intval($subNode->Value);
		
		$megaTable[$pid] = (object)[
			"pid"        => $pid,
			"parent"     => $pillarPid,
			"zoneIndex"  => $zoneIndex,
			"ptype"      => "logicGrid",
			"category"   => "LogicGrid",
			//"path"       => "World / " . $zoneName . " / StaticPuzzle / PillarOfInsight_" . $pillarPid . " / LogicGrid_" . $pid,
			"path"       => $basePath . " / PillarOfInsight_" . $pillarPid . " / LogicGrid_" . $pid,
			"comment"    => "",
			"extraData"  => (object)[],
			"family"     => "static",
			"solveValue" => 0,
			"coords"     => [ $coord ],
		];
	}unset($subNode);
	//printf("%s\n\n", json_encode($pids));
}

// Add the camphor entrance unlock.
$entranceUnlockList = [
	// path, objName, zoneIndex, parent
	[ "..\\OutputJsons\\Maps\\CamphorCorridorTemple.json", "BP_LevelRestrictionVolume5", 2, $CAMPHOR_DUNGEON_PID ],
];
foreach($entranceUnlockList as $tuple){
	list($path, $objName, $zoneIndex, $parent) = $tuple;
	$zoneName = ZoneToPrettyNoColor($zoneIndex);
	$json = LoadDecodedUasset($path);
	$lrv = FetchObjectByName($json, $objName);
	if(empty($lrv)){
		printf("[ERROR] Failed to find object %s in %s\n", $objName, $path);
		exit(1);
	}
	$lrvPidNode = FetchObjectField($lrv, "KrakenId");
	$lrvPid = intval($lrvPidNode);
	
	$lrvRoot = FetchObjectField($lrv, "RootComponent");
	$rootObj = FetchObjectByIntegerIndex($json, intval($lrvRoot));
	$locationNode = FetchObjectField($rootObj, "RelativeLocation");
	$t = UeTransformUnpack($locationNode[0]->Value);
	$coord = [ "x" => $t->Translation->X, "y" => $t->Translation->Y, "z" => $t->Translation->Z, "pitch" => 0, "yaw" => 0, "roll" => 0 ];
	
	$megaTable[$lrvPid] = (object)[
		"pid"        => $lrvPid,
		"parent"     => $parent,
		"zoneIndex"  => $zoneIndex,
		"ptype"      => "levelRestrictionVolume",
		"category"   => "EntranceUnlock",
		"path"       => "World / " . $zoneName . " / Enclave / Camphor / EntranceUnlock_" . $lrvPid,
		"comment"    => "",
		"extraData"  => (object)[],
		"family"     => "static",
		"solveValue" => 0,
		"coords"     => [ $coord ],
	];
}

// Add other Camphor puzzles.
$camphorWorldPuzzles = [
	25257,25258,25259,25260, // archways
	25263,25264,25265,25266,25267,25268,25269,25270,25271,25272, // matchboxes
	25273,25274,25275,25276,25277,25278,25279,25280,25281,25282,25283,25284,25285,25286,25287,25288,25289, // unused matchboxes?
	25290,25291,25292, // gliderings
	25293,25294, // unused gliderings?
];
foreach($camphorWorldPuzzles as $pid){
	if(!isset($puzzleDatabase->krakenIDToWorldPuzzleData[$pid])){
		printf("[WARNING] Puzzle %d doesn't exist, ignoring.\n", $pid);
		continue;
	}
	$ser_ref = &$puzzleDatabase->krakenIDToWorldPuzzleData[$pid];
	$miniJson = json_decode($ser_ref);
	//printf("%s\n", json_encode($miniJson, 0xc0));
	$megaTable[$pid] = (object)[
		"pid"        => $pid,
		"parent"     => $CAMPHOR_DUNGEON_PID,
		"zoneIndex"  => $CAMPHOR_ZONE,
		"ptype"      => $miniJson->PuzzleType,
		"category"   => PuzzlePrettyName($miniJson->PuzzleType),
		"path"       => "World / " . ZoneToPrettyNoColor($CAMPHOR_ZONE) . " / Enclave / Camphor / " . PuzzlePrettyName($miniJson->PuzzleType) . "_" . $pid,
		"comment"    => "",
		"extraData"  => (object)[],
		"family"     => "static",
		"solveValue" => 0,
		"coords"     => [],
	];
	printf("Added puzzle %d (%s) to megaTable\n", $pid, $miniJson->PuzzleType);
}


// Now finally we scan the pdb/sbz jsons to update real live coordinates of all puzzles, and some other data that may have been modified.
 
foreach($puzzleDatabase->krakenIDToWorldPuzzleData as $pid => $ser){
	if(!isset($megaTable[$pid])){
		continue;
	}
	$miniJson = json_decode($ser);
	$ptype = $miniJson->PuzzleType;
	$zoneIndex = $megaTable[$pid]->zoneIndex;
	
	//$oldCoords = json_encode($megaTable[$pid]->coords);
	$newCoords = ParseCoordinates($pid, $ptype, $miniJson);
	foreach($newCoords as &$coord){
		unset($coord->rot);
		//$coord = (object)array_map(function($t) {return ((float)sprintf("%.2f", $t);}, (array)$coord);
		$coord = (object)array_map(function($t) {return (abs($t) < 1e-2 ? 0 : (float)sprintf("%.2f", $t));}, (array)$coord);
	}unset($coord);
	
	$megaTable[$pid]->coords = $newCoords;
	
	if(isset($miniJson->ghostType)){
		if(!isset($megaTable[$pid]->extraData->ghostType) || ($megaTable[$pid]->extraData->ghostType != $miniJson->ghostType)){
			// I had to change one type of shy aura in the mod - it was a blue one hanging mid-air on a non-colliding tree.
			printf("pid %d (%s in %s): changed ghostType to %d\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $miniJson->ghostType);
		}
		$megaTable[$pid]->extraData->ghostType = $miniJson->ghostType;
	}
	
	if(isset($miniJson->SandboxMilestones)){
		if(!isset($megaTable[$pid]->extraData->SandboxMilestones) || ($megaTable[$pid]->extraData->SandboxMilestones != $miniJson->SandboxMilestones)){
			// This is a sanity check and it should never happen. Outside of Camphor at least.
			printf("pid %d (%s in %s): changed SandboxMilestones to %s\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $miniJson->SandboxMilestones);
		}
		$megaTable[$pid]->extraData->SandboxMilestones = $miniJson->SandboxMilestones;
	}
	
	if(isset($miniJson->SolveValue)){
		if($megaTable[$pid]->solveValue != $miniJson->SolveValue){
			// This is a sanity check and it should never happen. Outside of Camphor at least.
			printf("pid %d (%s in %s): changed solveValue to %s\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $miniJson->SolveValue);
		}
		$megaTable[$pid]->solveValue = $miniJson->SolveValue;
	}
	
	if(isset($miniJson->encycID)){
		// MIND THE CAPITALIZATION
		// I use encycId, so does most of the game, but pdb uses encycID
		if(!isset($megaTable[$pid]->extraData->encycId) || ($megaTable[$pid]->extraData->encycId != $miniJson->encycID)){
			// This is a sanity check and it should never happen. Outside of Camphor at least.
			printf("pid %d (%s in %s): changed encycID to %s\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $miniJson->encycID);
		}
		$megaTable[$pid]->extraData->encycId = $miniJson->encycID;
	}
}unset($ser);

foreach($puzzleDatabase->krakenIDToContainedPuzzleData as $pid => $arr){
	if(!isset($megaTable[$pid])){
		continue;
	}
	unset($arr["Solves"]); // ew
	
	$miniJson = (isset($arr["Serialized"]) ? json_decode($arr["Serialized"]) : (object)[]);
	switch($arr["PuzzleType"]){
		case "logicGrid": // actually also pattern/memory/music
		{
			$difficulty = $arr["Difficulty"];
			if($difficulty < 1 || $difficulty > 10){
				$base64 = $miniJson->BinaryData;
				$bytes = array_map(function ($x) { return ord($x); }, str_split(base64_decode($base64), 1));
				$difficulty = end($bytes);
				//printf("Shit grid %5d, difficulty should be %d\n", $pid, $difficulty);
			}
			
			$megaTable[$pid]->extraData->difficulty = $difficulty;
			
			break;
		}
		case "gyroRing":
		case "fractalMatch":
		case "klotski":
		case "lockpick":
		case "match3":
		case "rollingCube":
		case "ryoanji":
		case "mirrorMaze":
		{
			// Boring. You could snatch BinaryData here if you want it.
			break;
		}
		default:{
			printf("What the fuck is PuzzleType %s ?\n", $arr["PuzzleType"]);
			printf("%s\n", json_encode($miniJson, 0xc0)); exit(1);
			exit(1);
			break;
		}
	}
}unset($arr);


foreach($sandboxZones->Containers as $localID => $container){
	$containerType = $container->containerType; // "Rune", "Monument", "SlabSocket", or "GyroSpawn".
	
	if(!isset($container->serializedString)){
		printf("[ERROR] No serializedString in container!\n");
		var_dump($container);
		exit(1);
	}
	
	$ser = json_decode($container->serializedString);
	if(empty($ser)){
		printf("[ERROR] Failed to json_decode serializedString in container!\n");
		var_dump($container);
		exit(1);
	}
	//unset($container->serializedString);
	
	$merge = (object)array_merge((array)$container, (array)$ser);
	foreach($merge as $key => $value){
		if(str_contains($key, "Bounds")){
			unset($merge->$key);
		}
	}
	// desiredKrakenIDOverride is always set in the container data and, sometimes, duplicated in ser.
	if(!isset($merge->desiredKrakenIDOverride)){
		continue;
	}
	
	$pid = $merge->desiredKrakenIDOverride;
	if(!isset($megaTable[$pid])){
		//printf("I think pid %d is a forgotten static - %s\n", $pid, $localID);
		continue;
	}
	
	if(!isset($merge->PuzzleType)){
		printf("[ERROR] PuzzleType not set in the container!\n");
		var_dump($container);
		exit(1);
	}
	$ptype = $merge->PuzzleType; // seems to be always set for these. EDIT: unless json_decode failed
	$zoneIndex = $megaTable[$pid]->zoneIndex;
	
	$newCoords = ParseCoordinates($pid, $ptype, $merge);
	foreach($newCoords as &$coord){
		unset($coord->rot);
		//$coord = (object)array_map(function($t) {return ((float)sprintf("%.2f", $t);}, (array)$coord);
		$coord = (object)array_map(function($t) {return (abs($t) < 1e-2 ? 0 : (float)sprintf("%.2f", $t));}, (array)$coord);
	}unset($coord);
	
	$megaTable[$pid]->coords = $newCoords;
	
	if(isset($merge->SolveValue)){
		if($megaTable[$pid]->solveValue != $merge->SolveValue){
			// This will happen a lot - I never parsed container solve values.
			//printf("pid %d (%s in %s): changed solveValue to %s\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $merge->SolveValue);
		}
		$megaTable[$pid]->solveValue = $merge->SolveValue;
	}
	
	if(isset($merge->encycID)){
		// MIND THE CAPITALIZATION
		// I use encycId, so does most of the game, but pdb uses encycID
		if(!isset($megaTable[$pid]->extraData->encycId) || ($megaTable[$pid]->extraData->encycId != $merge->encycID)){
			// This is a sanity check and it should never happen. Outside of Camphor at least.
			printf("pid %d (%s in %s): changed encycID to %s\n", $pid, $ptype, ZoneToPrettyNoColor($zoneIndex), $merge->encycID);
		}
		$megaTable[$pid]->extraData->encycId = $merge->encycID;
	}
}unset($container);

SaveMegaTableNew($megaTable, $mtOut);
SaveMegaTableNew($megaTable, $mtCopy);


$knownClusters = array_keys(GetClusterNameMap());

//$map = [];
$csv = [];
foreach($sandboxZones->Containers as $localID => $container){
	$ptypeList = [];
	$pid = -1;
	$pool = "";
	$zoneIndex = 0;
	$containerType = "";
	if(isset($container->possiblePuzzleTypes)){
		$ptypeList = (is_string($container->possiblePuzzleTypes) ? [ $container->possiblePuzzleTypes ] : $container->possiblePuzzleTypes);
	}
	if(isset($container->desiredKrakenIDOverride)){
		$pid = intval($container->desiredKrakenIDOverride);
	}
	if(isset($container->desiredPuzzlePool)){
		$pool = $container->desiredPuzzlePool;
	}
	if(isset($container->ownerZone)){
		$zoneIndex = ZoneNameToInt($container->ownerZone);
	}
	if(isset($container->containerType)){
		$containerType = $container->containerType;
	}
	
	$t = UeTransformUnpack(json_decode($container->serializedString)->ActorTransform);
	
	if($pid != -1 || !in_array($containerType, [ "Rune", "Monument", "SlabSocket" ])){
		// Static spawns are ignored.
		continue;
	}
	
	$setZone = json_decode($container->serializedString)->Zone;
	if($zoneIndex != $setZone){
		printf("%s\n", ColorStr(sprintf("Container %s zone mismatch: should be %s, set to %s", $localID, ZoneToPrettyNoColor($setZone), ZoneToPrettyNoColor($zoneIndex)), 255, 128, 128));
		exit(1);
	}
	
	$entry = [
		"containerType" => $containerType,
		"zoneIndex" => intval($zoneIndex),
		"cluster" => "",
		"x" => (float)(sprintf("%.2f", $t->Translation->X)),
		"y" => (float)(sprintf("%.2f", $t->Translation->Y)),
		"z" => (float)(sprintf("%.2f", $t->Translation->Z)),
		"localID" => $localID,
		"ptypeList" => implode("|", $ptypeList),
	];
	if(in_array($pool, $knownClusters)){
		$entry["containerType"] = "ClusterRune";
		$entry["zoneIndex"] = 0; // some are set to wrong zone anyway
		$entry["cluster"] = $pool;
	}
	$key = (empty($entry["cluster"]) ? "~" : $entry["cluster"]) . $entry["containerType"] . $entry["zoneIndex"] . $entry["localID"];
	$csv[$key] = $entry;
	
	//printf("%20s %20s %5d %20s [%s] %.0f,%.0f,%.0f\n", ZoneToPrettyNoColor($zoneIndex), $containerType, $pid, $pool, implode("|", $ptypeList), $t->Translation->X, $t->Translation->Y, $t->Translation->Z);
	//$key = ZoneToPrettyNoColor($zoneIndex) . " / " . $containerType . " / " . $pool;
	//if(in_array($pool, $knownClusters)){
	//	$key = "cluster/" . $pool;
	//}else{
	//	//$key = ZoneToPrettyNoColor($zoneIndex) . " / " . $containerType;
	//	$key = $containerType . "/" . $zoneIndex;
	//}
	//if(!isset($map[$key])){
	//	$map[$key] = [];
	//}
	//$map[$key][] = intval(round($t->Translation->X)) . "," . intval(round($t->Translation->Y)) . "," . intval(round($t->Translation->Z)) . "," . implode("|", $ptypeList);
}unset($container);
//ksort($map);
//$a = array_map(function($x){ return count($x); }, $map);
//printf("%s\n", json_encode($map, 0xc0));
ksort($csv, SORT_NATURAL);
WriteFileSafe($spawnsOut, FormCsv($csv), true);


