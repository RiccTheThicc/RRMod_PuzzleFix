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
		        preg_match("/^SpawnedPuzzleContainer_([\d_]+)$/",  $objName, $matches)){
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
			unset($miniJson->acceptedTypes);
			$miniJson->rejectedTypes = [];
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
			unset($miniJson->acceptedTypes);
			$miniJson->rejectedTypes = [];
		}
	}
	$ser = json_encode($miniJson, JSON_UNESCAPED_SLASHES);
}


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

function AdjustAssetCoordinates(&$puzzleDatabase, &$sandboxZones, string $csvPath){
	$adjuster = LoadCsv($csvPath);

	foreach($adjuster as $entry){
		//localID,pid,fields,dx,dy,dz,comment
		$localID   = $entry["localID"];
		$pid       = $entry["pid"];
		$fieldList = explode("|", $entry["fields"]);
		$comment   = (empty($entry["comment"]) ? "nocomment" : $entry["comment"]);
		
		if(is_numeric($pid) && $pid > 0){
			// We assume it's a puzzle. So we look for it in the puzzleDatabase.
			// Not that we only check the World (fixed-coordinate) puzzles. Can't move contained ones anyway.
			if(!isset($puzzleDatabase->krakenIDToWorldPuzzleData[$pid])){
				printf("%s: pid %d not found in puzzleDatabase, failed replacing:\n%s\n", __FUNCTION__, $pid, json_encode($entry, JSON_UNESCAPED_SLASHES));
				exit(1);
			}
			$ser_ref = &$puzzleDatabase->krakenIDToWorldPuzzleData[$pid];
			$ptype = json_decode($ser_ref)->PuzzleType;
			
			//printf("\n%s\n", addslashes($ser_ref));
			
			foreach($fieldList as $fieldName){
				// Here we *can* decode the json, change the data, and re-encode it back.
				// However, I really do not feel like using something like:
				// $miniJson->{'DuplicatedObjectOfType-Seek5HiddenObject'}->{'DuplicateTransform-1'}
				// Let alone define this in the initial .csv. So we'll go with regex instead.
				//$miniJson = json_decode($puzzleDatabase->krakenIDToWorldPuzzleData[$pid]);
				//var_dump($miniJson);
				if(!preg_match("/\\\"" . $fieldName . "\\\"\s*\:\s*\\\"([\d\.\-\+\|,]+)\\\"/", $ser_ref, $matches)){
					printf("%s: preg_match for field %s failed, serialized string and request:\n%s\n%s\n", __FUNCTION__, addslashes($ser_ref), json_encode($entry, JSON_UNESCAPED_SLASHES));
					exit(1);
				}
				//printf("%5d |%s| = |%s|\n", $pid, $fieldName, $matches[1]);
				$str = $matches[1];
				$t = UeTransformUnpack($str);
				$t->Translation->X += $entry["dx"];
				$t->Translation->Y += $entry["dy"];
				$t->Translation->Z += $entry["dz"];
				$t->Rotation->X += $entry["dpitch"];
				$t->Rotation->Y += $entry["dyaw"];
				$t->Rotation->Z += $entry["droll"];
				UeTransformPackInto($t, $str);
				
				$ser_ref = preg_replace("/(\\\"" . $fieldName . "\\\"\s*\:\s*\\\")([\d\.\-\+\|,]+)(\\\")/", '${1}' . $str . '${3}', $ser_ref);
			}
			//printf("%s: puzzle %-18s %5d adjusted (%5.0f,%5.0f,%5.0f) <%s>\n", __FUNCTION__, $ptype, $pid, $entry["dx"], $entry["dy"], $entry["dz"], $comment);
			unset($ser_ref);
			
		}elseif(isset($sandboxZones->Containers[$localID])){
			// SandboxZones-defined object.
			$obj_ref = &$sandboxZones->Containers[$localID];
			
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
						$t->Rotation->X += $entry["dpitch"];
						$t->Rotation->Y += $entry["dyaw"];
						$t->Rotation->Z += $entry["droll"];
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
					$t->Rotation->X += $entry["dpitch"];
					$t->Rotation->Y += $entry["dyaw"];
					$t->Rotation->Z += $entry["droll"];
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
			printf("%s: unable to identify this entry:\n", __FUNCTION__, json_encode($entry, JSON_UNESCAPED_SLASHES));
			exit(1);
		}
	}
}

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
