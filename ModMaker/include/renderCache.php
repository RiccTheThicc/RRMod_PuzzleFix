<?php

include_once("include\\config.php");
include_once("include\\lookup.php");

$g_cachePath = $config["temp_dir"] . "renderCache.json";

function IsCacheExists(){
	global $g_cachePath;
	return (is_file($g_cachePath));
}

function ReadCache(){
	global $g_cachePath;
	
	$cache = [];
	if(!is_file($g_cachePath)){
		return [];
	}
	
	$raw = file_get_contents($g_cachePath);
	if($raw === false){
		printf("%s\n", ColorStr("Failed to read " . $g_cachePath . ", maybe delete this file and try again", 255, 128, 128));
		exit(1);
	}
	if(empty($raw)){
		// Edge case: user clears the file contents, keeps the file intact.
		return [];
	}
	$cache = json_decode($raw);
	if($cache === null){
		printf("%s\n", ColorStr("Failed to decode " . $g_cachePath . ", maybe delete this file and try again", 255, 128, 128));
		exit(1);
	}
	return (array)$cache;
}

function WriteCache($cache){
	global $g_cachePath;
	
	$raw = json_encode($cache, JSON_PRETTY_PRINT);
	if(!file_put_contents($g_cachePath, $raw)){
		printf("%s\n", ColorStr("Failed to write " . $g_cachePath, 255, 128, 128));
		exit(1);
	}
	return true;
}

function ClearCache(){
	global $g_cachePath;
	
	if(is_file($g_cachePath)){
		unlink($g_cachePath);
	}
}

function UpdateCache(string $key, array $pids){
	
	$pids = array_values($pids);
	sort($pids);
	$value = sha1(implode($pids));
	
	//printf("Requested update for key |%s|, %d pids, hash %s\n", $key, count($pids), $value);
	
	$cache = ReadCache();
	
	$isModified = !isset($cache[$key]) || ($cache[$key] != $value);
	if($isModified){
		$cache[$key] = $value;
		WriteCache($cache);
	}
	return $isModified;
}

