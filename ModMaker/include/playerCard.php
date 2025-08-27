<?php

include_once("include\\lookup.php");
include_once("include\\config.php");
include_once("include\\imageSmoothArc_fast.php");
include_once("include\\imageSmoothLine.php");
include_once("include\\imgex.php");
include_once("include\\drawCommon.php");
include_once("include\\renderCache.php");
include_once("include\\icons.php");

define("PC_IMAGE_VERSION",   "1.2");
define("PC_MAX_PLAYERNAME",     30);
define("PC_MAX_PLAYERTITLE",    60);

// Fonts.
define("PC_FONT_BOLD",   "./taileb.ttf");
//define("PC_FONT_NORMAL", "./arial.ttf");

// Some sizes.
define("PC_SMALL_SPACER",  8);
define("PC_LARGE_SPACER", 20);
define("PC_IS_SMALL_SIDE_SPACER", false);

// Background colors.
define("PC_CLR_TRANSPARENT",      "#0000007F");
define("PC_CLR_GRAYBACK",         "#00000070");
define("PC_CLR_HIGHLIGHT",        "#FFFFFF70"); // 00000050
define("PC_CLR_ZONETITLEBACK",    "#40404000");
define("PC_CLR_ZONECOMPLETEBACK", "#30303000");
define("PC_CLR_HUBREWARDGOT",     "#00000035");
define("PC_CLR_MASTERYNORMAL",    "#00000060");
define("PC_CLR_MASTERYGOT",       "#00000047");

// Font colors.
define("PC_CLR_HUBTRACKREMAIN",   "#FDD07B20"); // edc30700
define("PC_CLR_WHITE",            "#FFFFFF00");
define("PC_CLR_PLAYERLEVEL",      "#FDD07B00");
define("PC_CLR_GRAYFONT",         "#CCCCCC00");

// Misc colors.
define("PC_CLR_AVATARBORDER",     "#60606000"); // A0A0A000 light gray
define("PC_CLR_MASTERYTITLEBACK", "#00000045");
define("PC_CLR_GENTIMEBACK",      "#00000040");

function LoadPlayerCardBackground(){
	static $cardBackPath = "media\\img\\cardback.png";
	if(!is_file($cardBackPath)){
		printf("%s\n", ColorStr("Missing file " . $cardBackPath, 255, 128, 128));
		exit(1);
	}
	$gd = imagecreatefrompng($cardBackPath);
	if(!$gd){
		printf("%s\n", ColorStr("Failed to load " . $cardBackPath, 255, 128, 128));
		exit(1);
	}
	return $gd;
}

function DrawPlayerCard($saveJson, $outputPath){
	printf("%s\n", ColorStr("Assembling the player card pic...", 160, 160, 160));
	
	$hubStatsGd = GeneratePcHubStats($saveJson);
	$masteryGd  = GeneratePcMasteries($saveJson, [ "forcedHeight" => imagesy($hubStatsGd) ]);
	
	$mainGd     = TileImages([ $hubStatsGd, $masteryGd ], [ "mode" => "horizontal", "align" => "center", "spacer" => PC_LARGE_SPACER, "margin" => 0 ]);
	$headerGd   = GenerateHeaderRow($saveJson, [ "forcedWidth" => imagesx($mainGd) ]);
	$fullGd     = TileImages([ $headerGd, $mainGd ], [ "mode" => "vertical", "align" => "center", "spacer" => PC_LARGE_SPACER, "margin" => 0 ]);
	
	$back = LoadPlayerCardBackground();
	$margin = PC_LARGE_SPACER;
	$back = imagescale($back, imagesx($fullGd) + $margin * 2, imagesy($fullGd) + $margin * 2, DEFAULT_INTERPOLATION);
	imagesavealpha($back, true);
	imagealphablending($back, false);
	
	OverlayImageSmart($back, $fullGd, [ "blend" => true ]);
	printf("%s\n", ColorStr("Rendering ". $outputPath, 128, 192, 255));
	SaveImageAs($back, $outputPath);
	
	//printf(ColorStr("Debug exit!\n", 255, 128, 128)); exit(1);
}

function GenerateHeaderRow($saveJson, array $options = []){
	$defaultOptions = [
		"forcedWidth" => -1,
		"forcedHeight" => -1,
	];
	$options = array_merge($defaultOptions, $options);
	$forcedWidthTotal = $options["forcedWidth"];
	if($forcedWidthTotal <= 0){
		printf("%s: I need forcedWidth!\n", __FUNCTION__);
		exit(1);
	}
	$headerHeight = round(250);
	
	static $weights = [
		"primary"    => 2.03,
		"hubstatic"  => 1.25,
		"cosmystery" => 0.96,
		"armidrop"   => 1.08,
		"clusters"   => 1.22,
		"florbs"     => 0.39,
		"glides"     => 0.39,
	];
	$totalWeight = array_sum($weights);
	
	$headerSpacerHor = PC_SMALL_SPACER;
	$headerSpacerVer = $headerSpacerHor;
	$headerItemCount = count($weights) + 2; // extra for avatar and the manual spacer
	$headerSpacerCount = $headerItemCount - 1;
	$avatarSize = $headerHeight * 1.0;
	
	$manualSpacerWidth = max(0, PC_LARGE_SPACER - 2 * $headerSpacerHor);
	
	$remainingPixels = $forcedWidthTotal - $manualSpacerWidth - $avatarSize - ($headerSpacerCount * $headerSpacerHor);
	$defaultWidth = 1.0 * $remainingPixels / $totalWeight;
	$sizeMap = [];
	foreach($weights as $key => $weight){
		$sizeMap[$key] = intval(floor($defaultWidth * $weight + 1e-4));
	}
	$usedPixels = array_sum($sizeMap);
	$extraRemainingPixels = ($remainingPixels - $usedPixels);
	$sizeMap["primary"] += $extraRemainingPixels;
	
	//printf("Total width: %d pixels\n", $forcedWidthTotal);
	//printf("Default width: %.1f\n", $defaultWidth);
	//printf("Used pixels: %d\n", $usedPixels);
	//printf("Extra pixels: %d\n", $extraRemainingPixels);
	//printf("Final size map:\n%s\n", json_encode($sizeMap, JSON_PRETTY_PRINT));
	//printf("Sum of size map: %d\n", array_sum($sizeMap));
	//printf("Avatar width: %d\n", $avatarSize);
	//printf("Total taken pixels: %d\n", array_sum($sizeMap) + $avatarSize + ($headerSpacerCount * $headerSpacerHor));
	//printf("Header has %d items with %d spacers in between, each spacer is %d pixels, total for spaceers %d\n", $headerItemCount, $headerSpacerCount, $headerSpacerHor, ($headerSpacerCount * $headerSpacerHor));
	
	$staticsAllCount = count(GetAllStaticPids());
	$staticsSolvedCount = count($saveJson->staticSolvedPids);
	$isStaticsComplete = ($staticsSolvedCount == $staticsAllCount);
	
	$hubAllCount = count(GetAllHubPids());
	$hubSolvedCount = count($saveJson->hubSolvedPids);
	$isHubsComplete = ($hubSolvedCount == $hubAllCount);
	
	$templeArmiAllCount = count(GetTempleArmillaries());
	$templeArmiSolvedCount = count($saveJson->templeSolvedArmillaries);
	$isTempleArmiComplete = ($templeArmiSolvedCount == $templeArmiAllCount);
	
	$skydropMedalName = strtolower(MedalTierToName($saveJson->skydropChallengeMedal));
	$skydropPb = ($saveJson->skydropChallengePb > 0 ? sprintf("%.2f", $saveJson->skydropChallengePb) : "none");
	$isSkydropComplete = ($skydropMedalName == "platinum");
	
	$cosmeticsAllCount = GetTotalCosmeticsCount();
	$cosmeticsUnlockedCount = count($saveJson->cosmetics);
	$isCosmeticsComplete = ($cosmeticsUnlockedCount == $cosmeticsAllCount);
	
	$mysteriesAllCount = GetTotalMysteriesCount();
	$mysteriesSolvedCount = count($saveJson->solvedMysteries);
	$isMysteriesComplete = ($mysteriesSolvedCount == $mysteriesAllCount);
	
	$consolidatedClusterMap = GetConsolidatedClusterMap();
	$isClustersComplete = true;
	$clusterItems = [];
	foreach($consolidatedClusterMap as $publicName => $allClusterPids){
		$allCount = count($allClusterPids);
		$solvedCount = count(array_intersect($allClusterPids, $saveJson->allSolvedPids));
		$clusterItems[] = [ "curr" => $solvedCount, "total" => $allCount, "showPct" => true, "itemName" => $publicName ];
		if($solvedCount != $allCount){
			$isClustersComplete = false;
		}
	}
	//printf("%s\n", json_encode($clusterItems, 0xc0));
	
	$defaultWidth = 300;
	$headerHalfHeight = intval(round(($headerHeight - $headerSpacerVer) / 2));
	
	$isFlorbsComplete = ((count($saveJson->florbMedalMap[0]) + count($saveJson->florbMedalMap[1]) + count($saveJson->florbMedalMap[2]) == 0) && count($saveJson->florbMedalMap[3]) > 0);
	$isGlidesComplete = ((count($saveJson->glideMedalMap[0]) + count($saveJson->glideMedalMap[1]) + count($saveJson->glideMedalMap[2]) == 0) && count($saveJson->glideMedalMap[3]) > 0);
	
	$gdAvatar    = GenerateAvatar([ "forcedHeight" => $avatarSize ]);
	$gdManualSpacer = CreateBlankImage($manualSpacerWidth, $headerHeight);
	$gdPrimary   = GeneratePrimaryInfo($saveJson, [ "forcedWidth" => $sizeMap["primary"], "forcedHeight" => $headerHeight ]);
	
	$gdStatics   = CreateGenericStatCard($sizeMap["hubstatic"], $headerHalfHeight, [
						[ "curr" => $staticsSolvedCount,      "total" => $staticsAllCount,    "showPct" => true ]
					],  [ "title" => "Static puzzles", "titlePosX" => 0.55, "isComplete" => $isStaticsComplete,
						  "titleIcon" => "special/hiddencube", "titleIconPos" => 50, "itemPos" => 0.52,
					   ]);
	$gdHubs      = CreateGenericStatCard($sizeMap["hubstatic"], $headerHalfHeight, [
						[ "curr" => $hubSolvedCount,          "total" => $hubAllCount,        "showPct" => true ]
					],  [ "title" => "Hub puzzles", "titlePosX" => 0.55, "isComplete" => $isHubsComplete,
						  "titleIcon" => "special/ryoanji",    "titleIconPos" => 50, "itemPos" => 0.52,
					   ]);
	$gdCosmetics = CreateGenericStatCard($sizeMap["cosmystery"], $headerHalfHeight, [
						[ "curr" => $cosmeticsUnlockedCount,  "total" => $cosmeticsAllCount,  "showPct" => true, "showDeluxe" => $saveJson->hasDeluxe ]
					],  [ "title" => "Cosmetics", "titlePosX" => 0.55, "isComplete" => $isCosmeticsComplete,
						  "titleIcon" => "special/cosmetic", "itemPos" => 0.52,
					   ]);
	$gdMysteries = CreateGenericStatCard($sizeMap["cosmystery"], $headerHalfHeight, [
						[ "curr" => $mysteriesSolvedCount,    "total" => $mysteriesAllCount,  "showPct" => true ]
					],  [ "title" => "Mysteries", "titlePosX" => 0.55, "isComplete" => $isMysteriesComplete,
						  "titleIcon" => "special/mystery", "itemPos" => 0.52,
					   ]);
	$gdArmillary = CreateGenericStatCard($sizeMap["armidrop"], $headerHalfHeight, [
						[ "curr" => $templeArmiSolvedCount,   "total" => $templeArmiAllCount, "showPct" => true ]
					],  [ "title" => "  Temple Armillaries", "titlePosX" => 0.58, "titleFontSize" => 20,  "isComplete" => $isTempleArmiComplete,
						  "titleIcon" => "special/gyroring", "itemPos" => 0.52, //"titleIconSize" => 66, "titleIconPos" => 37,
					   ]);
	$gdSkydrop   = CreateGenericStatCard($sizeMap["armidrop"], $headerHalfHeight, [
						[ "value" => $skydropPb, "iconSize" => 30, "icon" => "medal_scaled/" . $skydropMedalName, "iconPos" => 0.35,
						  "checkmark" => ($skydropMedalName == "none" ? "special/none" : "special/checkmark"), "checkmarkSize" => 40, "checkmarkPos" => 0.85 ]
					],  [ "title" => "  Skydrop Challenge", "titlePosX" => 0.58, "titleFontSize" => 20, "itemAlign" => "center", "isComplete" => $isSkydropComplete,
						  "titleIcon" => "special/skydrop_challenge", "itemPos" => 0.55, //"titleIconSize" => 70, "titleIconPos" => 40,
					   ]);
					// "extraIcon" => "medal_scaled/" . $skydropMedalName, "extraIconSize" => 30, "extraIconPos" => 0.4
	$gdFlorbs    = CreateGenericStatCard($sizeMap["florbs"], $headerHeight, [
						[ "value" => count($saveJson->florbMedalMap[0]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(0)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->florbMedalMap[1]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(1)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->florbMedalMap[2]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(2)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->florbMedalMap[3]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(3)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => $saveJson->florbTotalScore,          "icon" => null, "overridePos" => 0.5, "overrideAlign" => "center", "overrideFontSize" => 18 ],
					],  [ "title" => "Florbs", "itemPos" => 0.42, "itemAlign" => "left", "iconSize" => 30, "isComplete" => $isFlorbsComplete,
						  "titleIcon" => "", "itemFontSize" => 21,
					   ]);
	$gdGlides    = CreateGenericStatCard($sizeMap["glides"], $headerHeight, [
						[ "value" => count($saveJson->glideMedalMap[0]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(0)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->glideMedalMap[1]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(1)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->glideMedalMap[2]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(2)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => count($saveJson->glideMedalMap[3]),  "icon" => "medal_scaled/" . strtolower(MedalTierToName(3)), "iconSize" => 30, "iconPos" => 0.25 ],
						[ "value" => $saveJson->glideTotalScore . " pts", "icon" => null, "overridePos" => 0.5, "overrideAlign" => "center", "overrideFontSize" => 17 ],
					],  [ "title" => "Glides", "itemPos" => 0.42, "itemAlign" => "left", "iconSize" => 30, "isComplete" => $isGlidesComplete,
						  "titleIcon" => "", "itemFontSize" => 21,
					   ]);
	$gdClusters  = CreateGenericStatCard($sizeMap["clusters"], $headerHeight,
						$clusterItems,
					    [ "title" => "Clusters", "itemPos" => 0.60, "isComplete" => $isClustersComplete,
						  "titleIcon" => "", "itemFontSize" => 22,
					   ]);
	
	$gdArray = [
		$gdAvatar,
		$gdManualSpacer,
		$gdPrimary,
		TileImages([ $gdStatics,   $gdHubs      ], [ "mode" => "vertical", "align" => "center", "spacer" => $headerSpacerVer, "margin" => 0 ]),
		TileImages([ $gdCosmetics, $gdMysteries ], [ "mode" => "vertical", "align" => "center", "spacer" => $headerSpacerVer, "margin" => 0 ]),
		TileImages([ $gdArmillary, $gdSkydrop   ], [ "mode" => "vertical", "align" => "center", "spacer" => $headerSpacerVer, "margin" => 0 ]),
		$gdFlorbs,
		$gdGlides,
		$gdClusters,
	];
	$gd = TileImages($gdArray, [ "mode" => "horizontal", "align" => "center", "spacer" => $headerSpacerHor, "margin" => 0 ]);
	
	return $gd;
}

function GenerateAvatar(array $options = []){
	$defaultOptions = [
		"forcedHeight" => -1,
	];
	$options = array_merge($defaultOptions, $options);
	$forcedHeight = $options["forcedHeight"];
	if($forcedHeight <= 0){
		printf("%s: I need forcedHeight!\n", __FUNCTION__);
		exit(1);
	}
	
	$size = $forcedHeight;
	$cornerRadius = 40;
	$avatarScale = 0.95;
	$gd = CreateCard($size, $size, [ "backColor" => PC_CLR_TRANSPARENT, "cardColor" => PC_CLR_AVATARBORDER, "cardMargin" => 0, "cardRadius" => $cornerRadius / $avatarScale ]);
	
	$avatarPath = "avatar.png";
	if(!is_file($avatarPath)){
		//printf("\n");
		printf("%s\n", ColorStr("Hi! I couldn't find the file \"" . $avatarPath . "\" in the savedump folder.", 200, 200, 40));
		printf("%s\n", ColorStr("Please make a square picture (.png format) and put it there :)", 200, 200, 40));
		printf("\n");
		return $gd;
	}
	$rawData = file_get_contents($avatarPath);
	$hash = sha1($rawData);
	$defaultPictureHash = "f3b928c70eb12145c48ecd9b8c70677d73063f04"; // lol
	if($hash == $defaultPictureHash){
		//printf("\n");
		printf("%s\n", ColorStr("Hi! Please customize your avatar for a better player card :)", 200, 200, 40));
		printf("%s\n", ColorStr("Replace \"" . $avatarPath . "\" with a square picture of your character or profile picture etc", 200, 200, 40));
		printf("\n");
		//usleep(3.0 * 1e6);
	}
	//printf("hash: %s\n", $hash);
	$avatar = @imagecreatefromstring($rawData); // suppress gd-png: libpng warning: iCCP: known incorrect sRGB profile
	if($avatar === false){
		printf("%s\n", ColorStr("I couldn't read the \"" . $avatarPath . "\" file :( Is the picture ok?", 255, 128, 128));
		return $gd;
	}
	
	$picSize = round($size * $avatarScale);
	$avatar = ImageTrimToSquare($avatar);
	$avatar = imagescale($avatar, $picSize, $picSize, DEFAULT_INTERPOLATION);
	imagesavealpha($avatar, true);
	imagealphablending($avatar, false);
	ImageTrimRoundCorners($avatar, $cornerRadius);
	OverlayImageSmart($gd, $avatar, [ ]);
	
	return $gd;
}

function GeneratePrimaryInfo($saveJson, array $options = []){
	global $config;
	global $configPath;
	
	$defaultOptions = [
		"forcedWidth" => -1,
		"forcedHeight" => -1,
	];
	$options = array_merge($defaultOptions, $options);
	$width  = $options["forcedWidth"];
	$height = $options["forcedHeight"];
	if($width <= 0 || $height <= 0){
		printf("%s: I need forcedWidth and forcedHeight!\n", __FUNCTION__);
		exit(1);
	}
	
	$fontColorPlayerLevel = PC_CLR_PLAYERLEVEL;
	$fontNamePlayerLevel  = PC_FONT_BOLD;
	$fontSizePlayerLevel  = 29;
	$levelIconSize        = 135;
	
	$fontColorPlayerName  = PC_CLR_WHITE;
	$fontNamePlayerName   = PC_FONT_BOLD;
	$fontSizePlayerName   = 40;
	
	$fontColorPlayerTitle = PC_CLR_WHITE;
	$fontNamePlayerTitle  = PC_FONT_BOLD;
	$fontSizePlayerTitle  = 28;
	
	$fontColorPlayerTitle = PC_CLR_WHITE;
	$fontNamePlayerTitle  = PC_FONT_BOLD;
	$fontSizePlayerTitle  = 28;
	
	$smallIconSize        = 56;
	$fontColorStat        = PC_CLR_WHITE;
	$fontNameStat         = PC_FONT_BOLD;
	$fontSizeStat         = 20;

	$defaultPlayerName = "Anonymous";
	$defaultPlayerTitle = "Ignorer of Config File";
	$playerName = $config["name"];
	if(empty($playerName)){
		$playerName = $defaultPlayerName;
	}
	$playerTitle = $config["title"];
	if(empty($playerTitle)){
		$playerTitle = $defaultPlayerTitle;
	}
	
	if($playerName == $defaultPlayerName || $playerTitle == $defaultPlayerTitle){
		//printf("\n");
		printf("%s\n", ColorStr("Please set your name and title in \"" . $configPath . "\" :)", 200, 200, 40));
		printf("\n");
	}
	
	$playerName  = substr(trim($playerName),  0, PC_MAX_PLAYERNAME);
	$playerTitle = substr(trim($playerTitle), 0, PC_MAX_PLAYERTITLE);
	
	$iconLevelDiamondGd = GetIcon("special/level_diamond");
	$iconSparkGd        = GetIcon("special/sparks");
	$iconMirabilisGd    = GetIcon("special/mirabilis_simple");
	$iconPlaytimeGd     = GetIcon("special/hourglass");
	
	$isPrimaryComplete = ($saveJson->buggedMirabilis >= GetMaxMirabilisCount());
	$cardColor = ($isPrimaryComplete ? PC_CLR_MASTERYGOT : PC_CLR_MASTERYNORMAL);
	
	$playerLevel = $saveJson->playerLevel;
	$sparkCount = number_format($saveJson->sparks, 0, ".", ",");
	$mirabilisString = ($saveJson->buggedMirabilis <= GetMaxMirabilisCount() ? (int)$saveJson->buggedMirabilis : ((string)GetMaxMirabilisCount()) . "+");
	
	//$playingSince = (empty($saveJson->playingSince) ? "Offline-only player" : "Playing since " . $saveJson->playingSince); // unused now
	$playTime = (empty($saveJson->playTimeMinutes) ? "unknown" : sprintf("%.1f hours", $saveJson->playTimeMinutes / 60.0));
	
	$gd = CreateCard($width, $height, [ "backColor" => PC_CLR_TRANSPARENT, "cardColor" => $cardColor, "cardMargin" => 0, "cardRadius" => 6 ]);
	
	OverlayImageSmart($gd, $iconLevelDiamondGd, [ "x" => $width * 0.11, "y" => $height * 0.37, "size" => $levelIconSize ]);
	DrawSmartText    ($gd, $playerLevel,        [ "x" => $width * 0.11, "y" => $height * 0.38,
												"colorCode" => $fontColorPlayerLevel, "fontName" => $fontNamePlayerLevel, "fontSize" => $fontSizePlayerLevel ]);
	
	DrawSmartText    ($gd, $playerName,         [ "x" => $width * 0.22, "y" => $height * 0.25, "align" => "left", "maxWidth" => $width * 0.7,
												"colorCode" => $fontColorPlayerName,  "fontName" => $fontNamePlayerName,  "fontSize" => $fontSizePlayerName  ]);
	DrawSmartText    ($gd, $playerTitle,        [ "x" => $width * 0.22, "y" => $height * 0.54, "align" => "left", "maxWidth" => $width * 0.7,
												"colorCode" => $fontColorPlayerTitle, "fontName" => $fontNamePlayerTitle, "fontSize" => $fontSizePlayerTitle ]);
	
	$statHeight = $height * 0.85;
	$iconHeightAdjust = -3;
	OverlayImageSmart($gd, $iconSparkGd,        [ "x" => $width * 0.07, "y" => $statHeight + $iconHeightAdjust,    "size" => $smallIconSize ]);
	DrawSmartText    ($gd, $sparkCount,         [ "x" => $width * 0.12, "y" => $statHeight,    "align" => "left",
												"colorCode" => $fontColorStat,  "fontName" => $fontNameStat,  "fontSize" => $fontSizeStat ]);
	
	OverlayImageSmart($gd, $iconMirabilisGd,    [ "x" => $width * 0.40, "y" => $statHeight + $iconHeightAdjust,    "size" => $smallIconSize ]);
	DrawSmartText    ($gd, $mirabilisString,    [ "x" => $width * 0.45, "y" => $statHeight,    "align" => "left",
												"colorCode" => $fontColorStat,  "fontName" => $fontNameStat,  "fontSize" => $fontSizeStat ]);
	
	OverlayImageSmart($gd, $iconPlaytimeGd,     [ "x" => $width * 0.65, "y" => $statHeight + $iconHeightAdjust,    "size" => $smallIconSize ]);
	DrawSmartText    ($gd, $playTime,           [ "x" => $width * 0.70, "y" => $statHeight,    "align" => "left",
												"colorCode" => $fontColorStat,  "fontName" => $fontNameStat,  "fontSize" => $fontSizeStat ]);
	
	return $gd;
}

function CreateGenericStatCard($width, $height, array $items, array $options = []){
	$defaultOptions = [
		"title"             => "",
		"titleColor"        => PC_CLR_WHITE,
		"titleFontName"     => PC_FONT_BOLD,
		"titleFontSize"     => 22,
		"titlePosX"         => 0.5,
		"itemColor"         => PC_CLR_WHITE,
		"itemFontName"      => PC_FONT_BOLD,
		"itemFontSize"      => 22,
		"itemAlign"         => "center",
		"itemPos"           => 0.5,
		"titleIcon"         => "",
		"titleIconSize"     => 72,
		"titleIconPos"      => 45,
		"backColor"         => PC_CLR_TRANSPARENT,
		"cardColor"         => PC_CLR_MASTERYNORMAL, //PC_CLR_GRAYBACK,
		"cardMargin"        => 0,
		"cardRadius"        => 3,
		"isComplete"        => false,
	];
	$options = array_merge($defaultOptions, $options);
	$title               = $options["title"];
	$titleColor          = $options["titleColor"];
	$titleFontName       = $options["titleFontName"];
	$titleFontSize       = $options["titleFontSize"];
	$titlePosX           = $options["titlePosX"];
	$itemColor           = $options["itemColor"];
	$itemFontName        = $options["itemFontName"];
	$itemFontSizeDefault = $options["itemFontSize"];
	$itemAlignDefault    = $options["itemAlign"];
	$itemPosDefault      = $options["itemPos"];
	$titleIcon           = $options["titleIcon"];
	$titleIconSize       = $options["titleIconSize"];
	$titleIconPos        = $options["titleIconPos"];
	$backColor           = $options["backColor"];
	$cardColor           = $options["cardColor"];
	$cardMargin          = round(max(0, $options["cardMargin"]));
	$cardRadius          = round(max(0, $options["cardRadius"]));
	$isComplete          = $options["isComplete"];
	
	if($isComplete){
		$cardColor = PC_CLR_MASTERYGOT;
	}
	$isSmallCard = (count($items) == 1);
	
	$gd = CreateCard($width, $height, [ "backColor" => $backColor, "cardColor" => $cardColor, "cardMargin" => $cardMargin, "cardRadius" => $cardRadius ]);
	$titleHeight = $height * ($isSmallCard ? 0.32 : 0.12);
	$itemHeightStep = $height * 0.16;
	$firstItemHeight = ($isSmallCard ? $height * 0.72 : $titleHeight + $itemHeightStep);
	
	if(!empty($title)){
		DrawSmartText($gd, $title, [ "x" => $titlePosX * $width, "y" => $titleHeight, "colorCode" => $titleColor, "fontName" => $titleFontName, "fontSize" => $titleFontSize ]);
	}
	if(!empty($titleIcon)){
		$iconGd = GetIcon($titleIcon);
		if($titleIconPos < 1.0){
			$titleIconPos = $width * $titleIconPos;
		}
		OverlayImageSmart($gd, $iconGd, [ "x" => $titleIconPos, "size" => $titleIconSize ]);
	}
	
	$maxLen = 0;
	foreach($items as $item){
		if(isset($item["curr"]) && isset($item["total"])){
			$maxLen = max(strlen($item["curr"]), strlen($item["total"]));
		}
	}
	
	static $iconHeightAdjust = -3;
	
	for($index = 0; $index < count($items); ++$index){
		$item = $items[$index];
		$itemPos = $itemPosDefault;
		$itemHeight = $firstItemHeight + $index * $itemHeightStep;
		$itemAlign = $itemAlignDefault;
		$itemFontSize = $itemFontSizeDefault;
		if(isset($item["overridePos"])){
			$itemPos = $item["overridePos"];
		}
		if(isset($item["overrideAlign"])){
			$itemAlign = $item["overrideAlign"];
		}
		if(isset($item["overrideFontSize"])){
			$itemFontSize = $item["overrideFontSize"];
		}
		if(isset($item["curr"]) && isset($item["total"])){
			$curr = $item["curr"];
			$total = $item["total"];
			$midTextSpacer = 0;
			$midTextSpacer = ($isSmallCard ? 0.08 : 0.06) + $maxLen * 0.012;
			DrawSmartText($gd, $curr,  [ "x" => $width * ($itemPos - $midTextSpacer), "y" => $itemHeight, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
			DrawSmartText($gd, "/",    [ "x" => $width * ($itemPos                 ), "y" => $itemHeight, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
			DrawSmartText($gd, $total, [ "x" => $width * ($itemPos + $midTextSpacer), "y" => $itemHeight, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
			if(isset($item["showPct"]) && $item["showPct"] == true){
				$pctString = number_format(floor(100.0 * $curr / $total + 1e-6), 0, ".", ",") . "%";
				DrawSmartText($gd, $pctString, [
					"x" => $width * ($isSmallCard ? 0.97 : 0.98), "y" => $itemHeight,
					"colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize * 0.85,
					"align" => "right" ]);
			}
		}
		if(isset($item["value"])){
			$value = $item["value"];
			DrawSmartText($gd, $value, [
				"x" => $width * $itemPos, "y" => $itemHeight,
				"colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize,
				"align" => $itemAlign ]);
		}
		if(isset($item["checkmark"])){
			$checkmarkGd = GetIcon($item["checkmark"]);
			$checkmarkSize = $item["checkmarkSize"];
			$checkmarkPos = $item["checkmarkPos"];
			OverlayImageSmart($gd, $checkmarkGd, [ "x" => $checkmarkPos * $width, "y" => $itemHeight + $iconHeightAdjust, "size" => $checkmarkSize ]);
		}
		if(isset($item["value"])){
			$value = $item["value"];
			DrawSmartText($gd, $value, [
				"x" => $width * $itemPos, "y" => $itemHeight,
				"colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize,
				"align" => $itemAlign ]);
		}
		if(isset($item["icon"])){
			$iconGd = GetIcon($item["icon"]);
			$iconPos = $item["iconPos"];
			$iconSize = $item["iconSize"];
			OverlayImageSmart($gd, $iconGd, [ "x" => $width * $iconPos, "y" => $itemHeight + $iconHeightAdjust, "size" => $iconSize ]);
		}
		if(isset($item["itemName"])){
			$itemName = $item["itemName"];
			DrawSmartText($gd, $itemName, [
				"x" => $width * 0.02, "y" => $itemHeight,
				"colorCode" => $itemColor, "fontName" => $itemFontName , "fontSize" => $itemFontSize * 0.82,
				"align" => "left" ]);
		}
		if(isset($item["showDeluxe"]) && $item["showDeluxe"] == true){
			DrawSmartText($gd, "+deluxe", [
				"x" => $width * 0.84, "y" => $height * 0.88 ,
				"colorCode" => $itemColor, "fontName" => $itemFontName , "fontSize" => $itemFontSize * 0.6,
				"align" => "center" ]);
		}
	}
	
	return $gd;
}

//function CreateClustersCard($width, $height, array $items, array $options = []){
//	$defaultOptions = [
//		"title"         => "",
//		"titleColor"    => PC_CLR_WHITE,
//		"titleFontName" => PC_FONT_BOLD,
//		"titleFontSize" => 18,
//		"itemColor"     => PC_CLR_WHITE,
//		"itemFontName"  => PC_FONT_BOLD,
//		"itemFontSize"  => 22,
//		"backColor"     => PC_CLR_TRANSPARENT,
//		"cardColor"     => PC_CLR_MASTERYNORMAL,
//		"cardMargin"    => 0,
//		"cardRadius"    => 3,
//		"isComplete"    => false,
//	];
//	$options = array_merge($defaultOptions, $options);
//	$title          = $options["title"];
//	$titleColor     = $options["titleColor"];
//	$titleFontName  = $options["titleFontName"];
//	$titleFontSize  = $options["titleFontSize"];
//	$itemColor      = $options["itemColor"];
//	$itemFontName   = $options["itemFontName"];
//	$itemFontSize   = $options["itemFontSize"];
//	$backColor      = $options["backColor"];
//	$cardColor      = $options["cardColor"];
//	$cardMargin     = round(max(0, $options["cardMargin"]));
//	$cardRadius     = round(max(0, $options["cardRadius"]));
//	$isComplete     = $options["isComplete"];
//	
//	if($isComplete){
//		$cardColor = PC_CLR_MASTERYGOT;
//	}
//	
//	$gd = CreateCard($width, $height, [ "backColor" => $backColor, "cardColor" => $cardColor, "cardMargin" => $cardMargin, "cardRadius" => $cardRadius ]);
//	
//	foreach($items as $index => $item){
//		$itemName = $item["itemName"] . " Cluster";
//		$curr = $item["curr"];
//		$total = $item["total"];
//		$pctString = number_format(floor(100.0 * $curr / $total + 1e-6), 0, ".", ",") . "%";
//		
//		$myTitleY = $height * (0.12 + $index * 0.32);
//		$myItemY  = $height * (0.26 + $index * 0.32);
//		
//		DrawSmartText($gd, $itemName, [ "y" => $myTitleY, "colorCode" => $titleColor, "fontName" => $titleFontName, "fontSize" => $titleFontSize ]);
//		
//		static $midTextSpacer = 0.12;
//		DrawSmartText($gd, $curr,      [ "x" => $width * (0.32 - $midTextSpacer), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, "/",        [ "x" => $width * (0.32                 ), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, $total,     [ "x" => $width * (0.32 + $midTextSpacer), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, $pctString, [ "x" => $width * 0.90,                   "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName,
//			"fontSize" => $itemFontSize * 0.85, "align" => "right" ]);
//	}
//	return $gd;
//}

function GeneratePcHubStats($saveJson){
	$solvedPcatSplit = SplitProfileByCategories($saveJson->hubSolvedProfile);
	$remainPcatSplit = SplitProfileByCategories($saveJson->hubRemainingProfile);
	$totalPcatSplit  = SplitProfileByCategories(GetHubProfile());
	
	$solvedZoneSplit = ReduceProfileToZones($saveJson->hubSolvedProfile);
	$remainZoneSplit = ReduceProfileToZones($saveJson->hubRemainingProfile);
	$totalZoneSplit  = ReduceProfileToZones(GetHubProfile());
		
	$rewardZoneSplit = $saveJson->hubRewards;
	
	$gdArray = [];
	foreach($totalPcatSplit as $zoneIndex => $pcatInfo){
		$hubZoneGd = GeneratePcHubZone([
			"solvedCatInfo"   => $solvedPcatSplit[$zoneIndex],
			"remainCatInfo"   => $remainPcatSplit[$zoneIndex],
			"totalCatInfo"    => $totalPcatSplit [$zoneIndex],
			"solvedZoneCount" => count($solvedZoneSplit[$zoneIndex]),
			"remainZoneCount" => count($remainZoneSplit[$zoneIndex]),
			"totalZoneCount"  => count($totalZoneSplit[$zoneIndex]),
			"rewardCatInfo"   => $rewardZoneSplit[$zoneIndex],
			"zoneIndex"       => $zoneIndex,
		]);
		$gdArray[] = $hubZoneGd;
	}
	
	$gd = TileImages($gdArray, [ "mode" => "vertical", "align" => "center", "spacer" => PC_LARGE_SPACER, "margin" => 0 ]);
	
	return $gd;
}

function GeneratePcHubZone(array $options){
	$defaultOptions = [
		"solvedCatInfo"   => [],
		"remainCatInfo"   => [],
		"totalCatInfo"    => [],
		"solvedZoneCount" => 0,
		"remainZoneCount" => 1,
		"totalZoneCount"  => 1,
		"rewardCatInfo"   =>[],
		"zoneIndex"       => 2,
	];
	$options = array_merge($defaultOptions, $options);
	$solvedCatInfo   = $options["solvedCatInfo"];
	$remainCatInfo   = $options["remainCatInfo"];
	$totalCatInfo    = $options["totalCatInfo"];
	$solvedZoneCount = $options["solvedZoneCount"];
	$remainZoneCount = $options["remainZoneCount"];
	$totalZoneCount  = $options["totalZoneCount"];
	$rewardCatInfo   = $options["rewardCatInfo"];
	$zoneIndex       = $options["zoneIndex"];
	
	$gdArray = [];
	foreach($totalCatInfo as $pcat => $ptypeinfo){
		$hubCatGd = GeneratePcHubCat([
			"solvedPtypeInfo" => $solvedCatInfo[$pcat],
			"remainPtypeInfo" => $remainCatInfo[$pcat],
			"totalPtypeInfo"  => $totalCatInfo [$pcat],
			"rewardArray"     => $rewardCatInfo[$pcat],
			"zoneIndex"       => $zoneIndex,
			"pcat"            => $pcat,
			//"scale"           => 1.0,
		]);
		$gdArray[] = $hubCatGd;
	}
	$zoneNameGd = GeneratePcHubZoneName([
		"zoneIndex"    => $zoneIndex,
		"forcedHeight" => max(array_map("imagesy", $gdArray)),
		"solvedCount"  => $solvedZoneCount,
		"remainCount"  => $remainZoneCount,
		"totalCount"   => $totalZoneCount,
	]);
	array_unshift($gdArray, $zoneNameGd);
	
	
	static $zoneColors = [
		2 => "#FFA42C5d", // "#ffa43c5f"
		3 => "#93ceff5d", // "#93ceff5f"
		4 => "#ff8c7f5f", // "#ff8c7f5f"
		5 => "#4ECC2E5d", // "#4ee02a5f"
		6 => "#FFFF8f5d", // "#ffff8c5f"
	];
	$zoneColorCode = $zoneColors[$zoneIndex];
	
	$zoneCornerRadius = 16;
	$zoneOuterMargin = 12;
	$zoneSpacer = PC_LARGE_SPACER;
	$gd = TileImages($gdArray, [ "mode" => "horizontal", "align" => "top", "spacer" => $zoneSpacer, "margin" => 0, "cardColor" => $zoneColorCode, "cardRadius" => $zoneCornerRadius, "blend" => true, "margin" => $zoneOuterMargin ]);
	
	//$iconZoneGd = GetIcon("zone/" . $zoneIndex, [ "alpha" => 0.9 ]);
	//static $zoneIconScale = [ 2 => 1.37, 3 => 1.3, 4 => 1.15, 5 => 1.15, 6 => 1.11 ];
	//$myScale = $zoneIconScale[$zoneIndex];
	//$myX = imagesx($gd) - $zoneOuterMargin - imagesx($gdArray[1]) / 2.0; // please never mind ok
	//$myY = imagesy($gd) + 1; // why +1 and not -1? cuz looks a lil better with these, that's why
	//OverlayImageSmart($gd, $iconZoneGd, [ "x" => $myX, "y" => $myY, "scale" => $myScale, "alignX" => "center", "alignY" => "bottom" ]);
	
	return $gd;
}

function GeneratePcHubZoneName(array $options){
	$defaultOptions = [
		"zoneIndex"    => 2,
		"forcedHeight" => 100,
		"solvedCount"  => 0,
		"remainCount"  => 1,
		"totalCount"   => 1,
	];
	$options = array_merge($defaultOptions, $options);
	$zoneIndex    = $options["zoneIndex"];
	$forcedHeight = $options["forcedHeight"];
	$solvedCount  = $options["solvedCount"];
	$remainCount  = $options["remainCount"];
	$totalCount   = $options["totalCount"];
	$pctString    = sprintf("%d%%", floor(($solvedCount + 1e-4) / $totalCount * 100.0));
	
	$isZoneComplete = ($remainCount == 0); //  || ($zoneIndex % 2 == 1); // debug
	$zoneTitleColor = ($isZoneComplete ? PC_CLR_ZONECOMPLETEBACK : PC_CLR_ZONETITLEBACK);
	
	$colorCode = GetZoneColorCode($zoneIndex);
	$zoneName = ZoneToPrettyNoColor($zoneIndex);
	
	$width = 237;
	$height = $forcedHeight;
	$gd = CreateCard($width, $height, [ "cardColor" => $zoneTitleColor, "cardRadius" => 16, "margin" => 0 ]);
	
	$iconZoneGd = GetIcon("zone/" . $zoneIndex, [ "alpha" => 0.9 ]);
	static $zoneIconScale = [ 2 => 1.72, 3 => 1.20, 4 => 1.25, 5 => 1.20, 6 => 1.15 ];
	$myScale = $zoneIconScale[$zoneIndex];
	OverlayImageSmart($gd, $iconZoneGd, [ "y" => $height + 1, "scale" => $myScale, "alignX" => "center", "alignY" => "bottom" ]);
	
	$solvedCount = number_format($solvedCount, 0, ".", ",");
	$totalCount  = number_format($totalCount,  0, ".", ",");
	
	$fontSize = 21;
	DrawSmartText($gd, $zoneName,    [ "y" => $height * 0.13, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize * 1.1 * ($zoneIndex == 5 ? 0.95 : 1.0) ]);
	
	DrawSmartText($gd, $solvedCount,[ "x" => $width * 0.30, "y" => $height * 0.29, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize ]);
	DrawSmartText($gd, "/",         [ "x" => $width * 0.50, "y" => $height * 0.29, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize ]);
	DrawSmartText($gd, $totalCount, [ "x" => $width * 0.70, "y" => $height * 0.29, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize ]);
	
	DrawSmartText($gd, $pctString,  [ "y" => $height * 0.45, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize * 1.3 ]);
	
	return $gd;
}

function GeneratePcHubCat(array $options){
	$defaultOptions = [
		"solvedPtypeInfo" => [],
		"remainPtypeInfo" => [],
		"totalPtypeInfo"  => [],
		"rewardArray"     => [],
		"zoneIndex"       => 2,
		"pcat"            => "movement",
	];
	$options = array_merge($defaultOptions, $options);
	$solvedPtypeInfo = $options["solvedPtypeInfo"];
	$remainPtypeInfo = $options["remainPtypeInfo"];
	$totalPtypeInfo  = $options["totalPtypeInfo"];
	$rewardArray     = $options["rewardArray"];
	$zoneIndex       = $options["zoneIndex"];
	$pcat            = $options["pcat"];
	
	$gdArray = [];
	foreach($totalPtypeInfo as $ptype => $ignore){
		$hubItemGd = GeneratePcHubItem([
			"solvedCount" => count($solvedPtypeInfo[$ptype]),
			"remainCount" => count($remainPtypeInfo[$ptype]),
			"totalCount"  => count($totalPtypeInfo [$ptype]),
			"zoneIndex"   => $zoneIndex,
			"pcat"        => $pcat,
			"ptype"       => $ptype,
			"scale"       => 0.7,
		]);
		$gdArray[] = $hubItemGd;
	}
	
	// Append a few blank pics for uniform size.
	// Technically I could tile them horizontally with align = top later, but we need to add the reward track below also.
	$extraBlanks = (4 - count($totalPtypeInfo));
	$cardWidth  = imagesx($gdArray[0]);
	$cardHeight = imagesy($gdArray[0]);
	for($i = 0; $i < $extraBlanks; ++$i){
		//$gdArray[] = CreateBlankImage($cardWidth, $cardHeight);
	}
	
	$gdRewards = GeneratePcHubRewards([ "rewardArray" => $rewardArray, "pcat" => $pcat, "zoneIndex" => $zoneIndex, "forcedWidth" => $cardWidth ]);//, "forcedHeight" => $rewardsHeight ]);
	array_unshift($gdArray, $gdRewards);
	
	$gd = TileImages($gdArray, [ "mode" => "vertical", "align" => "center", "spacer" => 0, "margin" => 0 ]);
	return $gd;
}

function GeneratePcHubItem(array $options){
	$defaultOptions = [
		"solvedCount" => 0,
		"remainCount" => 1,
		"totalCount"  => 1,
		"zoneIndex"   => 2,
		"pcat"        => "movement",
		"ptype"       => "racingBallCourse",
		"scale"       => 1.0,
	];
	$options = array_merge($defaultOptions, $options);
	$solvedCount = $options["solvedCount"];
	$remainCount = $options["remainCount"];
	$totalCount  = $options["totalCount"];
	$zoneIndex   = $options["zoneIndex"];
	$pcat        = $options["pcat"];
	$ptype       = $options["ptype"];
	$scale       = $options["scale"];
	
	$pctString   = sprintf("%d%%", floor(($solvedCount + 1e-4) / $totalCount * 100.0));
	$nicePtype   = PuzzlePrettyName($ptype);
	
	$width  = round(440 * $scale);
	$height = round( 80 * $scale);
	
	$isComplete = ($remainCount == 0);
	$colorFont = ($isComplete ? PC_CLR_WHITE : PC_CLR_GRAYFONT);
	$colorCard = ($isComplete ? PC_CLR_HIGHLIGHT : PC_CLR_GRAYBACK);
	
	$gd = CreateCard($width, $height, [ "cardColor" => $colorCard ]);
	$fontSize = 34 * $scale;
	
	$iconGd = GetIcon("ptype_scaled/" . $ptype);
	$iconSize = 50;
	OverlayImageSmart($gd, $iconGd, [ "x" => $width * 0.12, "size" => $iconSize ]);
	
	$fontName = PC_FONT_BOLD;
	
	DrawSmartText($gd, $solvedCount,[ "x" => $width * 0.37, "colorCode" => $colorFont, "fontName" => $fontName, "fontSize" => $fontSize ]);
	DrawSmartText($gd, "/",         [ "x" => $width * 0.50, "colorCode" => $colorFont, "fontName" => $fontName, "fontSize" => $fontSize ]);
	DrawSmartText($gd, $totalCount, [ "x" => $width * 0.63, "colorCode" => $colorFont, "fontName" => $fontName, "fontSize" => $fontSize ]);
	DrawSmartText($gd, $pctString,  [ "x" => $width * 0.98, "colorCode" => $colorFont, "fontName" => $fontName, "fontSize" => $fontSize * 0.8, "align" => "right" ]);
	
	return $gd;
}

function GeneratePcHubRewards(array $options = []){
	$defaultOptions = [
		"rewardArray"   => [],
		"zoneIndex"     =>  2,
		"pcat"          => "movement",
		"forcedWidth"   => -1,
	];
	$options = array_merge($defaultOptions, $options);
	$rewardArray  = $options["rewardArray"];
	$zoneIndex    = $options["zoneIndex"];
	$pcat         = $options["pcat"];
	$forcedWidth  = $options["forcedWidth"];
	
	$isTrackComplete = (bool)(empty(array_filter($rewardArray, function ($r) { return (!$r["isObtained"]); }))); // shit one-liner to check if all values are true
	
	if($forcedWidth <= 0){
		printf("%s: I need forcedWidth!", __FUNCTION__);
		exit(1);
	}
	$rewardCount = count($rewardArray);
	$rewardSpacerSize = 1;
	$rewardSpacerCount = $rewardCount - 1;
	$remainingPixels = ($forcedWidth - $rewardSpacerCount * $rewardSpacerSize);
	$widthPerReward = intval(floor(1.0 * $remainingPixels / $rewardCount));
	
	$gdArray = [];
	foreach($rewardArray as $index => $rewardInfo){
		$hubItemGd = GeneratePcHubRewardItem([
			"rewardName"   => $rewardInfo["reward"],
			"isObtained"   => $rewardInfo["isObtained"],
			"pOffset"      => $rewardInfo["offset"],
			"zoneIndex"    => $zoneIndex,
			"pcat"         => $pcat,
			"forcedHeight" => $widthPerReward,
			"forcedWidth"  => $widthPerReward,
		]);
		$gdArray[] = $hubItemGd;
	}
	
	$cardColor = PC_CLR_GRAYBACK;
	$gd = TileImages($gdArray, [ "mode" => "horizontal", "align" => "center", "spacer" => $rewardSpacerSize, "cardColor" => $cardColor, "cardRadius" => 3, "margin" => 0 ]);
	
	return $gd;
}

function GeneratePcHubRewardItem(array $options = []){
	$defaultOptions = [
		"rewardName"   => "Unknown",
		"isObtained"   => true,
		"poffset"      => 0,
		"zoneIndex"    => 2,
		"pcat"         => "movement",
		"forcedWidth"  => -1,
		"forcedHeight" => -1,
	];
	$rewardName   = $options["rewardName"];
	$isObtained   = (bool)$options["isObtained"];
	$pOffset      = intval(abs($options["pOffset"]));
	$zoneIndex    = $options["zoneIndex"];
	$pcat         = $options["pcat"];
	$forcedWidth  = $options["forcedWidth"];
	$forcedHeight = $options["forcedHeight"];
	
	$cardColor = ($isObtained ? PC_CLR_HUBREWARDGOT : PC_CLR_GRAYBACK); // overlap
	
	$gd = CreateCard($forcedWidth, $forcedHeight, [ "cardColor" => $cardColor ]);
	$fontColor = PC_CLR_HUBTRACKREMAIN;
	$fontSize = ParseFontSize($forcedHeight * 0.35);

	$iconGd = GetIcon("reward_scaled/" . strtolower($rewardName), [ "alpha" => ($isObtained ? 1.0 : 0.1) ]);
	$iconSize = 38;
	OverlayImageSmart($gd, $iconGd, [ "size" => $iconSize ]);
	
	if(!$isObtained){
		if($pOffset > 1000){
			$pOffset = "1k+";
		}elseif($pOffset == 1000){
			$pOffset = "1k";
		}
		DrawSmartText($gd, $pOffset, [ "colorCode" => $fontColor, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize ]);
	}
	
	return $gd;
}

//function CreateExtraContentCard($saveJson, $width, $height, array $options = []){
//	$defaultOptions = [
//		"title"         => "",
//		"titleColor"    => PC_CLR_WHITE,
//		"titleFontName" => PC_FONT_BOLD,
//		"titleFontSize" => 18,
//		"itemColor"     => PC_CLR_WHITE,
//		"itemFontName"  => PC_FONT_BOLD,
//		"itemFontSize"  => 22,
//		"backColor"     => PC_CLR_TRANSPARENT,
//		"cardColor"     => PC_CLR_MASTERYNORMAL,
//		"cardMargin"    => 0,
//		"cardRadius"    => 3,
//		"fakeHeight"    => $height,
//	];
//	$options = array_merge($defaultOptions, $options);
//	$title          = $options["title"];
//	$titleColor     = $options["titleColor"];
//	$titleFontName  = $options["titleFontName"];
//	$titleFontSize  = $options["titleFontSize"];
//	$itemColor      = $options["itemColor"];
//	$itemFontName   = $options["itemFontName"];
//	$itemFontSize   = $options["itemFontSize"];
//	$backColor      = $options["backColor"];
//	$cardColor      = $options["cardColor"];
//	$cardMargin     = round(max(0, $options["cardMargin"]));
//	$cardRadius     = round(max(0, $options["cardRadius"]));
//	$fakeHeight     = $options["fakeHeight"];
//	
//	$staticLostgridsCount = count(GetStaticsBySource("lostgrids"));
//	$staticLostgridsSolvedCount = count($saveJson->lostgridsSolvedPids);
//	$isStaticLostgridsComplete = ($staticLostgridsCount == $staticLostgridsSolvedCount);
//	//$pctStaticLostgrids = number_format(floor(100.0 * $staticLostgridsSolvedCount / $staticLostgridsCount + 1e-6), 0, ".", ",") . "%";
//	
//	$clusterLostgridsCount = count(GetClusterMap()["lostgrids"]);
//	$clusterLostgridsSolvedCount = count($saveJson->solvedClusterMap["lostgrids"]);
//	$isClusterLostgridsComplete = ($clusterLostgridsCount == $clusterLostgridsSolvedCount);
//	//$pctClusterLostgrids = number_format(floor(100.0 * $clusterLostgridsSolvedCount / $clusterLostgridsCount + 1e-6), 0, ".", ",") . "%";
//	
//	$isLostgridsComplete = ($isStaticLostgridsComplete && $isClusterLostgridsComplete);
//	
//	if($isLostgridsComplete){
//		$cardColor = PC_CLR_MASTERYGOT;
//	}
//	
//	$gd = CreateCard($width, $height, [ "backColor" => $backColor, "cardColor" => $cardColor, "cardMargin" => $cardMargin, "cardRadius" => $cardRadius ]);
//	
//	$items = [];
//	$items[] = [
//		"itemName" => "LostGrids Cluster",
//		"curr" => $clusterLostgridsSolvedCount,
//		"total" => $clusterLostgridsCount,
//	];
//	$items[] = [
//		"itemName" => "LostGrids Statics",
//		"curr" => $staticLostgridsSolvedCount,
//		"total" => $staticLostgridsCount,
//	];
//	
//	foreach($items as $index => $item){
//		$itemName = $item["itemName"];
//		$curr = $item["curr"];
//		$total = $item["total"];
//		$pctString = number_format(floor(100.0 * $curr / $total + 1e-6), 0, ".", ",") . "%";
//		
//		$myTitleY = $fakeHeight * (0.12 + $index * 0.32);
//		$myItemY  = $fakeHeight * (0.26 + $index * 0.32);
//		
//		DrawSmartText($gd, $itemName, [ "y" => $myTitleY, "colorCode" => $titleColor, "fontName" => $titleFontName, "fontSize" => $titleFontSize ]);
//		
//		static $midTextSpacer = 0.12;
//		DrawSmartText($gd, $curr,      [ "x" => $width * (0.32 - $midTextSpacer), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, "/",        [ "x" => $width * (0.32                 ), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, $total,     [ "x" => $width * (0.32 + $midTextSpacer), "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName, "fontSize" => $itemFontSize ]);
//		DrawSmartText($gd, $pctString, [ "x" => $width * 0.90,                   "y" => $myItemY, "colorCode" => $itemColor, "fontName" => $itemFontName,
//			"fontSize" => $itemFontSize * 0.85, "align" => "right" ]);
//	}
//	return $gd;
//}

function GeneratePcMasteries($saveJson, array $options = []){
	$defaultOptions = [
		"forcedHeight" => -1,
	];
	$options = array_merge($defaultOptions, $options);
	$forcedHeightTotal = $options["forcedHeight"];
	if($forcedHeightTotal <= 0){
		printf("%s: I need forcedHeight!\n", __FUNCTION__);
		exit(1);
	}
	
	$weightMasteryTitle = 0.8;
	$weightGenerationTs = 0.9;
	
	$masterySmallSpacer = 4;
	$masteryCardMargin = 0;
	
	$smallSpacerCount = count($saveJson->masteryTable) + 2;
	//$largeSpacerSize = max(0, PC_LARGE_SPACER - $masterySmallSpacer * 2);
	$largeSpacerSize = (PC_IS_SMALL_SIDE_SPACER ? 3 : max(0, PC_LARGE_SPACER - $masterySmallSpacer * 2));
	
	$totalWeight = (1.0 * count($saveJson->masteryTable) + $weightMasteryTitle + $weightGenerationTs);
	$heightPerItem = round(1.0 * ($forcedHeightTotal - $largeSpacerSize - $smallSpacerCount * $masterySmallSpacer) / $totalWeight + 1e-4);
	
	$masteryTitleHeight = round($heightPerItem * $weightMasteryTitle);
	$generationTsHeight = $forcedHeightTotal - $largeSpacerSize - ($heightPerItem * count($saveJson->masteryTable)) - $masteryTitleHeight - $smallSpacerCount * $masterySmallSpacer;
	
	// Fill mastery items.
	$gdArray = [];
	foreach($saveJson->masteryTable as $entry){
		$masteryItemGd = GeneratePcMasteryItem([
			"ptype"        => $entry["ptype"],
			"level"        => $entry["level"],
			"xp"           => $entry["xp"],
			"title"        => $entry["title"],
			"inGameBorder" => $entry["border"],
			"inGameSkin"   => $entry["skin"],
			"scale"        => 0.7,
			"forcedHeight" => $heightPerItem,
			"cardMargin"   => $masteryCardMargin,
		]);
		$gdArray[] = $masteryItemGd;
	}
	$forcedWidth = imagesx($gdArray[0]);
	
	// Add mastery header.
	$gdMasteryHeader = CreateCard($forcedWidth, $masteryTitleHeight, [ "cardColor" => PC_CLR_MASTERYTITLEBACK, "cardRadius" => 3, "cardMargin" => $masteryCardMargin ]);
	DrawSmartText($gdMasteryHeader, "Masteries", [ "colorCode" => PC_CLR_WHITE, "fontName" => PC_FONT_BOLD, "fontSize" => 22 ]);
	
	// Add generation date.
	$localDt = GetLocalDt();
	$generationDate = $localDt->format("M j, Y"); // "Oct 30, 2024";
	$generationString = "Made on " . $generationDate . " (v" .PC_IMAGE_VERSION . ")";
	$gdGenerationTs = CreateCard($forcedWidth, $generationTsHeight, [ "cardColor" => PC_CLR_GENTIMEBACK, "cardRadius" => 3, "margin" => 0 ]);
	DrawSmartText($gdGenerationTs, $generationString, [ "colorCode" => PC_CLR_WHITE, "fontName" => PC_FONT_BOLD, "fontSize" => 18 ]);
	
	// Add a spacer in between.
	$gdSpacer = CreateBlankImage($forcedWidth, $largeSpacerSize);
	
	array_unshift($gdArray, $gdMasteryHeader);
	$gdArray[] = $gdSpacer;
	$gdArray[] = $gdGenerationTs;
	$gd = TileImages($gdArray, [ "mode" => "vertical", "align" => "center", "spacer" => $masterySmallSpacer, "margin" => 0, "cardColor" => PC_CLR_TRANSPARENT ]); // PC_CLR_MASTERYNORMAL
	
	return $gd;
}

function GeneratePcMasteryItem(array $options = []){
	$defaultOptions = [
		"ptype"        => "racingBallCourse",
		"level"        => 1,
		"xp"           => 65,
		"title"        => "none",
		"inGameBorder" => "none",
		"inGameSkin"   => 0,
		"scale"        => 1.0,
		"forcedHeight" => -1,
		"cardMargin"   => 0,
	];
	$options       = array_merge($defaultOptions, $options);
	$ptype         = $options["ptype"];
	$level         = $options["level"];
	$xp            = $options["xp"];
	$scale         = $options["scale"];
	$title         = $options["title"];
	$forcedHeight  = $options["forcedHeight"];
	$inGameBorder  = $options["inGameBorder"];
	$inGameSkin    = $options["inGameSkin"];
	$cardMargin    = $options["cardMargin"];
	
	$isMaxLevel = ($level >= 99);
	$width  = round(540 * $scale);
	$height = $forcedHeight;
	
	static $masteryColorMap = [
		"none"     => "#8c888b00",
		"bronze"   => "#b38c6b00",
		"silver"   => "#f2e9d000",
		"gold"     => "#e0b66300",
		"platinum" => "#8696c400",
		"ascend"   => "#f09b5d00",
		"demigod"  => "#8CC45C00", // cc5a3d00
	];
	if($isMaxLevel){
		$inGameBorder = "demigod";
	}
	if(!isset($masteryColorMap[$inGameBorder])){
		printf("%s\n", ColorStr(__FUNCTION__ . ": unknown border color \"" . $inGameBorder . " for " . PuzzlePrettyName($ptype) . " level " . $level, 255, 128, 128));
		exit(1);
	}
	$colorCode = $masteryColorMap[$inGameBorder];
	
	$cardColor = ($isMaxLevel ? PC_CLR_MASTERYGOT : PC_CLR_MASTERYNORMAL);
	$gd = CreateCard($width, $height, [ "cardColor" => $cardColor, "cardMargin" => $cardMargin ]);
	
	$xpString = number_format($xp, 0, ".", ",") . " XP";
	$pctString = number_format(floor(100.0 * $xp / GetTotalXpTo99() + 1e-6), 0, ".", "") . "%";
	$fontSize = 34 * $scale;
	
	$iconGd = GetIcon("ptype_scaled/" . $ptype);
	$iconSize = 50;
	OverlayImageSmart($gd, $iconGd, [ "x" => $width * 0.10, "size" => $iconSize ]);
	
	DrawSmartText($gd, $level,    [ "x" => $width * 0.25, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize ]);
	DrawSmartText($gd, $xpString, [ "x" => $width * 0.72, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize * 0.75, "align" => "right" ]);
	DrawSmartText($gd, $pctString,[ "x" => $width * 0.97, "colorCode" => $colorCode, "fontName" => PC_FONT_BOLD, "fontSize" => $fontSize * 0.85, "align" => "right" ]);
	
	return $gd;
}

