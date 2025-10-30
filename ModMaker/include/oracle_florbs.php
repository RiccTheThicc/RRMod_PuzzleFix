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

function UeRand($seed){
	$seed &= 0xFFFFFFFF;
	$seed = ($seed * 196314165 + 907633515) & 0xFFFFFFFF;
	$data = 0x3F800000 | ($seed >> 9);
	$frand = unpack("f", pack("i", $data))[1] - 1.0;
	return $frand;
}

function UeRandRange($seed, $a, $b){
	$result = $a + intval(($b - $a + 1) * UeRand($seed));
	return $result;
}

function Oracle_CalcZoneFlorbSpawns(int $zoneIndex, array $stats, $ptypeHash, int $currentTs, int $minTs){
	$spawnMap = [];
	
	$spawnJitterSeed = $ptypeHash + crc32(pack("H*", sprintf("%02X", $zoneIndex)));
	$spawnJitter = UeRandRange($spawnJitterSeed, -43200, 43200);
	
	$groupSize    = $stats["groupSize"];
	$groupCount   = $stats["groupCount"];
	$totalPuzzles = $stats["totalPuzzles"];
	
	$currentBlock = intval(floor(($currentTs + $spawnJitter) / (86400 * $groupCount)));
	
	$hubProfile = GetHubProfile();
	$pids = $hubProfile[$zoneIndex]["racingBallCourse"];
	$spawnMap = [];
	
	foreach($pids as $pid){
		// Test current block and next block.
		for($block = $currentBlock; $block <= $currentBlock + 1; ++$block){
			$blockBeginTs = $block * $groupCount * 86400 - $spawnJitter;
			$seed = crc32(pack("i", $pid)) + crc32(pack("q", $block));
			$groupId = UeRandRange($seed, 0, $groupCount - 1);
			$spawnTs = $blockBeginTs + $groupId * 86400;
			if($spawnTs >= $minTs){
				//printf("%-5d: group %-2d of block %-4d\n", $pid, $groupId, $block);
				$spawnMap[$pid] = $spawnTs;
				break;
			}
		}
	}
	return $spawnMap;
}

function Oracle_CalcAllFlorbSpawns(){
	static $florbSpawnPlan = [
		2 => [ "groupSize" =>  5, "groupCount" => 7, "totalPuzzles" => 35 ],
		3 => [ "groupSize" =>  9, "groupCount" => 8, "totalPuzzles" => 70 ],
		4 => [ "groupSize" => 10, "groupCount" => 9, "totalPuzzles" => 93 ],
		5 => [ "groupSize" =>  6, "groupCount" => 9, "totalPuzzles" => 54 ],
		6 => [ "groupSize" =>  6, "groupCount" => 7, "totalPuzzles" => 39 ],
	];
	static $florbHash = 0x5c677a7d;
	
	$currentTs = time();
	$minTs = $currentTs - 86400;
	
	$fullSpawnMap = [];
	foreach($florbSpawnPlan as $zoneIndex => $stats){
		$fullSpawnMap += Oracle_CalcZoneFlorbSpawns($zoneIndex, $stats, $florbHash, $currentTs, $minTs);
	}
	asort($fullSpawnMap, SORT_NATURAL);
	
	return $fullSpawnMap;
}

$florbSpawnMap = Oracle_CalcAllFlorbSpawns();

$test = [];
foreach($florbSpawnMap as $pid => $ts){
	$test[$pid] = (new DateTime("@" . (string)($ts + 3*60*60)))->format("Y.m.d H:i");
}

printf("%s\n", json_encode($test, 0xc0));

