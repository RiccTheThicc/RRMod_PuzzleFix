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

$pathCamphorIn  = "..\\OutputJsons\\Maps\\CamphorCorridorTemple.json";
$pathCamphorOut = "..\\OutputJsons\\Maps\\CamphorCorridorTemple.json";

$ROTATOR = UeDefaultTransform();
$ROTATOR->Translation->X = 18223.021484375;
$ROTATOR->Translation->Y = 42844.50390625;
$ROTATOR->Translation->Z = 1371.434814453125;
$ROTATOR->Rotation->X = 0;
$ROTATOR->Rotation->Y = 30;
$ROTATOR->Rotation->Z = 0;

$MOVER = UeDefaultTransform();
$MOVER->Translation->X =  32300 +  8660;
$MOVER->Translation->Y = -48000 - 15000;
$MOVER->Translation->Z = -10000;

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
	"PlayerTeleportVolume",
];

$skipExports = [
	19,   // %i_19_DetectVolume                                (#LevelRestrictionVolume_Camphor)
	802,  // #Root_LRV_Camphor                                 (#LevelRestrictionVolume_Camphor)
	1607, // %i_1607_Blocker                                   (#LevelRestrictionVolume_Camphor)
	1645, // %i_1645_RibbonMesh
	1640, // #SceneRoot_TargetedJumpingPad_HubToEnclave        (#BP_TargetedJumpingPad_HubToEnclave)
	//1646, // #SceneRoot_TargetedJumpingPad_EnclaveToHub        (#BP_TargetedJumpingPad_EnclaveToHub)
	2160, // #JumpTarget_HubJumppad                            (#BP_TargetedJumpingPad_HubToEnclave)
	2161, // #JumpTarget_EnclaveJumppad                        (#BP_TargetedJumpingPad_EnclaveToHub)
	2559, // #RootComp_CamphorEntrance                         (#BP_DungeonEntrance_CamphorEntrance)
	2563, // #SM_LightPillar_Ribbons_Spiral_CamphorEntrance    (#BP_DungeonEntrance_CamphorEntrance)
	2564, // #SM_TopMarker_CamphorEntrance                     (#BP_DungeonEntrance_CamphorEntrance)
	2570, // #SceneRoot_TargetedJumpingPad_Scaled_HubToEnclave (#BP_TargetedJumpingPad_HubToEnclave)
];

$mainJson = LoadDecodedUasset($pathCamphorIn);
$exports = &$mainJson->Exports;

foreach($exports as $ii => &$export_ref){
	$realIndex = $ii + 1;
	$objectName = $export_ref->ObjectName;
	//$indexStr = $export_ref->{'$index'};
	
	//$actuallyHasLocation = (!empty(FetchObjectField($export_ref, "RelativeLocation")));
	
	//$parent = @$export_ref->OuterIndex;
	//if(!empty($parent) && in_array($parent, $skipIfBelongsTo)){
	//	//if($actuallyHasLocation){
	//	//	printf("[DEBUG] Ignoring export %d - %s (%s)\n", $realIndex, $indexStr, $parent);
	//	//}
	//	continue;
	//}
	if(in_array($realIndex, $skipExports)){
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
	if(!$shouldMove){// || !$actuallyHasLocation){ // don't do this, don't skip destinationPosition
		continue;
	}
	
	//if($actuallyHasLocation){
	//	printf("[DEBUG] Moving export %d - %s (%s)\n", $realIndex, $indexStr, $parent);
	//}
	
	UeCompleteTransform($export_ref);
	
	$t = UeTransformUnpack($export_ref);	
	$t = UeTransformRotateAround($t, $ROTATOR);
	$t = UeTransformAdd($t, $MOVER);	
	UeTransformPackInto($t, $export_ref);
	unset($t);
	
	// Special handling for teleporter destinations - for archways and under-enclave.
	$dest = FetchObjectField($export_ref, "destinationPosition");
	if(!empty($dest)){
		//printf("[DEBUG] Moving destPos of %d - %s (%s)\n", $realIndex, $indexStr, $parent);
		printf("[DEBUG] Moving destPos of %d (named %s)\n", $realIndex, $objectName);
		$t = UeTransformUnpack($dest[0]->Value);
		$t = UeTransformRotateAround($t, $ROTATOR);
		$t = UeTransformAdd($t, $MOVER);
		UeTransformPackInto($t, $dest[0]->Value);
	}
	
	unset($dest);
	
}unset($export_ref);


SaveCompressedDecodedUasset($pathCamphorOut, $mainJson, [
	"skipArrayIndices" => true,
	"bakeAutoObjectNames" => true,
	"bakeAllIndices" => false,
	
	//"buildDefaultIndices" => true,
	//"bakeDefaultIndices" => true,
]);

