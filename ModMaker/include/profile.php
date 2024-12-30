<?php

include_once("include\\stringex.php");

define("TOTAL_HUB_PUZZLES", 16203);

//function GetBadSentinels(){
//	static $badSentinels = null;
//	if($badSentinels === null){
//		$path = "media\\data\\badSentinels.txt";
//		if(!is_file($path)){
//			printf("%s\n", ColorStr("File " . $path . " not found - assuming there are no longer bad sentinels", 160, 160, 160));
//			$badSentinels = [];
//		}else{
//			$badSentinels = array_values(array_map("intval", file($path)));
//		}
//	}
//	return $badSentinels;
//}

function GetHubProfile(){
	static $allHubProfile = null;
	if($allHubProfile === null){
		$profileHubsPath = "media\\data\\profileHub.json";
		if(!is_file($profileHubsPath)){
			printf("%s\n", ColorStr("Missing file " . $profileHubsPath, 255, 128, 128));
			exit(1);
		}
		$raw = file_get_contents($profileHubsPath);
		if(empty($raw)){
			printf("%s\n", ColorStr("Failed to read file " . $profileHubsPath, 255, 128, 128));
			exit(1);
		}
		$allHubProfile = [];
		$json = json_decode($raw);
		if(empty($json)){
			printf("%s\n", ColorStr("Failed to decode file " . $profileHubsPath, 255, 128, 128));
			exit(1);
		}
		$testCount = 0;
		foreach($json as $zoneIndex => $info){
			$zoneIndex = (int)$zoneIndex;
			$allHubProfile[$zoneIndex] = [];
			foreach($info as $actualPtype => $pidString){
				$pidList = array_map("intval", explode(",", $pidString));
				sort($pidList);
				$allHubProfile[$zoneIndex][$actualPtype] = $pidList;
				$testCount += count($allHubProfile[$zoneIndex][$actualPtype]);
			}
		}
		
		// Temporary: manual fix for unknown sentinel stones.
		//$badSentinels = GetBadSentinels();
		//$badSentinelIndex = 0;
		//static $expectedSentinelCounts = [ 2 => 52, 4 => 94, 5 => 100, 6 => 109 ];
		//foreach($allHubProfile as $zoneIndex => &$info_ref){
		//	$zoneIndex = (int)$zoneIndex;
		//	if(!isset($info_ref["ryoanji"])){
		//		continue;
		//	}
		//	while(count($info_ref["ryoanji"]) < $expectedSentinelCounts[$zoneIndex]){
		//		$info_ref["ryoanji"][] = $badSentinels[$badSentinelIndex++];
		//		++$testCount;
		//	}
		//	//printf("%d: %3d total - %s\n", $zoneIndex, count($info_ref["ryoanji"]), $info_ref["ryoanji"]);
		//}
		
		if($testCount != TOTAL_HUB_PUZZLES){
			printf("%s\n", ColorStr("File " . $profileHubsPath . " appears to be malformed", 255, 128, 128));
			printf("%s\n", ColorStr("Expected " . TOTAL_HUB_PUZZLES . " total hub puzzles, got: " . $testCount, 255, 128, 128));
			exit(1);
		}
	}
	return $allHubProfile;
}

function GetAllHubPids(){
	static $allHubPids = null;
	if($allHubPids === null){
		$allHubPids = ExtractAllPids(GetHubProfile());
	}
	return $allHubPids;
}

function InitializeProfile(){
	$hubProfile = GetHubProfile();
	$profile = [];
	foreach($hubProfile as $zoneIndex => $info){
		$zoneIndex = (int)$zoneIndex;
		$profile[$zoneIndex] = [];
		foreach($info as $actualPtype => $ignored){
			$profile[$zoneIndex][$actualPtype] = [];
		}
	}
	return $profile;
}

function ExportProfile(array $profile, string $outputPath){
	$json = [];
	foreach($profile as $zoneIndex => $info){
		$json[$zoneIndex] = [];
		foreach($info as $actualPtype => $pidList){
			$json[$zoneIndex][$actualPtype] = implode(",", $pidList);
		}
	}
	file_put_contents($outputPath, json_encode($json, JSON_PRETTY_PRINT));
	printf("Wrote \"%s\"\n", $outputPath);
}

function ExportProfileCsv(array $profile, string $outputPath){
	$csv = [];
	foreach($profile as $zoneIndex => $info){
		foreach($info as $actualPtype => $pidList){
			foreach($pidList as $pid){
				$entry = [
					"pid" => $pid,
					"zone" => ZoneToPrettyNoColor($zoneIndex),
					"ptype" => PuzzlePrettyName($actualPtype),
				];
				$csv[] = $entry;
			}
		}
	}
	$final = FormCsv($csv);
	file_put_contents($outputPath, $final);
	//printf("Wrote \"%s\"\n", $outputPath);
}

function ExtractAllPids(array $profile){
	$extractedPids = [];
	foreach($profile as $zoneIndex => $info){
		foreach($info as $actualPtype => $pidList){
			$extractedPids = array_values(array_merge($extractedPids, $pidList));
		}
	}
	sort($extractedPids);
	return $extractedPids;
}

function ProfileTotalSolved(array $profile){
	return count(ExtractAllPids($profile));
}

function BuildProfileFromArray(array $input){
	$profile = InitializeProfile();
	$hubProfile = GetHubProfile();
	foreach($hubProfile as $zoneIndex => $info){
		foreach($info as $actualPtype => $allowedPidList){
			$profile[$zoneIndex][$actualPtype] = array_values(array_intersect($allowedPidList, $input));
			// don't need to sort if the allowedPidList is pre-sorted
		}
	}
	return $profile;
}

function BuildProfileFromCsv(string $csvPath){
	$csv = LoadCsv($csvPath);
	$pids = array_map("intval", array_column($csv, "pid"));
	return BuildProfileFromArray($pids);
}

function BuildProfileFromJson(string $jsonPath){
	$pids = [];
	if(!is_file($jsonPath)){
		printf("File not found: %s\n", $jsonPath);
		return null;
	}
	$raw = file_get_contents($jsonPath);
	if(empty($raw)){
		printf("Could not read %s\n", $jsonPath);
		return null;
	}
	$json = json_decode($raw);
	if(empty($json)){
		printf("Could not decode %s\n", $jsonPath);
		return null;
	}
	foreach($json as $zoneIndex => $info){
		foreach($info as $actualPtype => $value){
			if(is_string($value)){
				$value = explode(",", $value);
			}
			$value = array_map("intval", $value);
			$pids = array_values(array_merge($pids, $value));
		}
	}
	return BuildProfileFromArray($pids);
}


function BuildProfile(mixed $input){
	if(is_array($input)){
		return BuildProfileFromArray($input);
	}
	if(is_string($input)){
		//printf("%s\n", $input);
		if(preg_match("#\.csv$#", $input)){
			//printf("Matched csv!\n");
			return BuildProfileFromCsv($input);
		}
		if(preg_match("#\.json$#", $input)){
			return BuildProfileFromJson($input);
		}
	}
	return null;
}

function PrintProfilePretty(array $profile){
	foreach($profile as $zoneIndex => $info){
		printf("\n");
		foreach($info as $actualPtype => $pidList){
			printf("%-38s %-18s %s\n", ZoneToPretty($zoneIndex), PuzzlePrettyName($actualPtype), count($pidList));
		}
	}
	printf("\n");
}

function SubtractProfiles(array $a, array $b){
	$profile = InitializeProfile();
	$hubProfile = GetHubProfile();
	foreach($hubProfile as $zoneIndex => $info){
		foreach($info as $actualPtype => $ignore){
			$profile[$zoneIndex][$actualPtype] = array_diff($a[$zoneIndex][$actualPtype], $b[$zoneIndex][$actualPtype]);
		}
	}
	return $profile;
}

function MergeMaxProfiles(array $a, array $b){
	$profile = InitializeProfile();
	$hubProfile = GetHubProfile();
	foreach($hubProfile as $zoneIndex => $info){
		foreach($info as $actualPtype => $ignore){
			$mergedAll = array_merge($a[$zoneIndex][$actualPtype], $b[$zoneIndex][$actualPtype]);
			$profile[$zoneIndex][$actualPtype] = array_values(array_unique($mergedAll));
			sort($profile[$zoneIndex][$actualPtype]);
		}
	}
	return $profile;
}

function PrintProfileComparison(array $profileMap){
	$hubProfile = GetHubProfile();
	
	static $padColorExtra = 23;
	static $padZone       = 15;
	static $padPtype      = 18;
	static $padCount      =  8;
	
	$msg = [];
	//$msg[] = "";
	$header = sprintf("%-" . $padZone . "s %-" . $padPtype . "s ", "Zone", "PuzzleType");
	foreach($profileMap as $title => $settings){
		$profile = $settings["profile"];
		$header .= sprintf("%" . $padCount . "s", $title);
	}
	//$msg[] = $header;
	$lastZoneIndex = -1;
	foreach($hubProfile as $zoneIndex => $info){
		if($zoneIndex != $lastZoneIndex){
			$lastZoneIndex = $zoneIndex;
			$msg[] = "";
			$msg[] = $header;
		}
		$zoneName = ZoneToPretty($zoneIndex);
		foreach($info as $actualPtype => $ignore){
			$line = sprintf("%-" . ($padZone + $padColorExtra). "s %-" . $padPtype . "s ", $zoneName, PuzzlePrettyName($actualPtype));
			foreach($profileMap as $title => $settings){
				$profile = $settings["profile"];
				$count = count($profile[$zoneIndex][$actualPtype]);
				$line .= sprintf("%" . ($padCount + ($count == 0 ? $padColorExtra : 0)). "s", ($count == 0 ? "\e[38;2;160;160;160m-\e[0m" : $count));
			}
			$msg[] = $line;
		}
	}
	$msg[] = "";
	printf("%s\r\n", implode("\r\n", $msg));
}

function SplitProfileByCategories(array $profile){
	$split = [];
	foreach($profile as $zoneIndex => $info){
		$split[$zoneIndex] = [];
		foreach($info as $actualPtype => $pidList){
			$pcat = PuzzleTypeToCategory($actualPtype);
			if(!isset($split[$zoneIndex][$pcat])){
				$split[$zoneIndex][$pcat] = [];
			}
			$split[$zoneIndex][$pcat][$actualPtype] = $pidList;
		}
	}
	return $split;
}

function ReduceProfileToPtypes(array $profile){
	$reduced = [];
	foreach($profile as $zoneIndex => $info){
		foreach($info as $actualPtype => $pidList){
			if(!isset($reduced[$actualPtype])){
				$reduced[$actualPtype] = [];
			}
			$reduced[$actualPtype] = array_values(array_merge($reduced[$actualPtype], $pidList));
		}
	}
	foreach($reduced as $actualPtype => &$fullPidList_ref){
		sort($fullPidList_ref);
		//$fullPidList_ref = count($fullPidList_ref); // debug
	}
	return $reduced;
}

function ReduceProfileToCategories(array $profile){
	$reduced = [];
	foreach($profile as $zoneIndex => $info){
		$reduced[$zoneIndex] = [];
		foreach($info as $actualPtype => $pidList){
			$pcat = PuzzleTypeToCategory($actualPtype);
			if(!isset($reduced[$zoneIndex][$pcat])){
				$reduced[$zoneIndex][$pcat] = [];
			}
			$reduced[$zoneIndex][$pcat] = array_values(array_merge($reduced[$zoneIndex][$pcat], $pidList));
		}
	}
	foreach($reduced as $zoneIndex => &$info_ref){
		foreach($info_ref as $pcat => &$pidList_ref){
			sort($pidList_ref);
			//$pidList_ref = count($pidList_ref); // debug
		}
	}
	return $reduced;
}

function ReduceProfileToZones(array $profile){
	$reduced = [];
	foreach($profile as $zoneIndex => $info){
		$reduced[$zoneIndex] = [];
		foreach($info as $actualPtype => $pidList){
			$reduced[$zoneIndex] = array_values(array_merge($reduced[$zoneIndex], $pidList));
		}
	}
	foreach($reduced as $zoneIndex => &$fullPidList_ref){
		sort($fullPidList_ref);
		//$fullPidList_ref = count($fullPidList_ref); // debug
	}
	return $reduced;
}


