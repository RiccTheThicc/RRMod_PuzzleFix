<?php

include_once("include\\file_io.php");
include_once("include\\stringex.php");
include_once("include\\vdfToJson.php");

function GetSteamInstallPath(){
	// Unfortunately I have to peek into Windows Registry to know where Steam is installed.
	// It's most likely C:\Program Files (x86)\Steam but you can't be sure.
	$registryCommand = "reg query HKLM\\SOFTWARE\\WOW6432Node\\Valve\\Steam /v InstallPath";
	$rawOutput = [];
	$resultCode = 0;
	exec($registryCommand, $rawOutput, $resultCode);
	if($resultCode != 0){
		return "";
	}
	foreach($rawOutput as $line){
		if(preg_match("/^\s+InstallPath\s+REG_SZ\s+(.*)$/", $line, $matches) && count($matches) == 2){
			//printf("|%s|\n", $line);
			$installPath = $matches[1];
			return asDir($installPath);
		}
	}
	return "";
}

function FindSteamLocalConfigPaths(){
	$steamInstallPath = GetSteamInstallPath();
	if(empty($steamInstallPath)){
		return [];
	}
	$userdataPath = asDir($steamInstallPath . "userdata");
	$accountIds = GetSubFolders($userdataPath);
	$localConfigPaths = [];
	foreach($accountIds as $accountId){
		$configPath = asDir($userdataPath . $accountId) . "config\\localconfig.vdf";
		if(!is_file($configPath)){
			continue;
		}
		$localConfigPaths[] = $configPath;
	}
	return $localConfigPaths;
}

function GetIslandsPlaytime(){
	// There might be multiple account info on your PC.
	// I don't care to figure out which is the proper one.
	// Just search for Islands playtime, grab the largest value if several exist.
	$largestPlaytime = 0;
	$localConfigPaths = FindSteamLocalConfigPaths();
	//$parser = new VdfParser\Parser;
	foreach($localConfigPaths as $localConfigPath){
		$vdfRaw = file_get_contents($localConfigPath);
		if($vdfRaw === false){
			continue;
		}
		//$json = $parser->parse($vdfRaw);
		$vdfRaw = strtolower($vdfRaw);
		$json = VdfToJson($vdfRaw);//, [ "forceLowerCase" => true]);
		if(empty($json)){
			continue;
		}
		//printf("Parsed file %s\n", $localConfigPath);
		//file_put_contents("R:\\Garbage\\testjson.json", json_encode($json, JSON_PRETTY_PRINT));
		$playTime = @$json["userlocalconfigstore"]["software"]["valve"]["steam"]["apps"]["2071500"]["playtime"];
		if(is_numeric($playTime)){
			$largestPlaytime = max($largestPlaytime, (int)$playTime);
		}
	}
	return $largestPlaytime;
}

