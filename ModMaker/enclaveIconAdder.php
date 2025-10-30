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

$SIZE_MULTIPLIER = 1.0;

$iconDataMap = LoadCsvMap("media\\mod\\enclave_icons.csv", "pid");
$iconDataMap = array_filter($iconDataMap, function($x){ return ($x["addIslandIcon"] == true); });
$addedIconsTo = [];

printf("Adding enclave icons...\n");

// Go over the output jsons folder, skip some of the files.
$outputMapsDir = asDir("..\\OutputJsons\\Maps");
$excludeFileNames = [
	"CamphorCorridorTemple_edittemp.json",
];

$fileNames = GetAllFiles($outputMapsDir);
foreach($fileNames as $fileName){
	$isFileChanged = false;
	
	if(in_array($fileName, $excludeFileNames) || str_ends_with($fileName, "_test.json")){
		//printf("File %s explicitly excluded - ignoring\n", $fileName);
		continue;
	}
	
	// Load the asset.
	$fullPath = $outputMapsDir . $fileName;
	$asset = LoadDecodedUasset($fullPath, false);
	
	// Check if it imports BP_Dungeon_C at all (and/or TutorialDungeon_C).
	$dungeonImportEntries = array_values(array_filter($asset->Imports, function($x){ return in_array($x->ClassName, [ "BP_Dungeon_C", "TutorialDungeon_C" ]); }));
	if(empty($dungeonImportEntries)){
		continue;
	}
	$dungeonImports = array_column($dungeonImportEntries, '$index');
	
	// Find exports 
	foreach($asset->Exports as $ee => &$export_ref){
		//if(str_contains($fileNameOnly, "Introduction")){printf("Export %-5d, looking for: %s in %s\n", $ee + 1, json_encode($validImportIndices), $fileNameOnly);}
		//if(!str_contains(strtolower($export_ref->ObjectName), "bp_dungeon")){
		if(!isset($export_ref->TemplateIndex) || !in_array($export_ref->TemplateIndex, $dungeonImports)){
			continue;
		}
		
		$pid = FetchObjectField($export_ref, "KrakenId");
		$title = "?";
		// Get title - optional, you can comment this out.
		{
			$questIndex = FetchObjectField($export_ref, "MyQuest") ?? 0;
			$quest = FetchObjectByIndex($asset, $questIndex);
			$title = (@FetchObjectString($quest, "QuestTitle")->CultureInvariantString ?? "?");
			$title = str_replace("Shattered Temple", "Shattered Library", $title);
			$title = str_replace("The Secret Cave", "The Highest Viewpoint", $title);
			$title = str_replace(",", "", $title);
			if($title == "Quest for Perfection"){
				$zoneEnum = FetchObjectField($quest, "ZoneName");
				$zoneIndex = ZoneNameToInt($zoneEnum);
				$title = ZoneToPrettyNoColor($zoneIndex) . " QFP";
				//printf("%s\n", json_encode($quest->Data, 0xc0)); exit(1);
			}
			//printf("%5d %-50s | in: %s\n", $pid, $title, $fileName);
		}
		
		if(!isset($iconDataMap[$pid])){
			//printf("Ignoring dungeon %5d in %s\n", $pid, $fileName);
			continue;
		}
		
		printf("> Adding enclave icon to pid %5d in %s\n", $pid, $fileName);
		
		$nameMapAdditions = [
			"Texture2D",
			"/Game/ASophia/UI/HUD/Markers/Textures/EnclaveEx",
			"EnclaveEx",
			"mapTex",
			"worldTex",
			"VisibleOnMap",
			"DrawNameOnMap",
			"HideCompletionIcon",
			"ShowInfoPanel",
			"NameZoomThreshold",
			"Opacity",
			"SizeMultiplier",
			"MapWorldPositionOffset",
		];
		$asset->NameMap = array_values(array_unique(array_merge($asset->NameMap, $nameMapAdditions)));
		
		$iconTextureImportIndex = 0; // import for the texture object within the parent package
		$iconPackageImportIndex = 0; // import for the icon asset package
		$testIconTextureImport = array_values(array_filter($asset->Imports, function($x){ return ($x->ObjectName == "EnclaveEx"                                       && $x->ClassName == "Texture2D"); }));
		$testIconPackageImport = array_values(array_filter($asset->Imports, function($x){ return ($x->ObjectName == "/Game/ASophia/UI/HUD/Markers/Textures/EnclaveEx" && $x->ClassName == "Package"  ); }));
		if(!empty($testIconTextureImport) && !empty($testIconPackageImport)){
			// Whoa there! Enclave icon is already imported here? We'll just go along with it then.
			$iconTextureImportIndex = $testIconTextureImport[0]->{'$index'};
			$iconPackageImportIndex = $testIconPackageImport[0]->{'$index'};
			//printf("ALREADY IMPORTED enclave icon as: package %d, texture %d\n", $iconPackageImportIndex, $iconTextureImportIndex);
		}else{
			$iconTextureImportIndex = -(count($asset->Imports) + 1);
			$iconPackageImportIndex = -(count($asset->Imports) + 2);
			$str1 = '{"$type":"UAssetAPI.Import, UAssetAPI","$index":' . $iconTextureImportIndex .
					',"OuterIndex":' . $iconPackageImportIndex . ',"ObjectName":"EnclaveEx",' .
					'"ClassPackage":"/Script/Engine","ClassName":"Texture2D"}';
			$str2 = '{"$type":"UAssetAPI.Import, UAssetAPI","$index":' . $iconPackageImportIndex .
					',"OuterIndex":0,"ObjectName":"/Game/ASophia/UI/HUD/Markers/Textures/EnclaveEx",' .
					'"ClassPackage":"/Script/CoreUObject","ClassName":"Package"}';
			$asset->Imports[] = json_decode($str1);
			$asset->Imports[] = json_decode($str2);
		}
		
		$rootIndex = FetchObjectField($export_ref, "RootComponent");
		$root_ref = &FetchObjectByIndex($asset, $rootIndex);
		$t = UeTransformUnpack($root_ref);
		$offsetX = ($iconDataMap[$pid]["posX"] - $t->Translation->X);
		$offsetY = ($iconDataMap[$pid]["posY"] - $t->Translation->Y);
		
		$markerIndex = FetchObjectField($export_ref, "MarkerEmitter_asdf");
		if(empty($markerIndex)){
			printf("[ERROR] Dungeon pid %d (export_ref %d in %s) doesn't have a MarkerEmitter!\n", $pid, $ee + 1, $fullPath);
			exit(1);
		}
		$marker_ref = &FetchObjectByIndex($asset, $markerIndex);
		//printf("%d / %s: %s\n\n", $pid, $title, json_encode($marker_ref->Data, 0xc0));
				
		$str = 
'		[  ' .
'			{  ' .
'				"$type": "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",  ' .
'				"StructType": "LocationMarkerData",  ' .
'				"SerializeNone": true,  ' .
'				"Name": "Marker",  ' .
'				"Value": [  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI", "Name":"worldTex",           "Value":' . $iconTextureImportIndex . ' },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.ObjectPropertyData, UAssetAPI", "Name":"mapTex",             "Value":' . $iconTextureImportIndex . ' },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"VisibleOnMap",       "Value":1 },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"DrawNameOnMap",      "Value":1 },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"HideCompletionIcon", "Value":1 },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"ShowInfoPanel",      "Value":1 },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.FloatPropertyData, UAssetAPI",  "Name":"NameZoomThreshold",  "Value":0.0001 },  ' .
'					{ "$type":"UAssetAPI.PropertyTypes.Objects.FloatPropertyData, UAssetAPI",  "Name":"SizeMultiplier",     "Value":' . $SIZE_MULTIPLIER . ' },  ' .
//'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"HideGenericMarkerBackground", "Value":0 },  ' .
//'					{ "$type":"UAssetAPI.PropertyTypes.Objects.BoolPropertyData, UAssetAPI",   "Name":"IsMainQuest",        "Value":0 },  ' .
'					{  ' .
'						"$type": "UAssetAPI.PropertyTypes.Structs.StructPropertyData, UAssetAPI",  ' .
'						"StructType": "Vector",  ' .
'						"SerializeNone": true,  ' .
'						"Name": "MapWorldPositionOffset",  ' .
'						"Value": [  ' .
'							{  ' .
'								"$type": "UAssetAPI.PropertyTypes.Structs.VectorPropertyData, UAssetAPI",  ' .
'								"Name": "MapWorldPositionOffset",  ' .
'								"Value": {  ' .
'									"$type": "UAssetAPI.UnrealTypes.FVector, UAssetAPI",  ' .
'									"X": ' . $offsetX . ',  ' .
'									"Y": ' . $offsetY . ',  ' .
'									"Z": 5000  ' .
'								}  ' .
'							}  ' .
'						]  ' .
'					}  ' .
'				]  ' .
'			}  ' .
'		]  ' .
'';
		$marker_ref->Data = json_decode($str);
		//if(empty($marker_ref->Data)){
		//	printf("json_decode failed\n");
		//	printf("%s\n", $str);
		//	exit(1);
		//}
		
		$isFileChanged = true;
		$addedIconsTo[] = $pid;
		
		//printf("%d / %s: %s\n\n", $pid, $title, json_encode($marker_ref->Data, 0xc0));
		
		unset($pid, $questIndex, $quest, $title, $markerIndex, $marker_ref, $root_ref);
	}unset($export_ref);
	
	if($isFileChanged){
		//$testOutputPath = $outputMapsDir . str_replace(".json", "_test.json", $fileName);
		//SaveCompressedDecodedUasset($testOutputPath, $asset, [
		//	"skipArrayIndices" => false,
		//	"bakeAllIndices" => true,
		//	"bakeAutoObjectNames" => true,
		//	"addObjectNamesToNameMap" => true,
		//	"simplifyImports" => false,
		//	"verbose" => false,
		//]);
		//if(file_get_contents($fullPath) == file_get_contents($testOutputPath)){
		//	printf("[ERROR] Changes failed to apply.\n");
		//	exit(1);
		//}
		$skipArrayIndices = ($fileName == "CamphorCorridorTemple.json"); // TODO this needs to go later, don't skip any
		//printf("|%s| %s\n", $fileName, BoolStr($skipArrayIndices));
		SaveCompressedDecodedUasset($fullPath, $asset, [
			"skipArrayIndices" => $skipArrayIndices,
			"bakeAllIndices" => true,
			"bakeAutoObjectNames" => true,
			"addObjectNamesToNameMap" => true,
			"simplifyImports" => false,
			"verbose" => false,
		]);
	}
}

$failedToAddIconsTo = array_values(array_diff(array_keys($iconDataMap), $addedIconsTo));
if(!empty($failedToAddIconsTo)){
	printf("[ERROR] Failed to add enclave icons to pids: %s\n", implode(",", $failedToAddIconsTo));
	exit(1);
}else{
	printf("All requested enclave icons have been added.\n");
}
