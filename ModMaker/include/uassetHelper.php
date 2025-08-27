<?php

include_once("include\\uassetParse.php");

function &ParseUassetPuzzleDatabase(&$json_ref){
	//  PuzzleDatabase.json:
	// 	0	zoneToSandboxProgressionRelevant	info whether the zone should display hub tracks
	// 	1	puzzleZoneToGroupNumOverrides		how many puzzles should spawn per cycle everywhere
	// 	2	krakenIDToWorldPuzzleData			info about 11000+ puzzles, seems to be fixed-coordinate puzzles
	// 	3	krakenIDToContainedPuzzleData		info about 13311 puzzles, seems to be logic grids
	// 	4	krakenIDToKrakenIDOnlyPuzzleData	list of Kraken IDs that should not count as "puzzles" (dungeons, puzzle totems, cutscenes etc)
	// 	5	krakenIDToPuzzleStatus				list of 25248 statuses, including 20455 live, 1610 dungeon, and retired/junk/etc
	// 	6	krakenIDToPuzzleData				list of 2888 puzzles that maps krakenIDToPuzzleData, but ALL values are zero.
	// 	7	bIsFakingKraken						a tiny node that defines bIsFakingKraken as an null value
	
	$exports = &ParseUassetExports($json_ref);
	$dataNode = &$exports["PuzzleDatabase"]->Data;
	$result = [];
	foreach((array)$dataNode as $key => &$subNode_ref){
		$result[$key] = &$subNode_ref;
	}unset($subNode_ref);
	$result = (object)$result;
	unset($dataNode);
	return $result;
}

function &ParseUassetSandboxZones(&$json_ref){
	
	$exports = &ParseUassetExports($json_ref);
	
	$result = (object)[
		"ZoneDefs"       => [],
		"Bounds"         => [],
		"SandboxRegions" => [],
		"Containers"     => [],
		"Billboard"      => null,
	];
	
	foreach($exports as $objName => &$szObj_ref){
		$szObjData_ref = &$szObj_ref->Data;
		
		//if(isset($szObjData_ref->bVisible)){
		//	$szObjData_ref->bVisible = true; // does not work :(
		//}
		
		if(preg_match("/^([\w]+)Zone([\d_]*?)$/", $objName, $matches)){
			//printf("<%s>\n", $objName);continue;
			// Current object is a zone definition.
			// One of: CentralZone1,EgyptZone1,IntroZone1,MiscZone1,MountainZone,RedwoodsZone1,RiverlandsZone1
			
			$zoneIndex = ZoneNameToInt($matches[1]);
			//static $desiredPriorityMap = [
			//	0 => 1,
			//	2 => 10002,
			//	3 => 10003,
			//	4 => 10004,
			//	5 => 10005,
			//	6 => 10006,
			//	7 => 2,
			//];
			//$cachedValue = $szObjData_ref->priority ?? -1; // no ref
			//$szObjData_ref->priority = $desiredPriorityMap[$zoneIndex]; // experimental, change hub puzzles to high priority.
			////printf("Priority for zone %s changed from %d to %d\n", ZoneToPrettyNoColor($zoneIndex), $cachedValue, $szObjData_ref->priority);
			//
			//$alwaysSpawn_ref = $szObjData_ref->alwaysSpawnContainerPuzzlesToBeSpawned;
			//// you can add stuff to $alwaysSpawn_ref array here.
			$result->ZoneDefs[$zoneIndex] = &$szObjData_ref;
			
		//}elseif(preg_match("/^([\w]+)Bounds([\d_]+?)$/", $objName, $matches)){
		}elseif(preg_match("/^([\w]+)Bounds([\d_]+?)$/", $objName, $matches)){
			//printf("<%s>\n", $objName);continue;
			// Current object is a zone bounds definition.
			
			$zoneIndex = ZoneNameToInt($matches[1]);
			$boundsID = $matches[2];
			//
			//$szObjData_ref->bBlockSpawning = false; // it's already false but whatever
			////printf("Found bounds ID %s for %s\n", $boundsID, ZoneToPrettyNoColor($zoneIndex));
			if(!isset($result->Bounds[$zoneIndex])){
				$result->Bounds[$zoneIndex] = [];
			}
			$result->Bounds[$zoneIndex][$objName] = &$szObjData_ref;
		
		}elseif(preg_match("/^SandboxRegions([\d_]+)$/", $objName, $matches)){
			// Current object is a SandboxRegions definition.
			// Only SandboxRegions2_0 exists.
			
			$regionID = $matches[1];
			//printf("Found SandboxRegions |%s|\n", $regionID);
			$result->SandboxRegions[$regionID] = &$szObjData_ref;
		
		}elseif(preg_match("/^Billboard$/", $objName, $matches)){
			// Current object is a Billboard.
			// It is located physically near the The Ophidian Pillar in Autumn Falls.
			// Its purpose in unknown, but its position is suspiciously close to the main menu camera location.
			
			//printf("Found Billboard\n");
			$result->Billboard = &$szObjData_ref;
			
		}elseif(preg_match("/^MonumentPuzzleContainer_([\d_]+)$/", $objName, $matches) ||
		        preg_match("/^SpawnedPuzzleContainer_([\d_]+)$/",  $objName, $matches) ||
		        preg_match("/^(Static|Cluster|Custom)Rune.*$/",    $objName, $matches)){
			//$tempID = $matches[1];
			$localID = $szObjData_ref->localID;
			
			// Turns out you cannot trust the localID name. Or bIsFloorSlab value. Or containerPuzzleClass.
			// You can either check possiblePuzzleTypes or perhaps filter for BP_Lockpick_C etc but these won't include handle hub slabs.
			static $ptypeToContainerType = [
				"logicGrid"          => "Rune",
				"completeThePattern" => "Rune",
				"musicGrid"          => "Rune",
				"memoryGrid"         => "Rune",
				"fractalMatch"       => "Monument",
				"klotski"            => "Monument",
				"lockpick"           => "Monument",
				"match3"             => "Monument",
				"mirrorMaze"         => "SlabSocket",
				"rollingCube"        => "SlabSocket",
				"ryoanji"            => "SlabSocket",
				"gyroRing"           => "GyroSpawn",
			];
			$firstPtype = ((array)$szObjData_ref->possiblePuzzleTypes)[0];
			$result->Containers[$localID] = &$szObjData_ref;
			$result->Containers[$localID]->containerType = $ptypeToContainerType[$firstPtype]; // non-reference, custom extra info
		}else{
			// All other objects. They seem totally uninteresting.
			// Full list: PersistentLevel,Model_0,NavigationSystemModuleConfig_0,SophiaWorldSettings,SandboxZones,WorldSettings_0
			//printf("%s\n%s\n\n", $objName, json_encode($szObjData_ref));
		}
		unset($szObjData_ref);
	}unset($szObj_ref);
	
	ksort($result->ZoneDefs,       SORT_NATURAL);
	ksort($result->Bounds,         SORT_NATURAL);
	ksort($result->SandboxRegions, SORT_NATURAL);
	ksort($result->Containers,     SORT_NATURAL);
	
	return $result;
}

//$g_boundsMurderCount = 0;
function YeetBoundsTransform(mixed &$input){
	//global $g_boundsMurderCount;
	
	$t = UeTransformUnpack($input);
	
	$t->Translation->X *= 1e4;
	$t->Translation->Y *= 1e4;
	$t->Translation->Z -= 1e7;
	
	$t->Rotation->X = 0.0;
	$t->Rotation->Y = 0.0;
	$t->Rotation->Z = 0.0;
	$t->Rotation->W = 1.0;
	
	$t->Scale3D->X  = 1.0;
	$t->Scale3D->Y  = 1.0;
	$t->Scale3D->Z  = 1.0;
	
	UeTransformPackInto($t, $input);
}

function YeetBoundsBox(mixed &$input){
	//global $g_boundsMurderCount;
	
	$box = UeBoxUnpack($input);
	//printf("%s\n", json_encode($box));
	
	$box->MinX *= 1e4;
	$box->MinY *= 1e4;
	$box->MinZ -= 1e7;
	$box->MaxX = $box->MinX + 1;
	$box->MaxY = $box->MinY + 1;
	$box->MaxZ = $box->MinZ + 1;
	
	UeBoxPackInto($box, $input);
}

function ShrinkBoundsBox(mixed &$input){
	//global $g_boundsMurderCount;
	
	$box = UeBoxUnpack($input);
	
	$midX = ($box->MinX + $box->MaxX) / 2.0;
	$midY = ($box->MinY + $box->MaxY) / 2.0;
	$midZ = ($box->MinZ + $box->MaxZ) / 2.0;
	
	$box->MinX = $midX - 0.1;
	$box->MaxX = $midX + 0.1;
	$box->MinY = $midY - 0.1;
	$box->MaxY = $midY + 0.1;
	$box->MinZ = $midZ - 0.1;
	$box->MaxZ = $midZ + 0.1;
	
	UeBoxPackInto($box, $input);
}

function DisableSerializedIncompatibles(string &$ser){
	$miniJson = json_decode($ser);
	unset($miniJson->IncompatibleKrakenIDs);
	$ser = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}

function DisableSerializedBounds(string &$ser){
	$miniJson = json_decode($ser);
	foreach(array_keys((array)$miniJson) as $fieldName){
		if(preg_match("/^SERIALIZEDSUBCOMP_PuzzleBounds\-\d*$/", $fieldName)){
			$miniJson->$fieldName->bBlockSpawning = false;
			$miniJson->$fieldName->acceptAllByDefault = true;
			unset($miniJson->$fieldName->acceptedTypes);
			$miniJson->$fieldName->rejectedTypes = "";
		}
	}
	$ser = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}

function ShrinkSerializedBounds(string &$ser){
	$miniJson = json_decode($ser);
	foreach(array_keys((array)$miniJson) as $fieldName){
		if(preg_match("/^SERIALIZEDSUBCOMP_PuzzleBounds\-\d*$/", $fieldName)){
			ShrinkBoundsBox($miniJson->$fieldName->Box);
			$miniJson->$fieldName->bBlockSpawning = false;
			$miniJson->$fieldName->acceptAllByDefault = true;
			unset($miniJson->$fieldName->acceptedTypes);
			$miniJson->$fieldName->rejectedTypes = "";
		}
	}
	$ser = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}

/*
function YeetSerializedBounds(string &$ser){
	
	$miniJson = json_decode($ser);
	
	foreach(array_keys((array)$miniJson) as $fieldName){
		
		//if(preg_match("/^(?:SERIALIZEDSUBCOMP_)?PuzzleBounds\-\d*$/", $fieldName)){ // ignore "PuzzleBounds-0" I think
		if(preg_match("/^SERIALIZEDSUBCOMP_PuzzleBounds\-\d*$/", $fieldName)){
			YeetBoundsTransform($miniJson->$fieldName->RelativeTransform);
			YeetBoundsTransform($miniJson->$fieldName->WorldTransform);
			YeetBoundsBox($miniJson->$fieldName->Box);
			$miniJson->$fieldName->bBlockSpawning = false;
			$miniJson->$fieldName->acceptAllByDefault = true;
//			if(isset($miniJson->rejectedTypes)){
//				$minijson->rejectedTypes = [];
//			}
			unset($miniJson->acceptedTypes);
			$miniJson->rejectedTypes = [];
			
			//printf("%s\n", BoolStr($miniJson->$fieldName->acceptAllByDefault));
			//if($miniJson->$fieldName->acceptAllByDefault == true || @!empty($miniJson->$fieldName->acceptedTypes) || @!empty($miniJson->$fieldName->rejectedTypes)){
			//	printf("acceptedTypes [%s], rejectedTypes [%s], acceptAllByDefault %s\n",
			//			$miniJson->$fieldName->acceptedTypes ?? "", //implode(',', ($miniJson->$fieldName->acceptedTypes ?? [])),
			//			$miniJson->$fieldName->rejectedTypes ?? "", //implode(',', ($miniJson->$fieldName->rejectedTypes ?? [])),
			//			BoolStr($miniJson->$fieldName->acceptAllByDefault == true));
			//	//var_dump($ser);
			//}
		
			// FIELDS:
			// RelativeTransform
			// bUseForDungeonIdentification
			// acceptAllByDefault
			// bBlockSpawning
			// acceptedTypes/rejectedTypes
			// WorldTransform
			// Box
			
		}
	}
	
	$ser = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}
*/

function FixSlabPtype(&$jsonSandboxZones, string $csvPath){
	$desiredSlabPtypes = LoadCsvMap($csvPath, "localID");

	static $typeToClassMap = [
		"mirrorMaze"  => -16,
		"rollingCube" => -18,
		"ryoanji"     => -20,
	];

	$exports_ref = &$jsonSandboxZones->Exports;
	foreach($exports_ref as $index => &$obj_ref){
		if(!isset($obj_ref->Data)){
			continue;
		}
		$data_ref = &$obj_ref->Data;
		foreach($data_ref as $index => &$node_ref){
			if($node_ref->Name != "localID"){
				continue;
			}
			$localID = $node_ref->Value;
			if(!isset($desiredSlabPtypes[$localID])){
				continue;
			}
			
			// We found the data node that we need to replace.
			$entry    = $desiredSlabPtypes[$localID];
			$ptype    = $entry["ptype"];
			$slabName = $entry["slabName"];
			$classID  = $typeToClassMap[$ptype];
			
			foreach($data_ref as $reindex => &$renode_ref){
				if($renode_ref->Name == "possiblePuzzleTypes"){
					//printf("%-40s %s\n", $slabName, json_encode($renode_ref->Value));
					$renode_ref->Value[0]->Value = $ptype;
					//printf("%-40s %s\n", $slabName, json_encode($renode_ref->Value));
					//printf("\n");
				}elseif($renode_ref->Name == "typeToClass"){
					//print_r($renode_ref);
					foreach($renode_ref->Value[0] as $subIndex => &$subNode_ref){
						if($subNode_ref->{'$type'} == "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI"){
							$subNode_ref->Value = $ptype;
						}elseif($subNode_ref->{'$type'} == "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI"){
							$subNode_ref->Value = $classID;
						//printf("%-40s %s\n", $slabName, json_encode($subNode_ref));
						}
					}
					//print_r($renode_ref);
				}
			}unset($renode_ref);
			break;
		}unset($node_ref);
		unset($data_ref);
	}unset($obj_ref);
	unset($exports_ref);
}

function FixAdjusterCsv(string $csvPath){
	
	// Converting old formats to the new one. This shouldn't be needed anymore.
	//$lines = file($csvPath);
	//$lines = array_map(function($x) { return str_replace(["\r","\n"], "", $x); }, $lines);
	//$lines = array_values(array_filter($lines, function($x) { return (!empty($x) && !str_starts_with($x, "localID,pid,fields")); }));
	//
	//foreach($lines as &$line_ref){
	//	$elems = explode(",", $line_ref);
	//	$elemCount = count($elems);
	//	if($elemCount == 10){
	//		$comment = $elems[9];
	//		unset($elems[9]);
	//		$elems = array_values(array_merge($elems, [ "1.0000", "1.0000", "1.0000", "", $comment ]));
	//		$line_ref = implode(",", $elems);
	//	}elseif($elemCount == 13){
	//		$comment = $elems[12];
	//		unset($elems[12]);
	//		$elems = array_values(array_merge($elems, [ "", $comment ]));
	//		$line_ref = implode(",", $elems);
	//	}elseif($elemCount == 14){
	//		// Expected amount.
	//	}else{
	//		printf("[ERROR] Adjuster log format seems broken for %s, see line:\n%s\n\n", $csvPath, $line_ref);
	//		exit(1);
	//	}
	//	//printf("%d\n", count($elems));
	//}unset($line_ref);
	//static $header = "localID,pid,fields,dx,dy,dz,dpitch,dyaw,droll,msx,msy,msz,datetime,comment";
	//$newFile = implode("\n", array_values(array_merge([ $header ], $lines, [ "" ])));
	//file_put_contents($csvPath, $newFile);
	
	$csvOriginal = LoadCsv($csvPath);
	$csvNew = [];
	foreach($csvOriginal as $entry){
		//$localID = $entry["localID"];
		//$pid     = $entry["pid"];
		//$fields  = $entry["fields"];
		$key = $entry["localID"] . "," . $entry["pid"] . "," . $entry["fields"];
		if($key == "localID,pid,fields"){
			continue;
		}
		if(!isset($csvNew[$key])){
			$csvNew[$key] = $entry;
			$csvNew[$key]["msx"] *= 1.0;
			$csvNew[$key]["msy"] *= 1.0;
			$csvNew[$key]["msz"] *= 1.0;
		}else{
			$csvNew[$key]["dx"]     += $entry["dx"];
			$csvNew[$key]["dy"]     += $entry["dy"];
			$csvNew[$key]["dz"]     += $entry["dz"];
			$csvNew[$key]["dpitch"] += $entry["dpitch"];
			$csvNew[$key]["dyaw"]   += $entry["dyaw"];
			$csvNew[$key]["droll"]  += $entry["droll"];
			$csvNew[$key]["msx"]    *= $entry["msx"];
			$csvNew[$key]["msy"]    *= $entry["msy"];
			$csvNew[$key]["msz"]    *= $entry["msz"];
			$csvNew[$key]["datetime"] = max($csvNew[$key]["datetime"], $entry["datetime"]);
			if(empty($csvNew[$key]["comment"]) && !empty($entry["comment"])){
				$csvNew[$key]["comment"] = $entry["comment"];
			}
		}
		//$csvNew[$key]["msx"] = sprintf("%.4f", $csvNew[$key]);
		//$csvNew[$key]["msy"] = sprintf("%.4f", $csvNew[$key]);
		//$csvNew[$key]["msz"] = sprintf("%.4f", $csvNew[$key]);
	}
	
	array_multisort(array_column($csvNew, "datetime"), SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "pid" ),     SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "localID" ), SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "fields" ),  SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "dx" ),      SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "dy" ),      SORT_ASC, SORT_NATURAL,
					array_column($csvNew, "dz" ),      SORT_ASC, SORT_NATURAL,
					$csvNew);
	file_put_contents($csvPath, FormCsv($csvNew) . "\n");
}

$g_adjustedItems = [];
function AdjustAssetCoordinates(&$puzzleDatabase, &$sandboxZones, string $csvPath){
	global $g_adjustedItems;
	
	FixAdjusterCsv($csvPath);
	$adjuster = LoadCsv($csvPath);
	$puzzleMap = GetPuzzleMap(true);
	
	$myZone = -1;
	if(preg_match("/adjust_([\w\W_]+)\.csv$/", $csvPath, $matches)){
		$zoneName = $matches[1];
		$zoneIndex = ZoneNameToInt(str_replace("_", "", $zoneName));
		if(IsHubZone($zoneIndex)){
			$myZone = $zoneIndex;
			//printf("Matched |%s| (%d) in %s\n", $zoneName, $zoneIndex, $csvPath);
		}
	}
	
	$adjustCount = 0;
	
	foreach($adjuster as $entry){
		//localID,pid,fields,dx,dy,dz,comment
		$localID   = $entry["localID"];
		$pid       = $entry["pid"];
		$fieldList = explode("|", $entry["fields"]);
		$comment   = (empty($entry["comment"]) ? "nocomment" : $entry["comment"]);
		$itemKey   = $localID . "," . $pid . "," . $entry["fields"];
		
		//$zeroCoord = (object)[ "x" => 0, "y" => 0, "z" => 0 ];
		//$diffCoord = (object)[ "x" => $entry["dx"], "y" => $entry["dy"], "z" => $entry["dz"] ];
		//$dist = Distance($zeroCoord, $diffCoord);
		//printf("Distance: %.1f\n", $dist / 100.0);
		//printf("Searching for |%s|\n", $localID);
		
		if(is_numeric($pid) && $pid > 0){
			// We assume it's a puzzle. So we look for it in the puzzleDatabase.
			// Not that we only check the World (fixed-coordinate) puzzles. Can't move contained ones anyway.
			if(!isset($puzzleDatabase->krakenIDToWorldPuzzleData[$pid])){
				printf("%s: pid %d not found in puzzleDatabase, failed replacing:\n%s\n", __FUNCTION__, $pid, json_encode($entry, JSON_UNESCAPED_SLASHES));
				exit(1);
			}
			$ser_ref = &$puzzleDatabase->krakenIDToWorldPuzzleData[$pid];
			$ptype = json_decode($ser_ref)->PuzzleType;
			if(isset($puzzleMap[$pid])){
				$zoneIndex = $puzzleMap[$pid]->actualZoneIndex;
			}else{
				printf("%s\n", ColorStr(sprintf("Warning: puzzle %d not in puzzleMap, defaulting to Lucent zone!\n", $pid), 200, 200, 40));
				$zoneIndex = 3;
			}
			if($myZone != -1 && $myZone != $zoneIndex){
				printf("%s\n", ColorStr(sprintf("Warning: entry from %s should belong to %s:\n%s\n", ZoneToPrettyNoColor($myZone), ZoneToPrettyNoColor($zoneIndex), implode(",", $entry)), 200, 200, 40));
			}
			
			//printf("\n%s\n", addslashes($ser_ref));
			
			foreach($fieldList as $fieldName){
				// Here we *can* decode the json, change the data, and re-encode it back.
				// However, I really do not feel like using something like:
				// $miniJson->{'DuplicatedObjectOfType-Seek5HiddenObject'}->{'DuplicateTransform-1'}
				// Let alone define this in the initial .csv. So we'll go with regex instead.
				//$miniJson = json_decode($puzzleDatabase->krakenIDToWorldPuzzleData[$pid]);
				//var_dump($miniJson);
				if(!preg_match("/\\\"" . $fieldName . "\\\"\s*\:\s*\\\"([\d\.\-\+\|,]+)\\\"/", $ser_ref, $matches)){
					printf("%s: preg_match for field %s failed, serialized string and request:\n%s\n%s\n", __FUNCTION__, $fieldName, addslashes($ser_ref), json_encode($entry, JSON_UNESCAPED_SLASHES));
					exit(1);
				}
				//printf("%5d |%s| = |%s|\n", $pid, $fieldName, $matches[1]);
				$str = $matches[1];
				$t = UeTransformUnpack($str);
				$t->Translation->X += $entry["dx"];
				$t->Translation->Y += $entry["dy"];
				$t->Translation->Z += $entry["dz"];
				$t->Rotation->X    += $entry["dpitch"];
				$t->Rotation->Y    += $entry["dyaw"];
				$t->Rotation->Z    += $entry["droll"];
				$t->Scale3D->X     *= $entry["msx"];
				$t->Scale3D->Y     *= $entry["msy"];
				$t->Scale3D->Z     *= $entry["msz"];
				UeTransformPackInto($t, $str);
				
				$ser_ref = preg_replace("/(\\\"" . $fieldName . "\\\"\s*\:\s*\\\")([\d\.\-\+\|,]+)(\\\")/", '${1}' . $str . '${3}', $ser_ref);
			}
			//printf("%s: puzzle %-18s %5d adjusted (%5.0f,%5.0f,%5.0f) <%s>\n", __FUNCTION__, $ptype, $pid, $entry["dx"], $entry["dy"], $entry["dz"], $comment);
			unset($ser_ref);
			
		}elseif(isset($sandboxZones->Containers[$localID])){
			// SandboxZones-defined object.
			$obj_ref = &$sandboxZones->Containers[$localID];
			
			if($myZone != -1){
				if(!isset($obj_ref->ownerZone)){
					printf("%s\n", ColorStr("Something is wrong! Dump:", 255, 128, 128));
					var_dump($obj_ref);
					var_dump($entry);
					var_dump($csvPath);
					exit(1);
				}
				$zoneIndex = ZoneNameToInt($obj_ref->ownerZone);
				if($myZone != $zoneIndex){
					printf("%s\n", ColorStr(sprintf("Warning: entry from %s should belong to %s:\n%s\n", ZoneToPrettyNoColor($myZone), ZoneToPrettyNoColor($zoneIndex), implode(",", $entry)), 200, 200, 40));
				}
			}
			
			foreach($fieldList as $fieldName){
				if(isset($obj_ref->$fieldName)){
					$field_ref = &$obj_ref->$fieldName;
					if(count((array)$field_ref) == 3 && isset($field_ref->X) && isset($field_ref->Y) && isset($field_ref->Z)){
						// It's a simple vector of three values.
						 $field_ref->X += $entry["dx"];
						 $field_ref->Y += $entry["dy"];
						 $field_ref->Z += $entry["dz"];
					}else{
						$t = UeTransformUnpack($field_ref);
						$t->Translation->X += $entry["dx"];
						$t->Translation->Y += $entry["dy"];
						$t->Translation->Z += $entry["dz"];
						$t->Rotation->X    += $entry["dpitch"];
						$t->Rotation->Y    += $entry["dyaw"];
						$t->Rotation->Z    += $entry["droll"];
						$t->Scale3D->X     *= $entry["msx"];
						$t->Scale3D->Y     *= $entry["msy"];
						$t->Scale3D->Z     *= $entry["msz"];
						UeTransformPackInto($t, $field_ref);
					}
					unset($field_ref);
				}elseif(isset($obj_ref->serializedString) && isset(json_decode($obj_ref->serializedString)->$fieldName)){
					$ser_ref = &$obj_ref->serializedString;
					if(!preg_match("/\\\"" . $fieldName . "\\\"\s*\:\s*\\\"([\d\.\-\+\|,]+)\\\"/", $ser_ref, $matches)){
						printf("%s: preg_match for field %s failed, serialized string and request:\n%s\n%s\n", __FUNCTION__, addslashes($ser_ref), json_encode($entry, JSON_UNESCAPED_SLASHES));
						exit(1);
					}
					$str = $matches[1];
					$t = UeTransformUnpack($str);
					$t->Translation->X += $entry["dx"];
					$t->Translation->Y += $entry["dy"];
					$t->Translation->Z += $entry["dz"];
					$t->Rotation->X    += $entry["dpitch"];
					$t->Rotation->Y    += $entry["dyaw"];
					$t->Rotation->Z    += $entry["droll"];
					$t->Scale3D->X     *= $entry["msx"];
					$t->Scale3D->Y     *= $entry["msy"];
					$t->Scale3D->Z     *= $entry["msz"];
					UeTransformPackInto($t, $str);
					
					$ser_ref = preg_replace("/(\\\"" . $fieldName . "\\\"\s*\:\s*\\\")([\d\.\-\+\|,]+)(\\\")/", '${1}' . $str . '${3}', $ser_ref);
					
					unset($ser_ref);
				
				}else{
					printf("%s: no field %s for object %s\n%s\n", __FUNCTION__, $fieldName, $localID, json_encode($entry, JSON_UNESCAPED_SLASHES));
					exit(1);
				}
			}
			
			$shortName = $localID;
			if(strlen($localID) > 21){
				$shortName = str_pad(substr($localID, -21), 24, ".", STR_PAD_LEFT);
			}
			//printf("%s: object %-24s adjusted (%5.0f,%5.0f,%5.0f) <%s>\n", __FUNCTION__, $shortName, $entry["dx"], $entry["dy"], $entry["dz"], $comment);
			
			unset($obj_ref);
			
		}else{
			printf("%s: unable to identify this entry:\n%s\n\n", __FUNCTION__, json_encode($entry, JSON_UNESCAPED_SLASHES));
			exit(1);
		}
		
		if(in_array($itemKey, $g_adjustedItems)){
			printf("%s\n", ColorStr(sprintf("Warning: multiple adjustments for %s\n", $itemKey), 200, 200, 40));
		}else{
			$g_adjustedItems[] = $itemKey;
		}
		++$adjustCount;
	}
	printf("  %s\n", ColorStr(sprintf("Adjusted %4d objects from %s", $adjustCount, $csvPath), 160, 160, 160));
}

function &BuildContainedPuzzlesRefMap(&$jsonPuzzleDatabase){
	$refMap = [];
	foreach($jsonPuzzleDatabase->Exports[0]->Data as $parent_ref){
		if(!is_object($parent_ref) || $parent_ref->Name != "krakenIDToContainedPuzzleData"){
			continue;
		}
		foreach($parent_ref->Value as &$node_ref){
			$realNode_ref = &$node_ref[1]->Value;
			$rawGrid = [];
			foreach($realNode_ref as &$pair_ref){
				$rawGrid[$pair_ref->Name] = &$pair_ref->Value;
			}unset($pair_ref);
			unset($realNode_ref);
			if(!isset($rawGrid["Pid"])){
				printf("[WARNING] %s: value Pid not found for object: %s\n", __FUNCTION__, json_encode($node_ref, JSON_UNESCAPED_SLASHES));
				continue;
			}
			$pid = $rawGrid["Pid"];
			//$ptype = $rawGrid["PuzzleType"];
			//if($ptype != "logicGrid" || !isset($rawGrid["Solves"])){
			//	continue;
			//}
			$refMap[$pid] = $rawGrid;
		}unset($node_ref);
	}unset($parent_ref);

	ksort($refMap, SORT_NATURAL);
	return $refMap;
	
	//   fields:
	// Pid
	// Difficulty
	// Pdata
	// Serialized
	// Status
	// Zone
	// bOverride_Difficulty
	// bOverride_Pdata
	// bOverride_Serialized
	// bOverride_Solves
	// bOverride_Status
	// bOverride_Zone
}

function &BuildExternalStatusRefMap(&$jsonPuzzleDatabase){
	$refMap = [];
	foreach($jsonPuzzleDatabase->Exports[0]->Data as $parent_ref){
		if(!is_object($parent_ref) || $parent_ref->Name != "krakenIDToPuzzleStatus"){
			continue;
		}
		foreach($parent_ref->Value as &$node_ref){
			$refMap[$node_ref[0]->Value] = &$node_ref[1]->Value;
		}unset($node_ref);
	}unset($parent_ref);

	ksort($refMap, SORT_NATURAL);
	return $refMap;
}

function EmbedUassetPuzzleMetadataFrom($filePath, &$containedRefMap, &$externalStatusRefMap){
	if(!is_file($filePath)){
		printf("%s: file %s doesn't exist.\n", __FUNCTION__, $filePath);
		return FALSE;
	}
	$csv = LoadCsvMap($filePath, "pid", "\t");
	foreach($csv as $pid => $entry){
		if(empty($pid)){
			continue;
		}
		if(!isset($containedRefMap[$pid])){
			printf("[ERROR] %s: pid %d is not in the database!\n", __FUNCTION__, $pid);
			exit(1);
		}
		if(!isset($containedRefMap[$pid]["PuzzleType"])){
			printf("[ERROR] %s: pid %d has unknown puzzle type.\n", __FUNCTION__, $pid);
			exit(1);
		}
		$ptype = $containedRefMap[$pid]["PuzzleType"];
		switch($ptype){
			case "logicGrid":{
				if(isset($entry["pdata"]) && strlen($entry["pdata"]) > 0){
					// Warning: pdata must be changed first, as solvePath depends on it.
					if($containedRefMap[$pid]["Serialized"] != null){
						$containedRefMap[$pid]["Serialized"] = preg_replace("/(\\\"BinaryData\\\":\s*)\\\"(.*?)\\\",/", "\${1}\"" . $entry["pdata"] . "\",", $containedRefMap[$pid]["Serialized"]);
					}else{
						$containedRefMap[$pid]["Pdata"] = $entry["pdata"];
						$containedRefMap[$pid]["bOverride_Pdata"] = 1;
					}
					printf("  Accepted pdata %12s~ for pid %d\n", substr($entry["pdata"], 0, 10), $pid);
				}
				if(isset($entry["solvePath"]) && strlen($entry["solvePath"]) > 0 && preg_match("/^[\d\,: ]+$/", $entry["solvePath"])){
					$base64Str = ($containedRefMap[$pid]["Pdata"] ?? ((json_decode($containedRefMap[$pid]["Serialized"]))->BinaryData));
					$grid = GetGridBasics($base64Str);
					
					$hints = array_merge($grid->g[0], $grid->g[1]);
					//printf("Grid %d pre-hints: %s\n", $pid, implode("-", $hints));
					$solvePathXY = explode(",", preg_replace("/\s+/", "", $entry["solvePath"]));
					foreach($solvePathXY as $xy){
						list($x, $y) = explode(":", $xy);
						$hints[] = $x + $y * $grid->cn;
					}
					//printf("pid %d, solvePath: %s\n\n", $pid, implode("-", $hints));
					//$containedRefMap[$pid]["Solves"] = implode("-", $hints);
					
					$uassetHints = [];
					foreach($hints as $hint){
						$uassetHints[] = json_encode((object)[
							"\$type" => "UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI",
							"Value" => $hint,
						], JSON_UNESCAPED_SLASHES);
					}
					$finalStr = '[{"$type":"UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI","StructType":"KrakenSolvePath","SerializeNone":true,"Name":"Solves","Value":[{"$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI","Name":"bGoodhint","Value":1},{"$type":"UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI","ArrayType":"IntProperty","Name":"Hint","Value":[' .
					//json_encode($uassetHints, JSON_UNESCAPED_SLASHES) . 
					implode(",", $uassetHints) .
					']},{"$type":"UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI","Name":"Version","Value":1},{"$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI","Name":"bOverride_Goodhint","Value":1},{"$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI","Name":"bOverride_Hint","Value":1},{"$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI","Name":"bOverride_Version","Value":1}]}]';
					$finalSolves = json_decode($finalStr);
					//var_dump($finalStr, $finalSolves); exit(1);
					//printf("%d\n%s\n\n", $pid, $finalStr);
					
					$containedRefMap[$pid]["Solves"] = $finalSolves;
					$containedRefMap[$pid]["bOverride_Solves"] = 1;
					printf("  Accepted solvePath %9s for pid %d\n", sprintf("[%d]", count($hints)), $pid);
				}
				if(isset($entry["difficulty"]) && strlen($entry["difficulty"]) > 0){
					$containedRefMap[$pid]["Difficulty"] = intval($entry["difficulty"]);
					$containedRefMap[$pid]["bOverride_Difficulty"] = 1;
					printf("  Accepted difficulty %8d for pid %d\n", $entry["difficulty"], $pid);
				}
				if(isset($entry["status"]) && strlen($entry["status"]) > 0){
					$containedRefMap[$pid]["Status"] = $entry["status"];
					$containedRefMap[$pid]["bOverride_Status"] = 1;
					$externalStatusRefMap[$pid] = $entry["status"];
					if($containedRefMap[$pid]["Serialized"] != null){
						$containedRefMap[$pid]["Serialized"] = preg_replace("/(\\\"bLive\\\":\s*)(.*?),/", "\${1}true,", $containedRefMap[$pid]["Serialized"]);
					}
					printf("  Accepted status %12s for pid %d\n", $entry["status"], $pid);
				}
				if(isset($entry["zone"]) && strlen($entry["zone"]) > 0){
					if($containedRefMap[$pid]["Serialized"] != null){
						//$containedRefMap[$pid]["Zone"] = $entry["zone"];
						//$containedRefMap[$pid]["bOverride_Zone"] = 1;
						$containedRefMap[$pid]["Serialized"] = preg_replace("/(\\\"PoolName\\\":\s*)\\\"(.*?)\\\",/", "\${1}\"" . $entry["zone"] . "\",", $containedRefMap[$pid]["Serialized"]);
					}else{
						$containedRefMap[$pid]["Zone"] = $entry["zone"];
						$containedRefMap[$pid]["bOverride_Zone"] = 1;
					}
					printf("  Accepted zone %14s for pid %d\n", $entry["zone"], $pid);
				}
				break;
			}
			default:{
				printf("[WARNING] %s: I don't know how to embed metadata to puzzle type %s, pid %d\n", __FUNCTION__, $ptype, $pid);
				break;
			}
		}
	}
	return true;
}

function EnforceGridAssetFormat($pid, bool $doForceSerialized, &$containedRefMap){
	if(!isset($containedRefMap[$pid])){
		printf("[ERROR] %s: pid %d is not in containedRefMap.\n", __FUNCTION__, $pid);
		exit(1);
	}
	$node = &$containedRefMap[$pid];
	//static $a = false, $b = false;
	//if($containedRefMap[$pid]["Serialized"] != null && !$a){
	//	$a = true;
	//	unset($containedRefMap[$pid]["Solves"]);
	//	printf("%s\n\n", json_encode($containedRefMap[$pid], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	//}
	//if($containedRefMap[$pid]["Serialized"] == null && !$b){
	//	$b = true;
	//	unset($containedRefMap[$pid]["Solves"]);
	//	printf("%s\n\n", json_encode($containedRefMap[$pid], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	//}
	//if($a && $b){
	//	exit(1);
	//}
	if($doForceSerialized && $node["Serialized"] == null){
		// eh?
	}elseif(!$doForceSerialized && $node["Serialized"] != null){
		//unset($containedRefMap[$pid]["Solves"]);
		//printf("%s\n\n", json_encode($containedRefMap[$pid], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$miniJson = json_decode($node["Serialized"]);
		$node["Serialized"] = null;
		$node["bOverride_Serialized"] = 0;
		$node["Pdata"] = $miniJson->BinaryData;
		$node["bOverride_Pdata"] = 1;
		if(isset($miniJson->PoolName) && strlen($miniJson->PoolName) > 0){
			$node["Zone"] = $miniJson->PoolName;
			$node["bOverride_Zone"] = 1;
		}
		//printf("%s\n\n", json_encode($containedRefMap[$pid], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
	unset($node);
}
	
//function EmbedUassetSolvePaths(&$containedRefMap, $filePath){
//	if(!is_file($filePath)){
//		printf("%s: file %s doesn't exist.\n", __FUNCTION__, $filePath);
//		return FALSE;
//	}
//	$lines = file($filePath);
//	if(empty($lines)){
//		printf("%s: failed to read %s.\n", __FUNCTION__, $filePath);
//		return FALSE;
//	}
//	foreach($lines as $line){
//		if(!preg_match("/^(\d+)\t([\d ,:]+)$/", trim($line), $matches)){
//			continue;
//		}
//		$pid = (int)$matches[1];
//		$solvePathXY = explode(",", preg_replace("/\s+/", "", $matches[2]));
//		if(!isset($containedRefMap[$pid])){
//			printf("Warning: skipping solve path embed for pid %d - not found in refmap.\n", $pid);
//			continue;
//		}
//		//printf("Embedding |%d| -> |%s|\n", $pid, implode("-", $solvePathXY));
//	}
//	return true;
//}

function SaveUassetSubNodeAs($path, $node){
	if(is_array($node)){
		ksort($node, SORT_NATURAL);
	}
	$text = print_r($node, true);
	$text = str_replace(["=> +0\n", "=> -0\n", "=> \n"], "=> 0\n", $text);
	WriteFileSafe($path, $text);
}

function SaveReadableUassetDataTo($folderReadables, &$puzzleDatabase, &$sandboxZones){
	
	$copyPuzzleDatabase = unserialize(serialize($puzzleDatabase));
	$copySandboxZones   = unserialize(serialize($sandboxZones));
	
	$folderReadables = asDir($folderReadables);
	
	// Remove "Solves" nodes from logic grids as they take up tons of lines but aren't interesting usually.
	foreach($copyPuzzleDatabase->krakenIDToContainedPuzzleData as $pid => &$arr_ref){
		unset($arr_ref["Solves"]);
	}unset($arr_ref);

	// Unserialize fields for some puzzle types.
	foreach($copyPuzzleDatabase->krakenIDToContainedPuzzleData as $pid => &$arr_ref){
		if(isset($arr_ref["Serialized"])){
			$arr_ref["Serialized"] = json_decode($arr_ref["Serialized"]);
		}
	}unset($arr_ref);
	
	// For reasons I don't care to remember the (now disabled and boring puzzle bounds) have float precision noise issues.
	foreach($copySandboxZones->Containers as $localID => &$container_ref){
		unset($container_ref->puzzleBoundsTransforms);
		unset($container_ref->puzzleBoundsBoxes);
	}unset($container_ref);
	
	// All "world" puzzles are defined as serialized strings - let's decode them for readability.
	foreach($copyPuzzleDatabase->krakenIDToWorldPuzzleData as $pid => $serialized){
		$copyPuzzleDatabase->krakenIDToWorldPuzzleData[$pid] = json_decode($serialized);
	}
	
	// All containers have a serializedString value, let's decode it for readability too.
	foreach($copySandboxZones->Containers as $localID => $obj){
		if(isset($obj->serializedString)){
			$copySandboxZones->Containers[$localID]->serializedString = json_decode($obj->serializedString);
		}
	}
	
	printf("Writing human-readable data to %s...\n", $folderReadables);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_zoneToSandboxProgressionRelevant.json", $copyPuzzleDatabase->zoneToSandboxProgressionRelevant);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_puzzleZoneToGroupNumOverrides.json",    $copyPuzzleDatabase->puzzleZoneToGroupNumOverrides);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_krakenIDToWorldPuzzleData.json",        $copyPuzzleDatabase->krakenIDToWorldPuzzleData);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_krakenIDToContainedPuzzleData.json",    $copyPuzzleDatabase->krakenIDToContainedPuzzleData);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_krakenIDToKrakenIDOnlyPuzzleData.json", $copyPuzzleDatabase->krakenIDToKrakenIDOnlyPuzzleData);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_krakenIDToPuzzleStatus.json",           $copyPuzzleDatabase->krakenIDToPuzzleStatus);
	SaveUassetSubNodeAs($folderReadables . "PuzzleDatabase_krakenIDToPuzzleData.json",             $copyPuzzleDatabase->krakenIDToPuzzleData);
	SaveUassetSubNodeAs($folderReadables . "SandboxZones_ZoneDefs.json",                           $copySandboxZones->ZoneDefs);
	SaveUassetSubNodeAs($folderReadables . "SandboxZones_Bounds.json",                             $copySandboxZones->Bounds);
	SaveUassetSubNodeAs($folderReadables . "SandboxZones_SandboxRegions.json",                     $copySandboxZones->SandboxRegions);
	SaveUassetSubNodeAs($folderReadables . "SandboxZones_Containers.json",                         $copySandboxZones->Containers);
	SaveUassetSubNodeAs($folderReadables . "SandboxZones_Billboard.json",                          $copySandboxZones->Billboard);
}

function GenerateRuneObject(array $options = []){
	$defaultOptions = [
		"myIndex"     => 0,
		"myName"      => "NewTestRune",
		"pool"        => "testpool",
		"forcedPid"   => 0,
		"parentIndex" => 0,
		"originX"     => 9000,
		"originY"     => 5000,
		"originZ"     => 25000,
		"localID"     => "BP_RuneAnimated_C MyTestRune",
	];
	$options = array_merge($defaultOptions, $options); // don't merge recursively
	
	$myIndex     = $options["myIndex"];
	$myName      = $options["myName"];
	$pool        = $options["pool"];
	$forcedPid   = $options["forcedPid"];
	$parentIndex = $options["parentIndex"];
	$originX     = (float)$options["originX"];
	$originY     = (float)$options["originY"];
	$originZ     = (float)$options["originZ"];
	$localID     = $options["localID"];
	
	$serializedObj = (object)[
		"AdditionalBinaryData" => "\\u0006\\u0001\\u0001\\u0001Opof\\u0001\\u0001\\u0001\\u0001\\u0001",
		"PuzzleClass" => "BP_RuneAnimated_C",
		"SpawnBehaviour" => 0,
		"SolveValue" => 0,
		"CanAwardAutoQuest" => true,
		"Disabled" => false,
		"AwakenIfAlwaysSpawn" => false,
		"PuzzleType" => "logicGrid",
		"LocalID" => $localID,
		"ActorTransform" => sprintf("%.0f,%.0f,%.0f|0.000000,0.000000,0.000000|1.000000,1.000000,1.000000", $originX, $originY, $originZ),
		"Zone" => 2, // eh
		"Map" => "BetaCampaign",
		"SERIALIZEDSUBCOMP_PuzzleBounds-0" => (object)[
			"RelativeTransform" => "0.000000,0.000000,130.000000|0.000000,0.000000,0.000000|2.000000,2.000000,2.000000",
			"bUseForDungeonIdentification" => true,
			"acceptAllByDefault" => true,
			"bBlockSpawning" => false,
			"acceptedTypes" => "",
			//"rejectedTypes" => "",
			"WorldTransform" => sprintf("%.2f,%.2f,%.2f|0.000000,0.000000,0.000000|2.000000,2.000000,2.000000", $originX, $originY, $originZ),
			"Box" => sprintf("Min=X=%.2f Y=%.2f Z=%.2f|Max=X=%.2f Y=%.2f Z=%.2f", $originX - 1, $originY - 1, $originZ - 1, $originX + 1, $originY + 1, $originZ + 1),
		],
		"desiredKrakenIDOverride" => $forcedPid,
		"desiredDirectPuzzleRef" => "",
		"desiredPuzzlePool" => $pool,
		"bTutorialMode" => false,
		"puzzleTutorialText" => "",
		"bIsHintTutorial" => false,
		"bShouldHideExtraGridButtons" => false,
		"finalPuzzle" => false,
		"nodeBindForbidden" => false,
		"enableHintSystem" => false,
		"wipeType" => 255,
		"wipeTime" => 2,
		"wipeThickness" => 1,
		"gridType" => 0,
		"randomizeGrid" => false,
		"bIsDoppel" => false,
	];
	if($forcedPid <= 0){
		unset($serializedObj->desiredKrakenIDOverride);
	}
	$mainObj = (object)[
		"\$type" => "UAssetAPI.ExportTypes.NormalExport, UAssetAPI",
		"\$index" => $myIndex,
		"ObjectName" => $myName,
		"ObjectFlags" => "RF_NoFlags",
		"SerialSize" => 2500,
		"SerialOffset" => 13105047,
		"ScriptSerializationStartOffset" => 0,
		"ScriptSerializationEndOffset" => 0,
		"bForcedExport" => false,
		"bNotForClient" => false,
		"bNotForServer" => false,
		"PackageGuid" => "{00000000-0000-0000-0000-000000000000}",
		"IsInheritedInstance" => false,
		"PackageFlags" => "PKG_None",
		"bNotAlwaysLoadedForEditorGame" => true,
		"bIsAsset" => false,
		"GeneratePublicHash" => false,
		"SerializationBeforeSerializationDependencies" => [],
		"CreateBeforeSerializationDependencies" => [-19],
		"SerializationBeforeCreateDependencies" => [-79,-275],
		"CreateBeforeCreateDependencies" => [$parentIndex],
		"PublicExportHash" => 0,
		"Padding" => null,
		"Extras" => (object)[ "\$type" => "System.Byte[], System.Private.CoreLib", "\$value" => "AAAAAA==" ],
		"OuterIndex" => $parentIndex,
		"ClassIndex" => -79,
		"SuperIndex" => 0,
		"TemplateIndex" => -275,
		"Data" => [
			(object)[
				"\$type" => "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI",
				"ArrayType" => "StrProperty",
				"Name" => "possiblePuzzleTypes",
				"Value" => [
					(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Value" => "completeThePattern" ],
					(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Value" => "memoryGrid" ],
					(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Value" => "musicGrid" ],
					(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Value" => "logicGrid" ]
				]
			],
			(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.IntPropertyData, UAssetAPI", "Name" => "desiredKrakenIDOverride", "Value" => $forcedPid ],
			(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Name" => "desiredPuzzlePool", "Value" => $pool ],
			(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI", "Name" => "localID", "Value" => $localID ],
			(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI", "Name" => "containerPuzzleClass", "Value" => -19 ],
			(object)[
				"\$type" => "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI",
				"ArrayType" => "StructProperty",
				"Name" => "puzzleBoundsTransforms",
				"Value" => [
					(object)[
						"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
						"StructType" => "Transform",
						"SerializeNone" => true,
						"Name" => "puzzleBoundsTransforms",
						"Value" => [
							(object)[
								"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
								"StructType" => "Quat",
								"SerializeNone" => true,
								"Name" => "Rotation",
								"Value" => [
									(object)[
										"\$type" => "UAssetAPI.PropertyTypes.Structs.QuatPropertyData, UAssetAPI",
										"Name" => "Rotation",
										"Value" => (object)[
											"\$type" => "UAssetAPI.UnrealTypes.FQuat, UAssetAPI",
											"X" => "+0",
											"Y" => "-0",
											"Z" => "+0",
											"W" => 1
										]
									]
								]
							],
							(object)[
								"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
								"StructType" => "Vector",
								"SerializeNone" => true,
								"Name" => "Translation",
								"Value" => [
									(object)[
										"\$type" => "UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI",
										"Name" => "Translation",
										"Value" => (object)[
											"\$type" => "UAssetAPI.UnrealTypes.FVector, UAssetAPI",
											"X" => $originX,
											"Y" => $originY,
											"Z" => $originZ
										]
									]
								]
							],
							(object)[
								"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
								"StructType" => "Vector",
								"SerializeNone" => true,
								"Name" => "Scale3D",
								"Value" => [
									(object)[
										"\$type" => "UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI",
										"Name" => "Scale3D",
										"Value" => (object)[
											"\$type" => "UAssetAPI.UnrealTypes.FVector, UAssetAPI",
											"X" => 2,
											"Y" => 2,
											"Z" => 2
										]
									]
								]
							]
						]
					]
				]
			],
			(object)[
				"\$type" => "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI",
				"ArrayType" => "StructProperty",
				"Name" => "puzzleBoundsBoxes",
				"Value" => [
					(object)[
						"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
						"StructType" => "Box",
						"SerializeNone" => true,
						"Name" => "puzzleBoundsBoxes",
						"Value" => [
							(object)[
								"\$type" => "UAssetAPI.PropertyTypes.Structs.BoxPropertyData, UAssetAPI",
								"Name" => "puzzleBoundsBoxes",
								"Value" => (object)[
									"\$type" => "UAssetAPI.UnrealTypes.TBox`1[[UAssetAPI.UnrealTypes.FVector, UAssetAPI]], UAssetAPI",
									"Min" => (object)[
										"\$type" => "UAssetAPI.UnrealTypes.FVector, UAssetAPI",
										"X" => $originX - 1,
										"Y" => $originY - 1,
										"Z" => $originZ - 1
									],
									"Max" => (object)[
										"\$type" => "UAssetAPI.UnrealTypes.FVector, UAssetAPI",
										"X" => $originX + 1,
										"Y" => $originY + 1,
										"Z" => $originZ + 1
									],
									"IsValid" => 1
								]
							]
						]
					]
				]
			],
			(object)[
				"\$type" => "UAssetAPI.PropertyTypes.Objects.ArrayPropertyData, UAssetAPI",
				"ArrayType" => "StructProperty",
				"Name" => "puzzleBoundsInfos",
				"Value" => [
					(object)[
						"\$type" => "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",
						"StructType" => "PuzzleBoundsInfo",
						"SerializeNone" => true,
						"Name" => "puzzleBoundsInfos",
						"Value" => [
							(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.SetPropertyData, UAssetAPI", "ArrayType" => "StrProperty", "DummyStruct" => null, "Name" => "acceptedTypes", "Value" => [] ],
							(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI", "Name" => "acceptAllByDefault", "Value" => 0 ],
							(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.SetPropertyData, UAssetAPI", "ArrayType" => "StrProperty", "DummyStruct" => null, "Name" => "rejectedTypes", "Value" => [] ],
						]
					]
				]
			],
			(object)[
				"\$type" => "UAssetAPI.PropertyTypes.Objects.StrPropertyData, UAssetAPI",
				"Name" => "serializedString",
				"Value" => str_replace("\\\\", "\\", json_encode($serializedObj, JSON_UNESCAPED_SLASHES)),
			],
			// ???
			(object)[ "\$type" => "UAssetAPI.PropertyTypes.Objects.EnumPropertyData, UAssetAPI", "EnumType" => "EMainMapZoneName", "InnerType" => null, "Name" => "ownerZone", "Value" => "EMainMapZoneName::Egypt" ],
		]
	];
	if($forcedPid <= 0){
		foreach($mainObj->Data as $index => &$node_ref){
			if($node_ref->Name == "desiredKrakenIDOverride"){
				//printf("Removing %s\n\n", json_encode($mainObj->Data[$index]));
				unset($mainObj->Data[$index]);
				$mainObj->Data = array_values($mainObj->Data);
				break;
			}
		}unset($node_ref);
		//printf("%s\n\n", json_encode($mainObj, JSON_PRETTY_PRINT));
	}
	return $mainObj;
}

function CreateClusterRunes(&$jsonSandboxZones, array $options = []){
	$defaultOptions = [
		"clusterName" => "testcluster",
		//"parentName" => "EgyptZone1",
		"runeCount" => 5,
		"originX" => 9000,
		"originY" => 5000,
		"originZ" => 25000,
		"localIDbase" => "TestCluster",
	];
	$options = array_merge($defaultOptions, $options); // don't merge recursively
	
	$clusterName  = $options["clusterName"];
	//$parentName   = $options["parentName"];
	$runeCount    = max(1, (int)$options["runeCount"]);
	$originX      = (float)$options["originX"];
	$originY      = (float)$options["originY"];
	$originZ      = (float)$options["originZ"];
	$localIDbase  = $options["localIDbase"];
	
	//$parentName = "EgyptZone1";
	//$parent = &FetchObjectByName($jsonSandboxZones, $parentName);
	//if(empty($parent)){
	//	printf("[ERROR] %s: failed to find parent object %s\n", __FUNCTION__, $parentName);
	//	exit(1);
	//}
	//$parentIndex = $parent->{'$index'};
	
	$egyptZoneParent = &FetchObjectByName($jsonSandboxZones, "EgyptZone1");
	$miscZoneParent = &FetchObjectByName($jsonSandboxZones, "MiscZone1");
	
	$createDependencyField = &$miscZoneParent->CreateBeforeSerializationDependencies ?? null;
	$alwaysSpawnField = &FetchObjectField($miscZoneParent, "alwaysSpawnContainerPuzzlesToBeSpawned");
	if(empty($createDependencyField) || empty($alwaysSpawnField)){
		printf("[ERROR] %s: miscZoneParent object is missing CreateBeforeSerializationDependencies / alwaysSpawnContainerPuzzlesToBeSpawned\n", __FUNCTION__);
		exit(1);
	}
	//printf("%s\n", json_encode($createDependencyField));
	//printf("%s\n", json_encode($alwaysSpawnField));
	//exit(1);
	
	$refs = [];
	for($r = 0; $r < $runeCount; ++$r){
		//$localID = sprintf("%s_r%02d", $localIDbase, $r);
		//$myName = sprintf("ClusterRune_%s_r%02d", $clusterName, $r);
		
		$localID = sprintf("%s%02d", $localIDbase, $r);
		$myIndex = count($jsonSandboxZones->Exports) + 1;
		//$myName = sprintf("SpawnedPuzzleContainer_9%03d", $r);
		//$myName = "ClusterRune_" . $clusterName . "_spawn" . $myIndex;
		$myName = "ClusterRune_" . $clusterName . "_spawn" . ($r + 1);
		
		$obj = GenerateRuneObject([
			"myIndex"     => $myIndex,
			"myName"      => $myName,
			"pool"        => $clusterName,
			"forcedPid"   => 0,
			"parentIndex" => $egyptZoneParent->{'$index'},
			"originX"     => $originX - ($r) * 80,
			"originY"     => $originY,
			"originZ"     => $originZ,
			"localID"     => $localID,
		]);
		
		// Append this new object to exports.
		$jsonSandboxZones->Exports[] = $obj;
		$refs[] = &$jsonSandboxZones->Exports[count($jsonSandboxZones->Exports) - 1];
		
		// Put its name in the NameMap.
		if(!in_array($myName, $jsonSandboxZones->NameMap)){
			$jsonSandboxZones->NameMap[] = $myName;
		}
		
		// Add it to dependency list.
		$createDependencyField[] = $myIndex;
		
		// Add it to "always spawn these runes" list.
		$alwaysSpawnField[] = (object)[
			"\$type" => "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI",
			"Value" => $myIndex,
		];
		
		printf("Generated cluster rune %s (cluster %s, myIndex %d, misc parent %s, zone parent %s).\n", $myName, $clusterName, $myIndex, $miscZoneParent->ObjectName, $egyptZoneParent->ObjectName);
	}
	return $refs;
}

function CreateStaticRune(&$jsonSandboxZones, array $options = []){
	$defaultOptions = [
		//"parentName" => "MiscZone1",
		"objName" => "",
		"forcedPid" => 292,
		"originX" => 9000,
		"originY" => 5000,
		"originZ" => 25000,
		"localID" => "BP_RuneAnimated_C MyTestRune",
	];
	$options = array_merge($defaultOptions, $options); // don't merge recursively
	
	//$parentName = $options["parentName"];
	$forcedPid  = $options["forcedPid"];
	$originX    = (float)$options["originX"];
	$originY    = (float)$options["originY"];
	$originZ    = (float)$options["originZ"];
	$localID    = $options["localID"];
	$objName    = (empty($options["objName"]) ? "StaticRune_pid" . $forcedPid : $options["objName"]);
	
	//$parent = &FetchObjectByName($jsonSandboxZones, $parentName);
	//if(empty($parent)){
	//	printf("[ERROR] %s: failed to find parent object %s\n", __FUNCTION__, $parentName);
	//	exit(1);
	//}
	//$parentIndex = $parent->{'$index'};
	
	$egyptZoneParent = &FetchObjectByName($jsonSandboxZones, "EgyptZone1");
	$miscZoneParent = &FetchObjectByName($jsonSandboxZones, "MiscZone1");
	
	$createDependencyField = &$miscZoneParent->CreateBeforeSerializationDependencies ?? null;
	$alwaysSpawnField = &FetchObjectField($miscZoneParent, "alwaysSpawnContainerPuzzlesToBeSpawned");
	if(empty($createDependencyField) || empty($alwaysSpawnField)){
		printf("[ERROR] %s: miscZoneParent object is missing CreateBeforeSerializationDependencies / alwaysSpawnContainerPuzzlesToBeSpawned\n", __FUNCTION__);
		exit(1);
	}
	
	$myIndex = count($jsonSandboxZones->Exports) + 1;
	$myName = $objName;
	
	$obj = GenerateRuneObject([
		"myIndex"     => $myIndex,
		"myName"      => $myName,
		"pool"        => "live",
		"forcedPid"   => $forcedPid,
		"parentIndex" => $egyptZoneParent->{'$index'},
		"originX"     => $originX,
		"originY"     => $originY,
		"originZ"     => $originZ,
		"localID"     => $localID,
	]);
	
	// Append this new object to exports.
	$jsonSandboxZones->Exports[] = $obj;
	
	// Put its name in the NameMap.
	if(!in_array($myName, $jsonSandboxZones->NameMap)){
		$jsonSandboxZones->NameMap[] = $myName;
	}
	
	// Add it to dependency list.
	$createDependencyField[] = $myIndex;
	
	// Add it to "always spawn these runes" list.
	$alwaysSpawnField[] = (object)[
		"\$type" => "UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI",
		"Value" => $myIndex,
	];
	
	printf("Generated static rune %s / pid %d (localID |%s|, myIndex %d, misc parent %s, zone parent %s).\n", $myName, $forcedPid, $localID, $myIndex, $miscZoneParent->ObjectName, $egyptZoneParent->ObjectName);
	
	$ref = &$jsonSandboxZones->Exports[count($jsonSandboxZones->Exports) - 1];
	return $ref;
}

