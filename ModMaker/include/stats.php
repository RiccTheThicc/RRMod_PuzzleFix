<?php

include_once("include\\config.php");
include_once("include\\pjson_parse.php");
include_once("include\\lookup.php");

function Clamp($value, $min, $max){
	if($min > $max){
		$a = $max; $max = $min; $min = $a;
	}
	return (max(min($value, $max), $min)); // makes my head spin reading this
}

function GetAllStaticPids(){ // does NOT include mysteries
	static $allStaticPids = null;
	if($allStaticPids === null){
		$megaTable = GetMegaTable();
		$excludePtypes = [ "dungeon", "obelisk", "object", "puzzleTotem" ];
		$excludePaths = [ "DailyPuzzle", "ClusterPuzzle", "QfpPuzzle" ];
		$excludePids = [
			15379, // EntranceUnlock at the starting island; it exists but spawns in pre-solved, so your save file can't have it as a puzzle that YOU solved
			18616, // same as above
		];
		$allStaticPids = [];
		foreach($megaTable as $entry){
			$ptype = $entry["ptype"];
			if(in_array($ptype, $excludePtypes)){
				// Don't draw unwanted puzzle types, such as Enclaves (which themselves are puzzles and are solved by meeting minimum enclave-completion requirements).
				continue;
			}
			$pid = (int)$entry["pid"];
			if(in_array($pid, $excludePids)){
				continue;
			}
			if(!empty($entry["extraData"])){
				// Why am I doing this again rather than reading $puzzleMap?
				$extraData = json_decode(str_replace("|", ",", $entry["extraData"]));
				if(isset($extraData->mysteryId)){
					//printf("Found mystery %2d, pid %5d\n", $extraData->mysteryId, $pid);
					// Ignore mysteries, don't count them as static puzzles to avoid spoilers.
					continue;
				}
			}
			$isOk = true;
			$path = $entry["path"];
			foreach($excludePaths as $excludePath){
				if(str_contains($path, $excludePath)){
					// Don't draw daily hub puzzles, clusters, QFP grids.
					$isOk = false;
					break;
				}
			}
			if(!$isOk){
				continue;
			}
			$allStaticPids[] = $pid;
		}
		sort($allStaticPids);
	}
	return $allStaticPids;
}

function GetMysteryMap(){
	static $mysteryMap = null;
	if($mysteryMap === null){
		$mysteryMap = [];
		$megaTable = GetMegaTable();
		foreach($megaTable as $entry){
			$pid = (int)$entry["pid"];
			if(!empty($entry["extraData"])){
				// Why am I doing this again rather than reading $puzzleMap?
				$extraData = json_decode(str_replace("|", ",", $entry["extraData"]));
				if(isset($extraData->mysteryId)){
					$mysteryMap[$pid] = $extraData->mysteryId;
				}
			}
		}
		asort($mysteryMap);
	}
	return $mysteryMap;
}

function GetAllArmillaries(){
	static $allArmillaries = null;
	if($allArmillaries === null){
		$allArmillaries = [];
		$puzzleMap = GetPuzzleMap(true);
		foreach($puzzleMap as $data){
			if($data->actualPtype == "gyroRing"){
				$allArmillaries[] = (int)$data->pid;
			}
		}
	}
	return $allArmillaries;
}

function GetTempleArmillaries(){
	static $templeArmillaries = null;
	if($templeArmillaries === null){
		$allArmillaries = GetAllArmillaries();
		$allStaticPids = GetAllStaticPids();
		$templeArmillaries = array_values(array_diff($allArmillaries, $allStaticPids));
	}
	return $templeArmillaries;
}

function GetStaticArmillaries(){
	static $staticArmillaries = null;
	if($staticArmillaries === null){
		$allArmillaries = GetAllArmillaries();
		$templeArmillaries = GetTempleArmillaries();
		$staticArmillaries = array_values(array_diff($allArmillaries, $templeArmillaries));
	}
	return $staticArmillaries;
}

function GetFlorbTiers(){
	static $florbTiers = null;
	if($florbTiers === null){
		$florbTiers = [];
		$puzzleMap = GetPuzzleMap(true);
		foreach($puzzleMap as $pid => $data){
			$pid = (int)$pid;
			if($data->actualPtype != "racingBallCourse"){
				continue;
			}
			if(!isset($data->SandboxMilestones)){
				printf("%s\n", ColorStr("Flow orb " . $pid . " is missing medal times", 255, 128, 128));
				exit(1);
			}
			$arr = explode("-", $data->SandboxMilestones);
			if(count($arr) != 4){
				printf("%s\n", ColorStr("Flow orb " . $pid . " has corrupted medal times: " . $data->SandboxMilestones, 255, 128, 128));
				exit(1);
			}
			$florbTiers[$pid] = [];
			foreach($arr as $timeStr){
				$florbTiers[$pid][] = (float)$timeStr;
			}
		}
	}
	return $florbTiers;
}

function PrintFlorbTiers(){
	$tiers = GetFlorbTiers();
	foreach($tiers as $pid => $tierList){
		printf("%5d : %5.2f %5.2f %5.2f %5.2f\n", $pid, $tierList[0], $tierList[1], $tierList[2], $tierList[3]);
	}
}

function GetGlideTiers(){
	static $glideTiers = null;
	if($glideTiers === null){
		$glideTiers = [];
		$puzzleMap = GetPuzzleMap(true);
		foreach($puzzleMap as $pid => $data){
			$pid = (int)$pid;
			if($data->actualPtype != "racingRingCourse"){
				continue;
			}
			$ringCount = count((array)$data->{"DuplicatedObjectOfType-RacingRingsMeshComponent"});
			$maxScore = $ringCount * 4;
			$glideTiers[$pid] = [
				intval(round($maxScore * 0.60)),
				intval(round($maxScore * 0.75)),
				intval(round($maxScore * 0.90)),
				intval(round($maxScore * 1.00)),
			];
		}
	}
	return $glideTiers;
}

function PrintGlideTiers(){
	$tiers = GetGlideTiers();
	foreach($tiers as $pid => $tierList){
		printf("%5d : %3d %3d %3d %3d\n", $pid, $tierList[0], $tierList[1], $tierList[2], $tierList[3]);
	}
}

function GetGlideXpByTier(int $tier){
	static $xpTiers = [
		50,  // bronze
		75,  // silver
		100, // gold
		150, // platinum
	];
	if($tier < 0 || $tier >= count($xpTiers)){
		printf("%s: requested tier %d\n", __FUNCTION__, $tier);
		exit(1);
	}
	return $xpTiers[$tier];
}

function GetSkydropChallengePid(){
	// Internally this is defined like any regular skydrop, except it has an actual pid instead of -1.
	// It's messy and it's not worth writing some sort of automatic detection for what's actually the skydrop challenge.
	return 25248;
}

function GetSkydropChallengeTiers(){
	static $sscTiers = null;
	if($sscTiers === null){
		$sscPid = GetSkydropChallengePid();
		$puzzleMap = GetPuzzleMap(true);
		if(!isset($puzzleMap[$sscPid])){
			printf("%s\n", ColorStr("Skydrop speed challenge data (pid " . $sscPid . ") is missing", 255, 128, 128));
			exit(1);
		}
		$data = $puzzleMap[$sscPid];
		$arr = explode("-", $data->SandboxMilestones);
		if(count($arr) != 4){
			printf("%s\n", ColorStr("Skydrop speed challenge data (pid " . $sscPid . " has corrupted medal times: " . $data->SandboxMilestones, 255, 128, 128));
			exit(1);
		}
		$sscTiers = [];
		foreach($arr as $timeStr){
			$sscTiers[] = (float)$timeStr;
		}
	}
	return $sscTiers;
}

function GetXpTable(){
	static $xpTable = null;
	if($xpTable === null){
		$xpTable = [];
		$xpCsvPath = "media\\data\\xp.csv";
		if(!is_file($xpCsvPath)){
			printf("%s\n", ColorStr("File " . $xpCsvPath . " is missing", 255, 128, 128));
			exit(1);
		}
		$xpTable = LoadCsvMap($xpCsvPath, "level");
		array_multisort(array_column($xpTable, "xpTotal"), SORT_ASC, $xpTable);
	}
	return $xpTable;
}

function XpToLevel(int $xp){
	if($xp < 0){
		return -1;
	}
	$xpTable = GetXpTable();
	// I shouldn't be writing this at 4 in the morning but here we go
	$myLevel = 0;
	while(true){
		if(!isset($xpTable[$myLevel]) || $xp < $xpTable[$myLevel]["xpTotal"]){
			return ($myLevel - 1);
		}
		++$myLevel;
	}
}

function GetXpLevelInfo(int $level){
	$xpTable = GetXpTable();
	foreach($xpTable as $entry){
		if($entry["level"] == $level){
			return $entry;
		}
	}
	printf("%s\n", ColorStr(__FUNCTION__ . ": unknown player level " . $level, 255, 128, 128));
	exit(1);
	return [];
}

function GetTotalXpTo99(){
	//return 121118;
	// Fine fine I'll rewrite this
	$xpTable = GetXpTable();
	$lastEntry = end($xpTable);
	$totalXpAt99 = $lastEntry["xpTotal"];
	return $totalXpAt99;
}

function GetZoneCategoryPtypes(){
	static $zoneCategoryPtypes = null;
	if($zoneCategoryPtypes === null){
		$hubProfile = GetHubProfile();
		$zoneCategoryPtypes = SplitProfileByCategories($hubProfile);
		foreach($zoneCategoryPtypes as $zoneIndex => &$categoryInfo_ref){
			foreach($categoryInfo_ref as $pcat => &$ptypeInfo_ref){
				$ptypeInfo_ref = array_keys($ptypeInfo_ref);
			}
		}
	}
	return $zoneCategoryPtypes;
}

function GetZoneCategories(){
	static $zoneCategories = null;
	if($zoneCategories === null){
		$zoneCategories = GetZoneCategoryPtypes();
		foreach($zoneCategories as $zoneIndex => &$categoryInfo_ref){
			$categoryInfo_ref = array_keys($categoryInfo_ref);
		}
	}
	return $zoneCategories;
}

function LoadHubTrackRewards(){
	$hubTrackRewards = null;
	if($hubTrackRewards === null){
		$hubTrackRewards = [];
		$allPcats = GetZoneCategories();
		foreach($allPcats as $zoneIndex => $pcatList){
			$hubTrackRewards[$zoneIndex] = [];
			foreach($pcatList as $pcat){
				$hubTrackRewards[$zoneIndex][$pcat] = [];
			}
		}
		$csvPath = "media\\data\\hubRewards.csv";
		$csv = [];
		if(!is_file($csvPath) || empty($csv = LoadCsv($csvPath))){
			printf("%s\n", ColorStr("Failed to load file " . $csvPath, 255, 128, 128));
			exit(1);
		}
		foreach($csv as $entry){
			$zoneName  = $entry["zone"];
			$pcatName  = $entry["category"];
			$tierCount = $entry["puzzleCount"];
			$reward    = $entry["reward"];
			$value     = $entry["value"];
			$zoneIndex = ZoneNameToInt($zoneName);
			$pcat      = PuzzleCategoryInternalName($pcatName);
			$hubTrackRewards[$zoneIndex][$pcat][$tierCount] = $reward;
		}
		foreach($hubTrackRewards as $zoneIndex => &$pcatList_ref){
			foreach($pcatList_ref as $pcat => &$rewardTiers_ref){
				ksort($rewardTiers_ref);
				if(count($rewardTiers_ref) != 7){
					printf("%s\n", ColorStr("File " . $csvPath . " is damaged, " . $zoneName . " / " . $pcatName . " has " . count($rewardTiers_ref) . " tiers (expected: 7)", 255, 128, 128));
					exit(1);
				}
			}
		}
	}
	return $hubTrackRewards;
}

//function GetTotalCosmeticsCount(){
//	// Technically 309 but 8 are deluxe, I am now excluding deluxe ones
//	// Some people without the deluxe edition randomly have them in the save file, including the author of this program
//	// I'm just gonna filter them out in the save analyzer code
//	// Whether you (definitely) have the deluxe edition is reliably set as "Has Deluxe Edition" bool in Settings_0 in the save
//	return 301;
//}

function GetCosmeticsMap(){
	static $cosmeticsMap = null;
	if($cosmeticsMap === null){
		$cosmeticsMap = [];
		$csv = LoadCsv("media\\data\\cosmetics.csv");
		foreach($csv as $entry){
			$cosmeticsMap[$entry["name"]] = $entry["type"];
		}
		ksort($cosmeticsMap);
	}
	return $cosmeticsMap;
}

function GetTotalCosmeticsCount(){
	return count(GetCosmeticsMap());
}

function GetTotalMysteriesCount(){
	// Lazy
	return 21;
}

function GetMaxMirabilisCount(){
	// Lazy
	return 262;
}
