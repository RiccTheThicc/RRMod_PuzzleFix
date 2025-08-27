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
$pathCamphorTemp = "..\\OutputJsons\\CamphorCorridorTemple_taggger.json";
 
// Scan file.
$mainJson = LoadDecodedUasset($pathCamphorBase);
$exports = &$mainJson->Exports;

$indexToExport = [];
$objectNameToIndex = [];
$objectNameMaxCounter = [];
$persistentLevel = null;
$lodActor = null;

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
			//printf("Found LOD actor: %s (%s)\n", $objectName, $indexStr);
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
}unset($export_ref);
$objectNameToIndex = array_map(function($y) { return $y[0]; }, array_filter($objectNameToIndex, function($x) { return (count($x) == 1); }));

$shellMeshes       = array_map(function($x) use($objectNameToIndex){ return $objectNameToIndex[$x]; }, array_values(array_column(LoadCsv("_adjusterLogs\\adjust_camphor - shell.csv"),       "localID")));
$scaffoldingMeshes = array_map(function($x) use($objectNameToIndex){ return $objectNameToIndex[$x]; }, array_values(array_column(LoadCsv("_adjusterLogs\\adjust_camphor - scaffolding.csv"), "localID")));
//var_dump($shellMeshes, $scaffoldingMeshes); exit(1);

$objectsToTag = array_values(array_merge($persistentLevel->NotActors, $persistentLevel->Actors));
foreach($objectsToTag as $indexStr){
	$obj = &FetchObjectByIndex($mainJson, $indexStr);
	$tags = [];
	
	$isDisabled = in_array($indexStr, $persistentLevel->NotActors);
	if($isDisabled){
		$tags[] = "disabled";
	}
	$meshIndexStr  = FetchObjectField($obj, "StaticMeshComponent");
	$decalIndexStr = FetchObjectField($obj, "Decal");
	$lightIndexStr = FetchObjectField($obj, "LightComponent");
	if(!empty($meshIndexStr)){
		$tags[] = "mesh";
		//printf("Got %s\n", $indexStr);
		if(in_array($indexStr, $shellMeshes)){
			$tags[] = "shell";
		}elseif(in_array($indexStr, $scaffoldingMeshes)){
			$tags[] = "scaffolding";
		}else{
			$tags[] = "interior";
		}
	}
	if(!empty($decalIndexStr)){
		$tags[] = "decal";
	}
	if(!empty($lightIndexStr)){
		$tags[] = "light";
	}
	
	$tempValues = [
		"\$type"      => $obj->{'$type'},
		"\$index"     => $obj->{'$index'},
		"ObjectName"  => $obj->ObjectName,
		"ObjectFlags" => $obj->ObjectFlags,
	];
	unset($obj->{'$type'}, $obj->{'$index'}, $obj->ObjectName, $obj->ObjectFlags);
	
	$obj = (object)(array_merge($tempValues, [ "Tags" => $tags ], (array)$obj));
	//var_dump($obj); exit(1);
	
	//printf("%-60s: %s\n", $indexStr, implode(", ", $tags));
	unset($obj);
}

// Export a special temporary json to back-import manual edits.
SaveCompressedDecodedUasset($pathCamphorTemp, $mainJson, [
	"skipArrayIndices" => true,
	"bakeAutoObjectNames" => true,
	"bakeAllIndices" => false,
	"simplifyImports" => true,
	"scalarizeNodes" => [ "ConvexElems", "ResponseArray" ],
]);
