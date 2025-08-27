<?php

include_once("include\\file_io.php");
include_once("include\\puzzleDecode.php");

function CreateChestBinary(string $inputPath, string $outputPath){
	$output = "";
	
	$version = 1;
	WriteBinaryInt32($output, $version);
	
	$csv = LoadCsv($inputPath);
	array_multisort(
					array_column($csv, "zoneIndex"), SORT_ASC, SORT_NATURAL,
					array_column($csv, "x" ),        SORT_ASC, SORT_NATURAL,
					array_column($csv, "y" ),        SORT_ASC, SORT_NATURAL,
					array_column($csv, "z" ),        SORT_ASC, SORT_NATURAL,
					$csv);
	//var_dump($csv[0]);
	
	// Sanity check: find chests that are too close to one another.
	$isErrorDetected = false;
	foreach($csv as $ii => $entryA){
		for($jj = 0; $jj < $ii; ++$jj){
			//printf("Comparing %4d vs %4d\n", $ii, $jj);
			$entryB = $csv[$jj];
			$distSquared = DistanceSquared($entryA, $entryB);
			static $maxDist = 320;
			if($distSquared < $maxDist * $maxDist){
				printf("%s", ColorStr("[WARNING] Detected chests too close together: ", 255, 128, 128));
				printf("%.0f %.0f %.0f [%d] <--> %.0f %.0f %0.f [%d] = dist %.2f\n",
						$entryA["x"], $entryA["y"], $entryA["z"], $entryA["zoneIndex"],
						$entryB["x"], $entryB["y"], $entryB["z"], $entryA["zoneIndex"],
						sqrt($distSquared));
				$isErrorDetected = true;
			}
		}
	}
	
	if($isErrorDetected){
		printf("%s\n", ColorStr("=== PAUSING ===", 255, 128, 128)); system("PAUSE");
		//exit(1);
	}
	
	$spawnsPerZone = array_count_values(array_map(function($x){ return $x["zoneIndex"]; }, $csv));
	//printf("%s\n", json_encode($spawnsPerZone, 0xc0));
	
	$totalSpawnCount = count($csv);
	WriteBinaryInt32($output, $totalSpawnCount);
	
	WriteBinaryInt32($output, 0);
	WriteBinaryInt32($output, 0);
	
	$zoneLines = [
		2 => (object)[ "k" => 7.143, "b" => -0.429 ],
		3 => (object)[ "k" => 5.455, "b" => -0.909 ],
		4 => (object)[ "k" => 9.231, "b" => -1.308 ],
		5 => (object)[ "k" => 7.692, "b" => -0.932 ],
		6 => (object)[ "k" => 9.211, "b" => -0.382 ],
	];
	
	for($zoneIndex = 2; $zoneIndex <= 6; ++$zoneIndex){
		WriteBinaryFloat32($output, $zoneLines[$zoneIndex]->k);
		WriteBinaryFloat32($output, $zoneLines[$zoneIndex]->b + 1e-4);
		//WriteBinaryInt32($output, 0);
		WriteBinaryInt32($output, $spawnsPerZone[$zoneIndex]);
		WriteBinaryInt32($output, 0);
	}
	
	foreach($csv as $entry){
		$zoneIndex = intval($entry["zoneIndex"]);
		$flags     = 0; // intval($entry["flags"]);
		$x         = (float)$entry["x"];
		$y         = (float)$entry["y"];
		$z         = (float)$entry["z"];
		$pitch     = (float)$entry["pitch"];
		$yaw       = (float)$entry["yaw"];
		$roll      = (float)$entry["roll"];
		
		//printf("X: |%.4f|\n", $x);
		WriteBinaryInt32(  $output, $zoneIndex);
		WriteBinaryInt32(  $output, $flags);
		WriteBinaryFloat32($output, $x);
		WriteBinaryFloat32($output, $y);
		WriteBinaryFloat32($output, $z);
		WriteBinaryFloat32($output, $pitch);
		WriteBinaryFloat32($output, $yaw);
		WriteBinaryFloat32($output, $roll);
	}
	WriteFileSafe($outputPath, $output, true);
}

function LoadChestBinary(){
	static $chestBin = null;
	if($chestBin === null){
		$chestBinPath = "media\\data\\puzzleradar.bin";
		if(is_file($chestBinPath)){
			$chestBin = file_get_contents($chestBinPath);
		}
	}
	return $chestBin;
}

function ParseChestMeta(){
	$chestMeta = null;
	if($chestMeta === null){
		$rawBin = LoadChestBinary();
		$chestMeta = [];
		for($zoneIndex = 2; $zoneIndex <= 6; ++$zoneIndex){
			$k = StringToFloat($rawBin, 16 + ($zoneIndex - 2) * 16 + 0);
			$b = StringToFloat($rawBin, 16 + ($zoneIndex - 2) * 16 + 4);
			$chestMeta[$zoneIndex] = (object)[
				"zoneIndex" => $chestMeta,
				"k" => $k,
				"b" => $b,
			];
		}
	}
	return $chestMeta;
}

function ParseChestCoords(){
	$chestDataMap = null;
	if($chestDataMap === null){
		$rawBin = LoadChestBinary();
		$chestDataMap = [];
		$chestCount = StringToInt32($rawBin, 4);
		for($ii = 0; $ii < $chestCount; ++$ii){
			$zoneIndex = StringToInt32($rawBin, 96 + $ii * 32 +  0);
			$x         = StringToFloat($rawBin, 96 + $ii * 32 +  8);
			$y         = StringToFloat($rawBin, 96 + $ii * 32 + 12);
			$z         = StringToFloat($rawBin, 96 + $ii * 32 + 16);
			$chestDataMap[] = [
				"ptypeList" => "chest",
				"zoneIndex" => $zoneIndex,
				"x" => $x,
				"y" => $y,
				"z" => $z,
			];
		}
	}
	return $chestDataMap;
}
         
function CreateChestBinary_DEPRECATED(string $inputPath, string $outputPath){
	$csv = LoadCsv($inputPath);
	$output = "";
	foreach($csv as $entry){
		$zoneIndex = intval($entry["zoneIndex"]);
		$flags     = intval($entry["flags"]);
		$x         = (float)$entry["x"];
		$y         = (float)$entry["y"];
		$z         = (float)$entry["z"];
		$pitch     = (float)$entry["pitch"];
		$yaw       = (float)$entry["yaw"];
		$roll      = (float)$entry["roll"];
		
		//printf("X: |%.4f|\n", $x);
		WriteBinaryInt32(  $output, $zoneIndex);
		WriteBinaryInt32(  $output, $flags);
		WriteBinaryFloat32($output, $x);
		WriteBinaryFloat32($output, $y);
		WriteBinaryFloat32($output, $z);
		WriteBinaryFloat32($output, $pitch);
		WriteBinaryFloat32($output, $yaw);
		WriteBinaryFloat32($output, $roll);
	}
	WriteFileSafe($outputPath, $output, true);
}
