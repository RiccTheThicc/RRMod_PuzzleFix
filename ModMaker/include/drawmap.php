<?php

include_once("include\\lookup.php");
include_once("include\\config.php");
include_once("include\\imageSmoothArc_fast.php");
include_once("include\\imageSmoothLine.php");
include_once("include\\imgex.php");
include_once("include\\drawCommon.php");
include_once("include\\renderCache.php");
include_once("include\\icons.php");

$g_canvas = (object)[
	"path" => "",
	"gd" => null,
	"shiftX" => 0,
	"shiftY" => 0,
	"unitsPerPixelsX" => 100,
	"unitsPerPixelsY" => 100,
	"legendScale" => 1.0,
	"isLoaded" => false,
];

function WorldToPixel($x, $y){
	global $g_canvas;
	return [round(($x + $g_canvas->shiftX) / $g_canvas->unitsPerPixelsX), round(($y + $g_canvas->shiftY) / $g_canvas->unitsPerPixelsY)];
}

function SetCanvas(string $imgPath, float $shiftX, float $shiftY, float $uppX, float $uppY, float $legendScale = 1.0){
	global $g_canvas;
	unset($g_canvas->gd);
	$g_canvas->gd = null;
	$g_canvas->isLoaded = false;
	if(!is_file($imgPath)){
		printf("%s\n", ColorStr("Missing canvas file " . $imgPath, 255, 128, 128));
		exit(1);
	}
	$canvasGd = LoadImage($imgPath);
	if(!$canvasGd){
		printf("%s\n", ColorStr("Failed to load canvas " . $imgPath, 255, 128, 128));
		exit(1);
	}
	if($shiftX < 0 || $shiftY < 0 || $uppX <= 0 || $uppY <= 0){
		printf("%s\n", ColorStr("Bad settings for canvas " . $imgPath, 255, 128, 128));
		exit(1);
	}
	//imagesavealpha($g_canvas, true);
	//imagealphablending($g_canvas, false);
	$g_canvas->path = $imgPath;
	$g_canvas->gd = $canvasGd;
	$g_canvas->shiftX = $shiftX;
	$g_canvas->shiftY = $shiftY;
	$g_canvas->unitsPerPixelsX = $uppX;
	$g_canvas->unitsPerPixelsY = $uppY;
	$g_canvas->isLoaded = true;
	$g_canvas->legendScale = $legendScale;
	//printf("%s\n", ColorStr("Loaded canvas " . $imgPath, 160, 160, 160));
	return true;
}

function GetMedalColors(){
	static $medalColors = [
		-1 => "#a3120500", // unsolved
		 0 => "#CE6A0000", // bronze
		 1 => "#D8D8D800", // silver
		 2 => "#CEA55800", // gold
		 3 => "#85A4F200", // platinum
	];
	return $medalColors;
}

function BuildPidToMedalColorMap($pids, array $medalMap){
	if(count($medalMap) != 4){
		printf("%s\n", ColorStr(__FUNCTION__ . "expects an array of 4 sub-arrays.", 255, 128, 128));
		exit(1);
	}
	$medalColors = GetMedalColors();
	$pidToMedalColorMap = [];
	foreach($medalMap as $tier => $pidList){
		foreach($pidList as $pid){
			$pidToMedalColorMap[$pid] = $medalColors[$tier];
		}
	}
	$unsolvedPids = array_diff($pids, array_keys($pidToMedalColorMap));
	foreach($unsolvedPids as $pid){
		$pidToMedalColorMap[$pid] = $medalColors[-1];
	}
	ksort($pidToMedalColorMap);
	return $pidToMedalColorMap;
}

$g_drawInfo = [];
function SetDrawInfo(array $drawInfo){
	global $g_drawInfo;
	$g_drawInfo = [];
	foreach($drawInfo as $ptypeName => $info){
		$ptype = PuzzleInternalName($ptypeName);
		if(empty($ptype)){
			printf("%s\n", ColorStr(__FUNCTION__ . ": unknown puzzle type \"" . $ptypeName . "\"", 255, 128, 128));
			exit(1);
		}
		$color   = ($info["color"] ?? "#FFFFFF00");
		$size    = Clamp(intval($info["size"] ?? 14), 2, 200);
		$icon    = (trim(strtolower($info["icon"])) ?? "circle");
		$graph   = (trim(strtolower($info["graph"])) ?? "node");
		$outline = Clamp(intval($info["outline"] ?? 0), 0, 200);
		if(!in_array($icon, [ "circle", "square" ])){
			printf("%s\n", ColorStr(__FUNCTION__ . ": unknown icon \"" . $icon . "\" for " . $ptypeName, 255, 128, 128));
			exit(1);
		}
		if(!in_array($graph, [ "node", "vect", "flow", "boss", "star" ])){
			printf("%s\n", ColorStr(__FUNCTION__ . ": unknown graph \"" . $graph . "\" for " . $ptypeName, 255, 128, 128));
			exit(1);
		}
		$g_drawInfo[$ptype] = [
			"color"   => $color,
			"size"    => $size,
			"icon"    => $icon,
			"graph"   => $graph,
			"outline" => $outline,
		];
	}
}
function GetAllDrawInfoKeys(){
	global $g_drawInfo;
	return (array_keys($g_drawInfo));
}
function GetDrawInfoFor(string $ptype){
	global $g_drawInfo;
	if(!isset($g_drawInfo[$ptype])){
		return null;
	}
	return $g_drawInfo[$ptype];
}

function DrawItem($gd, $px, $py, $icon, $size, $colorCode){
	switch($icon){
		case "circle":{
			imageSmoothFilledCircle($gd, $px, $py, $size, ColorCodeToRGBA($gd, $colorCode));
			break;
		}
		case "square":{
			imagefilledrectangle($gd, round($px - $size / 2.0), round($py - $size / 2.0), round($px + $size / 2.0), round($py + $size / 2.0), ColorCodeToIndex($gd, $colorCode));
			break;
		}
		default:{
			break;
		}
	}
}

function DrawLabel($gd, $px, $py, $text, $colorCode, $iconSize, $fontSize){
	$shiftY = round(-$iconSize / 2.0 - ($fontSize / 0.75) - 3.0);
	DrawTextFast($gd, $text, $px, $py + $shiftY, $colorCode, $fontSize, "./arial.ttf", 0, 0, true);
}

function DrawMap(array $options = []){
	global $g_canvas;
	if(!$g_canvas->isLoaded){
		printf("%s\n", ColorStr("Canvas not set - call SetCanvas first", 255, 128, 128));
		exit(1);
	}
	
	$defaultOptions = [
		"pids"           => [],
		"output"         => "sample.jpg",
		"forceZoneColor" => false,
		"onlyDraw"       => [],
		"dontDraw"       => [],
		"onlyZone"       => -1,
		"labelType"      => "",
		"labelSize"      => 18,
		"jpgQuality"     => 96, // 97-99% quality SIGNIFICANTLY reduces the file size / time to image save to disk compared to 100%
		"vectLength"     => 900, // length of directional vectors in map units
		"title"          => "",
		"titleColor"     => "#FFFFFF00",
		"titleSize"      => 46,
		"autoTitle"      => "",
		"legendColumns"  => 2,
		"legendScale"    => $g_canvas->legendScale,
		"useMedalMap"    => null,
		"forceRender"    => false,
	];
	$options = array_merge($defaultOptions, $options); // don't merge recursively
	
	$pidsGiven      = (array)$options["pids"];
	$outputPath     = $options["output"];
	$ext            = GetFileExtension($outputPath);
	$fileName       = GetFileNameWithoutExtension($outputPath);
	$forceZoneColor = (bool)$options["forceZoneColor"];
	$onlyDraw       = array_map(fn($ptypeName) => PuzzleInternalName($ptypeName), (array)$options["onlyDraw"]);
	$dontDraw       = array_map(fn($ptypeName) => PuzzleInternalName($ptypeName), (array)$options["dontDraw"]);
	$onlyZone       = (int)($options["onlyZone"]);
	$labelType      = (in_array($options["labelType"], [ "ptype", "pid" ] ) ? $options["labelType"] : "");
	$labelSize      = Clamp(intval($options["labelSize"]), 1, 1000);
	$jpgQuality     = Clamp(intval($options["jpgQuality"]), 10, 100);
	$vectLength     = Clamp($options["vectLength"], 0, 10000);
	$title          = $options["title"];
	$titleColor     = $options["titleColor"];
	$titleSize      = Clamp(intval($options["titleSize"]), 1, 10000);
	$autoTitle      = $options["autoTitle"];
	$legendColumns  = Clamp(intval($options["legendColumns"]), 1, 3);
	$legendScale    = Clamp($options["legendScale"], 0.1, 10.0);
	$useMedalMap    = $options["useMedalMap"];
	$forceRender    = $options["forceRender"];
	
	$renderOrderMap = array_flip(array_reverse(GetAllDrawInfoKeys()));
	//print_r($renderOrder);
	
	$puzzleMap = GetPuzzleMap(true);
	$allPidsToDraw = [];
	$pidsToDrawOrdered = [];
	foreach($pidsGiven as $pid){
		if(!isset($puzzleMap[$pid])){
			printf("%s\n", ColorStr("Unknown puzzle id " . $pid . " - rendering " . $outputPath . " failed", 255, 128, 128));
			printf("%s\n", ColorStr("How did this even happen though?", 255, 128, 128));
			exit(1);
		}
		$data = $puzzleMap[$pid];
		$zoneIndex = $data->zoneIndex;
		$ptype = $data->ptype;
		if(!isset($renderOrderMap[$ptype])){
			continue;
		}
		if(!empty($onlyDraw) && !in_array($ptype, $onlyDraw)){
			continue;
		}
		if(!empty($dontDraw) && in_array($ptype, $dontDraw)){
			continue;
		}
		if(IsHubZone($onlyZone) && $onlyZone != $zoneIndex){
			continue;
		}
		$renderOrder = $renderOrderMap[$ptype];
		if(!isset($pidsToDrawOrdered[$renderOrder])){
			$pidsToDrawOrdered[$renderOrder] = [];
		}
		$pidsToDrawOrdered[$renderOrder][] = $pid;
		$allPidsToDraw[] = $pid;
	}
	ksort($pidsToDrawOrdered);
	sort($allPidsToDraw);
	
	if(!empty($autoTitle)){
		$autoTitleTest = explode("|", $autoTitle);
		if(count($autoTitleTest) >= 2){
			$atType = trim(strtolower($autoTitleTest[0]));
			$atName = trim($autoTitleTest[1]);
			if($atType == "all"){
				$title = "All " . $atName . " puzzles.";
			}elseif($atType == "remain" || $atType == "remaining"){
				if(empty($allPidsToDraw)){
					$title = "You have solved every " . $atName . " puzzle in the game!\nNothing to draw here.";
				}else{
					$title = "Your remaining " . $atName . " puzzles.";
				}
			}
		}
	}
	
	$extraData = [];
	if(!empty($useMedalMap)){
		$extraData = array_map(function($x){ return count($x); }, $useMedalMap);
		//printf("%s %s\n", $outputPath, implode(",", $extraData));
	}
	$needsRedraw = (UpdateCache($fileName, $allPidsToDraw, $extraData) || !is_file($outputPath) || $forceRender);
	if(!$needsRedraw){
		//printf("%s\n", ColorStr(sprintf("Skipping  %4d puzzles of %s", count($allPidsToDraw), $outputPath), 160, 160, 160));
		return true;
	}
	printf("%s\n", ColorStr(sprintf("Rendering %4d puzzles to %s", count($allPidsToDraw), $outputPath), 128, 192, 255));
	
	$canvas = $g_canvas->gd;
	$imgWidth  = imagesx($canvas);
	$imgHeight = imagesy($canvas);
	$gd = imagecrop($canvas, ["x" => 0, "y" => 0, "width" => $imgWidth, "height" => $imgHeight]); // copy canvas
	imagesavealpha($gd, true);
	imagealphablending($gd, true);
	
	foreach($pidsToDrawOrdered as $order => $pidList){
		$ptype = array_search($order, $renderOrderMap); // oopsie lol
		
		$myDrawInfo   = GetDrawInfoFor($ptype);
		$colorCode    = $myDrawInfo["color"];
		$graph        = $myDrawInfo["graph"];
		$sizeBase     = $myDrawInfo["size"];
		$icon         = $myDrawInfo["icon"];
		$outlineBase  = $myDrawInfo["outline"];
		
		$pidToMedalColorMap = null;
		if(!empty($useMedalMap)){
			$pidToMedalColorMap = BuildPidToMedalColorMap($pidList, $useMedalMap);
			//printf("%s\n", json_encode($pidToMedalColorMap, 0xc0)); exit(1);
		}
		
		foreach($pidList as $pid){
			$data = $puzzleMap[$pid];
			if(!isset($data->coords) || empty($data->coords)){
				printf("%s\n", ColorStr("Warning: failed to render puzzle " . $pid . ", no coordinate data\n", 255, 128, 128));
				continue;
			}
			$zoneIndex = $data->zoneIndex;
			if($forceZoneColor){
				$colorCode = GetZoneColorCode($zoneIndex);
			}
			if(!empty($useMedalMap) && !empty($pidToMedalColorMap)){
				$colorCode = $pidToMedalColorMap[$pid];
			}
			
			$coordsList = $data->coords;
			for($index = count($coordsList) - 1; $index >= 0; --$index){
				$coord = $coordsList[$index];
				list($px, $py) = WorldToPixel($coord->x, $coord->y);
				$isFirstCoord = ($index == 0);
				
				if(!$isFirstCoord && $graph == "node"){
					continue;
				}
				
				$iconSize = $sizeBase;
				$outline = $outlineBase;
				if(!$isFirstCoord && $graph == "star"){
					$iconSize /= 2;
					$outline /= 2;
				}
				if(!$isFirstCoord && $graph == "boss"){
					$iconSize /= 4;
					$outline /= 4;
				}
				
				if($outline > 0){
					DrawItem($gd, $px, $py, $icon, $iconSize + $outline, "#00000030");
				}
				DrawItem($gd, $px, $py, $icon, $iconSize, $colorCode);
				
				if($isFirstCoord){
					if($graph == "vect"){
						$myVectLength = $vectLength;
						if($icon == "square"){
							$myVectLength *= 1.3;
						}
						$angleRad = deg2rad($coord->yaw);
						$targetX = $coord->x + $myVectLength * (cos($angleRad));
						$targetY = $coord->y + $myVectLength * (sin($angleRad));
						list($tx, $ty) = WorldToPixel($targetX, $targetY);
						DrawLine($gd, $px, $py, $tx, $ty, $colorCode, 3);
					}
					// UPD: draw labels later, so that they're on top of everything else.
					//if(!empty($labelType)){
					//	$text = "";
					//	switch($labelType){
					//		case "pid":   { $text = $pid; break; }
					//		case "ptype": { $text = PuzzlePrettyName($ptype); break; }
					//		default: break;
					//	}
					//	DrawLabel($gd, $px, $py, $text, $colorCode, $iconSize + $outline, $labelSize);
					//}
				}else{
					$otherIndex = -1;
					switch($graph){
						case "flow":
						case "boss":{
							$otherIndex = $index - 1;
							break;
						}
						case "star":{
							$otherIndex = 0;
							break;
						}
						default:{
							break;
						}
					}
					if($otherIndex != -1){
						$otherCoord = $coordsList[$otherIndex];
						list($opx, $opy) = WorldToPixel($otherCoord->x, $otherCoord->y);
						DrawLine($gd, $px, $py, $opx, $opy, $colorCode, 2);
					}
				}
			}
		}
	}
	
	if(!empty($labelType)){
		foreach($pidsToDrawOrdered as $order => $pidList){
			$ptype = array_search($order, $renderOrderMap);
			$myDrawInfo   = GetDrawInfoFor($ptype);
			$colorCode    = $myDrawInfo["color"];
			$sizeBase     = $myDrawInfo["size"];
			$outlineBase  = $myDrawInfo["outline"];
			
			foreach($pidList as $pid){
				$data = $puzzleMap[$pid];
				if(!isset($data->coords) || empty($data->coords)){
					printf("%s\n", ColorStr("Warning: failed to render puzzle " . $pid . ", no coordinate data\n", 255, 128, 128));
					continue;
				}
				$zoneIndex = $data->zoneIndex;
				if($forceZoneColor){
					$colorCode = GetZoneColorCode($zoneIndex);
				}
				if(!empty($useMedalMap) && !empty($pidToMedalColorMap)){
					$colorCode = $pidToMedalColorMap[$pid];
				}
				
				$coordsList = $data->coords;
				$coord = $coordsList[0];
				list($px, $py) = WorldToPixel($coord->x, $coord->y);

				$text = "";
				switch($labelType){
					case "pid":   { $text = $pid; break; }
					case "ptype": { $text = PuzzlePrettyName($ptype); break; }
					default: break;
				}
				DrawLabel($gd, $px, $py, $text, $colorCode, $sizeBase + $outlineBase, $labelSize);
			}
		}
	}
	
	$legendLineIndex = 0;
	$currLegendColumn = 0;
	$colToX = [ 1 => 0.86, 2 => 0.77, 3 => 0.68 ];
	
	$fontSize = round(23 * $legendScale);
	
	$pidsToDrawReordered = array_reverse($pidsToDrawOrdered, true);
	foreach($pidsToDrawReordered as $order => $pidList){
		if(empty($useMedalMap)){
			$count        = count($pidList);
			$ptype        = array_search($order, $renderOrderMap);
			$myDrawInfo   = GetDrawInfoFor($ptype);
			$colorCode    = $myDrawInfo["color"];
			$graph        = $myDrawInfo["graph"];
			$sizeBase     = $myDrawInfo["size"];
			$icon         = $myDrawInfo["icon"];
			$outlineBase  = Clamp($myDrawInfo["outline"], 0, 1000);
			
			// This is absolutely awful but I really don't care to write something more robust.
			$px = round($imgWidth  * ($colToX[$legendColumns] - ($legendScale - 1.0) * 0.05 + $currLegendColumn * 0.13));
			$py = round($imgHeight * (0.03 + $legendLineIndex * 0.035));

			$finalSize = $sizeBase * 2.5 * $legendScale;
			$finalOutline = $outlineBase * 2.5 * $legendScale;
			if($finalOutline > 0){
				DrawItem($gd, $px, $py, $icon, $finalSize + $finalOutline, "#00000030");
			}
			DrawItem($gd, $px, $py, $icon, $finalSize, $colorCode);
			
			$text = " " . $count . " " . PuzzlePrettyName($ptype);
			DrawTextFast($gd, $text, round($px + 45 * $legendScale), round($py - ($fontSize * 0.9)), $colorCode, $fontSize, "./arial.ttf", -1, 0, true);
			++$legendLineIndex;
			if($legendLineIndex >= count($pidsToDrawReordered) / $legendColumns){
				++$currLegendColumn;
				$legendLineIndex = 0;
			}
		}else{
			// very big yikes
			$medalColors = GetMedalColors();
			$countMap = [];
			$countMap[-1] = count($pidList);
			foreach($useMedalMap as $medalIndex => $pp){
				$countMap[$medalIndex] = count($pp);
				$countMap[-1] -= count($pp);
			}
			$ptype        = array_search($order, $renderOrderMap);
			$myDrawInfo   = GetDrawInfoFor($ptype);
			$graph        = $myDrawInfo["graph"];
			$sizeBase     = 14;
			$icon         = $myDrawInfo["icon"];
			$outlineBase  = 3;
			foreach($countMap as $medalIndex => $count){
				$colorCode = $medalColors[$medalIndex];
				
				$px = round($imgWidth  * ($colToX[$legendColumns] - ($legendScale - 1.0) * 0.05 + $currLegendColumn * 0.13));
				$py = round($imgHeight * (0.03 + $legendLineIndex * 0.035));

				$finalSize = $sizeBase * 2.5 * $legendScale;
				$finalOutline = $outlineBase * 2.5 * $legendScale;
				if($finalOutline > 0){
					DrawItem($gd, $px, $py, $icon, $finalSize + $finalOutline, "#00000030");
				}
				DrawItem($gd, $px, $py, $icon, $finalSize, $colorCode);
				
				$text = " " . $count . " " . MedalTierToName($medalIndex);
				DrawTextFast($gd, $text, round($px + 45 * $legendScale), round($py - ($fontSize * 0.9)), $colorCode, $fontSize, "./arial.ttf", -1, 0, true);
				++$legendLineIndex;
				//if($legendLineIndex >= count($pidsToDrawReordered) / $legendColumns){
				//	++$currLegendColumn;
				//	$legendLineIndex = 0;
				//}
			}
		}
	}
	
	if(!empty($title)){
		$px = round($imgWidth  * 0.015);
		$py = round($imgHeight * 0.010);
		DrawTextFast($gd, $title, $px, $py, $titleColor, $titleSize, "./arial.ttf", -1, -1, true);
	}

	SaveImageAs($gd, $outputPath, $jpgQuality);
	imagedestroy($gd);
	$gd = null;
	
	return true;
}

function DrawSpawnMap(array $options = []){
	global $g_canvas;
	if(!$g_canvas->isLoaded){
		printf("%s\n", ColorStr("Canvas not set - call SetCanvas first", 255, 128, 128));
		exit(1);
	}
	
	$defaultOptions = [
		"spawnType"      => "none",
		"output"         => "sample.jpg",
		"iconScale"      => 1.0,
		"forceZoneColor" => false,
		"onlyDraw"       => [],
		"dontDraw"       => [],
		"onlyZone"       => -1,
		"labelType"      => "",
		"labelSize"      => 18,
		"jpgQuality"     => 96, // 97-99% quality SIGNIFICANTLY reduces the file size / time to image save to disk compared to 100%
		"vectLength"     => 900, // length of directional vectors in map units
		"title"          => "",
		"titleColor"     => "#FFFFFF00",
		"titleSize"      => 46,
		"autoTitle"      => "",
		"legendColumns"  => 2,
		"legendScale"    => $g_canvas->legendScale,
		"useMedalMap"    => null,
		"forceRender"    => false,
	];
	$options = array_merge($defaultOptions, $options); // don't merge recursively
	
	$spawnType      = $options["spawnType"];
	$outputPath     = $options["output"];
	$iconScale      = $options["iconScale"];
	$ext            = GetFileExtension($outputPath);
	$fileName       = GetFileNameWithoutExtension($outputPath);
	$forceZoneColor = (bool)$options["forceZoneColor"];
	$onlyDraw       = array_map(fn($ptypeName) => PuzzleInternalName($ptypeName), (array)$options["onlyDraw"]);
	$dontDraw       = array_map(fn($ptypeName) => PuzzleInternalName($ptypeName), (array)$options["dontDraw"]);
	$onlyZone       = (int)($options["onlyZone"]);
	$labelType      = (in_array($options["labelType"], [ "ptype", "pid" ] ) ? $options["labelType"] : "");
	$labelSize      = Clamp(intval($options["labelSize"]), 1, 1000);
	$jpgQuality     = Clamp(intval($options["jpgQuality"]), 10, 100);
	$vectLength     = Clamp($options["vectLength"], 0, 10000);
	$title          = $options["title"];
	$titleColor     = $options["titleColor"];
	$titleSize      = Clamp(intval($options["titleSize"]), 1, 10000);
	$autoTitle      = $options["autoTitle"];
	$legendColumns  = Clamp(intval($options["legendColumns"]), 1, 3);
	$legendScale    = Clamp($options["legendScale"], 0.1, 10.0);
	$useMedalMap    = $options["useMedalMap"];
	$forceRender    = $options["forceRender"];
	
	$sourceCsvPath = "media\\data\\spawns.csv";
	$rawCsv = file_get_contents($sourceCsvPath);
	$chestBin = LoadChestBinary();
	$extraData = array_map(function($x){ return ord($x); }, str_split(md5($rawCsv . $chestBin)));
	
	$needsRedraw = (UpdateCache($fileName, [], $extraData) || !is_file($outputPath) || $forceRender);
	if(!$needsRedraw){
		//printf("%s\n", ColorStr(sprintf("Skipping  %4d puzzles of %s", count($allPidsToDraw), $outputPath), 160, 160, 160));
		return true;
	}
	$canvas = $g_canvas->gd;
	$imgWidth  = imagesx($canvas);
	$imgHeight = imagesy($canvas);
	$gd = imagecrop($canvas, ["x" => 0, "y" => 0, "width" => $imgWidth, "height" => $imgHeight]); // copy canvas
	imagesavealpha($gd, true);
	imagealphablending($gd, true);
	
	$isGenericMode = in_array($spawnType, [ "Rune", "Monument", "SlabSocket" ]);
	$isClusterMode = ($spawnType == "ClusterRune");
	$isChestMode = ($spawnType == "Chest");
	$clusterBoundsMap = [];
	
	static $sourceCsv = null;
	if($sourceCsv === null){
		$sourceCsv = LoadCsv($sourceCsvPath);
	}
	$chestDataMap = [];
	$filteredData = [];
	if($isChestMode){
		$filteredData = ParseChestBinary();
		if(empty($filteredData)){
			printf("Puzzle Radar map couldn't be rendered.\n", ColorStr(sprintf("Rendering spawn map %s", $outputPath), 128, 192, 255));
			return false;
		}
	}else{
		$filteredData = array_values(array_filter($sourceCsv, function($e) use($spawnType){ return ($e["containerType"] == $spawnType); }));
	}
	
	printf("%s\n", ColorStr(sprintf("Rendering spawn map %s", $outputPath), 128, 192, 255));
	
	foreach($filteredData as $ii => $entry){
		$ptypeList = explode("|", $entry["ptypeList"]);
		$isSinglePtype = (count($ptypeList) == 1);
		$ptype = ($isClusterMode ? "logicGrid" : ($isChestMode ? "chest" : $ptypeList[0]));
		
		$zoneIndex = $entry["zoneIndex"];
		
		$myDrawInfo   = GetDrawInfoFor($ptype);
		$colorCode    = $myDrawInfo["color"];
		$graph        = $myDrawInfo["graph"];
		$sizeBase     = $myDrawInfo["size"];
		$icon         = $myDrawInfo["icon"];
		$outlineBase  = $myDrawInfo["outline"];
		
		if($forceZoneColor){
			$colorCode = GetZoneColorCode($zoneIndex);
		}elseif($isClusterMode){
			$colorCode = "#FFFFFF00";
		}
		
		$coord = (object)$entry;
		list($px, $py) = WorldToPixel($coord->x, $coord->y);
		$isFirstCoord = true;
		
		if(!$isFirstCoord && $graph == "node"){
			continue;
		}
		
		$iconSize = $sizeBase * $iconScale;
		$outline = $outlineBase * $iconScale;
		
		if($outline > 0){
			DrawItem($gd, $px, $py, $icon, $iconSize + $outline, "#00000030");
		}
		DrawItem($gd, $px, $py, $icon, $iconSize, $colorCode);
		
		//$text = PuzzlePrettyName($ptype);
		static $customName = [
			"mirrorMaze" => "Maze",
			"ryoanji" => "Sentinels",
			"rollingCube" => "Rollblock",
		];
		//if(isset($customName[$ptype])){
		if(!empty($labelType) && $isSinglePtype && isset($customName[$ptype])){
			DrawLabel($gd, $px, $py - 20, $customName[$ptype], $colorCode, $sizeBase + $outlineBase, $labelSize);
		}
		
		if($isClusterMode){
			$publicName = GetPublicClusterName($entry["cluster"]);
			if(!isset($clusterBoundsMap[$publicName])){
				$clusterBoundsMap[$publicName] = (object)[
					"minX" =>  1e7,
					"maxX" => -1e7,
					"minY" =>  1e7,
					"maxY" => -1e7,
				];
			}
			$clusterBoundsMap[$publicName]->minX = min($clusterBoundsMap[$publicName]->minX, $coord->x);
			$clusterBoundsMap[$publicName]->maxX = max($clusterBoundsMap[$publicName]->maxX, $coord->x);
			$clusterBoundsMap[$publicName]->minY = min($clusterBoundsMap[$publicName]->minY, $coord->y);
			$clusterBoundsMap[$publicName]->maxY = max($clusterBoundsMap[$publicName]->maxY, $coord->y);
		}
	}
	
	if($isClusterMode){
		//printf("%s\n", json_encode($clusterBoundsMap, 0xc0));
		//$myDrawInfo   = GetDrawInfoFor("logicGrid");
		//$colorCode    = $myDrawInfo["color"];
		//$sizeBase     = $myDrawInfo["size"];
		//$icon         = $myDrawInfo["icon"];
		//$outlineBase  = $myDrawInfo["outline"];
		$colorCode = "#FFFFFF00";
		$sizeBase  = 10;
		$icon      = "square";
		$outline   = 2;
		foreach($clusterBoundsMap as $publicName => $bounds){
			$isLabelAbove = ($publicName != "Ascension");
			list($px, $py) = WorldToPixel(($bounds->minX + $bounds->maxX) / 2.0, ($isLabelAbove ? $bounds->minY : $bounds->maxY));
			DrawLabel($gd, $px, ($isLabelAbove ? $py - 10 : $py + 40), $publicName, $colorCode, $sizeBase + $outlineBase, $labelSize);
		}
	}
	
	if(!empty($title)){
		$px = round($imgWidth  * 0.015);
		$py = round($imgHeight * 0.010);
		DrawTextFast($gd, $title, $px, $py, $titleColor, $titleSize, "./arial.ttf", -1, -1, true);
	}

	SaveImageAs($gd, $outputPath, $jpgQuality);
	imagedestroy($gd);
	$gd = null;
	
	return true;
}
