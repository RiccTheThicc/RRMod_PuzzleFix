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

$pathCamphorBase = "..\\BaseJsons\\CamphorEntrance.json";
$pathCamphorOut  = "..\\OutputJsons\\CamphorEntrance.json";

$ROTATOR = UeDefaultTransform();
$ROTATOR->Translation->X = 23000.333984375;
$ROTATOR->Translation->Y = 16555.875;
$ROTATOR->Translation->Z = 16193.9521484375;
$ROTATOR->Rotation->X = 0;
$ROTATOR->Rotation->Y = -60;
$ROTATOR->Rotation->Z = 0;

$MOVER = UeDefaultTransform();
$MOVER->Translation->X = 4200;
$MOVER->Translation->Y = 6300;
$MOVER->Translation->Z = -775;

$moveMap = [
	"BrushComponent",
	"DefaultSceneRoot",
	"JumpTarget",
	"LightComponent",
	"Mesh",
	"NewDecalComponent",
	"NewReflectionComponent",
	"ParticleSystemComponent",
	"Root",
	"SceneRoot",
	"StaticMeshComponent",
	"boxMesh",
];

$skipExports = [
	58, // statue
	60, // statue
];

$skipExportsStartingWith = 85; // don't move exports after this index

$mainJson = LoadDecodedUasset($pathCamphorBase);
$exports = &$mainJson->Exports;

foreach($exports as $ii => &$export_ref){
	$realIndex = $ii + 1;
	$objectName = $export_ref->ObjectName;
	
	
	if($realIndex >= $skipExportsStartingWith || in_array($realIndex, $skipExports)){
		printf("[DEBUG] Ignoring export %d (named %s)\n", $realIndex, $objectName);
		continue;
	}
	
	$shouldMove = false;
	foreach($moveMap as $possibleName){
		if(preg_match('/^' . $possibleName . '/i', $objectName)){
			$shouldMove = true;
			break;
		}
	}
	if(!$shouldMove){
		continue;
	}
	
	UeCompleteTransform($export_ref);
	$t = UeTransformUnpack($export_ref);
	$t = UeTransformRotateAround($t, $ROTATOR);
	$t = UeTransformAdd($t, $MOVER);
	$t = UeTransformPackInto($t, $export_ref);
	unset($t);
	
}unset($export_ref);


SaveCompressedDecodedUasset($pathCamphorOut, $mainJson, [
	"skipArrayIndices" => false,
	"bakeAllIndices" => true,
]);

// (\t\t\t\t\t\t"(?:Pitch|Yaw|Roll)"\s*:\s*)[\d\-\+\.\"]*