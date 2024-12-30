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



///////////////////////////////////////////////////////////////////////////////
// Setup paths.
///////////////////////////////////////////////////////////////////////////////

$inputPuzzleDatabase  = "..\\BaseJsons\\PuzzleDatabase.json";
$inputSandboxZones    = "..\\BaseJsons\\SandboxZones.json";
$inputReadables       = "..\\BaseReadable\\";

$outputPuzzleDatabase = "..\\OutputJsons\\PuzzleDatabase.json";
$outputSandboxZones   = "..\\OutputJsons\\SandboxZones.json";
$outputReadables      = "..\\OutputReadable\\";



///////////////////////////////////////////////////////////////////////////////
// Load files.
///////////////////////////////////////////////////////////////////////////////

$jsonPuzzleDatabase = LoadDecodedUasset($inputPuzzleDatabase);
$puzzleDatabase = &ParseUassetPuzzleDatabase($jsonPuzzleDatabase);

$jsonSandboxZones = LoadDecodedUasset($inputSandboxZones);
$sandboxZones = &ParseUassetSandboxZones($jsonSandboxZones);

SaveReadableUassetDataTo($inputReadables, $puzzleDatabase, $sandboxZones);


