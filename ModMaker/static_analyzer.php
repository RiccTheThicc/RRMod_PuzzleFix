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

$pdbIn = "..\\OutputJsons\\PuzzleDatabase.json";
$sbzIn = "..\\OutputJsons\\SandboxZones.json";
$mtIn  = "media\\data\\megatable.csv";
$mtOut  = "media\\data\\megatable_v2.csv";

$jsonPuzzleDatabase   = LoadDecodedUasset($pdbIn);
$puzzleDatabase       = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($sbzIn);
$sandboxZones     = &ParseUassetSandboxZones($jsonSandboxZones);

$krakenIdToEncycId = array_map(function($x){ return $x["encycId"]; }, LoadCsvMap("media\\data\\krakenIdToEncycId.csv", "pid"));
$allHubPids = GetAllHubPids();

$puzzleMap = GetPuzzleMap(true);
$knownClusters = [
	"HardCOWYC",
	"Lobby",
	"Cong",
	"myopia",
	"lostgridsLogic",
	"lostgridsMusic",
	"lostgridsPattern",
];

$match3Pools = [
	"Rated/Easy" => 2,
	"Rated/Hard" => 5,
];

$megaTable = LoadCsvMap($mtIn, "pid");
foreach($megaTable as &$entry){
	$entry = (object)$entry;
	
	$entry->family = "";
	$entry->solveValue = 0;
	
	if(!isset($entry->coords) || empty($entry->coords)){
		$entry->coords = [];
	}
	if(isset($entry->extraData) && !empty($entry->extraData)){
		$entry->extraData = (object)json_decode(str_replace(";", ",", $entry->extraData));
	}else{
		$entry->extraData = (object)[];
	}
	
	if(isset($entry->x) && isset($entry->y) && isset($entry->z) && strlen($entry->x) * strlen($entry->y) * strlen($entry->z) > 0){
		$entry->coords = str_replace(",", ";", json_encode([
			(object)[
				"x"     => (float)sprintf("%.2f", $entry->x),
				"y"     => (float)sprintf("%.2f", $entry->y),
				"z"     => (float)sprintf("%.2f", $entry->z),
				"pitch" => (float)sprintf("%.2f", $entry->pitch),
				"yaw"   => (float)sprintf("%.2f", $entry->yaw),
				"roll"  => (float)sprintf("%.2f", $entry->roll),
			]
		]));
	}
	unset($entry->x);
	unset($entry->y);
	unset($entry->z);
	unset($entry->pitch);
	unset($entry->yaw);
	unset($entry->roll);
	
	$pid = $entry->pid;
	if(isset($puzzleMap[$pid])){
		$data = $puzzleMap[$pid];
		if($data->qfp > 0){
			$entry->extraData->qfp = $data->qfp;
			//$entry->family = "qfp"; // don't. the first grid in each qfp is both qfp AND static!
		}
		//if(!empty($data->pool) || !empty($data->Pool)){
		//	printf("%5d %s %s\n", $pid, $data->pool ?? "-", $data->Pool ?? "-");
		//}
		if(isset($data->pool) && !empty($data->pool)){
			if(in_array($data->pool, $knownClusters)){
				$entry->extraData->cluster = $data->pool;
				$entry->family = "cluster";
			}elseif(isset($match3Pools[$data->pool])){
				$entry->extraData->match3challengeZone = $match3Pools[$data->pool];
				// do not assign family. first match3 puzzle in each pool is both match3pool AND static!
			}
		}
		if(isset($data->SandboxMilestones) && !empty($data->SandboxMilestones)){
			$entry->extraData->SandboxMilestones = $data->SandboxMilestones;
			//printf("%5d %s\n", $pid, $data->SandboxMilestones);
		}
		if(isset($data->SolveValue) && $data->SolveValue > 0){
			$entry->solveValue = $data->SolveValue;
			//printf("%5d %s\n", $pid, $data->SolveValue);
		}
		
		if(isset($data->ghostType)){
			$entry->extraData->ghostType = $data->ghostType;
		}
	}
	
	if(isset($krakenIdToEncycId[$pid])){
		$entry->extraData->encycId = $krakenIdToEncycId[$pid];
	}
	
	if($entry->ptype == "monolithFragment"){
		$papaPid = $entry->parent;
		$papaData = (object)$megaTable[$papaPid];
		//printf("%s\n", $entry->coords);
		//$entry->coords[] = $papaData->coords[0];
		$papaCoord = json_decode(str_replace(";", ",", $papaData->coords))[0];
		$myCoord   = json_decode(str_replace(";", ",", $entry->coords))[0];
		$finalCoord = str_replace(",", ";", json_encode([ $myCoord, $papaCoord ]));
		//$entry->coords = str_replace(";", ",", json_encode([
		//printf("%d %s\n", $entry->pid, $entry->ptype);
		//printf("%s\n", json_encode($papaCoord, 0xc0));
		//printf("%s\n", json_encode($myCoord, 0xc0));
		$entry->coords = $finalCoord;
		//printf("%s\n", $entry->coords);
		//exit(1);
	}
	
	if(empty($entry->family)){
		if(isset($entry->extraData->mysteryId)){
			$entry->family = "mystery";
		}elseif(in_array($pid, $allHubPids)){
			$entry->family = "hub"; 
		}elseif($entry->ptype == "gyroRing" && str_contains($entry->path, "TempleArmillary")){
			$entry->family = "templering";
		}elseif(str_ends_with($entry->path, "Script")){
			$entry->family = "script";
		}elseif(str_contains($entry->path, "Match3ChallengePuzzle")){
			$entry->family = "match3challenge";
		}elseif(str_contains($entry->path, "QfpPuzzle")){
			$entry->family = "qfpPuzzle";
		}elseif(in_array($entry->ptype, [ "puzzleTotem", "dungeon", "obelisk" ])){
			$entry->family = "internal";
		}else{
			$entry->family = "static";
		}
	}
	
	foreach($entry as $key => &$value){
		if(is_array($value) || is_object($value)){
			$value = str_replace(",", ";", json_encode($value));
		}
	}unset($value);
	$entry = (array)$entry;
}unset($entry);

array_multisort(
				array_column($megaTable, "family"), SORT_ASC, SORT_NATURAL,
				array_column($megaTable, "path"  ), SORT_ASC, SORT_NATURAL,
				array_column($megaTable, "pid"  ), SORT_ASC, SORT_NATURAL,
				$megaTable);

WriteFileSafe($mtOut, FormCsv($megaTable), true);
