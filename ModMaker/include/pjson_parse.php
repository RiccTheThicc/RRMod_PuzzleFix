<?php

include_once("include\\config.php");
include_once("include\\lookup.php");
include_once("include\\coordinates.php");
include_once("include\\stringex.php");
include_once("include\\profile.php");

function CreatePidToZoneIndex(){
	static $pidToZoneIndex = null;
	
	if($pidToZoneIndex === null){
		$pidToZoneIndex = [];
		$allHubProfile = GetHubProfile();
		foreach($allHubProfile as $zoneIndex => $info){
			foreach($info as $actualPtype => $pidList){
				foreach($pidList as $pid){
					$pidToZoneIndex[$pid] = $zoneIndex;
				}
			}
		}
	}
	return $pidToZoneIndex;
}

function GetMegaTable(){
	global $config;
	static $megaTable = null;
	
	if($megaTable === null){
		$megaTablePath = $config["base_dir"] . "media\\data\\megatable.csv";
		if(!is_file($megaTablePath)){
			printf("%s\n", ColorStr("Failed to locate file " . $megaTablePath, 255, 128, 128));
			exit(1);
		}
		$csv = LoadCsv($megaTablePath);
		if(empty($csv)){
			printf("%s\n", ColorStr("Failed to load file " . $megaTablePath, 255, 128, 128));
			printf("%s\n", ColorStr("Please report this on the Discord server", 255, 128, 128));
			exit(1);
		}
		foreach($csv as $entry){
			$pid = intval($entry["pid"]);
			if($pid <= 0){
				continue;
			}
			$megaTable[$pid] = $entry;
		}
		//printf("%s\n", ColorStr("Loaded file " . $megaTablePath, 160, 160, 160));
		//printf("Loaded file %s\n", $megaTablePath);
	}
	return $megaTable;
}

function GetMegaTable3(){
	global $config;
	static $megaTable = null;
	
	if($megaTable === null){
		$megaTablePath = $config["base_dir"] . "media\\data\\megatable_v3.csv";
		$megaTable = LoadCsvMap($megaTablePath, "pid");
		foreach($megaTable as &$entry){
			$entry = (object)$entry;
			$entry->coords    = json_decode(str_replace(";", ",", $entry->coords));
			$entry->extraData = json_decode(str_replace(";", ",", $entry->extraData));
			foreach($entry->extraData as $subKey => $subValue){
				$entry->$subKey = $subValue;
			}
			unset($entry->extraData);
		}unset($entry);
	}
	return $megaTable;
};


function GetAllKnownPids(){
	static $allKnownPids = null;
	if($allKnownPids === null){
		$megaTable = GetMegaTable();
		$allKnownPids = array_unique(array_filter(array_column($megaTable, "pid"), function($pid) { return ($pid > 0 && $pid < 26000); }));
		sort($allKnownPids);
	}
	return $allKnownPids;
}

function IsKnownPid($pid){
	return (in_array($pid, GetAllKnownPids()));
}

function IsDeprecated($pid){
	return !IsKnownPid($pid);
}

$g_unlockTitleToPid = [];

function UnlockTitleToPid(string $title){
	global $g_unlockTitleToPid;
	$title = str_replace(",", "", $title);
	if(!isset($g_unlockTitleToPid[$title])){
		return FALSE;
	}
	return $g_unlockTitleToPid[$title];
}

function LoadMegaTableNew(string $path){
	static $megaTable = null;
	if($megaTable === null){
		printf("%s\n", ColorStr("Loading " . $path, 160, 160, 160));
		$megaTable = LoadCsvMap($path, "pid");
		foreach($megaTable as &$entry){
			$entry = (object)$entry;
			$entry->coords    = json_decode(str_replace(";", ",", $entry->coords));
			$entry->extraData = json_decode(str_replace(";", ",", $entry->extraData));
		}unset($entry);
	}
	return $megaTable;
};

function SaveMegaTableNew(array $megaTable, string $path){
	$clone = unserialize(serialize($megaTable));
	foreach($clone as &$entry){
		foreach($entry as $key => &$value){
			if(is_array($value) || is_object($value)){
				$value = str_replace(",", ";", json_encode($value, JSON_UNESCAPED_SLASHES));
			}
		}unset($value);
		$entry = (array)$entry;
	}
	WriteFileSafe($path, FormCsv($clone), true);
};

function GetPuzzleMap(bool $includeDungeons = false){
	global $config;
	global $g_unlockTitleToPid;
	
	static $puzzleMap = null;
	if($puzzleMap === null){
		
		$seenZonesUppercase = [];
		$seenZonesLowercase = [];
		$seenStatuses       = [];
		$seenPools          = [];
		
		$pidToZoneIndex = CreatePidToZoneIndex();
		$megaTable = GetMegaTable();
		
		$puzzleMap = [];
		$seenPtypes = [];
		
		if(!file_exists($config["pjson_path"])){
			printf("[ERROR] File \"%s\" not found, check config.\n", $config["pjson_path"]);
			exit(1);
		}
		$pjson_raw = file_get_contents($config["pjson_path"]);
		if($pjson_raw === FALSE){
			printf("[ERROR] Failed to read \"%s\".\n", $config["pjson_path"]);
			exit(1);
		}
		$pjson_main = json_decode($pjson_raw);
		if(empty($pjson_main)){
			printf("[ERROR] File \"%s\" might be corrupted.\n", $config["pjson_path"]);
			exit(1);
		}
		
		foreach($pjson_main->puzzles as $puzzle){
			$data = $puzzle;
			if(isset($puzzle->serialized)){
				$mishMash = array_merge((array)($data), (array)(json_decode($puzzle->serialized)));
				unset($mishMash["serialized"]);
				$data = (object)$mishMash;
			}
			$ptype = $puzzle->puzzleType;
			$pid = $puzzle->pid;
			$data->ptype = $ptype;
			$data->pid = $pid;
			$data->isDungeonPuzzle = false;
			
			if(isset($data->Disabled) && $data->Disabled == 1){
				//printf("Skipping disabled puzzle %d (%s)\n", $pid, $ptype);
				//if(in_array($pid, $solved_ids)){
				//	printf("How did you solve the disabled puzzle %d?\n", $pid);
				//}
				//continue; // don't skip disabled :>
			}
			$data->isDungeonPuzzle = IsDungeonPuzzle($pid, $ptype, $data);
			//if(!$includeDungeons && $data->isDungeonPuzzle){
				//printf("Skipping dungeon puzzle %d (%s)\n", $pid, $ptype);
				// BUGGY AS FUCK!!!!!!!!
				//continue;
			//}
			
			// Simplify hints data into a single string instead of array, if possible.
			if(isset($data->solves)){
				foreach($data->solves as &$a){
					if(isset($a->hint)){
						$a->hint = implode(",", $a->hint);
					}
				}
			}
			
			$data->pool = "";
			if(isset($data->PoolName)){
				if(!isset($seenPools[$data->PoolName])){
					$seenPools[$data->PoolName] = 0;
				}
				++$seenPools[$data->PoolName];
				$data->pool = $data->PoolName;
			}
			if(isset($data->status)){
				if(!isset($seenStatuses[$data->status])){
					$seenStatuses[$data->status] = 0;
				}
				++$seenStatuses[$data->status];
			}
			if(isset($data->Zone)){
				if(!isset($seenZonesUppercase[$data->Zone])){
					$seenZonesUppercase[$data->Zone] = 0;
				}
				++$seenZonesUppercase[$data->Zone];
				$data->pool = $data->Zone;
			}
			if(isset($data->zone)){
				if(!isset($seenZonesLowercase[$data->zone])){
					$seenZonesLowercase[$data->zone] = 0;
				}
				++$seenZonesLowercase[$data->zone];
				$data->pool = $data->zone;
			}
			if(!isset($data->status)){
				$data->status = "";
			}
			
			// Oh my god
			$data->actualZoneIndex = -1;
			if(isset($data->Zone)){
				$data->actualZoneIndex = $data->Zone;
			}
			
			static $qfpNameToZone = [
				"none"            => 0,
				"QFP1"            => 2,
				"QFP2"            => 3,
				"QFPDominion"     => 4,
				"QFPIslands"      => 5,
				"QFPConnectivity" => 6,
			];
			$data->qfp = 0;
			
			// TODO: $data->PoolName to zone // old unchecked code
			if(isset($data->PoolName)){
				if(preg_match("/^Zone([0-9])$/", $data->PoolName, $matches) && count($matches) == 2){
					if(!isset($data->Zone)){
						$data->Zone = (int)$matches[1] + 1; // Zone1..Zone5 to 2..6
						$data->actualZoneIndex = $data->Zone; // I'm so sorry
						$data->pool = $data->Zone;
					}
				}elseif(isset($qfpNameToZone[$data->PoolName])){
					$z = $qfpNameToZone[$data->PoolName];
					$data->qfp = $z;
					$data->actualZoneIndex = $z;
					$data->Zone = $z;
					$data->isDungeonPuzzle = true;
					$data->pool = $z;
				}elseif($data->PoolName != "live"){
					// THIS IS UNRELIABLE AS FUCK!
					$data->isDungeonPuzzle = true;
					//printf("%5d %-18s %s\n", $pid, $ptype, $data->PoolName);
					//continue;
				}
			}
			
			if(!$includeDungeons && $data->isDungeonPuzzle){
				continue;
			}
			
			$data->coords = [];
			if(isset($megaTable[$pid])){
				$entry = $megaTable[$pid];
				$data->coords[] = (object)[
					"x"     => (float)$entry["x"],
					"y"     => (float)$entry["y"],
					"z"     => (float)$entry["z"],
					"pitch" => (float)$entry["pitch"],
					"yaw"   => (float)$entry["yaw"],
					"roll"  => (float)$entry["roll"],
					
					"rot"   => (float)$entry["yaw"], // legacy compat
				];
				if(($data->actualZoneIndex < 2 || $data->actualZoneIndex > 7) && $entry["zoneIndex"] >= 2 && $entry["zoneIndex"] <= 7){
					$data->actualZoneIndex = $entry["zoneIndex"];
					//printf("Reassigned %5d %-18s to zone %d\n", $pid, $ptype, $data->actualZoneIndex);
				}
			}
			
			if(isset($data->ActorTransform)){
				$data->coords = ParseCoordinates($pid, $ptype, $data);
			}
			if(isset($data->LocalID)){
				if(str_starts_with($data->LocalID, "/Game/")){
					// Do nothing, local id is good.
				}else{
					$parts = preg_split('/\s+/', $data->LocalID, -1, PREG_SPLIT_NO_EMPTY);
					if(count($parts) == 2){
						$data->LocalID = $parts[1];
					}
				}
			}
			
			// Logic grid shit
			$data->actualPtype = $ptype;
			$data->actualDifficulty = -1;
			if(isset($data->difficulty)){
				$data->actualDifficulty = (int)$data->difficulty;
			}
			if(isset($data->Difficulty)){
				$data->actualDifficulty = (int)$data->Difficulty;
			}
			if($ptype == "logicGrid"){
				if(isset($puzzle->serialized)){
					$data->pdata = $data->BinaryData;
					unset($data->BinaryData);
				}

				$data->actualPtype = "logicGrid";
				$pdataEncoded = $data->pdata;
				$pdataRaw = base64_decode($pdataEncoded);
				$modifierCount = ord($pdataRaw[1]);
				for($ptr = 2; $ptr < 2 + $modifierCount; ++$ptr){
					$modifier = ord($pdataRaw[$ptr]);
					static $gridModifierIntentifiers = [
						2 => "completeThePattern",
						4 => "musicGrid",
						12 => "memoryGrid",
						11 => "logicGrid", // npl compatibility override
					];
					if(isset($gridModifierIntentifiers[$modifier])){
						$data->actualPtype = $gridModifierIntentifiers[$modifier];
						break;
					}
				}
			}
			if(isset($pidToZoneIndex[$pid]) && ($data->actualZoneIndex < 2 || $data->actualZoneIndex > 6)){
				$data->actualZoneIndex = $pidToZoneIndex[$pid];
				$data->Zone = $pidToZoneIndex[$pid];
				$data->pool = $data->Zone;
			}
			
			if(isset($qfpNameToZone[$data->pool])){
				$data->qfp = $qfpNameToZone[$data->pool];
			}
			
			$puzzleMap[$pid] = $data;
		}
		
		foreach($megaTable as $pid => $entry){
			$pid = (int)$pid;
			if(isset($puzzleMap[$pid])){
				continue;
			}
			//pid,parent,zoneIndex,x,y,z,pitch,yaw,roll,ptype,category,path,comment,extraData
			//41078,-1,7,108542.18,68519.55,43618.28,0.00,0.00,0.00,loreFragment,LoreFragment,World / First Echoes / StaticPuzzle / LoreFragment_41078,,{"saveName":"Whispers of Obli the First Part 2"}
			$data = (object)[];
			$data->pid              = $pid;
			$data->ptype            = $entry["ptype"];
			$data->actualPtype      = $entry["ptype"];
			$data->zoneIndex        = $entry["zoneIndex"];
			$data->actualZoneIndex  = $entry["zoneIndex"];
			$data->comment          = $entry["comment"];
			$data->isDungeonPuzzle  = true;
			$data->pool             = "";
			$data->status           = "";
			$data->actualDifficulty = -1;
			$data->qfp              = 0;
			
			$data->coords[] = (object)[
				"x"     => (float)$entry["x"],
				"y"     => (float)$entry["y"],
				"z"     => (float)$entry["z"],
				"pitch" => (float)$entry["pitch"],
				"yaw"   => (float)$entry["yaw"],
				"roll"  => (float)$entry["roll"],
				
				"rot"   => (float)$entry["yaw"], // legacy compat
			];
			if($data->ptype == "monolithFragment"){
				$papaPid = $entry["parent"];
				$papaData = $puzzleMap[$papaPid];
				$data->coords[] = $papaData->coords[0];
			}
			if(!empty($entry["extraData"])){
				$data = (object)array_merge((array)$data, (array)json_decode(str_replace("|", ",", $entry["extraData"])));
			}
			if(isset($data->saveName)){
				$g_unlockTitleToPid[$data->saveName] = $pid;
			}
			
			$puzzleMap[$pid] = $data;
		}
		
		ksort($puzzleMap);
		
		ksort($seenZonesUppercase);
		ksort($seenZonesLowercase);
		ksort($seenStatuses);
		ksort($seenPools);
		asort($g_unlockTitleToPid);
	}
	
	return $puzzleMap;
}

function DumpPuzzleMap(array $puzzleMap, string $outputPath){
	$pjsonDump = [];
	foreach($puzzleMap as $pid => $data){
		$sorted = (array)$data;
		ksort($sorted);
		$sorted = (object)$sorted;
		$pjsonDump[] = sprintf(" %5d %-22s %-15s %s", $pid, $data->actualPtype, ZoneToPrettyNoColor($data->actualZoneIndex), json_encode($sorted, JSON_UNESCAPED_SLASHES));
	}
	file_put_contents($outputPath, implode("\r\n", $pjsonDump) . "\r\n");
	printf("Wrote %d puzzles' data to \"\%s\"\n", count($pjsonDump), $outputPath);
}

function GroupByPtype(&$puzzleMap){
	static $ptypeToPids = null;
	if($ptypeToPids === null){
		$ptypeToPids = [];
		foreach($puzzleMap as $pid => &$data_ref){
			$ptype = $data_ref->actualPtype;
			if(!isset($ptypeToPids[$ptype])){
				$ptypeToPids[$ptype] = [];
			}
			$ptypeToPids[$ptype][] = $pid;
		}
		ksort($ptypeToPids);
	}
	return $ptypeToPids;
}

function CollectPuzzleMapStats(&$puzzleMap){
	$ptypeToPids = GroupByPtype($puzzleMap);
	$s = [];
	foreach($ptypeToPids as $ptype => &$pids_ref){
		$s[] = sprintf("%d %s", count($pids_ref), $ptype);
	}
	return $s;
}

function GetPtypeAndZone($pid){
	$pid = (int)$pid;
	$puzzleMap = GetPuzzleMap(true);
	$ptype = "unknown";
	$zoneIndex = -1;
	if(isset($puzzleMap[$pid])){
		$ptype = $puzzleMap[$pid]->actualPtype;
		$zoneIndex = $puzzleMap[$pid]->actualZoneIndex;
	}
	return [ $ptype, $zoneIndex ];
}

