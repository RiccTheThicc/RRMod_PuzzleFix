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


// Compress the hand-placed chest spawn locations into binary format for dxgi.dll to pick up and use.
CreateChestBinary("media\\mod\\chest_locations.csv", "media\\mod\\puzzleradar.bin");

// Set up paths.
$assetDir   = asDir("..\\Assets");
$inputName  = "ES_Galaxy - TEMPLATE";
$outputName = "ES_Galaxy_chestmaker";

// Set up values to be embedded into the uexp file.
$CUTOFF_BEGIN    = 10000; // range at which the particles start to lose visibility
$CUTOFF_END      = 15000; // range at which the particles completely vanish
$TRANSITION_2TO3 = 500;   // range at which emitter002 should transition to emitter003
//$CUTOFF_BEGIN    = 1000; // range at which the particles start to lose visibility
//$CUTOFF_END      = 2000; // range at which the particles completely vanish
//$TRANSITION_2TO3 = 300;   // range at which emitter002 should transition to emitter003

// Set up the maximum scale of number of particles to spawn at full visibility, per emitter.
$COUNT_SCALE_MAP = [
	0 => 1.2,
	1 => 0.9,
	2 => 1.1,
	3 => 0.7,
];

// Expected hashes.
$expectedUassetHash = "a344cc37d25cea4ea2932b2a47295e14";
$expectedUexpHash = "840d9ae23d323aa2897f37ac5d591496";

// Hardcoded mapping of serial offsets of where LUTNumSamplesMinusOne FloatProperty is.
// We can deduce the rest of the values from it.
// These values should match exactly what UAssetGUI reports.
$offsetMapNumSamples = [
	0 => 52330, // export44
	1 => 51761, // export43
	2 => 48873, // export38
	3 => 48304, // export37
];

// Hardcoded mappings of serial offsets of where MaxCameraDistance FloatProperty is.
// This option does not appear to have any actual effect on the particles, but we change it just to be safe.
// These values should match exactly what UAssetGUI reports.
$offsetMapCamDist = [
	0 => 251611, // export147
	1 => 252416, // export148
	2 => 253250, // export149
	3 => 254084, // export150
];

// Where the beginning of the precompiled script is.
$offsetScriptBegin = 241784;

// Load the files.
$uasset = file_get_contents($assetDir . $inputName . ".uasset");
$uexp   = file_get_contents($assetDir . $inputName . ".uexp");

// Check if they match what we expect.
$uassetHash = md5($uasset);
$uexpHash   = md5($uexp);
if($uassetHash != $expectedUassetHash){
	printf("%s\n", ColorStr("[ERROR] Chestmaker: uasset hash is wrong!", 255, 0, 0));
	printf("Expected: %s\n", $expectedUassetHash);
	printf("Received: %s\n", $uassetHash);
	exit(1);
}
if($uexpHash != $expectedUexpHash){
	printf("%s\n", ColorStr("[ERROR] Chestmaker: uexp hash is wrong!", 255, 0, 0));
	printf("Expected: %s\n", $expectedUexpHash);
	printf("Received: %s\n", $uexpHash);
	exit(1);
}
printf("Chestmaker: accepted ES_Galaxy template file |%s|\n", $inputName);

// Collect the emitter data - offsets of values, and values themselves.
$dataMap = [];
//foreach($offsetMapNumSamples as $emi => $rawOffset){
for($emi = 0; $emi <= 3; ++$emi){
	
	$rawOffset = $offsetMapNumSamples[$emi];
	
	$emitter = (object)[];
	// The actual value is 25 bytes beyond the provided offset, as a float, and we need to add 1 to it.
	$emitter->offsetNumSamples = ($rawOffset + 25);
	$emitter->numSamples = 1 + intval(round(StringToFloat($uexp, $emitter->offsetNumSamples)));
	//printf("%d: %d\n", $emi, $actualNumSamples);
	
	$emitter->offsetInvTimeRange = ($rawOffset + 25) - 29;
	$emitter->offsetMaxTime      = ($rawOffset + 25) - 29 * 2;
	$emitter->offsetMinTime      = ($rawOffset + 25) - 29 * 3;
	$emitter->offsetCurvalLast   = ($rawOffset + 25) - 29 * 4;
	$emitter->offsetCurvalFirst  = $emitter->offsetCurvalLast - 4 * ($emitter->numSamples - 1);
	$emitter->offsetCamDist      = ($offsetMapCamDist[$emi] + 25);
	
	$emitter->invTimeRange       = StringToFloat($uexp, $emitter->offsetInvTimeRange);
	$emitter->maxTime            = StringToFloat($uexp, $emitter->offsetMaxTime);
	$emitter->minTime            = StringToFloat($uexp, $emitter->offsetMinTime);
	$emitter->camDist            = StringToFloat($uexp, $emitter->offsetCamDist);
	
	$tmp = (array)$emitter; ksort($tmp); $emitter = (object)$tmp;
	$dataMap[$emi] = $emitter;
	
	//printf("%d: %s\n", $emi, json_encode($emitter, 0xc0));
	
}

// Set cutoff range that affects Galaxy000 and Galaxy001 (small dots, large dots).
FloatIntoString($uexp, $offsetScriptBegin + 80, $CUTOFF_END);

// Set cutoff range for Galaxy003 (thick lines).
// For reasons beyond my comprehension, the game sets it to 200 larger than this, so we subtract it preemptively.
FloatIntoString($uexp, $offsetScriptBegin + 36, $CUTOFF_END - 200);

// Set cutoff range for Galaxy002 (thin lines).
// It's short, as they transition into becoming Galaxy003's thick lines past this range.
// It looks like the game adds 50 to this on top, so we subtract it preemptively.
FloatIntoString($uexp, $offsetScriptBegin + 64, $TRANSITION_2TO3 - 50);

// Set kick-in range for Galaxy003 (thick lines).
// This match the cutoff range for the thin lines.
FloatIntoString($uexp, $offsetScriptBegin + 32, $TRANSITION_2TO3);

// Set max camera distances to the same values, with a margin on top.
$CAMDIST_MARGIN = 500;
FloatIntoString($uexp, $dataMap[0]->offsetCamDist, $CUTOFF_END      + $CAMDIST_MARGIN); // max range
FloatIntoString($uexp, $dataMap[1]->offsetCamDist, $CUTOFF_END      + $CAMDIST_MARGIN); // max range
FloatIntoString($uexp, $dataMap[2]->offsetCamDist, $TRANSITION_2TO3 + $CAMDIST_MARGIN); // Galaxy002's thin lines cut off early
FloatIntoString($uexp, $dataMap[3]->offsetCamDist, $CUTOFF_END      + $CAMDIST_MARGIN); // max range

// Modify the curves.
foreach($dataMap as $emi => $emitter){
	$xStep = $CUTOFF_END / ($emitter->numSamples - 1);
	for($jj = 0; $jj < $emitter->numSamples; ++$jj){
		// Linear interpolation: y = maxCountScale * (end-x)/(end-begin)
		//FloatIntoString($uexp, $emitter->offsetCurvalFirst + $jj * 4, $COUNT_SCALE_MAP[$emi]);
		$x = $xStep * $jj;
		$y = $COUNT_SCALE_MAP[$emi] * ($CUTOFF_END - $x) / ($CUTOFF_END - $CUTOFF_BEGIN);
		$final = min($y, $COUNT_SCALE_MAP[$emi]);
		FloatIntoString($uexp, $emitter->offsetCurvalFirst + $jj * 4, $final);
		//printf("[%d] %3d (%8.2f): %.3f\n", $emi, $jj, $x, $final);
	}
	//printf("\n\n");
}

WriteFileSafe($assetDir . $outputName . ".uasset", $uasset, true);
WriteFileSafe($assetDir . $outputName . ".uexp",   $uexp,   true);




// Export146->NiagaraScript->CachedScriptVM->ScriptLiterals:

// byte | addr       | floatval       | intval      | 
//    0 | 0x0003B0A3 | 0.0000         | 14          | 
//    4 | 0x0003B0A7 | 0.0000         | 15          | 
//    8 | 0x0003B0AB | 0.0000         | 0           | 
//   12 | 0x0003B0AF | 0.0000         | 1           | 
//   16 | 0x0003B0B3 | 0.0000         | 0           | 
//   20 | 0x0003B0B7 | 5.0000         | 1084227584  | 
//   24 | 0x0003B0BB | 0.0000         | 2           | 
//   28 | 0x0003B0BF | 0.0000         | 3           | 

//   32 | 0x0003B0C3 | 500.0000       | 1140457472  | Galaxy003 kick-in range
//   36 | 0x0003B0C7 | 3500.0000      | 1163575296  | Galaxy003 cutoff range, adds 200 on top of this value
//   40 | 0x0003B0CB | 1.0000         | 1065353216  | 
//   44 | 0x0003B0CF | 0.0000         | 4           | 
//   48 | 0x0003B0D3 | 100.0000       | 1120403456  | 
//   52 | 0x0003B0D7 | 100000000.0000 | 1287568416  | 
//   56 | 0x0003B0DB | 0.0000         | 5           | 
//   60 | 0x0003B0DF | 0.0000         | 6           | 
//   64 | 0x0003B0E3 | 400.0000       | 1137180672  | Galaxy002 cutoff range (but not quite, this seems to have 50 or 100 added on top)
//   68 | 0x0003B0E7 | 0.0000         | 7           | 
//   72 | 0x0003B0EB | 0.0000         | 8           | 
//   76 | 0x0003B0EF | 0.0000         | 9           | 
//   80 | 0x0003B0F3 | 3900.0000      | 1165213696  | Galaxy000 and Galaxy001 cutoff range (both)
//   84 | 0x0003B0F7 | 0.8000         | 1061997773  | 
//   88 | 0x0003B0FB | 0.0000         | 10          | 
//   92 | 0x0003B0FF | 50.0000        | 1112014848  | 
//   96 | 0x0003B103 | 0.0000         | 11          | 
//  100 | 0x0003B107 | 0.0000         | 12          | 
//  104 | 0x0003B10B | 0.8500         | 1062836634  | 
//  108 | 0x0003B10F | 0.0000         | 13          | 
//  112 | 0x0003B113 | 1000.0000      | 1148846080  | 
//  116 | 0x0003B117 | NaN            | -1          | 

// Galaxy000: small colorful dots
// Galaxy001: large colorful dots
// Galaxy002: thin lines; 0..500 range only
// Galaxy003: thick lines; 500..3500 range only
