<?php

include_once("include\\stringex.php");

date_default_timezone_set("UTC"); // enforce UTC for all code for simplicity

include_once("include\\file_io.php");

$configPath = "config.txt";
$config = @parse_ini_file($configPath);
if($config === FALSE){
	//printf("[ERROR] Failed to read \"%s\". Make sure to start from the working directory.\n", $configPath);
	$config = [];
}

if(!isset($config["save_path"]))   { $config["save_path"]   = "";                                       }
if(!isset($config["output_dir"]))  { $config["output_dir"]  = "output";                                 }
if(!isset($config["details_dir"])) { $config["details_dir"] = asDir($config["output_dir"]) . "details"; }
if(!isset($config["spawns_dir"]))  { $config["spawns_dir"]  = asDir($config["output_dir"]) . "spawns";  }
if(!isset($config["temp_dir"]))    { $config["temp_dir"]    = "temp";                                   }
if(!isset($config["name"]))        { $config["name"]        = "Anonymous";                              }
if(!isset($config["title"]))       { $config["title"]       = "Ignorer of Config File";                 }

$config["base_dir"]    = asDir(".");
$config["output_dir"]  = asDir($config["output_dir"]);
$config["temp_dir"]    = asDir($config["temp_dir"]);
$config["details_dir"] = asDir($config["details_dir"]);
$config["spawns_dir"]  = asDir($config["spawns_dir"]);
$config["name"]        = trim($config["name"]); //substr(trim($config["name"]),  0, 50);
$config["title"]       = trim($config["title"]);//substr(trim($config["title"]), 0, 100);
$config["pjson_path"]  = "media\\data\\Puzzles.json";

//if(!is_dir($config["temp_dir"])){
//	if(!mkdir($config["temp_dir"], 0777, true)){
//		printf("%s\n", ColorStr("Failed to create temporary folder " . $config["temp_dir"], 255, 128, 128));
//		printf("%s\n", ColorStr("Please check your config.txt settings", 255, 128, 128));
//		exit(1);
//	}
//}
//if(!is_dir($config["output_dir"])){
//	if(!mkdir($config["output_dir"], 0777, true)){
//		printf("%s\n", ColorStr("Failed to create output folder " . $config["output_dir"], 255, 128, 128));
//		printf("%s\n", ColorStr("Please check your config.txt settings", 255, 128, 128));
//		exit(1);
//	}
//}
//if(!is_dir($config["details_dir"])){
//	if(!mkdir($config["details_dir"], 0777, true)){
//		printf("%s\n", ColorStr("Failed to create output folder " . $config["details_dir"], 255, 128, 128));
//		printf("%s\n", ColorStr("Please check your config.txt settings", 255, 128, 128));
//		exit(1);
//	}
//}
//if(!is_dir($config["spawns_dir"])){
//	if(!mkdir($config["spawns_dir"], 0777, true)){
//		printf("%s\n", ColorStr("Failed to create output folder " . $config["spawns_dir"], 255, 128, 128));
//		printf("%s\n", ColorStr("Please check your config.txt settings", 255, 128, 128));
//		exit(1);
//	}
//}

