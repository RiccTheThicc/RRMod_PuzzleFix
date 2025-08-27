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

$pathCamphorBase = "..\\BaseJsons\\CamphorCorridorTemple.json";
$pathCamphorOut  = "..\\OutputJsons\\CamphorCorridorTemple.json";
$pathCamphorTemp = "..\\OutputJsons\\CamphorCorridorTemple_edittemp.json";

// AutoExposureMaxBrightness: A good value should be positive (2 is a good value).
//   This is the maximum brightness the auto exposure can adapt to.
//   It should be tweaked in a bright lighting situation
//   (too small: image appears too bright, too large: image appears too dark).

$postProcessVolumeSettings = [
	"ColorSaturation"			=> 1.1,
	"FilmSlope"					=> 1,
	"BloomIntensity"			=> 2.0,
	"AutoExposureMinBrightness"	=> 3.5,
	"AutoExposureMaxBrightness"	=> 3.5,
	
	//	"ColorSaturation"			=> 1		,	// default: 1    	// camphor: 1.2
	//	"FilmSlope"					=> 0.88		,	// default: 0.88 	// camphor: 1
	//	"BloomIntensity"			=> 0.675	,	// default: 0.675	// camphor: 2
	//	"AutoExposureMinBrightness"	=> 0.03		,	// default: 0.03 	// camphor: 8.5
	//	"AutoExposureMaxBrightness"	=> 8		,	// default: 8    	// camphor: 8.5
];

$meshCloneMap = [
	//[ "Beam_1_D_low2", 1 ],
	//[ "Wallmesh22_111", 1 ],
	//[ "Wallmesh22_107", 1 ],
	
	//[ "Beam_1_low120", 1 ],
	//[ "Beam_1_low105", 1 ],
	//[ "Beam_1_low109", 1 ],
	
	//[ "Plane16_2", 2 ],
	//[ "Beam_1_low322", 1 ],
	//[ "ruins_3_low_ruins_3_low30", 1 ],
	
	//[ "Beam_1_low322", 1 ],
	//[ "Wallmesh21_70", 1 ],
	//[ "S_Column_2_low_Column_11", 1 ],
	
	//[ "Beam_1_D_low5", 1 ],
	//[ "Wallmesh11_92", 1 ],
	
	// august 2025
	//[ "Beam_1_low101", 1 ],
	//[ "Beam_1_low120", 1 ],
	//[ "Beam_1_low320", 1 ],
	//[ "Beam_1_low120", 1 ],
	//[ "Beam_1_low320", 1 ],
	//[ "ruins_3_low_ruins_3_low30", 1 ],
];

$adjustMeshesFrom = [
	//"_adjusterLogs\\adjust_camphor - shell.csv",
	//"_adjusterLogs\\adjust_camphor - scaffolding.csv",
	//"_adjusterLogs\\adjust_camphor - restore smallroom gates.csv",
	
	//"_adjusterLogs\\adjust_camphor - 56.csv",
	//"_adjusterLogs\\adjust_camphor - 57.csv",
	//"_adjusterLogs\\adjust_camphor - 58.csv",
	//"_adjusterLogs\\adjust_camphor - 59 moved east archway.csv",
	//"_adjusterLogs\\adjust_camphor - 60.csv",
	//"_adjusterLogs\\adjust_camphor - 61.csv",
	//"_adjusterLogs\\adjust_camphor - 62.csv",
	//"_adjusterLogs\\adjust_camphor - 63.csv",
	//"_adjusterLogs\\adjust_camphor - 64.csv",
	
	//"_adjusterLogs\\adjust_camphor - 65.csv",
	//"_adjusterLogs\\adjust_camphor - 66.csv",
	//"_adjusterLogs\\adjust_camphor - 67.csv",
	//"_adjusterLogs\\adjust_camphor - 68.csv",
	//"_adjusterLogs\\adjust_camphor - 69.csv",
	//"_adjusterLogs\\adjust_camphor - 70.csv",
	//"_adjusterLogs\\adjust_camphor - 71.csv",
	//"_adjusterLogs\\adjust_camphor - 72.csv",
	//"_adjusterLogs\\adjust_camphor - 73.csv",
	//"_adjusterLogs\\adjust_camphor - 74.csv",
	//"_adjusterLogs\\adjust_camphor - 75.csv",
	//"_adjusterLogs\\adjust_camphor - 76.csv",
	//"_adjusterLogs\\adjust_camphor - 77.csv",
	
	// august 2025
	//"_adjusterLogs\\adjust_camphor - 78.csv",
	//"_adjusterLogs\\adjust_camphor - 79.csv",
	//"_adjusterLogs\\adjust_camphor - 80.csv",
	//"_adjusterLogs\\adjust_camphor - 81.csv",
];

// Define tags to include/exclude from Actors.
$onlyIncludeTags = [];
$excludeTags = [];

//$onlyIncludeTags[] = "shell";
$excludeTags[] = "disabled";
$excludeTags[] = "scaffolding";

// Scan file.
$mainJson = LoadDecodedUasset($pathCamphorBase);
$exports = &$mainJson->Exports;

$indexToExport = [];
$objectNameToIndex = [];
$objectNameMaxCounter = [];
$persistentLevel = null;
$lodActor = null;
$highestSerialOffset = 0;

$doDisableLightingChannels = false;
if($doDisableLightingChannels){
	printf("[WARNING] Deleteting |LightingChannels| of all exports.\n");
}

foreach($exports as $ii => &$export_ref){
	$realIndex = $ii + 1;
	$indexStr = $export_ref->{'$index'};
	$indexToExport[$indexStr] = $export_ref;
	$objectName = "";
	if(isset($export_ref->ObjectName)){
		$objectName = $export_ref->ObjectName;
	}
	
	// Memorize where some of these objects are.
	if(!empty($objectName)){
		if($objectName == "PersistentLevel"){
			$persistentLevel = &$export_ref;
		//}elseif($objectName == "LODActor_96"){
		}elseif(preg_match("/LODActor/i", $objectName)){
			$lodActor = &$export_ref;
		}
		if(!isset($objectNameToIndex[$objectName])){
			$objectNameToIndex[$objectName] = [];
		}
		$objectNameToIndex[$objectName][] = $indexStr;
		if(preg_match('/^(.*?)(\d+)$/', $objectName, $matches)){
			list($fullName, $prefix, $num) = $matches;
			if(!isset($objectNameMaxCounter[$prefix])){
				$objectNameMaxCounter[$prefix] = 0;
			}
			$objectNameMaxCounter[$prefix] = max($objectNameMaxCounter[$prefix], intval($num));
		}
	}
	
	// Sanity check for serial offsets.
	if(isset($export_ref->SerialOffset)){
		static $isBadOffsetOrderDetected = false;
		if($export_ref->SerialOffset < $highestSerialOffset && !$isBadOffsetOrderDetected){
			$isBadOffsetOrderDetected = true;
			printf("[WARNING] SerialOffset order seems wrong: %d for %s, previously seen %d already.\n", $export_ref->SerialOffset, $indexStr, $highestSerialOffset);
		}
		$highestSerialOffset = max($highestSerialOffset, $export_ref->SerialOffset);
	}
}unset($export_ref);
//printf("%s\n", json_encode($objectNameToIndex, 0xc0)); //JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$objectNameToIndex = array_map(function($y) { return $y[0]; }, array_filter($objectNameToIndex, function($x) { return (count($x) == 1); }));
//printf("%s\n", json_encode($objectNameToIndex, 0xc0)); //JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

//printf("%s\n", json_encode($objectNameMaxCounter, 0xc0)); exit(1);

// Create mesh clones.
foreach($meshCloneMap as $cloneEntry){
	list($objectName, $cloneCount) = $cloneEntry;
	//printf("Cloning |%s| %d times...\n", $objectName, $cloneCount);
	if(!isset($objectNameToIndex[$objectName])){
		printf("[ERROR] Failed to clone object %s - no such object found.\n", $objectName);
		exit(1); //continue;
	}
	$indexStr = $objectNameToIndex[$objectName];
	$actorExport = $indexToExport[$indexStr];
	$meshIndexStr = FetchObjectField($actorExport, "StaticMeshComponent");
	if(empty($meshIndexStr)){
		printf("[ERROR] %s.\n", $indexStr);
		exit(1); //continue;
	}
	$meshExport = $indexToExport[$meshIndexStr];
	//printf("%s\n%s\n", $actorExport->{'$index'}, $meshExport->{'$index'});
	//$meshExport->Z -= 100000;
	
	$strActor = json_encode($actorExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	$strMesh  = json_encode($meshExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
	for($cloneNumber = 0; $cloneNumber < $cloneCount; ++$cloneNumber){
		$newActorRealIndex = count($exports) + 1;
		$newMeshRealIndex  = count($exports) + 2;
		
		if(!preg_match('/^(.*?)(\d+)$/', $objectName, $matches)){
			printf("[ERROR] Can't autoincrement object %s - not implemented.\n", $objectName);
			exit(1);
		}
		
		list($fullName, $prefix, $num) = $matches;
		$actorNewSuffix = ++$objectNameMaxCounter[$prefix];
		$actorNewName = $prefix . $actorNewSuffix;
		$actorNewIndex = sprintf("%%i_%d_%s", $newActorRealIndex, $actorNewName);
		//printf("%s\n", $actorNewIndex);
		
		$meshNewName = "StaticMeshComponent0";
		$meshNewIndex = sprintf("%%i_%d_%s", $newMeshRealIndex, $meshNewName);
		
		$newActorJson = json_decode(str_replace([
			'"' . $indexStr .      '"', '"' . $objectName .   '"', '"' . $meshIndexStr . '"' ], [
			'"' . $actorNewIndex . '"',	'"' . $actorNewName . '"', '"' . $meshNewIndex . '"' ], $strActor));
		$newMeshJson = json_decode(str_replace([
			'"' . $indexStr .      '"', '"' . $objectName .   '"', '"' . $meshIndexStr . '"' ], [
			'"' . $actorNewIndex . '"',	'"' . $actorNewName . '"', '"' . $meshNewIndex . '"' ], $strMesh));
		
		//printf("%s\n", json_encode($mesh, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		//printf("ORIGINAL ACTOR:\n%s\nNEW ACTOR:\n%s\n\n", $strActor, json_encode($newActorJson, 0xc0));
		
		$newActorJson->SerialOffset = $highestSerialOffset;
		$newMeshJson->SerialOffset  = $highestSerialOffset;
		
		//printf("ORIGINAL ACTOR:\n%s\nNEW ACTOR:\n%s\n\n", $strActor, json_encode($newActorJson, 0xc0));
		$nameMapEntry = $actorNewName;
		if(str_ends_with($prefix, "_")){
			$nameMapEntry = substr($prefix, 0, -1);
		}
		//printf("%s cloned as %s, adding %s to namemap\n", $objectName, $actorNewName, $nameMapEntry);
		printf("%s cloned as %s\n", $objectName, $actorNewName);
		if(!in_array($nameMapEntry, $mainJson->NameMap)){
			$mainJson->NameMap[] = $nameMapEntry;
		}
		
		$persistentLevel->Actors[] = $actorNewIndex;
		// Automatically done later.
		//$persistentLevel->CreateBeforeSerializationDependencies[] = $actorNewIndex;
		// Automatically done later.
		//$lodActor->CreateBeforeSerializationDependencies[] = $actorNewIndex;
		//$lodSubActors = &FetchObjectField($lodActor, "SubActors");
		//$lodSubActors[] = json_decode(sprintf('{ "$type":"UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI", "Name":"%d", "Value":"%s" }', count($lodSubActors), $actorNewIndex));
		
		$exports[] = $newActorJson;
		$exports[] = $newMeshJson;
		
		$indexToExport[$actorNewIndex] = &$exports[$newActorRealIndex - 1];
		$indexToExport[$meshNewIndex]  = &$exports[$newMeshRealIndex - 1];
		$objectNameToIndex[$actorNewName] = $actorNewIndex;
		
		unset($newActorJson, $newMeshJson, $lodSubActors);
	}
	unset($indexStr, $meshIndexStr);
}

// Collect references to coordinates in an easy to access fashion.
$objectNameToTransform = [];
foreach($objectNameToIndex as $objectName => $actorIndexStr){
	$export_ref = $indexToExport[$actorIndexStr];
	$meshIndexStr = &FetchObjectField($export_ref, "StaticMeshComponent");
	if(empty($meshIndexStr)){
		unset($meshIndexStr);
		$meshIndexStr = &FetchObjectField($export_ref, "Decal");
		if(empty($meshIndexStr)){
			unset($meshIndexStr);
			$meshIndexStr = &FetchObjectField($export_ref, "LightComponent");
			if(empty($meshIndexStr)){
				unset($meshIndexStr);
				$meshIndexStr = &FetchObjectField($export_ref, "RootComponent");
				if(empty($meshIndexStr)){
					unset($meshIndexStr);
					unset($export_ref);
					continue;
				}
			}
		}
	}
	$meshObject = $indexToExport[$meshIndexStr];
	$relativeLoc_ref   = &FetchObjectField($meshObject, "RelativeLocation");
	$relativeRot_ref   = &FetchObjectField($meshObject, "RelativeRotation");
	$relativeScale_ref = &FetchObjectField($meshObject, "RelativeScale3D");
	if(empty($relativeLoc_ref) || empty($relativeRot_ref) || empty($relativeScale_ref)){
		unset($export_ref, $meshObject, $relativeLoc_ref, $relativeRot_ref, $relativeScale_ref);
		continue;
	}
	
	$objectNameToTransform[$objectName] = (object)[
		"X"     => &$relativeLoc_ref[0]->Value->X,
		"Y"     => &$relativeLoc_ref[0]->Value->Y,
		"Z"     => &$relativeLoc_ref[0]->Value->Z,
		"Pitch" => &$relativeRot_ref[0]->Value->Pitch,
		"Yaw"   => &$relativeRot_ref[0]->Value->Yaw,
		"Roll"  => &$relativeRot_ref[0]->Value->Roll,
		"SX"    => &$relativeScale_ref[0]->Value->X,
		"SY"    => &$relativeScale_ref[0]->Value->Y,
		"SZ"    => &$relativeScale_ref[0]->Value->Z,
	];
	
	unset($export_ref, $meshObject, $relativeLoc_ref, $relativeRot_ref, $relativeScale_ref);
}

//$test = array_map(function($x) { return implode("|", array_values((array)$x)); }, $objectNameToTransform);
//printf("%s\n", json_encode($objectNameToIndex, 0xc0));
//printf("%s\n", json_encode($test, 0xc0));
//exit(1);

// Apply adjustments.
foreach($adjustMeshesFrom as $csvPath){
	FixAdjusterCsv($csvPath);
	$csv = LoadCsv($csvPath);
	printf("Adjusting %d meshes from %s\n", count($csv), $csvPath);
	foreach($csv as $entry){
		if(!isset($entry["localID"])){
			printf("[ERROR] Malformed %s\n", $csvPath);
			exit(1);
		}
		$actorName = $entry["localID"];
		//var_dump($objectNameToTransform[$actorName]); exit(1);
		if(!isset($objectNameToTransform[$actorName])){
			printf(ColorStr(sprintf("[ERROR] Failed to move non-existant mesh %s\n%s\n\n", $actorName, implode("|", array_values($entry))), 255, 128, 128));
			exit(1); //continue;
		}
		$objectNameToTransform[$actorName]->X     += $entry["dx"];
		$objectNameToTransform[$actorName]->Y     += $entry["dy"];
		$objectNameToTransform[$actorName]->Z     += $entry["dz"];
		$objectNameToTransform[$actorName]->Pitch += $entry["dpitch"];
		$objectNameToTransform[$actorName]->Yaw   += $entry["dyaw"];
		$objectNameToTransform[$actorName]->Roll  += $entry["droll"];
		$objectNameToTransform[$actorName]->SX    *= $entry["msx"];
		$objectNameToTransform[$actorName]->SY    *= $entry["msy"];
		$objectNameToTransform[$actorName]->SZ    *= $entry["msz"];
	}
}

// Export a special temporary json to back-import manual edits.
SaveCompressedDecodedUasset($pathCamphorTemp, $mainJson, [
	"skipArrayIndices" => true,
	"bakeAutoObjectNames" => true,
	"bakeAllIndices" => false,
	"simplifyImports" => true,
	"scalarizeNodes" => [ "ConvexElems", "ResponseArray", "Points" ],
]);

// Add or remove meshes based on tags.
foreach($persistentLevel->Actors as $jj => $indexStr){
	$obj = &FetchObjectByIndex($mainJson, $indexStr);
	if(empty($obj)){
		printf("%s\n", ColorStr(sprintf("[ERROR] Actor %s listed in persistent level but doesn't exist", $indexStr), 255, 128, 128));
		exit(1);
	}
	if(!isset($obj->Tags)){
		printf("%s\n", ColorStr(sprintf("[ERROR] Tags missing for object %s", $indexStr), 255, 128, 128));
		exit(1);
	}
	//printf("%-60s [%s] must include [%s]\n", $indexStr, implode(",", $obj->Tags), implode(",", $onlyIncludeTags));
	//$hasIncludeTags = (empty($onlyIncludeTags)); // if empty then auto-included; otherwise check.
	//$hasExcludeTags = false;
	$reasons = [];
	foreach($onlyIncludeTags as $onlyIncludeTag){
		if(!in_array($onlyIncludeTag, $obj->Tags)){
			//$hasIncludeTags = false;
			$reasons[] = "missing '" . $onlyIncludeTag . "'";
		}
	}
	foreach($excludeTags as $excludeTag){
		if(in_array($excludeTag, $obj->Tags)){
			//$hasExcludeTags = true;
			$reasons[] = "has unwanted '" . $excludeTag . "'";
		}
	}
	//if(!$hasIncludeTags || $hasExcludeTags){
	if(!empty($reasons)){
		printf("[WARNING] Actor %s disabled (%s)\n", $indexStr, implode(", ", $reasons));
		$persistentLevel->NotActors[] = $indexStr;
		unset($persistentLevel->Actors[$jj]);
	}else{
		//printf("[DEBUG] Actor %s is kept\n", $indexStr);
	}
	unset($obj);
}
$persistentLevel->Actors = array_values($persistentLevel->Actors);

// Add all actually active Actors as dependencies automatically.
foreach($persistentLevel->Actors as $indexStr){
	$persistentLevel->CreateBeforeSerializationDependencies[] = $indexStr;
}
$persistentLevel->CreateBeforeSerializationDependencies = array_values(array_unique($persistentLevel->CreateBeforeSerializationDependencies));

// Add all actually active Actors that have a mesh component to the LOD actor.
$lodSubActors = &FetchObjectField($lodActor, "SubActors");
foreach($persistentLevel->Actors as $indexStr){
	//if($indexStr != "%i_2821_Sculpture_2_low_Sculpture_2_low24"){ continue; }
	$obj = &FetchObjectByIndex($mainJson, $indexStr);
	$meshName = FetchObjectField($obj, "StaticMeshComponent");
	if(empty($meshName)){
		unset($obj);
		continue;
	}
	//printf("Adding to LOD: %s\n", $indexStr);
	$lodActor->CreateBeforeSerializationDependencies[] = $indexStr;
	$lodSubActors[] = json_decode(sprintf('{ "$type":"UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI", "Name":"%d", "Value":"%s" }', count($lodSubActors), $indexStr));
	unset($obj);
}

// Final touches...
foreach($exports as $ii => &$export_ref){
	$realIndex = $ii + 1;
	$indexStr = $export_ref->{'$index'};
	$indexToExport[$indexStr] = $export_ref;
	$objectName = "";
	if(isset($export_ref->ObjectName)){
		$objectName = $export_ref->ObjectName;
	}
	
	// Disable LightingChannels.
	if($doDisableLightingChannels && isset($export_ref->Data)){
		foreach($export_ref->Data as $i => $node){
			if($node->Name == "LightingChannels"){
				//printf("MURDERING LightingChannels of %s (%s)\n", $indexStr, $objectName);
				unset($export_ref->Data[$i]);
				$export_ref->Data = array_values($export_ref->Data);
				break;
			}
		}
	}
	
	// Fix PPV settings.
	if(preg_match('/^PostProcessVolume/', $objectName)){
		printf("[DEBUG] Fixing PPV settings for %s\n", $objectName);
		$ppvSettings = &FetchObjectField($export_ref, "Settings");
		foreach($ppvSettings as &$subSetting_ref){
			$settingName = $subSetting_ref->Name;
			if(isset($postProcessVolumeSettings[$settingName])){
				//if($subSetting_ref->{'$type'} == "UAssetAPI.PropertyTypes.Structs.Vector4PropertyData, UAssetAPI"){ // no no no
				if(in_array($settingName, [ "ColorSaturation" ])){
					$subSetting_ref->Value[0]->Value->X = (float)$postProcessVolumeSettings[$settingName];
					$subSetting_ref->Value[0]->Value->Y = (float)$postProcessVolumeSettings[$settingName];
					$subSetting_ref->Value[0]->Value->Z = (float)$postProcessVolumeSettings[$settingName];
				}else{
					$subSetting_ref->Value = (float)$postProcessVolumeSettings[$settingName];
				}
			}elseif(preg_match("/^bOverride_(.*)$/", $settingName, $matches)){
				// Automatically enable "bOverride_BloomIntensity" and the such IF they are set in this file.
				list($fullSettingName, $settingToOverride) = $matches;
				if(isset($postProcessVolumeSettings[$settingToOverride])){
					$subSetting_ref->Value = 1;
				}
			}
		}unset($subSetting_ref);
		unset($ppvSettings);
	}
}

// Export the "real" json.
SaveCompressedDecodedUasset($pathCamphorOut, $mainJson, [
	"skipArrayIndices" => false,
	"bakeAutoObjectNames" => true,
	"bakeAllIndices" => true,
	"simplifyImports" => false,
	"scalarizeNodes" => [ "ConvexElems", "ResponseArray", "Points" ],
]);



//
//$lodNames = array_map(function($x){ return $x->Value; }, $lodSubActors);
//sort($lodNames, SORT_NATURAL);
//printf("%s\n", implode("\n", $lodNames));
//foreach($lodNames as $indexStr){
//	$obj = &FetchObjectByIndex($mainJson, $indexStr);
//	//printf("%s\n", $a->ObjectName);
//	$meshName = &FetchObjectField($obj, "StaticMeshComponent");
//	if(empty($meshName)){
//		printf("[ERROR] Actor %s has no mesh.\n", $indexStr);
//	}
//	unset($obj);
//}
//
//$actorsWithMeshes = [];
//foreach($persistentLevel->Actors as $indexStr){
//	$obj = &FetchObjectByIndex($mainJson, $indexStr);
//	//printf("%s\n", $a->ObjectName);
//	$meshName = &FetchObjectField($obj, "StaticMeshComponent");
//	if(!empty($meshName)){
//		//printf("[ERROR] Actor %s has no mesh.\n", $indexStr);
//		//printf("\"%s\"\n", $indexStr);
//		$actorsWithMeshes[] = $indexStr;
//	}
//	unset($obj);
//}
//sort($actorsWithMeshes, SORT_NATURAL);
////printf("%s\n", implode("\n", $actorsWithMeshes));

//$actorsWithMeshes = [];
//foreach($persistentLevel->Actors as $indexStr){
//	$obj = &FetchObjectByIndex($mainJson, $indexStr);
//	//printf("%s\n", $a->ObjectName);
//	$meshName = &FetchObjectField($obj, "StaticMeshComponent");
//	if(!empty($meshName)){
//		//printf("[ERROR] Actor %s has no mesh.\n", $indexStr);
//		//printf("\"%s\"\n", $indexStr);
//		$actorsWithMeshes[] = $indexStr;
//		$meshObj = &FetchObjectByIndex($mainJson, $meshName);
//		$lodParentField = FetchObjectField($meshObj, "LODParentPrimitive");
//		if(empty($lodParentField)){
//			printf("[ERROR] Mesh %s has no LOD parent set.\n", $meshName);
//		}
//	}
//	unset($obj);
//}


$meshCloneMap_outdated = [
	//[ "Wall_2_1_low_Wall_2_1_low68", 12 ],
	//[ "Beam_1_low84", 12 ],

	//[ "Wall_2_1_low_Wall_2_1_low67", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low68", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low69", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low86", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low87", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low88", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low89", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low90", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low91", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low92", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low93", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low94", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low95", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low96", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low97", 1 ],


	//[ "Wall_2_1_low_Wall_2_1_low87", 10 ],
	//[ "Wall_2_1_low_Wall_2_1_low97", 10 ],

	//[ "Beam_1_low270", 8 ],

	//[ "Beam_1_low271", 8 ],
	//[ "Beam_1_low283", 8 ],
	//[ "Wall_2_1_low_Wall_2_1_low97", 20 ],
	//[ "Wall_2_1_low_Wall_2_1_low102", 7 ],

	//[ "Beam_1_low271", 8 ],
	
	//[ "Beam_1_low293", 1 ],
	//[ "Beam_1_low304", 1 ],
	
	//[ "Wall_2_2_low_Wall_2_2_low101", 1 ],[ "Wall_2_2_low_Wall_2_2_low103", 1 ],[ "Wall_2_2_low_Wall_2_2_low119", 1 ],[ "Wall_2_2_low_Wall_2_2_low55",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low56",  1 ],[ "Wall_2_2_low_Wall_2_2_low57",  1 ],[ "Wall_2_2_low_Wall_2_2_low58",  1 ],[ "Wall_2_2_low_Wall_2_2_low59",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low60",  1 ],[ "Wall_2_2_low_Wall_2_2_low61",  1 ],[ "Wall_2_2_low_Wall_2_2_low62",  1 ],[ "Wall_2_2_low_Wall_2_2_low63",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low64",  1 ],[ "Wall_2_2_low_Wall_2_2_low65",  1 ],[ "Wall_2_2_low_Wall_2_2_low66",  1 ],[ "Wall_2_2_low_Wall_2_2_low67",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low68",  1 ],[ "Wall_2_2_low_Wall_2_2_low69",  1 ],[ "Wall_2_2_low_Wall_2_2_low70",  1 ],[ "Wall_2_2_low_Wall_2_2_low71",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low72",  1 ],[ "Wall_2_2_low_Wall_2_2_low73",  1 ],[ "Wall_2_2_low_Wall_2_2_low74",  1 ],[ "Wall_2_2_low_Wall_2_2_low75",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low76",  1 ],[ "Wall_2_2_low_Wall_2_2_low77",  1 ],[ "Wall_2_2_low_Wall_2_2_low78",  1 ],[ "Wall_2_2_low_Wall_2_2_low79",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low80",  1 ],[ "Wall_2_2_low_Wall_2_2_low81",  1 ],[ "Wall_2_2_low_Wall_2_2_low82",  1 ],[ "Wall_2_2_low_Wall_2_2_low83",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low84",  1 ],[ "Wall_2_2_low_Wall_2_2_low85",  1 ],[ "Wall_2_2_low_Wall_2_2_low86",  1 ],[ "Wall_2_2_low_Wall_2_2_low87",  1 ],
	//[ "Wall_2_2_low_Wall_2_2_low88",  1 ],[ "Wall_2_2_low_Wall_2_2_low89",  1 ],[ "Wall_2_2_low_Wall_2_2_low90",  1 ],[ "Wall_2_2_low_Wall_2_2_low99",  1 ],
	
	//[ "Cube_Floor_24", 16 ],
	
	//[ "Wall_2_1_low_Wall_2_1_low63", 1 ],
	//[ "Wall_2_1_low_Wall_2_1_low55", 1 ],
	//[ "column_1_low100", 3 ],
	
	//[ "Cube_Floor_2",                   2 ],
	//[ "Wall_2_2_low_Wall_2_2_low102",   4 ],
	//[ "ruins_3_low_ruins_3_low30",      1 ],
	//[ "S_Main_door_low_Main_door_1_39", 1 ],
	//[ "S_Main_door_low_Main_door_1_2",  1 ],
	//[ "Bowl_low31",                     2 ],
	//[ "S_Column_2_low_Column_10",       1 ],
	
	//[ "Beam_1_low99",  1 ],
	//[ "Beam_1_low100", 1 ],
	//[ "Cube_Floor_47", 2 ],
	//[ "S_Column_2_low_Column_10", 1 ],
	//[ "S_Column_2_low_Column_11", 1 ],
	
	//[ "Cube_Roof_1", 4 ],
	//[ "Cube_Roof_2", 3 ],
	//[ "Cube_Roof_3", 3 ],
	
	//[ "Wall_2_2_low_Wall_2_2_low110", 1 ],
	//[ "Wall_2_2_low_Wall_2_2_low113", 1 ],
	//[ "Beam_1_low97",  2 ],
	//[ "Bowl_low34",  1 ],
	
	//[ "Beam_1_low103",  1 ],
	
	//[ "Wall_1_1_low_Wall_1_1_low106", 3 ], // 25
	//[ "Wall_2_2_low_Wall_2_2_low59",  1 ], // 25
	//[ "Wall_2_2_low_Wall_2_2_low63",  1 ], // 25
	//[ "Wall_2_2_low_Wall_2_2_low87",  1 ], // 25
	//[ "Wall_2_2_low_Wall_2_2_low79",  1 ], // 25
	//[ "Wall_2_2_low_Wall_2_2_low67",  1 ], // 25
	
	//[ "Wall_2_2_low_Wall_2_2_low129", 1 ], // 26
	//[ "Wall_2_2_low_Wall_2_2_low94",  1 ], // 26
	//[ "Cube_Roof_3", 1 ],
	
	//[ "Cube_Roof_14", 8 ], // 27 + 28
	
	//[ "Cube_Roof_16", 1 ],
	//[ "Cube_JumppadEntrance_1", 1 ],
	//[ "Beam_1_low103", 1 ],
	//[ "Sculpture_2_low_Sculpture_2_low16", 2 ],
	
	//[ "Cube_Roof_23", 18 ],
	
	//[ "Cube_Roof_3", 1 ], // 34
	
	//[ "Cube_Underbelly_1", 17 ], // 35 + 36
	
	//[ "Wall_2_1_low_Wall_2_1_low63", 1 ], // turned into BellyRock1, 37
	
	//[ "BellyRock_1", 5 ], // 39
	//[ "BellyRock_11", 4 ],
	
	//[ "BellyRock_1", 1 ], // turned into PointyRock_1, 42
	
	//[ "Beam_1_low107", 1 ] // turned into destroyed Beam
	//[ "Beam_1_low120", 1 ],
	//[ "Beam_1_D_low3", 2 ],
	
	//[ "S_Column_2_low_Column_13", 1 ], // matchbox bar
	//[ "SophiaBlockingVolume_1",   2 ], // attempt to seal the ceiling
	
	//[ "S_Column_2_low_Column_13", 1 ], // the other matchbox bar
	//[ "Beam_1_low97", 1 ],
	//[ "Cube_Roof_16", 1 ],
	
	//[ "PointyRock_1", 5 ], // 54
	
	//[ "BellyRock_16", 1 ],
];

$adjustMeshesFrom_outdated = [
	//"media\\lostgrids\\adjust_camphor.csv",
	//"_adjusterLogs\\adjust_camphor - 3.csv",
	//"_adjusterLogs\\adjust_camphor - 4.csv",
	//"_adjusterLogs\\adjust_camphor - 5.csv",
	//"_adjusterLogs\\adjust_camphor - 6.csv",
	//"_adjusterLogs\\adjust_camphor - 7.csv",
	//"_adjusterLogs\\adjust_camphor - 8.csv",
	//"_adjusterLogs\\adjust_camphor - 9.csv",
	//"_adjusterLogs\\adjust_camphor - floors 1.csv",
	//"_adjusterLogs\\adjust_camphor - floors 2.csv",
	//"_adjusterLogs\\adjust_camphor - floors 3.csv",
	//"_adjusterLogs\\adjust_camphor - floors 4.csv",
	//"_adjusterLogs\\adjust_camphor - 10.csv",
	//"_adjusterLogs\\adjust_camphor - 11.csv",
	//"_adjusterLogs\\adjust_camphor - 12.csv",
	//"_adjusterLogs\\adjust_camphor - 13.csv",
	//"_adjusterLogs\\adjust_camphor - 14.csv",
	//"_adjusterLogs\\adjust_camphor - 15.csv",
	//"_adjusterLogs\\adjust_camphor - 16.csv",
	//"_adjusterLogs\\adjust_camphor - 17.csv",
	//"_adjusterLogs\\adjust_camphor - 18.csv",
	//"_adjusterLogs\\adjust_camphor - 19.csv",
	
	//"_adjusterLogs\\adjust_camphor - cumulative 1.csv",
	//"_adjusterLogs\\adjust_camphor - cumulative 2.csv",
	//"_adjusterLogs\\adjust_camphor - cumulative 3.csv",
	
	//"_adjusterLogs\\adjust_camphor - 20.csv",
	//"_adjusterLogs\\adjust_camphor - 21.csv",
	//"_adjusterLogs\\adjust_camphor - 22.csv",
	//"_adjusterLogs\\adjust_camphor - 23.csv",
	
	//"_adjusterLogs\\adjust_camphor - 24 roof restructure.csv",
	//"_adjusterLogs\\adjust_camphor - 25 roof restructure.csv",
	//"_adjusterLogs\\adjust_camphor - 26 roof restructure.csv",
	//"_adjusterLogs\\adjust_camphor - 27 roof restructure.csv",
	//"_adjusterLogs\\adjust_camphor - 28 roof restructure.csv",
	//"_adjusterLogs\\adjust_camphor - 29 roof restructure.csv",
	
	//"_adjusterLogs\\adjust_camphor - 30.csv",
	//"_adjusterLogs\\adjust_camphor - 31.csv",
	//"_adjusterLogs\\adjust_camphor - 32.csv",
	//"_adjusterLogs\\adjust_camphor - 33.csv",
	
	//"_adjusterLogs\\adjust_camphor - 34 underbelly.csv",
	//"_adjusterLogs\\adjust_camphor - 35.csv",
	//"_adjusterLogs\\adjust_camphor - 36.csv",
	//"_adjusterLogs\\adjust_camphor - 37.csv",
	
	//"_adjusterLogs\\adjust_camphor - 38 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 39 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 40 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 41 bellyrocks.csv",
	
	//"_adjusterLogs\\adjust_camphor - 42 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 43 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 44 bellyrocks.csv",
	//"_adjusterLogs\\adjust_camphor - 45 bellyrocks.csv",
	
	//"_adjusterLogs\\adjust_camphor - 46 inner beams.csv",
	//"_adjusterLogs\\adjust_camphor - 47 inner beams.csv",
	//"_adjusterLogs\\adjust_camphor - 48 inner beams.csv",
	//"_adjusterLogs\\adjust_camphor - 49 inner beams.csv",
	//"_adjusterLogs\\adjust_camphor - 50 inner beams.csv",
	
	//"_adjusterLogs\\adjust_camphor - 51 polish.csv",
	//"_adjusterLogs\\adjust_camphor - 52 polish.csv",
	//"_adjusterLogs\\adjust_camphor - 53 polish.csv",
	
	//"_adjusterLogs\\adjust_camphor - 54 bellyrocks again.csv",
	//"_adjusterLogs\\adjust_camphor - 55 bellyrocks again.csv",
];
