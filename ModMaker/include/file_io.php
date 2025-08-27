<?php

include_once("include\\config.php");
include_once("include\\stringex.php");

function asDir(string $path){
	if(!str_ends_with($path, "\\")){
		$path .= "\\";
	}
	$path = str_replace("/", "\\", $path);
	$path = str_replace("%LOCALAPPDATA%", getenv("LOCALAPPDATA"), $path);
	$path = str_replace("%USERPROFILE%",  getenv("USERPROFILE") , $path);
	$path = str_replace("%APPDATA%",      getenv("APPDATA")     , $path);
	return $path;
}

function LoadCsv_DEPRECATED($path, $separator = ","){
	//printf("Loading \"%s\"... (%s)\n", $path, $separator);
	// TODO: I love how the $separator is not used at all here
	if(!file_exists($path)){
		printf(ColorStr(sprintf("[ERROR] CSV file doesn't exist: \"%s\"\n", $path), 255, 128, 128));
		exit(1);
	}
	$csv = array_map("str_getcsv", file($path));
	if(empty($csv)){
		printf(ColorStr(sprintf("[ERROR] Failed to load \"%s\"\n", $path), 255, 128, 128));
		exit(1);
	}
	// Sanity check.
	$headerCount = count($csv[0]);
	for($i = 1; $i < count($csv); ++$i){
		if(count($csv[$i]) != $headerCount){
			printf(ColorStr(sprintf("[ERROR] Failed to load \"%s\"\nEntry #%d has %d fields, header has %d, entry:\n", $path, $i, count($csv[$i]), $headerCount, implode(",", $csv[$i])), 255, 128, 128));
			exit(1);
		}
	}
	array_walk($csv, function(&$a) use ($csv) {
		$a = array_combine($csv[0], $a);
	});
	array_shift($csv); // remove column header
	return $csv;
}

function LoadCsv($path, $separator = ","){
	if(!file_exists($path)){
		printf(ColorStr(sprintf("[ERROR] CSV file doesn't exist: \"%s\"\n", $path), 255, 128, 128));
		exit(1);
	}
	$lines = file($path);
	$header = str_getcsv($lines[0], $separator);
	$headerCount = count($header);
	$csv = [];
	for($i = 1; $i < count($lines); ++$i){
		$line = $lines[$i];
		if(empty(trim($line))){
			continue;
		}
		$values = str_getcsv($line, $separator);
		if(count($values) != $headerCount){
			printf(ColorStr(sprintf("[ERROR] Failed to load \"%s\"\nEntry #%d has %d fields, header has %d, entry:\n", $path, $i, count($values), $headerCount, implode($separator, $values)), 255, 128, 128));
			exit(1);
		}
		$entry = [];
		for($j = 0; $j < $headerCount; ++$j){
			$entry[$header[$j]] = $values[$j];
		}
		$csv[] = $entry;
	}
	return $csv;
}

function LoadCsvMap($path, $keyColumn = "pid", $separator = ","){
	// TODO this neeeds a proper rewrite lol
	$csv = LoadCsv($path, $separator);
	
	$map = [];
	foreach($csv as $entry){
		if(!isset($entry[$keyColumn])){
			printf(ColorStr(sprintf("[ERROR] Csv \"%s\" doesn't have expected column \"%s\"\n", $path, $keyColumn), 255, 128, 128));
			exit(1);
		}
		$map[$entry[$keyColumn]] = $entry;
	}
	return $map;
}

function FormCsv(array $entries, $separator = ","){
	$csvLines = [];
	$isHeaderAdded = false;
	foreach($entries as $entry){
		$entry = (array)$entry;
		if(!$isHeaderAdded){
			$line = implode($separator, array_keys($entry));
			$csvLines[] = $line;
			$isHeaderAdded = true;
		}
		$values = [];
		foreach(array_values($entry) as $v){
			$values[] = (is_scalar($v) ? $v : json_encode($v));
		}
		$line = implode($separator, $values);
		$csvLines[] = $line;
	}
	$csv = implode("\r\n", $csvLines);
	return $csv;
}

function Clear(string $file){
	file_put_contents($file, "");
}

function Append(string $file, string $s){
	file_put_contents($file, $s. "\n", FILE_APPEND);
}

function GetFileExtension($path) {
	return (pathinfo($path, PATHINFO_EXTENSION));
}

function GetFileNameWithoutExtension($path) {
	return (pathinfo($path, PATHINFO_FILENAME));
}

function GetSubFolders(string $path){
	$path = asDir($path);
	if(!is_dir($path)){
		return [];
	}
	$result = [];
	$dirHandle = opendir($path);
	while(true){
		$something = readdir($dirHandle);
		//printf("|%s|\n", $something);
		if($something === false){
			break;
		}
		if(in_array($something, [ ".", ".." ]) || !is_dir($path . $something)){
			continue;
		}
		$result[] = $something;
	}
	return $result;
}

function WriteFileSafe(string $path, mixed $data, bool $doAnnounce = false){
	$dir = dirname($path);
	if(!is_dir($dir)){
		mkdir($dir, 0777, true);
	}
	if($doAnnounce){
		printf("Writing %s...\n", $path);
	}
	file_put_contents($path, $data);
}
