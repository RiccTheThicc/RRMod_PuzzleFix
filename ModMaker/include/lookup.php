<?php

include_once("include\\stringex.php");

function GetKnownPtypes(){
	static $knownPtypes = [
		// Only ACTUAL puzzles, i.e. 24 primary types.
		"completeThePattern",
		"followTheShiny",
		"fractalMatch",
		"ghostObject",
		"gyroRing",
		"hiddenArchway",
		"hiddenCube",
		"hiddenRing",
		"klotski",
		"lightPattern",
		"lockpick",
		"logicGrid",
		"match3",
		"matchbox",
		"memoryGrid",
		"mirrorMaze",
		"musicGrid",
		"racingBallCourse",
		"racingRingCourse",
		"rollingCube",
		"rosary",
		"ryoanji",
		"seek5",
		"viewfinder",
	];
	return $knownPtypes;
}

function IsKnownPtype(string $ptype){
	return (in_array($ptype, GetKnownPtypes()));
}

function PuzzlePrettyName(string $ptype){
	switch($ptype){
		case "completeThePattern"		: return "PatternGrid";
		case "dungeon"					: return "Enclave";
		case "followTheShiny"			: return "WanderingEcho";
		case "fractalMatch"				: return "MorphicFractal";
		case "ghostObject"				: return "ShyAura";
		case "gyroRing"					: return "ArmillaryRings";
		case "hiddenArchway"			: return "HiddenArchway";
		case "hiddenCube"				: return "HiddenCube";
		case "hiddenRing"				: return "HiddenRing";
		case "klotski"					: return "ShiftingMosaic";
		case "levelRestrictionVolume"	: return "EntranceUnlock"; // it's that glass thing that covers the jumppads to enclaves until you unlock them
		case "lightPattern"				: return "LightMotif";
		case "lockpick"					: return "PhasicDial";
		case "logicGrid"				: return "LogicGrid";
		case "match3"					: return "Match3";
		case "matchbox"					: return "MatchBox";
		case "memoryGrid"				: return "MemoryGrid";
		case "mirrorMaze"				: return "CrystalLabyrinth";
		case "musicGrid"				: return "MusicGrid";
		case "obelisk"					: return "Monolith";
		case "puzzleTotem"				: return "PillarOfInsight";
		case "racingBallCourse"			: return "FlowOrbs";
		case "racingRingCourse"			: return "GlideRings";
		case "rollingCube"				: return "RollingBlock";
		case "rosary"					: return "Skydrop";
		case "ryoanji"					: return "SentinelStones";
		case "seek5"					: return "HiddenPentad";
		case "viewfinder"				: return "SightSeer";
		case "loreFragment"				: return "LoreFragment"; // not a real puzzle
		case "monolithFragment"         : return "MonolithFragment"; // not a real puzzle
		default							: return FALSE;
	}
}

function PuzzleInternalName(string $name){
	$name = preg_replace("/\s+/", "", $name);
	$name = trim(strtolower($name));
	$name = str_replace(["_". "-", " "], "", $name);
	if(IsKnownPtype($name)){
		return $name;
	}
	switch($name){
		case "completethepattern"     : return "completeThePattern";
		case "patterngrid"            : return "completeThePattern";
		case "patterngrids"           : return "completeThePattern";
		case "dungeon"                : return "dungeon";
		case "enclave"                : return "dungeon";
		case "quest"                  : return "dungeon";
		case "script"                 : return "dungeon";
		case "followtheshiny"         : return "followTheShiny";
		case "wanderingecho"          : return "followTheShiny";
		case "wonderingecho"          : return "followTheShiny";
		case "echo"                   : return "followTheShiny";
		case "fractalmatch"           : return "fractalMatch";
		case "morphicfractal"         : return "fractalMatch";
		case "fractal"                : return "fractalMatch";
		case "ghostobject"            : return "ghostObject";
		case "shyaura"                : return "ghostObject";
		case "gyroring"               : return "gyroRing";
		case "armillaryrings"         : return "gyroRing";
		case "armillaryring"          : return "gyroRing";
		case "goldenrings"            : return "gyroRing";
		case "hiddenarchway"          : return "hiddenArchway";
		case "harchway"               : return "hiddenArchway";
		case "hiddencube"             : return "hiddenCube";
		case "hcube"                  : return "hiddenCube";
		case "hiddenring"             : return "hiddenRing";
		case "hring"                  : return "hiddenRing";
		case "shiftingmosaic"         : return "klotski";
		case "mosaic"                 : return "klotski";
		case "levelrestrictionvolume" : return "levelRestrictionVolume";
		case "entranceunlock"         : return "levelRestrictionVolume";
		case "shield"                 : return "levelRestrictionVolume";
		case "lightmotif"             : return "lightPattern";
		case "lightpattern"           : return "lightPattern";
		case "motif"                  : return "lightPattern";
		case "phasicdial"             : return "lockpick";
		case "phasic"                 : return "lockpick";
		case "logicgrid"              : return "logicGrid";
		case "logicgrids"             : return "logicGrid";
		case "gridpuzzle"             : return "logicGrid";
		case "match3"                 : return "match3";
		case "matchthree"             : return "match3";
		case "matchbox"               : return "matchbox";
		case "memorygrid"             : return "memoryGrid";
		case "memorygrids"            : return "memoryGrid";
		case "crystallabyrinth"       : return "mirrorMaze";
		case "crystalmaze"            : return "mirrorMaze";
		case "mirrorlabyrinth"        : return "mirrorMaze";
		case "mirrormaze"             : return "mirrorMaze";
		case "maze"                   : return "mirrorMaze";
		case "labyrinth"              : return "mirrorMaze";
		case "musicgrid"              : return "musicGrid";
		case "musicgrids"             : return "musicGrid";
		case "obelisk"                : return "obelisk";
		case "monument"               : return "obelisk";
		case "monolith"               : return "obelisk";
		case "puzzletotem"            : return "puzzleTotem";
		case "pillarofinsight"        : return "puzzleTotem";
		case "tutorialpillar"         : return "puzzleTotem";
		case "pillar"                 : return "puzzleTotem";
		case "racingballs"            : return "racingBallCourse";
		case "racingballcourse"       : return "racingBallCourse";
		case "floworbs"               : return "racingBallCourse";
		case "florb"                  : return "racingBallCourse";
		case "racingrings"            : return "racingRingCourse";
		case "racingringcourse"       : return "racingRingCourse";
		case "gliderings"             : return "racingRingCourse";
		case "rollingcube"            : return "rollingCube";
		case "rollingblock"           : return "rollingCube";
		case "skydrop"                : return "rosary";
		case "sentinelstones"         : return "ryoanji";
		case "sentinelstone"          : return "ryoanji";
		case "sentinels"              : return "ryoanji";
		case "hiddenpentad"           : return "seek5";
		case "hpentad"                : return "seek5";
		case "pentad"                 : return "seek5";
		case "sightseer"              : return "viewfinder";
		case "viewfinder"             : return "viewfinder";
		case "lorefragment"           : return "loreFragment";
		case "monolithfragment"       : return "monolithFragment";
		default                       : return FALSE;
	}
}

function ZoneNameToInt(string $name){
	// "VERDANT GLEN", "VerdantGlen" etc all work.
	$name = str_replace("EMainMapZoneName::", "", $name);
	$name = preg_replace("/\s+/", "", $name);
	$name = strtolower($name);
	switch($name){
		case "invalidzone"    : return 0; // many enclaves use this; not literally invalid zone
		case "unknownzone"    : return 0;
		case "dungeon"        : return 0;
		case "enclave"        : return 0;
		case "quest"          : return 0;
		case "msic"           : return 0;
		case "misc"           : return 0;
		
		case "lobby"          : return 1;
		
		case "verdantglen"    : return 2;
		case "verdantglenn"   : return 2;
		case "rainforest"     : return 2;
		case "egypt"          : return 2;
		
		case "lucentwater"    : return 3;
		case "lucentwaters"   : return 3;
		case "central"        : return 3;
		
		case "autumnfall"     : return 4;
		case "autumnfalls"    : return 4;
		case "riverland"      : return 4;
		case "riverlands"     : return 4;
		
		case "shadywildwood"  : return 5;
		case "shadywildwoods" : return 5;
		case "redwood"        : return 5;
		case "redwoods"       : return 5;
		
		case "serenedeluge"   : return 6;
		case "mountain"       : return 6;
		case "geyser"         : return 6;
		
		case "firstechoes"    : return 7;
		case "firstecho"      : return 7;
		case "tutorial"       : return 7;
		case "intro"          : return 7;
		default               : return FALSE;
	}
}

function GetHubZones(){
	return [ 2, 3, 4, 5, 6 ];
}

function IsHubZone(string|int $zone){
	if(is_string($zone)){
		$zone = ZoneNameToInt($s);
	}
	return (in_array($zone, GetHubZones()));
}

function GetZoneColor(int $zone){
	static $zoneColors = [
		0 => [ 255, 255, 255],
		2 => [ 255, 164,  60],
		3 => [ 147, 206, 255],
		4 => [ 255, 140, 127],
		5 => [  78, 224,  42],
		6 => [ 255, 255, 140],
		7 => [ 182,  56, 255],
	];
	if(isset($zoneColors[$zone])){
		return $zoneColors[$zone];
	}
	return [ 255, 255, 255 ];
}

function GetZoneColorCode(int $zone){
	list($r, $g, $b) = GetZoneColor($zone);
	$colorCode = sprintf("#%02x%02x%02x%02x", $r, $g, $b, 0);
	return $colorCode;
}

function ZoneToPretty(int $zone){
	$myColor = GetZoneColor($zone);
	$myColorEsc = sprintf("\e[38;2;%03d;%03d;%03dm", $myColor[0], $myColor[1], $myColor[2]);
	$resetColorEsc = "\e[0m";
	switch($zone){
		case  -1 : return "Unknown Zone";
		case   0 : return "Dungeon"; // all quests, enclaves, static puzzles
		case   1 : return "Lobby"; // deprecated multiplayer feature, not used by anything now
		case   2 : return $myColorEsc . "Verdant Glen"   . $resetColorEsc;
		case   3 : return $myColorEsc . "Lucent Waters"  . $resetColorEsc;
		case   4 : return $myColorEsc . "Autumn Falls"   . $resetColorEsc;
		case   5 : return $myColorEsc . "Shady Wildwood" . $resetColorEsc;
		case   6 : return $myColorEsc . "Serene Deluge"  . $resetColorEsc;
		case   7 : return "First Echoes";
		case  99 : return "Unknown Zone";
		default  : return FALSE;
	}
}

function ZoneToPrettyNoColor(int $zone){
	switch($zone){
		case  -1 : return "Unknown Zone";
		case   0 : return "Dungeon"; // all quests, enclaves, static puzzles
		case   1 : return "Lobby"; // deprecated multiplayer feature, not used by anything now
		case   2 : return "Verdant Glen"  ;
		case   3 : return "Lucent Waters" ;
		case   4 : return "Autumn Falls"  ;
		case   5 : return "Shady Wildwood";
		case   6 : return "Serene Deluge" ;
		case   7 : return "First Echoes";
		case  99 : return "Unknown Zone";
		default  : return FALSE;
	}
}

function GetKnownPuzzleCategories(){
	static $knownPuzzleCategories = [
		"perspective",
		"hiddenobjects",
		"puzzleboxes",
		"movement",
		"action",
		"environment",
	];
	return $knownPuzzleCategories;
}

function IsKnownPuzzleCategory(string $pcat){
	return (in_array($pcat, GetKnownPuzzleCategories()));
}

function PuzzleCategoryPrettyName(string $pcat){
	switch($pcat){
		case "perspective"		: return "Perspective";
		case "hiddenobjects"	: return "HiddenObjects";
		case "puzzleboxes"		: return "PuzzleBoxes";
		case "movement"			: return "Movement";
		case "action"			: return "Action";
		case "environment"		: return "Environment";
		default					: return FALSE;
	}
}

function PuzzleCategoryInternalName(string $catname){
	$catname = preg_replace("/\s+/", "", $catname);
	$catname = trim(strtolower($catname));
	$catname = str_replace(["_". "-", " "], "", $catname);
	
	switch($catname){
		case "perspective"		: return "perspective";
		case "hiddenobjects"	: return "hiddenobjects";
		case "puzzleboxes"		: return "puzzleboxes";
		case "cubes"		    : return "puzzleboxes"; // lol, save file tracks Cubes.
		case "movement"			: return "movement";
		case "action"			: return "action";
		case "environment"		: return "environment";
		default                 : return FALSE;
	}
}

function PuzzleTypeToCategory(string $ptype){
	static $ptypeToCategoryMap = [
		"matchbox"					=> "perspective",
		"lightPattern"				=> "perspective",
		"viewfinder"				=> "perspective",
		"ryoanji"					=> "perspective",
		"hiddenRing"				=> "hiddenobjects",
		"hiddenCube"				=> "hiddenobjects",
		"hiddenArchway"				=> "hiddenobjects",
		"seek5"						=> "hiddenobjects",
		"logicGrid"					=> "puzzleboxes",
		"completeThePattern"		=> "puzzleboxes",
		"memoryGrid"				=> "puzzleboxes",
		"musicGrid"					=> "puzzleboxes",
		"followTheShiny"			=> "movement",
		"racingRingCourse"			=> "movement",
		"racingBallCourse"			=> "movement",
		"match3"					=> "action",
		"lockpick"					=> "action",
		"rollingCube"				=> "action",
		"klotski"					=> "action",
		"mirrorMaze"				=> "environment",
		"fractalMatch"				=> "environment",
		"ghostObject"				=> "environment",
	];
	if(isset($ptypeToCategoryMap[$ptype])){
		return $ptypeToCategoryMap[$ptype];
	}
	return "";
}

function GetPuzzleCategoryToTypeMap(){
	static $pcatToPtypes = null;
	if($pcatToPtypes === null){
		$pcatToPtypes = [];
		$knownPtypes = GetKnownPtypes();
		foreach($knownPtypes as $ptype){
			$pcat = PuzzleTypeToCategory($ptype);
			if(empty($pcat)){
				continue;
			}
			if(!isset($pcatToPtypes[$pcat])){
				$pcatToPtypes[$pcat] = [];
			}
			$pcatToPtypes[$pcat][] = $ptype;
		}
	}
	return $pcatToPtypes;
}

function PuzzleCategoryToTypes(string $pcat){
	$map = GetPuzzleCategoryToTypeMap();
	if(isset($map[$pcat])){
		return $map[$pcat];
	}
	return [];
}
	
function IsDungeonPuzzle($pid, $ptype, &$data = null){
	static $dungeon_ptypes = [
		"dungeon", // quests and enclaves themselves are considered puzzles
		"puzzleTotem", // mutli-puzzle tutorials
		"obelisk", // duh
		"levelRestrictionVolume", // those forcefields early in the game that prevent from accessing later enclaves
	];
	if(in_array($ptype, $dungeon_ptypes)){
		return true;
	}
	if(isset($data->status) && $data->status == "dungeon"){
		return true;
	}
	if(isset($data) && isset($data->SpawnBehaviour) && ($data->SpawnBehaviour != 2)){
		// SpawnBehaviour: 0 = Always (static/dungeon puzzle), 1 = Never (unused), 2 = Dynamic.
		return true;
	}
	if(isset($data) && isset($data->PoolName)){
		// Pool name could be "live", "Zone1".."Zone5", or belong to a specific dungeon.
		//if($data->PoolName != "live" && !preg_match("/^Zone([0-9])$/", $data->PoolName)){
		//	return false;
		//}
		if($data->PoolName == "live" || preg_match("/^Zone\d$/", $data->PoolName)){
			return false;
		}else{
			// extra checks?
			return true;
		}
	}
	return false;
}

function IsUntrackedRandomPuzzle($pid, $ptype, &$data = null){
	static $untracked_random_ptypes = [
		"rosary",   // game generates sky drops randomly (outside of enclaves ofc)
		"gyroRing", // armillary rings are technically not random - they get picked from a limited pool
	];
	if(in_array($ptype, $untracked_random_ptypes)){
		return true;
	}
	if($pid == -1){
		// You should never be able to reach this section of the code. Let's dump what we found.
		printf("[Warning] Discovered unknown puzzle id %d, ptype \"%s\", data: %s\n", $pid, $ptype, (empty($data) ? "(null)" : json_encode($data)));
		return true; // don't track this anyway.
	}
	return false;
}

function IsSandboxPuzzle($pid, $ptype, &$data = null){
	return (!IsDungeonPuzzle($pid, $ptype, $data) && !IsUntrackedRandomPuzzle($pid, $ptype, $data));
}

function IsSeeThenSolve(string $ptype){
	// Some puzzles (e.g. logic grids) send a gm_open_puzzle event first, then gm_solve_puzzle or gm_abandon_puzzle.
	// Other puzzles (e.g. hidden archways) send no extra events; just gm_solve_puzzle when they're solved.
	// We want to collect which puzzles were *seen* in the logs (not necessarily solved; all of them).
	// So in order to avoid data duplication we use either open or solve event depending on puzzle type.
	static $seeThenSolvePtypes = [
		"completeThePattern",
		"fractalMatch",
		"klotski",
		"lockpick",
		"logicGrid",
		"match3",
		"memoryGrid",
		"musicGrid",
	];
	return (in_array($ptype, $seeThenSolvePtypes));
}

function IsFastTravelCoord($obj){
	$obj = (object)$obj;
	static $fastTravelCoords = [
		[ "x" =>  54963.086, "y" => 69537.516, "z" => 14137.530 ],
		[ "x" =>   9310.629, "y" =>  6042.678, "z" => 24729.635 ],
		[ "x" =>  -4269.844, "y" => 51476.879, "z" => 17571.201 ],
		[ "x" => -73283.195, "y" => 49543.297, "z" =>  9789.071 ],
		[ "x" => -35791.867, "y" =>  6345.087, "z" => 26547.492 ],
	];
	foreach($fastTravelCoords as $ftc){
		if(DistanceSquared($obj, (object)$ftc) < 5.0){
			return true;
		}
	}
	return false;
}

function MedalTierToName($tier){
	static $tierNames = [
		-1 => "None",
		0  => "Bronze",
		1  => "Silver",
		2  => "Gold",
		3  => "Platinum"
	];
	//if($tier < 0 || $tier >= count($tierNames)){
	if(!isset($tierNames[$tier])){
		return "Unknown";
	}
	return $tierNames[$tier];
}

function MonolithFragmentToFakePid(int $zoneIndex, int $fragmentIndex){
	$fakePid = 42000 + ($zoneIndex * 100) + $fragmentIndex;
	return $fakePid;
}
