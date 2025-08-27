<?php

include_once("include\\pjson_parse.php");
include_once("include\\lookup.php");
include_once("include\\config.php");
include_once("include\\timex.php");

function LocateSaveGamePath(){
	global $config;
	
	static $saveFilePath = null;
	if($saveFilePath === null){
		$saveFilePath = $config["save_path"];
		if(empty($saveFilePath)){
			$localAppData = getenv("LOCALAPPDATA");
			if(empty($localAppData)){
				printf("%s\n", ColorStr("Unable to determine the AppData/Local folder", 255, 128, 128));
				printf("%s\n", ColorStr("Please set the path to OfflineSavegame.sav file in config.txt manually", 255, 128, 128));
				exit(1);
			}
			$saveFilePath = asDir($localAppData) . "IslandsofInsight\\Saved\\SaveGames\\OfflineSavegame.sav";
		}
		if(!is_file($saveFilePath)){
			printf("%s\n", ColorStr("Failed to find OfflineSavegame.sav, tried looking here:", 255, 128, 128));
			printf("%s\n", ColorStr($saveFilePath, 255, 192, 192));
			printf("%s\n", ColorStr("Make sure you've downloaded your offline save data from the game", 255, 128, 128));
			printf("%s\n", ColorStr("If so, please set the path to OfflineSavegame.sav file in config.txt manually", 255, 128, 128));
			exit(1);
		}
		
		printf("%s\n\n", ColorStr("Save file located in " . $saveFilePath, 128, 255, 128));
	}
	return $saveFilePath;
}

function DecodeSaveFile($inputPath, $outputPath){
	$uesavePath = "uesave\\uesave.exe";
	if(!is_file($uesavePath)){
		printf("%s\n", ColorStr("uesave.exe tool appears to be missing", 255, 128, 128));
		printf("%s\n", ColorStr("Try redownloading this program", 255, 128, 128));
		exit(1);
	}

	// Note: attempts to use stuff like passthru(), shell_exec() etc to avoid writing .json to disk failed.
	// Output takes several minutes or more to capture despite my best efforts to speed it up.
	// Ironically, just letting the program dump a ~40 MB file to disk and load it back is almost instant.
	//ob_start(); passthru($uesaveCmd); $uesaveResult = ob_get_contents(); ob_end_clean();
	//$uesaveResult = shell_exec($uesaveCmd);
	
	// Decoding the OfflineSavegame.sav with uesave.exe tool
	printf("%s\n", ColorStr("Attempting to decode the save data...", 160, 160, 160));
	$uesaveCmd = $uesavePath . " to-json < \"" . $inputPath . "\" > \"" . $outputPath . "\"";
	//printf("Executing command: %s\n", $uesaveCmd);
	$uesaveOutput = "";
	$uesaveResult = 0;
	exec($uesaveCmd, $uesaveOutput, $uesaveResult);
	if($uesaveResult != 0 || !is_file($outputPath)){
		printf("%s\n", ColorStr("uesave.exe tool failed to decode OfflineSavegame.sav", 255, 128, 128));
		printf("%s\n", ColorStr("Please report this on the Discord server", 255, 128, 128));
		exit(1);
	}
	printf("%s\n", ColorStr("Save file decoded as " . $outputPath, 128, 255, 128));
	return true;
}

function EncodeSaveFile($inputPath, $outputPath){
	$uesavePath = "uesave\\uesave.exe";
	if(!is_file($uesavePath)){
		printf("%s\n", ColorStr("uesave.exe tool appears to be missing", 255, 128, 128));
		printf("%s\n", ColorStr("Try redownloading this program", 255, 128, 128));
		exit(1);
	}
	
	printf("%s\n", ColorStr("Attempting to encode the save data...", 160, 160, 160));
	$uesaveCmd = $uesavePath . " from-json < \"" . $inputPath . "\" > \"" . $outputPath . "\"";
	printf("Executing command: %s\n", $uesaveCmd);
	$uesaveOutput = "";
	$uesaveResult = 0;
	exec($uesaveCmd, $uesaveOutput, $uesaveResult);
	if($uesaveResult != 0 || !is_file($outputPath)){
		printf("%s\n", ColorStr("uesave.exe tool failed to encode OfflineSavegame.json", 255, 128, 128));
		exit(1);
	}
	printf("%s\n", ColorStr("Save file encoded as " . $outputPath, 128, 255, 128));
	return true;
}

function &RetrievePrimaryNode(&$rawJson, string $nodeName){
	if(empty($rawJson)){
		printf("[ERROR] Raw json file is emopty!\n");
		return;
	}
	if(!isset($rawJson->root)){
		$rawJson->root = json_decode('{ "save_game_type": "/Script/IslandsofInsight.SophiaSaveGame", "properties": { } }');
		printf("[WARNING] Creating a root subnode!\n");
	}
	if(!isset($rawJson->root->properties)){
		$rawJson->root->properties = (object)[];
	}
	
	static $defaultNode = null;
	if($defaultNode === null){
		$defaultNode = json_decode('{"Array": {"array_type": "StructProperty", "value": {"Struct": {"_type": "Unknown", "name": "StructProperty", "struct_type": {"Struct": "unknown"}, "id": "00000000-0000-0000-0000-000000000000", "value": []}}}}');
	}
	
	static $nodeStructInfoMap = [
		"Wallet"              => "KrakenWalletAccount",
		"Inventory"           => "KrakenInventory",
		"Settings"            => "KrakenCustomizationSetting",
		"Achievements"        => "KrakenAchievementsState",
		"Upgrades"            => "KrakenUpgradeStatus",
		"Statuses"            => "KrakenServerVerifiedStatus",
		"Quests"              => "KrakenQuestStatus",
		"PuzzleStatuses"      => "KrakenPlayerPuzzleStatusData",
		"RewardProgress"      => "KrakenRewardedProgressLevel",
		"RewardProgressArray" => "KrakenRewardedProgressLevelArray",
		"Unlockables"         => "KrakenUnlocksInCategory",
	];
	
	$nodeName = str_replace("_0", "", $nodeName);
	$parentNodeName = $nodeName . "_0";
	
	$mainNode_ref = &$rawJson->root->properties;
	if(!isset($mainNode_ref->$parentNodeName)){
		$mainNode_ref->$parentNodeName = unserialize(serialize($defaultNode));
		$mainNode_ref->$parentNodeName->{"Array"}->value->Struct->{"_type"} = $nodeName;
		$mainNode_ref->$parentNodeName->{"Array"}->value->Struct->struct_type->Struct = $nodeStructInfoMap[$nodeName];
	}
	
	$a = &$mainNode_ref->$parentNodeName->{"Array"}->value->Struct->value;
	return $a;
}

function RetrieveStructIndexByNameValue(&$primaryNode_ref, string $fieldName, $fieldValue){
	if(empty($primaryNode_ref)){
		printf("[ERROR] Given primary node is empty! Was looking for: %s = %s\n", $fieldName, $fieldValue);
		return -1;
	}
	foreach($primaryNode_ref as $index => &$struct_ref){
		if(!isset($struct_ref->Struct) || !isset($struct_ref->Struct->$fieldName)){
			continue;
		}
		$checkValue = array_values((array)($struct_ref->Struct->$fieldName))[0]->value;
		if($checkValue == $fieldValue){
			return $index;
		}
	}unset($struct_ref);
	return -1;
}

function &RetrieveStructByNameValue(&$primaryNode_ref, string $fieldName, $fieldValue){
	static $error = null;
	$index = RetrieveStructIndexByNameValue($primaryNode_ref, $fieldName, $fieldValue);
	//printf("Searching for %s = %s, found index: %d\n", $fieldName, $fieldValue, $index);
	if($index < 0){
		return $error;
	}
	$a = &$primaryNode_ref[$index];
	return $a;
}

function ParseAllPuzzles($rawJson, &$miscMap_ref){
	$puzzleMap = GetPuzzleMap(true);
	$puzzleCsvData = [];
	//if(!isset($mainNode->PuzzleStatuses_0)){
	//	return $puzzleCsvData;
	//}
	////$badSentinels = GetBadSentinels();
	//$allPuzzlesNode = $mainNode->PuzzleStatuses_0->{"Array"}->value->Struct->value; // yes this really sucks
	$allPuzzlesNode = RetrievePrimaryNode($rawJson, "PuzzleStatuses");
	foreach($allPuzzlesNode as $puzzleNode){
		// "BestScore_0": {                    // always 0
		// "LastSolveTimestamp_0": {           // seems to be always 0 in offline mode
		// "LeaderboardTime_0": {              // all-time PBs for florbs / skydrop challenge, mazes, and grids (lol)
		// "MiscStatus_0": {                   // 
		// "PlayerId_0": {                     // always empty
		// "PuzzleId_0": {                     // pid
		// "bOverride_BestScore_0": {          // always false
		// "bOverride_LastSolveTimestamp_0": { // always false
		// "bOverride_LeaderboardTime_0": {    // always false
		// "bOverride_MiscStatus_0": {         // always false
		// "bOverride_Reset_0": {              // always false
		// "bOverride_Unlocks_0": {            // always false
		// "bReset_0": {                       // always false
		// "bSolved_0": {                      // mostly true, can be false, see below
		$pid      = (int)    $puzzleNode->Struct->PuzzleId_0->{"Int"}->value;
		$isSolved = (bool)   $puzzleNode->Struct->bSolved_0->{"Bool"}->value;
		$score    = (int)    $puzzleNode->Struct->BestScore_0->{"Int"}->value;
		$ts       = (int)    $puzzleNode->Struct->LastSolveTimestamp_0->{"Int"}->value;
		$pb       = (float)  $puzzleNode->Struct->LeaderboardTime_0->{"Float"}->value;
		$misc     = (string) $puzzleNode->Struct->MiscStatus_0->{"Str"}->value;
		
		if(isset($puzzleMap[$pid])){
			$ptype = $puzzleMap[$pid]->ptype;
			$zoneIndex = $puzzleMap[$pid]->zoneIndex;
		}else{
			$ptype = "extraPuzzle";
			$zoneIndex = -1;
			//printf("Skipping unknown solved puzzle %d\n", $pid);
			//continue;
		}
		
		if(!empty($misc) && !in_array($ptype, [ "gyroRing" ])){
			//$misc = str_replace(["\r", "\n", "\t"], "", $misc);
			//$misc = str_replace(".000000", "", $misc);
			$misc = json_encode(json_decode($misc)); // un-shit the internal format
			//printf("%s\n", ColorStr(sprintf("Puzzle %5d %-20s has misc: %s", $pid, $ptype, $misc), 255, 255, 0));
			$miscMap_ref[$pid] = $misc;
		}
		
		if(!$isSolved){
			//printf("%s\n", ColorStr(sprintf("Puzzle %5d %-20s unsolved", $pid, $puzzleMap[$pid]->ptype), 255, 255, 0));
			// Note: *some* records are indeed unsolved. This seems to correlate with monoliths and quests resetting randomly online.
			// Note: scratch that. This is related to basically all dungeons being initially reset - records exist, but they're not solved.
			continue;
		}
		if($score != 0){
			// This also should't be the case. We don't store this field.
			//printf("%s\n", ColorStr(sprintf("Puzzle %5d %-20s has score %d", $pid, $puzzleMap[$pid]->ptype, $score), 255, 255, 0));
		}
		$csvEntry = [
			"pid"   => $pid,
			"ptype" => PuzzlePrettyName($ptype),
			"zone"  => ZoneToPrettyNoColor($zoneIndex),
			"tss"   => TimestampToTss($ts),
			"pb"    => $pb,
		];
		//$pidToData[$pid] = $csvEntry;
		$puzzleCsvData[] = $csvEntry;
		
		//if(in_array($pid, $badSentinels)){
		//	printf("%s\n", ColorStr("Discovered previously unseen Sentinel Stones puzzle " . $pid, 255, 128, 128));
		//	printf("%s\n", ColorStr("Your stats may be slightly incorrect. Please report this on Discord!", 255, 128, 128));
		//}
	}
	array_multisort(array_column($puzzleCsvData, "tss"), SORT_ASC, $puzzleCsvData);
	return $puzzleCsvData;
}

function GetAllSolvedPids($rawJson){
	$miscMap = [];
	$puzzleCsvData = ParseAllPuzzles($rawJson, $miscMap);
	$solvedPidList = array_values(array_column($puzzleCsvData, "pid"));
	
	//$test = array_map(function($x){ return json_decode($x); }, $miscMap);
	//ksort($test);
	//foreach($test as $pid => &$data){
	//	//unset($data->ObjData);
	//	if(isset($data->DungeonName)){
	//		unset($test[$pid]);
	//	}
	//}unset($data);
	//printf("%s\n", json_encode($test, 0xc0)); exit(1);
	
	return $solvedPidList;
}

function ParseDecodedSaveFile($decodedJsonPath){
	
	$rawJsonString = file_get_contents($decodedJsonPath);
	if(empty($rawJsonString)){
		printf("%s\n", ColorStr("uesave.exe tool decoded OfflineSavegame.sav", 255, 128, 128));
		printf("%s\n", ColorStr("However, file " . $decodedJsonPath . " is missing or unreadable", 255, 128, 128));
		printf("%s\n", ColorStr("Please report this on the Discord server", 255, 128, 128));
		exit(1);
	}
	$rawJson = json_decode($rawJsonString);
	if(empty($rawJson)){
		printf("%s\n", ColorStr("Failed to parse file " . $decodedJsonPath, 255, 128, 128));
		printf("%s\n", ColorStr("Please report this on the Discord server", 255, 128, 128));
		exit(1);
	}
	
	printf("%s\n", ColorStr("Attempting to parse the decoded save data...", 160, 160, 160));
	
	$miscMap = [];
	$puzzleCsvData = ParseAllPuzzles($rawJson, $miscMap);
	printf("%s\n", ColorStr("Solved puzzles data obtained OK", 160, 160, 160));
	
	$normalSolvedPids = array_column($puzzleCsvData, "pid");
	sort($normalSolvedPids);
	//printf("%s\n", implode(",", $normalSolvedPids)); exit(1);
	
	//$mainNode = $rawJson->root->properties;
	
	$florbPbs         = GetFlorbPbs($puzzleCsvData);
	$florbMedalMap    = GetFlorbMedalMap($florbPbs);
	$florbMedalCounts = GetMedalCounts($florbMedalMap);
	$florbTotalScore  = GetFlorbTotalScore($florbPbs);
	
	$glidePbs         = GetGlidePbs($puzzleCsvData);
	$glideMedalMap    = GetGlideMedalMap($glidePbs);
	$glideMedalCounts = GetMedalCounts($glideMedalMap);
	$glideTotalScore  = GetGlideTotalScore($glidePbs);
	
	$skydropChallengePb = GetSkydropChallengeTime($puzzleCsvData);
	//$skydropChallengeMedalTier = GetSkydropChallengeMedalTier($skydropChallengePb);
	//$skydropChallengeMedal = MedalTierToName($skydropChallengeMedalTier);
	$skydropChallengeMedal = GetSkydropChallengeMedalTier($skydropChallengePb);
	
	$playingSince = GetPlayingSince($puzzleCsvData);
	$playTimeMinutes = GetIslandsPlaytime();
	
	$unlockPids = GetUnlocks($rawJson);
	$monolithFragmentPids = GetMonolithFragments($miscMap);
	$allSolvedPids = array_merge($normalSolvedPids, $unlockPids, $monolithFragmentPids);
	
	$quests = GetSaveCampaignQuests($rawJson); // boring
	
	$masteryTable = GetMasteryTable($rawJson);
	$playerLevel = GetPlayerLevel($masteryTable);
	
	$sparks = GetSparks($rawJson);
	$buggedMirabilis = GetBuggedMirabilis($rawJson);
	
	$allRemainingArmillaries    = GetAllRemainingArmillaries($allSolvedPids);
	$allSolvedArmillaries       = GetAllSolvedArmillaries($allSolvedPids);
	$templeRemainingArmillaries = GetTempleRemainingArmillaries($allSolvedPids);
	$templeSolvedArmillaries    = GetTempleSolvedArmillaries($allSolvedPids);
	$staticRemainingArmillaries = GetStaticRemainingArmillaries($allSolvedPids);
	$staticSolvedArmillaries    = GetStaticSolvedArmillaries($allSolvedPids);

	$allHubProfile              = GetHubProfile();
	$staticRemainingPids        = GetStaticRemainingPids($allSolvedPids);
	$staticSolvedPids           = GetStaticSolvedPids($allSolvedPids);
	$hubSolvedProfile           = BuildProfile($allSolvedPids);
	$hubRemainingProfile        = SubtractProfiles($allHubProfile, $hubSolvedProfile);
	$hubSolvedPids              = ExtractAllPids($hubSolvedProfile);
	$hubRemainingPids           = ExtractAllPids($hubRemainingProfile);
	
	$remainingClusterMap        = GetClusterRemainingMap($allSolvedPids);
	$solvedClusterMap           = GetClusterSolvedMap($allSolvedPids);
	
	$remainingMysteries         = GetRemainingMysteries($allSolvedPids);
	$solvedMysteries            = GetSolvedMysteries($allSolvedPids);
	
	$cosmetics                  = GetCosmetics($rawJson);
	$hubRewards                 = GetHubRewards($hubSolvedProfile);
	$settings                   = GetSaveFileSettings($rawJson);
	$hasDeluxe                  = HasDeluxe($settings);
	
	// RewardProgressArray_0 contains what you've collected from leveling up on the Mastery tab - boring.
	// RewardProgress_0 contains what you've claimed from the hub track rewards. Not bad, but won't mention un-claimed ones.
	// Additionally we are interested in how many puzzles you have left till the remaining rewards so this becomes useless anyway.
	//$rewardTiers                = GetHubRewardTiers($rawJson);
	
	$saveJson = [
		"puzzleCsvData"              => $puzzleCsvData,              // 
		"allSolvedPids"              => $allSolvedPids,              // 

		"florbPbs"                   => $florbPbs,                   // 
		"florbMedalMap"              => $florbMedalMap,              // 
		"florbMedalCounts"           => $florbMedalCounts,           // 
		"florbTotalScore"            => $florbTotalScore,            // 
		
		"glidePbs"                   => $glidePbs,                   // 
		"glideMedalMap"              => $glideMedalMap,              // 
		"glideMedalCounts"           => $glideMedalCounts,           // 
		"glideTotalScore"            => $glideTotalScore,            // 
		
		"skydropChallengePb"         => $skydropChallengePb,         // 
		"skydropChallengeMedal"      => $skydropChallengeMedal,      // 
		"playingSince"               => $playingSince,               // 
		"playTimeMinutes"            => $playTimeMinutes,            // 
		
		"playerLevel"                => $playerLevel,                // 
		"masteryTable"               => $masteryTable,               // 
		"sparks"                     => $sparks,                     // 
		"buggedMirabilis"            => $buggedMirabilis,            // 

		"allRemainingArmillaries"    => $allRemainingArmillaries,    // 
		"allSolvedArmillaries"       => $allSolvedArmillaries,       // 
		"templeRemainingArmillaries" => $templeRemainingArmillaries, // 
		"templeSolvedArmillaries"    => $templeSolvedArmillaries,    // 
		"staticRemainingArmillaries" => $staticRemainingArmillaries, // 
		"staticSolvedArmillaries"    => $staticSolvedArmillaries,    // 

		"staticRemainingPids"        => $staticRemainingPids,        // 
		"staticSolvedPids"           => $staticSolvedPids,           // 
		"hubSolvedProfile"           => $hubSolvedProfile,           // 
		"hubRemainingProfile"        => $hubRemainingProfile,        // 
		"hubSolvedPids"              => $hubSolvedPids,              // 
		"hubRemainingPids"           => $hubRemainingPids,           // 

		"remainingClusterMap"        => $remainingClusterMap,        // 
		"solvedClusterMap"           => $solvedClusterMap,           // 

		"remainingMysteries"         => $remainingMysteries,         // 
		"solvedMysteries"            => $solvedMysteries,            // 
		
		"cosmetics"                  => $cosmetics,                  // list of unlocked cosmetics
		"hubRewards"                 => $hubRewards,                 // rewards you've obtained AND how much longer to the remaining ones
		"settings"                   => $settings,                   // mostly character customization, also stuff like "Accepted EULA" or "Crouch Toggle Mode", show ping/fps etc
		"hasDeluxe"                  => $hasDeluxe,                  // deluxe edition flag
		
		"quests"                     => $quests,                     // absolutely uninteresting
	//	"unlockPids"                 => $unlockPids,                 // included in allSolvedPids
	//	"monolithFragmentPids"       => $monolithFragmentPids,       // included in allSolvedPids
	//	"skydropChallengeMedalTier"  => $skydropChallengeMedalTier,  // redundant
	//	"rewardTiers"                => $rewardTiers,                // hub track rewards that you've *claimed*; we track available+claimed rewards instead
	];
	
	ksort($saveJson);
	printf("%s\n", ColorStr("Player stats read OK", 160, 160, 160));
	printf("\n");
	
	return (object)$saveJson;
}

function GetFlorbPbs($puzzleCsvData){
	$florbPbs = [];
	foreach($puzzleCsvData as $entry){
		if($entry["ptype"] == PuzzlePrettyName("racingBallCourse")){
			$florbPbs[$entry["pid"]] = $entry["pb"];
		}
	}
	ksort($florbPbs);
	return $florbPbs;
}

function GetFlorbMedalMap($florbPbs){
	$florbTiers = GetFlorbTiers();
	$tierCount = reset($florbTiers);
	$florbMedalMap = [[],[],[],[]];
	foreach($florbPbs as $pid => $pb){
		if(!isset($florbTiers[$pid])){
			//printf("Skipping unknown florb %d\n", $pid);
			continue;
		}
		$currTiers = $florbTiers[$pid];
		$bestTier = 0;
		while($bestTier < count($currTiers) && $pb < $currTiers[$bestTier] + 1e-3){
			++$bestTier;
		}
		--$bestTier; // -1 for no medal, 0..3 for medals
		if($bestTier < 0 || $bestTier > 3){
			printf("%s\n", ColorStr("Best tier for flow orb " . $pid . " sucks: recorded score is " . $pb . ", minimum for bronze is " . $currTiers[0] . " - counting this as bronze", 192, 128, 128));
			//continue;
			$bestTier = 0;
		}
		$florbMedalMap[$bestTier][] = $pid;
	}
	foreach($florbMedalMap as &$pidList_ref){
		sort($pidList_ref);
	}
	
	return $florbMedalMap;
}

function GetFlorbTotalScore(array $florbPbs){
	$tierMap = GetFlorbTiers();
	$sum = 0;
	foreach($tierMap as $pid => $tiers){
		// If solved, use actual time; otherwise use bronze tier (can't go slower than bronze anyway).
		$sum += ($florbPbs[$pid] ?? $tiers[0]);
	}
	$minutes = intval(floor($sum / 60 + 1e-4));
	$seconds = $sum - $minutes * 60;
	$formatted = sprintf("%d:%05.2f", $minutes, $seconds);
	return $formatted;
}

function GetGlidePbs($puzzleCsvData){
	$glidePbs = [];
	foreach($puzzleCsvData as $entry){
		if($entry["ptype"] == PuzzlePrettyName("racingRingCourse")){
			$glidePbs[$entry["pid"]] = intval(round($entry["pb"]));
		}
	}
	ksort($glidePbs);
	return $glidePbs;
}

function GetGlideMedalMap($glidePbs){
	$glideTiers = GetGlideTiers();
	$tierCount = reset($glideTiers);
	$glideMedalMap = [[],[],[],[]];
	foreach($glidePbs as $pid => $pb){
		if(!isset($glideTiers[$pid])){
			printf("Skipping unknown glide %d\n", $pid);
			continue;
		}
		$currTiers = $glideTiers[$pid];
		$bestTier = 0;
		while($bestTier < count($currTiers) && $pb >= $currTiers[$bestTier]){
			++$bestTier;
		}
		--$bestTier; // -1 for no medal, 0..3 for medals
		if($bestTier < 0 || $bestTier > 3){
			// -1 shouldn't be possible as there wouldn't be a solve to begin with.
			printf("%s\n", ColorStr("Best tier for glide ring " . $pid . " sucks: recorded score is " . $pb . ", minimum for bronze is " . $currTiers[0] . " - counting this as bronze", 192, 128, 128));
			//continue;
			$bestTier = 0;
		}
		$glideMedalMap[$bestTier][] = $pid;
	}
	foreach($glideMedalMap as &$pidList_ref){
		sort($pidList_ref);
	}
	
	return $glideMedalMap;
}

function GetGlideTotalScore(array $glidePbs){
	$sum = 0;
	foreach($glidePbs as $pid => $pb){
		$sum += $pb;
	}
	return $sum;
}

function GetMedalCounts($medalMap){
	$medalCounts = array_map("count", $medalMap);
	$result = [];
	foreach($medalCounts as $tier => $count){
		$result[MedalTierToName($tier)] = $count;
	}
	return $result;
}

function GetSkydropChallengeTime($puzzleCsvData){
	foreach($puzzleCsvData as $entry){
		if($entry["pid"] != GetSkydropChallengePid()){
			continue;
		}
		$pb = $entry["pb"];
		$sscTiers = GetSkydropChallengeTiers();
		if($pb > $sscTiers[0]){
			// By default, skydrop challenge is automatically solved with time below Bronze.
			return -1;
		}
		return $entry["pb"];
	}
	return -1;
}

function GetSkydropChallengeMedalTier($pb){
	if($pb < 0){
		return -1;
	}
	$sscTiers = GetSkydropChallengeTiers();
	$bestTier = 0;
	while($bestTier < count($sscTiers) && $pb < $sscTiers[$bestTier] + 1e-3){
		++$bestTier;
	}
	--$bestTier;
	return $bestTier;
}

function WriteRawPuzzleSolves($saveJson, $outputPath){
	//$result = @file_put_contents($outputPath, FormCsv($saveJson->puzzleCsvData));
	//if($result){
	//	//printf("%s\n", ColorStr("Writing ". $outputPath, 128, 192, 255));
	//}else{
	//	printf("%s\n", ColorStr("Failed to write ". $outputPath . " - most likely Excel is blocking it, close Excel first", 255, 128, 128));
	//}
	$formedCsv = FormCsv($saveJson->puzzleCsvData);
	if(empty($formedCsv)){
		printf("%s\n", ColorStr("No puzzles solved at all - skipping " . $outputPath, 200, 200, 40));
	}else{
		$result = @file_put_contents($outputPath, $formedCsv);
		if($result){
			printf("%s\n", ColorStr("Writing ". $outputPath, 128, 192, 255));
		}else{
			printf("%s\n", ColorStr("Failed to write ". $outputPath . " - most likely Excel is blocking it, close Excel first", 255, 128, 128));
		}
	}
}

function WriteFlorbPbs($saveJson, $outputPath){
	$florbMedalMap = $saveJson->florbMedalMap;
	$florbPbs = $saveJson->florbPbs;
	$csv = [];
	$puzzleMap = GetPuzzleMap(true);
	foreach($florbMedalMap as $tier => $pidList){
		foreach($pidList as $pid){
			$data = $puzzleMap[$pid];
			$csv[] = [
				"pid" => $pid,
				"medal" => $tier,
				//"type" => (int)$data->isDungeonPuzzle,
				"type" => $data->family,
				"zone" => $data->zoneIndex,
				"pb" => number_format($florbPbs[$pid], 2, ".", ""),
				//"cetus" => "https://cetus.torstenindustries.com/Puzzle/" . $pid,
			];
		}
	}
	array_multisort(
					array_column($csv, "medal"), SORT_ASC,  SORT_NATURAL,
					array_column($csv, "type" ), SORT_DESC, SORT_NATURAL,
					array_column($csv, "zone" ), SORT_ASC,  SORT_NATURAL,
					array_column($csv, "pid"  ), SORT_ASC,  SORT_NATURAL,
					$csv);
	
	// Finalize csv for user readability.
	foreach($csv as &$entry_ref){
		$zoneIndex = $entry_ref["zone"];
		//$entry_ref["type"] = ($entry_ref["type"] ? "Enclave" : "Hub");
		$entry_ref["zone"] = ($zoneIndex >= 2 && $zoneIndex <= 7 ? ZoneToPrettyNoColor($entry_ref["zone"]) : "Enclave");
		$entry_ref["medal"] = MedalTierToName($entry_ref["medal"]);
		
	}
	$formedCsv = FormCsv($csv);
	if(empty($formedCsv)){
		printf("%s\n", ColorStr("No florbs solved at all - skipping " . $outputPath, 200, 200, 40));
	}else{
		$result = @file_put_contents($outputPath, $formedCsv);
		if($result){
			printf("%s\n", ColorStr("Writing ". $outputPath, 128, 192, 255));
		}else{
			printf("%s\n", ColorStr("Failed to write ". $outputPath . " - most likely Excel is blocking it, close Excel first", 255, 128, 128));
		}
	}
}

function WriteFullSaveData($saveJson, $outputPath){
	// But like, why do this?
	unset($saveJson->puzzleCsvData);
	file_put_contents($outputPath, json_encode($saveJson, JSON_PRETTY_PRINT));
}

function GetUnlocks($rawJson){
	$fakePids = [];
	$unlocksNode = RetrievePrimaryNode($rawJson, "Unlockables");
	foreach($unlocksNode as $node){
		$subNode = $node->Struct->Unlocks_0->{"Array"}->value->Base->Str;
		foreach($subNode as $title){
			//printf("|%s|\n", $title);
			$pid = UnlockTitleToPid($title);
			if($pid === FALSE){
				continue;
			}
			$fakePids[] = $pid;
		}
	}
	return $fakePids;
}

function GetSaveCampaignQuests($rawJson){
	// This one is completely uninteresting.
	// It just contains the data for daily quests for sparks (including Wanderer ones).
	// And the progression data for the main campaign. You know, solve tutorial island, go to verdant, earn your wings...
	$quests = [];
	$questsNode = RetrievePrimaryNode($rawJson, "Quests");
	foreach($questsNode as $node){
		$questName = $node->Struct->QuestID_0->Str->value;
		if(str_starts_with($questName, "Daily") || str_starts_with($questName, "Auto")){
			continue;
		}
		$questStatusString = $node->Struct->QuestStatus_0->Str->value;
		$questDataRaw = json_decode($questStatusString);
		if(!isset($questDataRaw->ObjData)){
			// Should always be present.
			continue;
		}
		$questData = $questDataRaw->ObjData;
		$test = json_encode($questData);
		$test = str_replace("QuestObjectiveState", "Q", $test);
		$test = str_replace("false", "0", $test);
		$test = str_replace("true", "1", $test);
		//printf("%s: %s\n", $questName, json_encode($questData));
		//printf(ColorStr(sprintf("%s: %s\n", $questName, $test), 255, 255, 0));
		$quests[$questName] = $test;
	}
	return $quests;
}

function GetMonolithFragments($miscMap){
	$puzzleMap = GetPuzzleMap(true);
	$megaTable = GetMegaTable();
	$monolithFragmentPids = [];
	foreach($miscMap as $obeliskPid => $miscString){
		$json = json_decode($miscString);
		if(empty($json) || !isset($json->Found)){
			continue;
		}
		$boolArray = $json->Found;
		if(!isset($puzzleMap[$obeliskPid])){
			continue;
		}
		$data = $puzzleMap[$obeliskPid];
		$ptype = $data->ptype;
		if($ptype != "obelisk"){
			continue;
		}
		$actualZoneIndex = $data->zoneIndex;
		//printf("%5d (%d): %s\n", $obeliskPid, $actualZoneIndex, $miscString);
		foreach($boolArray as $fragmentIndex => $status){
			if($status == false){
				continue;
			}
			$fakePid = MonolithFragmentToFakePid($actualZoneIndex, $fragmentIndex);
			$monolithFragmentPids[] = $fakePid;
		}
	}
	return $monolithFragmentPids;
}

function GetPlayingSince($puzzleCsvData){
	if(empty($puzzleCsvData)){
		return "";
	}
	// puzzleCsvData assumed to be already sorted by tss
	$playingSinceTss = reset($puzzleCsvData)["tss"];
	if($playingSinceTss == TimestampToTss(0)){
		return "";
	}
	$playingSince = (TssToLocalDateTime($playingSinceTss))->format("M j, Y");
	return $playingSince;
}

function GetMasteryTable($rawJson){
	$masteryTable = [];
	$knownPtypes = GetKnownPtypes();
	if(count($knownPtypes) != 24){
		printf("%s\n", ColorStr("Yo Rushin, fix your code, GetKnownPtypes is all wrong", 255, 128, 128));
		exit(1);
	}
	foreach($knownPtypes as $ptype){
		$masteryTable[$ptype] = [
			"ptype" => $ptype,
			"level" => 0,
			"xp"    => 0,
			"pct"   => "0.00%",
			"title"  => "Novice",
			"border" => "none",
			"skin"   => 0,
		];
	}
	
	$masteryNode = RetrievePrimaryNode($rawJson, "Achievements");
	foreach($masteryNode as $node){
		$masteryInternalName = $node->Struct->AchievementId_0->Str->value;
		$xp    = $node->Struct->Value_0->Struct->value->Struct->Progress_0->{"Int"}->value;
		//$level = ((int)$node->Struct->Value_0->Struct->value->Struct->LastCompletedTier_0->{"Int"}->value) + 1; // don't trust the given value
		$ptype = PuzzleInternalName(str_replace(["Mastery", "-"], "", $masteryInternalName));
		// Note: last completed tier starts with 0. So completing tier 0 advances you from level 0 to 1.
		// The final completed tier is 98 which advances you from 98 to 99.
		// Also: in offline mode completed tier is always zero for all masteries.
		//printf("%-30s %7d %2d\n", $masteryInternalName, $xp, $level);
		//printf("|%s| -> |%s|\n", $masteryInternalName, $ptype);
		if(empty($ptype)){
			// This will ignore total xp across all masteries which is a useless stat.
			continue;
		}
		$level = XpToLevel($xp);
		$pct = 100.0 * $xp / GetTotalXpTo99();
		$extraInfo = GetXpLevelInfo($level);
		$masteryTable[$ptype] = [
			"ptype"  => $ptype,
			"level"  => $level,
			"xp"     => $xp, //number_format($xp, 0, ".", " "), // don't number format this aight?
			"pct"    => number_format($pct, 2, ".", "") . "%",
			"title"  => $extraInfo["title"],
			"border" => $extraInfo["border"],
			"skin"   => $extraInfo["skin"],
		];
	}
	$masteryTable = array_values($masteryTable);
	array_multisort(array_column($masteryTable, "xp"), SORT_DESC, SORT_NUMERIC, $masteryTable);
	return $masteryTable;
}

function GetPlayerLevel($masteryTable){
	$puzzleLevels = array_column($masteryTable, "level");
	$totalPuzzleLevels = array_sum($puzzleLevels);
	$playerLevel = intval(floor(1.01 + ($totalPuzzleLevels / 12.0)));
	return $playerLevel;
}

function WriteMasteries($saveJson, $outputPath){
	$formedCsv = FormCsv($saveJson->masteryTable);
	$result = @file_put_contents($outputPath, $formedCsv);
	if($result){
		//printf("%s\n", ColorStr("Writing ". $outputPath, 128, 192, 255));
	}else{
		printf("%s\n", ColorStr("Failed to write ". $outputPath . " - most likely Excel is blocking it, close Excel first", 255, 128, 128));
	}
}

function &RetrieveCurrency(&$rawJson, $currencyName){
	$walletNode_ref = &RetrievePrimaryNode($rawJson, "Wallet");
	$node_ref = &RetrieveStructByNameValue($walletNode_ref, "Currency_0", $currencyName);
	if($node_ref == null){
		$newNode = json_decode('{"Struct": {"Balance_0": {"Int": {"value": 0}}, "Currency_0": {"Str": {"value": "unknown"}}, "CurrencyGroup_0": {"Str": {"value": ""}}, "bDisabled_0": {"Bool": {"value": false}}, "LastRefillTimestamp_0": {"Int64": {"value": 0}}, "Origin_0": {"Str": {"value": ""}}, "ProviderBalances_0": {"Struct": {"value": {"Struct": {"JsonString_0": {"Str": {"value": ""}}}}, "struct_type": {"Struct": "JsonObjectWrapper"}, "struct_id": "00000000-0000-0000-0000-000000000000"}}, "Reason_0": {"Str": {"value": ""}}, "RefillAmount_0": {"Float": {"value": 0}}, "RequestID_0": {"Str": {"value": ""}}, "Tier_0": {"Int": {"value": 0}}, "UserId_0": {"Str": {"value": ""}}, "bOverride_CurrencyGroup_0": {"Bool": {"value": false}}, "bOverride_Disabled_0": {"Bool": {"value": false}}, "bOverride_LastRefillTimestamp_0": {"Bool": {"value": false}}, "bOverride_Origin_0": {"Bool": {"value": false}}, "bOverride_ProviderBalances_0": {"Bool": {"value": false}}, "bOverride_Reason_0": {"Bool": {"value": false}}, "bOverride_RefillAmount_0": {"Bool": {"value": false}}, "bOverride_RequestId_0": {"Bool": {"value": false}}, "bOverride_Tier_0": {"Bool": {"value": false}}, "bOverride_UserId_0": {"Bool": {"value": false}}}}');
		$newNode->Struct->Currency_0->Str->value = $currencyName;
		$node_ref = &$newNode;
		$walletNode_ref[] = &$newNode;
	}
	$actualValue_ref = &$node_ref->Struct->Balance_0->{"Int"}->value;
	return $actualValue_ref;
}

function GetCurrency($rawJson, $currencyName){
	return RetrieveCurrency($rawJson, $currencyName);
}

function GetSparks($rawJson){
	return RetrieveCurrency($rawJson, "coins");
}

function GetBuggedMirabilis($rawJson){
	return RetrieveCurrency($rawJson, "blue-orbs");
}

function SetCurrency(&$rawJson, $currencyName, $currencyValue){
	$value_ref = &RetrieveCurrency($rawJson, $currencyName);
	$value_ref = $currencyValue;
	printf("[DEBUG] Currency '%s' is set to %d\n", $currencyName, $currencyValue);
}

function &RetrieveUnlockableCategory(&$rawJson, string $categoryName){
	$unlocksNode_ref = &RetrievePrimaryNode($rawJson, "Unlockables");
	$node_ref = &RetrieveStructByNameValue($unlocksNode_ref, "UnlockableCategory_0", $categoryName);
	if($node_ref == null){
		$newNode = json_decode('{"Struct": {"UnlockableCategory_0": {"Str": {"value": "unknown"}}, "Unlocks_0": {"Array": {"array_type": "StrProperty", "value": {"Base": {"Str": []}}}}, "bOverride_Unlocks_0": {"Bool": {"value": false}}}}');
		$newNode->Struct->UnlockableCategory_0->Str->value = $categoryName;
		$node_ref = &$newNode;
		$unlocksNode_ref[] = &$newNode;
	}
	$actualValue_ref = &$node_ref->Struct->Unlocks_0->{"Array"}->value->Base->Str;
	return $actualValue_ref;
}

function &RetrieveClickedMasteryRewards(&$rawJson){
	$unlocksNode_ref = &RetrievePrimaryNode($rawJson, "RewardProgressArray");
	$node_ref = &RetrieveStructByNameValue($unlocksNode_ref, "ProgressId_0", "GlobalMasteryRewards");
	if($node_ref == null){
		$newNode = json_decode('{"Struct": {"ProgressId_0": {"Str": {"value": "GlobalMasteryRewards"}}, "RewardedLevels_0": {"Array": {"array_type": "IntProperty", "value": {"Base": {"Int": []}}}}}}');
		$node_ref = &$newNode;
		$unlocksNode_ref[] = &$newNode;
	}
	$actualValue_ref = &$node_ref->Struct->RewardedLevels_0->{"Array"}->value->Base->{"Int"};
	return $actualValue_ref;
}

function GetAllRemainingArmillaries($allSolvedPids){
	$allArmillaries = GetAllArmillaries();
	$remainingArmillaries = array_values(array_diff($allArmillaries, $allSolvedPids));
	return $remainingArmillaries;
}

function GetAllSolvedArmillaries($allSolvedPids){
	$allArmillaries = GetAllArmillaries();
	$solvedArmillaries = array_values(array_intersect($allArmillaries, $allSolvedPids));
	return $solvedArmillaries;
}

function GetTempleRemainingArmillaries($allSolvedPids){
	$templeArmillaries = GetTempleArmillaries();
	$remainingTempleArmillaries = array_values(array_diff($templeArmillaries, $allSolvedPids));
	return $remainingTempleArmillaries;
}

function GetTempleSolvedArmillaries($allSolvedPids){
	$templeArmillaries = GetTempleArmillaries();
	$solvedTempleArmillaries = array_values(array_intersect($templeArmillaries, $allSolvedPids));
	return $solvedTempleArmillaries;
}

function GetStaticRemainingArmillaries($allSolvedPids){
	$staticArmillaries = GetStaticArmillaries();
	$remainingStaticArmillaries = array_values(array_diff($staticArmillaries, $allSolvedPids));
	return $remainingStaticArmillaries;
}

function GetStaticSolvedArmillaries($allSolvedPids){
	$staticArmillaries = GetStaticArmillaries();
	$solvedStaticArmillaries = array_values(array_intersect($staticArmillaries, $allSolvedPids));
	return $solvedStaticArmillaries;
}

function GetStaticRemainingPids($allSolvedPids){
	$allStaticPids = GetAllStaticPids();
	$remainingStaticPids = array_values(array_diff($allStaticPids, $allSolvedPids));
	return $remainingStaticPids;
}

function GetStaticSolvedPids($allSolvedPids){
	$allStaticPids = GetAllStaticPids();
	$solvedStaticPids = array_values(array_intersect($allStaticPids, $allSolvedPids));
	return $solvedStaticPids;
}

function GetClusterRemainingMap($allSolvedPids){
	$clusterMap = GetClusterMap();
	$remainingClusterMap = [];
	foreach($clusterMap as $pool => $pidList){
		$remainingClusterMap[$pool] = array_values(array_diff($pidList, $allSolvedPids));
		//printf("remaining %d of %d in cluster %s\n", count($remainingClusterMap[$pool]), count($pidList), $pool);
	}
	return $remainingClusterMap;
}

function GetClusterSolvedMap($allSolvedPids){
	$clusterMap = GetClusterMap();
	$solvedClusterMap = [];
	foreach($clusterMap as $pool => $pidList){
		$solvedClusterMap[$pool] = array_values(array_intersect($pidList, $allSolvedPids));
		//printf("solved %d of %d in cluster %s\n", count($solvedClusterMap[$pool]), count($pidList), $pool);
		//printf("%s\n\n", implode(", ", $pidList));
	}
	return $solvedClusterMap;
}

function GetRemainingMysteries($allSolvedPids){
	$mysteryMap = GetMysteryMap();
	$remainingMysteries = [];
	foreach($mysteryMap as $pid => $mysteryId){
		if(!in_array($pid, $allSolvedPids)){
			$remainingMysteries[] = $mysteryId;
		}
	}
	return $remainingMysteries;
}

function GetSolvedMysteries($allSolvedPids){
	$mysteryMap = GetMysteryMap();
	$solvedMysteries = [];
	foreach($mysteryMap as $pid => $mysteryId){
		if(in_array($pid, $allSolvedPids)){
			$solvedMysteries[] = $mysteryId;
		}
	}
	return $solvedMysteries;
}

function GetCosmetics($rawJson){
	$cosmetics = [];
	$inventoryNode = RetrievePrimaryNode($rawJson, "Inventory");
	foreach($inventoryNode as $node){
		$item = $node->Struct->ObjectId_0->Str->value;
		$qty = $node->Struct->quantity_0->{"Int64"}->value;
		//$cosmetics[] = $item . " (" . $qty . ")";
		if($qty != 1){
			printf("%s\n", ColorStr("Cosmetic item \"" . $item . "\" has quantity " . $qty, 255, 128, 128));
			printf("%s\n", ColorStr("Please report this on the Discord server", 255, 128, 128));
			exit(1);
		}
		// Exclude deluxe edition unlocks from this list
		// Some people without the deluxe edition still have them in their Inventory_0, at random
		if(str_ends_with($item, "DX")){
			continue;
		}
		$cosmetics[] = $item;
	}
		
	// Throw in default and deluxe cosmetics just so that the numbers add up.
	// People randomly have or don't have them. Even people without deluxe sometimes have deluxe ones, sometimes not.
	$cosmeticsMap = GetCosmeticsMap();
	//printf("BEFORE: %d\n", count($cosmetics));
	foreach($cosmeticsMap as $name => $type){
		if(in_array($type, [ "deluxe", "unknown" ])){
			$cosmetics[] = $name;
		}
	}
	
	$cosmetics = array_values(array_unique($cosmetics));
	//printf("AFTER: %d\n", count($cosmetics));
	sort($cosmetics);
	return $cosmetics;
}

function GetHubRewardTiers($rawJson){
	$rewardTiers = [];
	$zoneCategories = GetZoneCategories();
	foreach($zoneCategories as $zoneIndex => $pcatList){
		$rewardTiers[$zoneIndex] = [];
		foreach($pcatList as $pcat){
			$rewardTiers[$zoneIndex][$pcat] = 0;
		}
	}

	$rewardsNode = RetrievePrimaryNode($rawJson, "RewardProgress");
	$regex = "/^([A-Z][a-z]*)([A-Z][a-zA-Z]*)SandboxProgression$/";
	foreach($rewardsNode as $node){
		$trackName = $node->Struct->ProgressId_0->Str->value;
		if(!preg_match($regex, $trackName, $matches) || count($matches) < 3){
			continue;
		}
		$zoneIndex = ZoneNameToInt($matches[1]);
		$pcat      = PuzzleCategoryInternalName($matches[2]);
		$trackTier = $node->Struct->RewardedLevel_0->{"Int"}->value;
		//printf("%-18s %-14s %d\n", ZoneToPrettyNoColor($zoneIndex), PuzzleCategoryPrettyName($pcat), $trackTier);
		if(!IsHubZone($zoneIndex) || empty($pcat) || $trackTier < 0){
			continue;
		}
		$rewardTiers[$zoneIndex][$pcat] = $trackTier;
		//printf("REWARD TIER: |%s| %d\n", $trackName, $trackTier);
		//printf("%s\n", $trackName);
	}
	return $rewardTiers;
}

function GetHubRewards($hubSolvedProfile){
	$reduced = ReduceProfileToCategories($hubSolvedProfile);
	$rewardsMap = LoadHubTrackRewards();
	$myRewards = [];
	
	foreach($rewardsMap as $zoneIndex => $pcatMap){
		$myRewards[$zoneIndex] = [];
		foreach($pcatMap as $pcat => $tierInfo){
			$myRewards[$zoneIndex][$pcat] = [];
			foreach($tierInfo as $pcount => $reward){
				//printf("%-18s %-18s %4d %s\n", ZoneToPrettyNoColor($zoneIndex), PuzzleCategoryPrettyName($pcat), $pcount, $reward);
				$myCount = count($reduced[$zoneIndex][$pcat]);
				$myRewards[$zoneIndex][$pcat][] = [ //(object)[
					"reward" => $reward,
					"isObtained" => (bool)($myCount >= $pcount),
					"offset" => ($pcount - $myCount),
				];
			}
		}
	}
	return $myRewards;
}

function GetSaveFileSettings($rawJson){
	$settings = [];
	$settingsNode = RetrievePrimaryNode($rawJson, "Settings");
	foreach($settingsNode as $node){
		$option = $node->Struct->OptionId_0->Str->value;
		$value  = $node->Struct->Value_0->Str->value;
		if($option == "unlocked_zones"){
			//$value = json_encode(json_decode($value)); // awful formatting
			$value = implode(",", array_map("intval", array_values((array)json_decode($value))));
		}
		//$option = str_pad($option, 23, " ", STR_PAD_RIGHT); // debug
		$settings[$option] = $value;
	}
	ksort($settings, SORT_NATURAL);
	return $settings;
}

function HasDeluxe(array $settings){
	$str = "Has Deluxe Edition";
	return (isset($settings[$str]) && $settings[$str] == 1);
}

function ResetAllSaveProgress(&$rawJson){	
	ResetSaveMirabilis($rawJson);
	//ResetSaveCosmeticsOfType($rawJson, "paidSpark");
	//ResetSaveCosmeticsOfType($rawJson, "deluxe");
	ResetSaveCosmeticsOfType($rawJson, "hubReward");
	ResetSaveCosmeticsOfType($rawJson, "masteryReward");
	ResetSaveCosmeticsOfType($rawJson, "campaignReward");

	ResetNonCriticalSaveSettings($rawJson);
	ResetSaveUnlockedZones($rawJson);
	
	unset($rawJson->root->properties->Achievements_0);
	unset($rawJson->root->properties->Upgrades_0);
	unset($rawJson->root->properties->Statuses_0);
	unset($rawJson->root->properties->Quests_0);
	unset($rawJson->root->properties->PuzzleStatuses_0);
	unset($rawJson->root->properties->RewardProgress_0);
	unset($rawJson->root->properties->RewardProgressArray_0);
	unset($rawJson->root->properties->Unlockables_0);
	unset($rawJson->root->properties);
}

//function ResetGenericSaveWallet(&$rawJson, string $currencyToReset){
//	$walletNode_ref = &RetrievePrimaryNode($rawJson, "Wallet");
//	foreach($walletNode_ref as $index => &$node_ref){
//		$currency = $node_ref->Struct->Currency_0->Str->value;
//		if(empty($currency) || $currency == $currencyToReset){
//			unset($walletNode_ref[$index]);
//		}
//		$walletNode_ref = array_values($walletNode_ref);
//	}unset($node_ref);
//	$walletNode_ref = array_values($walletNode_ref);
//	unset($walletNode_ref);
//}

function ResetSaveMirabilis(&$rawJson){
	//ResetGenericSaveWallet($rawJson, "blue-orbs");
	SetCurrency($rawJson, "blue-orbs", 0);
}

function ResetSaveSparks(&$rawJson){
	//ResetGenericSaveWallet($rawJson, "coins");
	SetCurrency($rawJson, "coins", 0);
}

function FixNegativeSparks(&$rawJson){
	$sparks = GetCurrency($rawJson, "coins");
	if($sparks < 0){
		printf("  %s\n", ColorStr("Detected negative sparks (" . $sparks . "), resetting to 0", 200, 200, 40));
		SetCurrency($rawJson, "coins", 0);
		return true;
	}
	return false;
}

function ResetSaveUnlockedZones(&$rawJson){
	$settingsNode_ref = &RetrievePrimaryNode($rawJson, "Settings");
	foreach($settingsNode_ref as $index => &$node_ref){
		$settingName = $node_ref->Struct->OptionId_0->Str->value;
		if($settingName == "unlocked_zones"){
			unset($settingsNode_ref[$index]);
		}
	}unset($node_ref);
	$settingsNode_ref = array_values($settingsNode_ref);
	unset($settingsNode_ref);
}

function ResetNonCriticalSaveSettings(&$rawJson){
	$settingsNode_ref = &RetrievePrimaryNode($rawJson, "Settings");
	static $settingsToKeep = [
		"Accepted Compendium",
		"Accepted EULA",
		"Accepted Privacy",
		"Crouch Toggle Mode",
		"HasDeluxeEdition",
		"Show Fps",
		"Show Ping",
		"Sprint Mode",
		"unlocked_zones",
	];
	foreach($settingsNode_ref as $index => &$node_ref){
		$settingName = $node_ref->Struct->OptionId_0->Str->value;
		if(!in_array($settingName, $settingsToKeep)){
			//printf("> Removing setting |%s|\n", $settingName);
			unset($settingsNode_ref[$index]);
		}
	}unset($node_ref);
	$settingsNode_ref = array_values($settingsNode_ref);
	unset($settingsNode_ref);
}

function ResetSpecificSaveSettings(&$rawJson, array $namesToRemove){
	$settingsNode_ref = &RetrievePrimaryNode($rawJson, "Settings");
	foreach($settingsNode_ref as $index => &$node_ref){
		$settingName = $node_ref->Struct->OptionId_0->Str->value;
		if(in_array($settingName, $namesToRemove)){
			printf("> Removing setting |%s|\n", $settingName);
			unset($settingsNode_ref[$index]);
		}
	}unset($node_ref);
	$settingsNode_ref = array_values($settingsNode_ref);
	unset($settingsNode_ref);
}

function ResetSaveCosmeticsOfType(&$rawJson, string $cosmeticTypeToReset){
	//ResetSaveCosmeticsOfType($rawJson, "paidSpark");
	//ResetSaveCosmeticsOfType($rawJson, "deluxe");
	//ResetSaveCosmeticsOfType($rawJson, "hubReward");
	//ResetSaveCosmeticsOfType($rawJson, "masteryReward");
	//ResetSaveCosmeticsOfType($rawJson, "campaignReward");
	$cosmeticsMap = GetCosmeticsMap();
	$cosmeticsNode_ref = &RetrievePrimaryNode($rawJson, "Inventory");
	foreach($cosmeticsNode_ref as $index => &$node_ref){
		$cosmeticName = $node_ref->Struct->ObjectId_0->Str->value;
		if(!isset($cosmeticsMap[$cosmeticName])){
			continue;
		}
		$cosmeticType = $cosmeticsMap[$cosmeticName];
		if($cosmeticTypeToReset == $cosmeticType){
			//printf("> Removed cosmetic |%s| (%s)\n", $cosmeticName, $cosmeticType);
			unset($cosmeticsNode_ref[$index]);
		}
		//if(in_array($settingName, $settingsToKeep)){
		//	unset($cosmeticsNode_ref[$index]);
		//}
	}unset($node_ref);
	$cosmeticsNode_ref = array_values($cosmeticsNode_ref);
	unset($cosmeticsNode_ref);
}

function ResetSaveOnlineFlorbs(&$rawJson){
	$allPuzzlesNode_ref = &RetrievePrimaryNode($rawJson, "PuzzleStatuses");
	
	$puzzleMap = GetPuzzleMap(true);
	$florbTiers = GetFlorbTiers();
	
	foreach($allPuzzlesNode_ref as $index => &$puzzleNode_ref){
		$pid      = (int)    $puzzleNode_ref->Struct->PuzzleId_0->{"Int"}->value;
		$isSolved = (bool)   $puzzleNode_ref->Struct->bSolved_0->{"Bool"}->value;
		//$score    = (int)    $puzzleNode->Struct->BestScore_0->{"Int"}->value;
		$ts       = (int)    $puzzleNode_ref->Struct->LastSolveTimestamp_0->{"Int"}->value;
		$pb       = (float)  $puzzleNode_ref->Struct->LeaderboardTime_0->{"Float"}->value;
		//$misc     = (string) $puzzleNode->Struct->MiscStatus_0->{"Str"}->value;
		
		if(!isset($puzzleMap[$pid])){
			continue;
		}
		$ptype = $puzzleMap[$pid]->ptype;
		
		if($ptype == "racingBallCourse" && $ts > 0){
			//printf("%s %d %d %.2f\n", $ptype, $pid, $ts, $puzzleNode_ref->Struct->LeaderboardTime_0->{"Float"}->value);
			$platTier = $florbTiers[$pid][3];
			if($pb < $platTier){
				$puzzleNode_ref->Struct->LeaderboardTime_0->{"Float"}->value = $platTier - 0.0001;
				$puzzleNode_ref->Struct->LastSolveTimestamp_0->{"Int"}->value = 0;
			}
		}
	}unset($puzzleNode_ref);
	$allPuzzlesNode_ref = array_values($allPuzzlesNode_ref);
	unset($allPuzzlesNode_ref);
}

function ResetSpecificSaveUpgrade(&$rawJson, string $upgradeNameToReset){
	$upgradesNode_ref = &RetrievePrimaryNode($rawJson, "Upgrades");
	$isReset = false;
	foreach($upgradesNode_ref as $index => &$node_ref){
		$upgradeName = $node_ref->Struct->UpgradeId_0->Str->value;
		$upgradeTier = $node_ref->Struct->UpgradeLevel_0->{"Int"}->value;
		$upgradeKey  = $upgradeName . "/" . $upgradeTier;
		if($upgradeName == $upgradeNameToReset){
			printf("  %s\n", ColorStr("Resetting upgrade " . $upgradeKey, 200, 200, 40));
			unset($upgradesNode_ref[$index]);
			$isReset = true;
			break;
		}
	}unset($node_ref);
	$upgradesNode_ref = array_values($upgradesNode_ref);
	unset($upgradesNode_ref);
	return $isReset;
}

function RefundSpecificSaveUpgrade(&$rawJson, string $upgradeNameToReset){
	$skillTree = LoadCsvMap("media/data/skillTree.csv", "key");
	$upgradesNode_ref = &RetrievePrimaryNode($rawJson, "Upgrades");
	$refundedCost = 0;
	$sparksBefore = GetCurrency($rawJson, "coins");
	foreach($upgradesNode_ref as $index => &$node_ref){
		$upgradeName = $node_ref->Struct->UpgradeId_0->Str->value;
		$upgradeTier = $node_ref->Struct->UpgradeLevel_0->{"Int"}->value;
		$upgradeKey  = $upgradeName . "/" . $upgradeTier;
		if($upgradeName == $upgradeNameToReset){
			$refundedCost += $skillTree[$upgradeKey]["totalCost"];
			$sparksAfter  = $sparksBefore + $totalCost;
			SetCurrency($rawJson, "coins", $sparksAfter);
			//printf("  %s\n", ColorStr("Refunding upgrade " . $upgradeKey . " for " . $totalCost . " sparks", 200, 200, 40));
			printf("  %s\n", ColorStr(sprintf("Refunding upgrade %s for %d sparks (%d -> %d)", $upgradeKey, $totalCost, $sparksBefore, $sparksAfter), 200, 200, 40));
			unset($upgradesNode_ref[$index]);
			$refundedCost += $totalCost;
			break;
		}
	}unset($node_ref);
	$upgradesNode_ref = array_values($upgradesNode_ref);
	unset($upgradesNode_ref);
	return $refundedCost;
}

function RefundAllSaveUpgrades(&$rawJson){
	$skillTree = LoadCsvMap("media/data/skillTree.csv", "key");
	$upgradesNode_ref = &RetrievePrimaryNode($rawJson, "Upgrades");
	$refundedCost = 0;
	$sparksBefore = GetCurrency($rawJson, "coins");
	foreach($upgradesNode_ref as $index => &$node_ref){
		$upgradeName = $node_ref->Struct->UpgradeId_0->Str->value;
		$upgradeTier = $node_ref->Struct->UpgradeLevel_0->{"Int"}->value;
		$upgradeKey  = $upgradeName . "/" . $upgradeTier;
		if($upgradeName == "SKILL_GLIDING"){
			// Gliding is a hidden upgrade that cannot be re-acquired via upgrade UI.
			// It is awarded for completing Empyrian Journey.
			// Use RefundSpecificSaveUpgrade function if you need to reset this also.
			continue;
		}
		$refundedCost += $skillTree[$upgradeKey]["totalCost"];
		unset($upgradesNode_ref[$index]);
	}unset($node_ref);
	$sparksAfter = $sparksBefore + $refundedCost;
	$upgradesNode_ref = array_values($upgradesNode_ref);
	unset($upgradesNode_ref);
	SetCurrency($rawJson, "coins", $sparksAfter);
	printf("  %s\n", ColorStr(sprintf("Refunded all upgrades for %d sparks (%d -> %d)", $refundedCost, $sparksBefore, $sparksAfter), 200, 200, 40));
	return $refundedCost;
}

function ResetSpecificSavePids(&$rawJson, array $pidsToReset){
	$puzzleMap = GetPuzzleMap(true);
	$allPuzzlesNode_ref = &RetrievePrimaryNode($rawJson, "PuzzleStatuses");
	$resetCount = 0;
	foreach($allPuzzlesNode_ref as $index => &$puzzleNode_ref){
		$pid = (int)$puzzleNode_ref->Struct->PuzzleId_0->{"Int"}->value;
		if(in_array($pid, $pidsToReset)){
			if(isset($puzzleMap[$pid])){
				$data = $puzzleMap[$pid];
				//$doHardReset = !(in_array($data->ptype, [ "racingBallCourse", "racingRingCourse" ]) || $pid == GetSkydropChallengePid());
				$doHardReset = (in_array($data->ptype, [ "dungeon", "obelisk" ]));
				if($doHardReset){
					// Hard reset: wipe out all associated puzzle data. This is important for dungeons and monoliths especially.
					unset($allPuzzlesNode_ref[$index]);
					++$resetCount;
				}else{
					$value_ref = &$allPuzzlesNode_ref[$index]->Struct->{"bSolved_0"}->{"Bool"}->value;
					if($value_ref == true){
						// Soft reset: set the puzzle solve status to false. Leave misc data and leaderboard times intact.
						$value_ref = false;
						++$resetCount;
					}
					unset($value_ref);
				}
			}else{
				printf("  %s\n", ColorStr("[WARNING] Unknown pid " . $pid, 255, 128, 128));
				unset($allPuzzlesNode_ref[$index]);
				++$resetCount;
			}
		}
		//if(!isset($puzzleMap[$pid])){
		//	printf("Unknown pid %d\n", $pid);
		//	//printf("%s\n", json_encode($puzzleNode_ref, 0xc0));
		//}
		//$data = $puzzleMap[$pid];
		//if(in_array($data->ptype, [ "dungeon", "monolithFragment" ])){
		//	printf("%5d %s\n", $pid, $data->ptype);
		//}
	}unset($puzzleNode_ref);
	$allPuzzlesNode_ref = array_values($allPuzzlesNode_ref);
	//unset($mainNode_ref->RewardProgress_0);
	unset($allPuzzlesNode_ref);
	return $resetCount;
}

function ResetSaveHubProgress(&$rawJson){
	// INTERNAL USE ONLY, not recommended to try!
	$resetCount = ResetSpecificSavePids($rawJson, GetAllHubPids());
	ResetSaveCosmeticsOfType($rawJson, "hubReward");
	unset($rawJson->root->properties->RewardProgress_0);
	// todo: reset echoes of time.
	// also mirabilis won't decrease etc.
	return $resetCount;
}

function FixMissingSilentWonders(&$rawJson){
	static $silentWonderMap = [
		 72 => "Silent Wonder 1",
		144 => "Silent Wonder 2",
		216 => "Silent Wonder 3",
		288 => "Silent Wonder 4",
		360 => "Silent Wonder 5",
		432 => "Silent Wonder 6",
		504 => "Silent Wonder 7",
		576 => "Silent Wonder 8",
		648 => "Silent Wonder 9",
		720 => "Silent Wonder 10",
	];
	
	// Find out which silent wonders were clicked on.
	$clickedMasteryRewards = &RetrieveClickedMasteryRewards($rawJson);
	$clickedSilentWonders = [];
	foreach($clickedMasteryRewards as $intValue){
		if(isset($silentWonderMap[$intValue])){
			$clickedSilentWonders[] = $silentWonderMap[$intValue];
		}
	}
	sort($clickedSilentWonders);
	
	// Find out which silent wonders are actually unlocked.
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$unlockedSilentWonders = array_filter($encyclopedia_ref, function($x){ return preg_match("/^Silent Wonder (\d+)$/", $x); });
	sort($unlockedSilentWonders);

	// Fix the difference, if any.
	$discrepancyArray = array_diff($clickedSilentWonders, $unlockedSilentWonders);	
	foreach($discrepancyArray as $missingSilentWonderName){
		printf("  %s\n", ColorStr("Adding missing lore fragment: " . $missingSilentWonderName, 200, 200, 40));
		$encyclopedia_ref[] = $missingSilentWonderName;
	}
	return $discrepancyArray;
}

function FixMissingEchoesOfTime(&$rawJson){
	// Load the lore map - it's a bit finicky.
	$loreDetails = LoadCsvMap("media/data/lore_hubs.csv", "encyID");
	$loreMap = [];
	foreach($loreDetails as $entry){
		$zoneIndex = ZoneNameToInt($entry["zone"]);
		$category = PuzzleCategoryInternalName($entry["category"]);
		$encyID = $entry["encyID"];
		$key = $zoneIndex . "_" . $category;
		$loreMap[$key] = $encyID;
	}
	
	// Find out which echoes of time were clicked on based on hub rewards.
	$clickedEchoesOfTime = [];
	$claimedTiersMap = GetHubRewardTiers($rawJson);
	$hubRewardsMap = LoadHubTrackRewards();
	foreach($claimedTiersMap as $zoneIndex => $infoArr){
		foreach($infoArr as $puzzleCategory => $lastClaimedTier){
			$hasClaimedLore = false;
			$justTheRewards = array_values($hubRewardsMap[$zoneIndex][$puzzleCategory]);
			for($i = 0; $i < $lastClaimedTier; ++$i){
				if($justTheRewards[$i] == "EchoOfTime"){
					$hasClaimedLore = true;
					break;
				}
			}
			//printf("%-20s %-15s %d %s\n", ZoneToPrettyNoColor($zoneIndex), $puzzleCategory, $lastClaimedTier, ($hasClaimedLore ? "ECHO" : "no echo"));
			if(!$hasClaimedLore){
				// Echo of time available but unclaimed - skip further checks.
				continue;
			}
			$key = $zoneIndex . "_" . $puzzleCategory;
			$encyID = $loreMap[$key];
			$clickedEchoesOfTime[] = $encyID;
		}
	}
	sort($clickedEchoesOfTime);
	
	// Find out which echoes of time are actually unlocked.
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$unlockedEchoesOfTime = array_filter($encyclopedia_ref, function($x){ return preg_match("/^lore\d_\d$/", $x); });
	sort($unlockedEchoesOfTime);

	// Fix the difference, if any.
	$discrepancyArray = array_diff($clickedEchoesOfTime, $unlockedEchoesOfTime);	
	foreach($discrepancyArray as $missingEchoOfTime){
		$displayName = $loreDetails[$missingEchoOfTime]["fullName"];
		printf("  %s\n", ColorStr("Adding missing Echo of Time: " . $displayName, 200, 200, 40));
		$encyclopedia_ref[] = $missingEchoOfTime;
	}
	return $discrepancyArray;
}

function FixMissingInsights(&$rawJson){
	// Which insights should the player have by now?
	$solvedPidList = GetAllSolvedPids($rawJson);
	$pidToEncycId = LoadCsvMap("media/data/krakenIdToEncycId.csv", "pid");
	$eligibleInsights = [];
	foreach($pidToEncycId as $pid => $data){
		if(in_array($pid, $solvedPidList)){
			$eligibleInsights[] = $data["encycId"];
		}
	}
	$eligibleInsights = array_values(array_unique($eligibleInsights));
	$eligibleInsights = array_diff($eligibleInsights, [
		"Phantoms", // deprecated insight, most likely a forgotten duplicate of Area Reach
		"SnakeBottleneck", // unknown insight but you can assume a lot from its name
	]);
	sort($eligibleInsights);
	//printf("%s\n", json_encode($eligibleInsights, 0xc0));
	
	// Are any of them missing from the list of insights?
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$discrepancyArray = array_diff($eligibleInsights, $encyclopedia_ref);
	foreach($discrepancyArray as $insightName){
		printf("  %s\n", ColorStr("Adding missing insight: " . $insightName, 200, 200, 40));
		$encyclopedia_ref[] = $insightName;
	}
	return $discrepancyArray;
}

function FixMissingGridRules(&$rawJson){
	// Which grid rules have been used in grids that the player has already solved?
	// First, collect all rules by scanning all puzzles and accumulating a rule bitmask.
	$gridRuleBitmasks = LoadCsvMap("media/data/gridRuleBitmasks.csv", "pid");
	$solvedPidList = GetAllSolvedPids($rawJson);
	$accumulatedBitmask = 0;
	foreach($solvedPidList as $pid){
		if(!isset($gridRuleBitmasks[$pid])){
			continue;
		}
		$accumulatedBitmask |= $gridRuleBitmasks[$pid]["bitmask"];
	}
	
	// Decipher the rule bitmask.
	static $encycRuleReverseList = [
		0  => "Rule_Alephs",
		1  => "Rule_AllLightIslandsAreCongruent",
		2  => "Rule_AllLightIslandsHaveOneSize",
		3  => "Rule_AvoidThisPattern",
		4  => "Rule_CaveNumbers",
		5  => "Rule_CompleteOnlyWhatYouCan",
		6  => "Rule_ConnectAllLightCells",
		7  => "Rule_DominionSymbols",
		8  => "Rule_IslandNumbers",
		9  => "Rule_KnappDanaben",
		10 => "Rule_MirrorSymmetry",
		11 => "Rule_Myopia",
		12 => "Rule_NoTwoLightIslandsAreCongruent",
		13 => "Rule_OneSymbolPerLightIsland",
		14 => "Rule_SearchTheEnvironment",
		15 => "Rule_Yajilin",
	];
	$eligibleRules = [];
	for($i = 0; $i < count($encycRuleReverseList); ++$i){
		$bit = ($accumulatedBitmask >> $i) & 0x1;
		if($bit){
			$eligibleRules[] = $encycRuleReverseList[$i];
		}
	}
	
	// Do we have any missing rules in the encyclopedia?
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$discrepancyArray = array_diff($eligibleRules, $encyclopedia_ref);
	foreach($discrepancyArray as $ruleName){
		printf("  %s\n", ColorStr("Adding missing grid rule: " . $ruleName, 200, 200, 40));
		$encyclopedia_ref[] = $ruleName;
	}
	return $discrepancyArray;
}

function FixMissingEncycPuzzleTypes(&$rawJson){
	// Find out which puzzle types have been solved at least once.
	$solvedPidList = GetAllSolvedPids($rawJson);
	$puzzleMap = GetPuzzleMap(true);
	$solvedPtypes = array_map(function($x) use($puzzleMap){ return (isset($puzzleMap[$x]) ? $puzzleMap[$x]->ptype : "unknown"); }, $solvedPidList);
	// Skydrops are special.
	$nonPuzzleSolves_ref = &RetrieveUnlockableCategory($rawJson, "fileBasedPuzzleSolutionHack");
	$rosaries = array_filter($nonPuzzleSolves_ref, function($x){ return preg_match("/BP_(Manual|Golden)?Rosary_C /", $x); });
	if(count($rosaries) > 0){
		$solvedPtypes[] = "rosary";
	}
	$solvedPtypes = array_values(array_unique($solvedPtypes));
	static $ptypeToEncycPuzzleName = [
		"lockpick"           => "Puzzle-Clockpick",
		"ghostObject"        => "Puzzle-EtherealObject",
		"fractalMatch"       => "Puzzle-FractalMatch",
		"gyroRing"           => "Puzzle-GyroPuzzle",
		"hiddenArchway"      => "Puzzle-HiddenArch",
		"hiddenCube"         => "Puzzle-HiddenCube",
		"hiddenRing"         => "Puzzle-HiddenRing",
		"klotski"            => "Puzzle-Klotski",
		"logicGrid"          => "Puzzle-LogicGrid",
		"match3"             => "Puzzle-Match3",
		"matchbox"           => "Puzzle-Matchbox",
		"memoryGrid"         => "Puzzle-MemoryGrid",
		"mirrorMaze"         => "Puzzle-MirrorMaze",
		"musicGrid"          => "Puzzle-MusicGrid",
		"completeThePattern" => "Puzzle-PatternFind",
		"lightPattern"       => "Puzzle-ProjectionCone",
		"racingBallCourse"   => "Puzzle-RacingBalls",
		"racingRingCourse"   => "Puzzle-RacingRings",
		"rollingCube"        => "Puzzle-RollingCube",
		"rosary"             => "Puzzle-Rosary",
		"ryoanji"            => "Puzzle-Ryoanji",
		"seek5"              => "Puzzle-Seek5",
		"viewfinder"         => "Puzzle-Viewfinder",
		"followTheShiny"     => "Puzzle-WanderingSpirit",
	];
	$eligibleEncycPtypes = [];
	foreach($solvedPtypes as $ptype){
		if(isset($ptypeToEncycPuzzleName[$ptype])){
			$eligibleEncycPtypes[] = $ptypeToEncycPuzzleName[$ptype];
		}
	}
	sort($eligibleEncycPtypes);
	
	// Do we have any missing puzzle types in the encyclopedia?
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$discrepancyArray = array_diff($eligibleEncycPtypes, $encyclopedia_ref);
	foreach($discrepancyArray as $ruleName){
		printf("  %s\n", ColorStr("Adding missing puzzle type: " . $ruleName, 200, 200, 40));
		$encyclopedia_ref[] = $ruleName;
	}
	return $discrepancyArray;
}

function FixMissingMysteryPickups(&$rawJson){
	$pidList = array_values(array_merge(GetAllSolvedPids($rawJson), GetUnlocks($rawJson)));
	$solvedMysteries = array_values(GetSolvedMysteries($pidList));
	//printf("%s\n", implode(",", $solvedMysteries));
	// Do we actually need to restore these?
}

function FixExcessMirabilis(&$rawJson){
	$maxMirabilis = GetMaxMirabilisCount();
	$mirabilisCount = GetCurrency($rawJson, "blue-orbs");
	if($mirabilisCount > $maxMirabilis){
		printf("  %s\n", ColorStr("Detected " . $mirabilisCount . " mirabilis, truncating to " . $maxMirabilis . ".", 200, 200, 40));
		SetCurrency($rawJson, "blue-orbs", $maxMirabilis);
		return $maxMirabilis;
	}
	return -1;
}

function ResetSaveCampaignProgress(&$rawJson){
	$settingsToRemove = [
		"unlocked_zones", // most importantly
		"TipTypeSeen-0",
		"TipTypeSeen-1",
		"TipTypeSeen-3",
		"TipTypeSeen-4",
		"TipTypeSeen-5",
		"TipTypeSeen-6",
		"TipTypeSeen-8",
		"TipTypeSeen-9",
		"TipTypeSeen-10",
		"TipTypeSeen-11",
		"TipTypeSeen-12",
		"TipTypeSeen-13",
		"TipTypeSeen-15",
		"TipTypeSeen-16",
		"TipTypeSeen-17",
		"TipTypeSeen-18",
		"TipTypeSeen-19",
		"TipTypeSeen-20",
		"TipTypeSeen-21",
	];
	ResetSpecificSaveSettings($rawJson, $settingsToRemove);
	
	//ResetSaveCosmeticsOfType($rawJson, "campaignReward");
	
	// We can't just reset static puzzles - SaveStats omits tracking cutscenes, tips, other internal stuff as static puzzles.
	// Instead we reset everything EXCEPT hub and cluster puzzles.
	$dontResetThese = array_values(array_merge(GetAllHubPids(), GetAllClusterPids(), GetTempleArmillaries()));
	$solvedPidList = GetAllSolvedPids($rawJson);
	$resetThese = array_diff($solvedPidList, $dontResetThese);
	ResetSpecificSavePids($rawJson, $resetThese);
	
	// This deletes player position and rotation, prompting to default-respawn at the intro island.
	$playerPos_ref = &RetrievePrimaryNode($rawJson, "Statuses");
	$playerPos_ref = [];
	
	// This deletes daily, wanderer's, and most importantly campaign progress trackings.
	$quests_ref = &RetrievePrimaryNode($rawJson, "Quests");
	$quests_ref = [];
	
	// Remove ALL insights, lore fragments, discovered puzzle types etc - except for Silent Wonders (tied to mastery) and Echoes of Time (tied to hub progress).
	$encyclopedia_ref = &RetrieveUnlockableCategory($rawJson, "encyclopedia");
	$encyclopedia_ref = array_values(array_filter($encyclopedia_ref,	function($x) { return (preg_match("/lore\d_\d(?:_seen)?/i", $x) || preg_match("/Silent Wonder \d+(?:_seen)?/i", $x)); }));
	
	// Remove all "non-puzzle puzzles" solves. This mainly includes static skydrops and 4 mysteries (non-grid ones).
	$nonPuzzleSolves_ref = &RetrieveUnlockableCategory($rawJson, "fileBasedPuzzleSolutionHack");
	$nonPuzzleSolves_ref = [];

	// Figure out how many mirabilis were obtained (and claimed!) from the hub progress specifically.
	$claimedMirabilisCount = 0;
	$hubRewardsMap = LoadHubTrackRewards();
	$claimedTiersMap = GetHubRewardTiers($rawJson);
	foreach($claimedTiersMap as $zoneIndex => $infoArr){
		foreach($infoArr as $puzzleCategory => $lastClaimedTier){
			$justTheRewards = array_values($hubRewardsMap[$zoneIndex][$puzzleCategory]);
			for($i = 0; $i < $lastClaimedTier; ++$i){
				if($justTheRewards[$i] == "Mirabilis"){
					++$claimedMirabilisCount;
				}
			}
		}
	}
	
	printf("You claimed %d mirabilis from hubs.\n", $claimedMirabilisCount);
	SetCurrency($rawJson, "blue-orbs", $claimedMirabilisCount);
}

function ResetSaveDailyQuests($rawJson){
	$questsNode_ref = &RetrievePrimaryNode($rawJson, "Quests");
	$resetCount = 0;
	foreach($questsNode_ref as $index => &$node_ref){
		$questName = $node_ref->Struct->QuestID_0->Str->value;
		if(!str_starts_with($questName, "Daily") && !str_starts_with($questName, "Auto")){
			continue;
		}
		$questStatusString = $node_ref->Struct->QuestStatus_0->Str->value;
		unset($questsNode_ref[$index]);
		++$resetCount;
	}unset($node_ref);
	$questsNode_ref = array_values($questsNode_ref);
	unset($questsNode_ref);
	
	printf("  %s\n", ColorStr("Reset all " . $resetCount . " daily and/or wanderer's quests", 200, 200, 40));
	return $resetCount;
}

function ResetSaveTempleArmillaries($rawJson){
	$templeArmillaryPids = GetTempleArmillaries();
	$resetCount = ResetSpecificSavePids($rawJson, $templeArmillaryPids);
	printf("  %s\n", ColorStr("Reset " . $resetCount . " temple armillaries", 200, 200, 40));
	return $resetCount;
}

function ResetSaveVanillaClusters($rawJson){
	$clusterMap = GetConsolidatedClusterMap();
	$pidsToRest = array_values(array_merge($clusterMap["Lucent"], $clusterMap["Cong."], $clusterMap["Myopia"]));
	$resetCount = ResetSpecificSavePids($rawJson, $pidsToRest);
	printf("  %s\n", ColorStr("Reset " . $resetCount . " vanilla cluster pids", 200, 200, 40));
	return $resetCount;
}

function ResetSaveLostgridsCluster($rawJson){
	$clusterMap = GetConsolidatedClusterMap();
	$pidsToRest = $clusterMap["LostGrids"];
	$resetCount = ResetSpecificSavePids($rawJson, $pidsToRest);
	printf("  %s\n", ColorStr("Reset " . $resetCount . " LostGrtids cluster pids", 200, 200, 40));
	return $resetCount;
}

function FixUnsolvedMonoliths(&$rawJson){
	// First, retrieve which quests are solved. Monolith quests, specifically.
	$quests_ref = &RetrievePrimaryNode($rawJson, "Quests");
	$solvedMonolithQuests = [];
	foreach($quests_ref as &$node_ref){
		$questName = $node_ref->Struct->QuestID_0->Str->value;
		if(!preg_match("/(.*) Orb Fragments$/", $questName, $matches)){
			continue;
		}
		$zoneIndex = ZoneNameToInt($matches[1]);
		$status_ref = &$node_ref->Struct->QuestStatus_0->Str->value;
		$solvedMonolithQuests[$zoneIndex] = &$status_ref;
		unset($status_ref);
		//printf(json_encode($node_ref)); exit(1);
	}unset($node_ref);
	ksort($solvedMonolithQuests);
	//var_dump($solvedMonolithQuests);
	
	// Retrieve misc data about all solved puzzles.
	$miscMap = [];
	$puzzleList = ParseAllPuzzles($rawJson, $miscMap);
	
	// Iterate over monoliths, see what's up.
	$fixCount = 0;
	$monolithMap = GetMonolithMap();
	foreach($monolithMap as $zoneIndex => $pid){
		if(!isset($miscMap[$pid])){
			// No data associated with this monolith - likely no fragments found yet.
			continue;
		}
		printf("%-14s monolith (pid %5d) state: %s\n", ZoneToPrettyNoColor($zoneIndex), $pid, json_encode($miscMap[$pid]));
		$miniJson = json_decode($miscMap[$pid]);
		$areAllFound = true;
		foreach($miniJson->Found as $bobo){
			$areAllFound &= $bobo;
		}
		if(!$areAllFound){
			// Some, but not all, fragments have been found. Skip this.
			continue;
		}
		// We can confirm that this monolith is fully solved.
		//printf("%-14s monolith (pid %5d) is fully solved.\n", ZoneToPrettyNoColor($zoneIndex), $pid);
		
		static $properSolvedQuest = "{\r\n\t\"ObjData\": [\r\n\t\t{\r\n\t\t\t\"QuestObjectiveState\": true\r\n\t\t}\r\n\t],\r\n\t\"QuestState\": 4\r\n}";
		if(isset($solvedMonolithQuests[$zoneIndex])){
			if($solvedMonolithQuests[$zoneIndex] != $properSolvedQuest){
				printf("  %s\n", ColorStr("Repairing " . ZoneToPrettyNoColor($zoneIndex) . " monolith data", 200, 200, 40));
				++$fixCount;
				$solvedMonolithQuests[$zoneIndex] = $properSolvedQuest;
			}
		}else{
			printf("  %s\n", ColorStr("Adding missing " . ZoneToPrettyNoColor($zoneIndex) . " monolith data", 200, 200, 40));
			static $zoneIndexToQuestName = [
				2 => "Rainforest Orb Fragments",
				3 => "Central Orb Fragments",
				4 => "Riverland Orb Fragments",
				5 => "Redwood Orb Fragments",
				6 => "Mountain Orb Fragments",
			];
			$str = 
				'{"Struct":{"QuestID_0":{"Str":{"value":"' .
				$zoneIndexToQuestName[$zoneIndex] .
				'"}},"QuestStatus_0":{"Str":{"value":"' .
				//$properSolvedQuest .
				"none" .
				'"}},"bOverride_QuestStatus_0":{"Bool":{"value":false}}}}' ;
			$fullQuestNode = json_decode($str);
			$fullQuestNode->Struct->QuestStatus_0->Str->value = $properSolvedQuest;
			$quests_ref[] = $fullQuestNode;
			++$fixCount;
			//var_dump($str);
			//var_dump($fullQuestNode);
		}
		
		// But does the related quest entry agree?
		//var_dump($miniJson->Found);
		//$t = array_count_values($miniJson->Found);
		//var_dump($t);
		//$tester = array_values(array_unique($miniJson->Found));
		//var_dump($tester, $areAllFound);
	}
	
	unset($quests_ref);
	return $fixCount;
}

function ResetNonPlatGeneric(&$rawJson, array $medalMap){
	if(count($medalMap) != 4){
		printf("[ERROR] Medal map is malformed!\n");
		return false;
	}
	$pidsToReset = array_merge(
		$medalMap[0],
		$medalMap[1],
		$medalMap[2],
	);
	//printf("%s\n", json_encode($medalMap, 0xc0));
	return ResetSpecificSavePids($rawJson, $pidsToReset);
}

function ResetNonPlatFlorbs(&$rawJson){
	$miscMap = [];
	$puzzleCsvData = ParseAllPuzzles($rawJson, $miscMap);
	$florbPbs = GetFlorbPbs($puzzleCsvData);
	$florbMedalMap = GetFlorbMedalMap($florbPbs);
	$resetCount = ResetNonPlatGeneric($rawJson, $florbMedalMap);
	if($resetCount > 0){
		printf("  %s\n", ColorStr("Reset " . $resetCount . " non-platinum florbs", 200, 200, 40));
	}
	return $resetCount;
}

function ResetNonPlatGlides(&$rawJson){
	$miscMap = [];
	$puzzleCsvData = ParseAllPuzzles($rawJson, $miscMap);
	$glidePbs = GetGlidePbs($puzzleCsvData);
	$glideMedalMap = GetGlideMedalMap($glidePbs);
	$resetCount = ResetNonPlatGeneric($rawJson, $glideMedalMap);
	if($resetCount > 0){
		printf("  %s\n", ColorStr("Reset " . $resetCount . " non-platinum glides", 200, 200, 40));
	}
	return $resetCount;
}


