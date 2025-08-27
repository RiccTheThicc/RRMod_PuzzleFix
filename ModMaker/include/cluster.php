<?php

include_once("include\\config.php");
include_once("include\\pjson_parse.php");
include_once("include\\lookup.php");

function GetClusterNameMap(){
	static $clusterNameMap = [
		"HardCOWYC"        => "Lucent",
		"Lobby"            => "Lucent",
		"Cong"             => "Cong.",
		"myopia"           => "Myopia",
		"lostgridsLogic"   => "LostGrids",
		"lostgridsPattern" => "LostGrids",
		"lostgridsMusic"   => "LostGrids",
	];
	return $clusterNameMap;
}

function GetPublicClusterName(string $internalName){
	$clusterNameMap = GetClusterNameMap();
	if(!isset($clusterNameMap[$internalName])){
		return "Unknown";
	}
	return $clusterNameMap[$internalName];
}

function GetClusterMap(){
	static $clusterMap = null;
	if($clusterMap === null){
		$clusterNamesInternal = array_keys(GetClusterNameMap());
		$clusterMap = [];
		foreach($clusterNamesInternal as $nameInternal){
			$clusterMap[$nameInternal] = [];
		}
		
		$puzzleMap = GetPuzzleMap(true);
		foreach($puzzleMap as $pid => $data){
			$pid = (int)$pid;
			$poolInternal = $data->pool;
			//if(in_array($data->pool, ["HardCOWYC","Lobby","Cong","myopia"])) { printf("What the hell is pool %5d %s\n", $pid, $data->pool); }
			if(!in_array($poolInternal, $clusterNamesInternal)){
				continue;
			}
			$clusterMap[$poolInternal][] = $pid;
		}
		
		foreach($clusterMap as $nameInternal => &$pidList_ref){
			sort($pidList_ref);
		}
	}
	return $clusterMap;
}

function GetAllClusterPids(){
	static $clusterPids = null;
	if($clusterPids === null){
		$clusterPids = [];
		$clusterMap = GetClusterMap();
		foreach($clusterMap as $nameInternal => $pidList){
			$clusterPids = array_values(array_merge($clusterPids, $pidList));
		}
		$clusterPids = array_values(array_unique($clusterPids));
		sort($clusterPids);
	}
	return $clusterPids;
}
