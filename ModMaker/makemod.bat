:: Enable ESC sequences. Some magic.
@echo off
for /F %%a in ('echo prompt $E ^| cmd') do set "ESC=%%a"

:: Run the modmaker to build the jsons.
cd /d %~dp0
"php\php.exe" modmaker.php || goto :error
"php\php.exe" chestmaker.php || goto :error
"php\php.exe" camphor_puzzles_moverotate.php || goto :error

"php\php.exe" camphor_main.php || goto :error
"php\php.exe" camphor_platform_moverotate.php || goto :error
"php\php.exe" camphor_meshes_moverotate.php || goto :error

"php\php.exe" icon_inject.php "..\Assets\MapMarker" "..\Assets\cirkul.png"      "..\Assets\EnclaveEx" || goto :error
"php\php.exe" icon_inject.php "..\Assets\MapMarker" "..\Assets\clustericon.png" "..\Assets\ClusterEx" || goto :error

:: Convert all other jsons.
if not exist	"..\OutputJsons\"	`	mkdir "..\OutputJsons\"
if not exist	"..\OutputJsons\Maps"	mkdir "..\OutputJsons\Maps"

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_BlueOrbFragmentBeam.json"						"..\OutputJsons\BP_BlueOrbFragmentBeam.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_ItemPickupChest.json"							"..\OutputJsons\BP_ItemPickupChest.json"							|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_MatchboxRadar.json"							"..\OutputJsons\BP_MatchboxRadar.json"								|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_RacingBalls.json"								"..\OutputJsons\BP_RacingBalls.json"								|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_RacingRings.json"								"..\OutputJsons\BP_RacingRings.json"								|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_Rosary.json"									"..\OutputJsons\BP_Rosary.json"										|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_Rune.json"										"..\OutputJsons\BP_Rune.json"										|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\WBP_LargeMap.json"								"..\OutputJsons\WBP_LargeMap.json"									|| goto :error

:: PuzzleDatabase.json handled by modmaker
:: SandboxZones.json handled by modmaker
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\QuestData_Missions.json"							"..\OutputJsons\QuestData_Missions.json"							|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\SandboxProgressionData.json"						"..\OutputJsons\SandboxProgressionData.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\ZoneData.json"									"..\OutputJsons\ZoneData.json"										|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\MainMap_BetaCampaign_Batched.json"			"..\OutputJsons\Maps\MainMap_BetaCampaign_Batched.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\RRMod_Objects.json"							"..\OutputJsons\Maps\RRMod_Objects.json"							|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_CampaignObjects.json"				"..\OutputJsons\Maps\Rainforest_CampaignObjects.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_EarnYourWings_Puzzles.json"		"..\OutputJsons\Maps\Rainforest_EarnYourWings_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_HardMatch3_Puzzles.json"			"..\OutputJsons\Maps\Rainforest_HardMatch3_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_Match3_Puzzles.json"				"..\OutputJsons\Maps\Rainforest_Match3_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_MazePatternTutorial_Puzzles.json"	"..\OutputJsons\Maps\Rainforest_MazePatternTutorial_Puzzles.json"	|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Rainforest_PyramidMirrorMaze_Puzzles.json"	"..\OutputJsons\Maps\Rainforest_PyramidMirrorMaze_Puzzles.json"		|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\SecretSeek5Puzzles.json"						"..\OutputJsons\Maps\SecretSeek5Puzzles.json"						|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_CampaignObjects.json"				"..\OutputJsons\Maps\Central_CampaignObjects.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\CentralGridsAndCOWYC_Puzzles.json"			"..\OutputJsons\Maps\CentralGridsAndCOWYC_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_BonusCOWYC_Puzzles.json"				"..\OutputJsons\Maps\Central_BonusCOWYC_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_Enlightenment_Puzzles.json"			"..\OutputJsons\Maps\Central_Enlightenment_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_RollingCubeTutorial_Puzzles.json"	"..\OutputJsons\Maps\Central_RollingCubeTutorial_Puzzles.json"		|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_SmallMatchbox_Puzzles.json"			"..\OutputJsons\Maps\Central_SmallMatchbox_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Central_TrickoftheWanderer_Puzzles.json"		"..\OutputJsons\Maps\Central_TrickoftheWanderer_Puzzles.json"		|| goto :error
:: CamphorCorridorTemple.json handled by camphor_main
:: CamphorEntrance.json handled by camphor_main

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverland_CampaignObjects.json"				"..\OutputJsons\Maps\Riverland_CampaignObjects.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverlands_Dyads_Puzzles.json"				"..\OutputJsons\Maps\Riverlands_Dyads_Puzzles.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverlands_EyeNeedle_Puzzles.json"			"..\OutputJsons\Maps\Riverlands_EyeNeedle_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverlands_LockpickTutorial_Puzzles.json"	"..\OutputJsons\Maps\Riverlands_LockpickTutorial_Puzzles.json"		|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverlands_Yajilin_Puzzles.json"				"..\OutputJsons\Maps\Riverlands_Yajilin_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Riverlands_YinYangDungeon_Puzzles.json"		"..\OutputJsons\Maps\Riverlands_YinYangDungeon_Puzzles.json"		|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_CampaignObjects.json"				"..\OutputJsons\Maps\Redwood_CampaignObjects.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_Kailasa.json"						"..\OutputJsons\Maps\Redwood_Kailasa.json"							|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_1SPIIntro_Puzzles.json"				"..\OutputJsons\Maps\Redwood_1SPIIntro_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_GhostObjects_Puzzles.json"			"..\OutputJsons\Maps\Redwood_GhostObjects_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_GrayNuri_Puzzles.json"				"..\OutputJsons\Maps\Redwood_GrayNuri_Puzzles.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_MirrorSymmetry_Puzzles.json"			"..\OutputJsons\Maps\Redwood_MirrorSymmetry_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Redwood_RosaryDungeon_Puzzles.json"			"..\OutputJsons\Maps\Redwood_RosaryDungeon_Puzzles.json"			|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_CampaignObjects.json"				"..\OutputJsons\Maps\Mountain_CampaignObjects.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_CaveTutorial_Puzzles.json"			"..\OutputJsons\Maps\Mountain_CaveTutorial_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_HardKlotski_Puzzles.json"			"..\OutputJsons\Maps\Mountain_HardKlotski_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_KlotskiTutorial_Puzzles.json"		"..\OutputJsons\Maps\Mountain_KlotskiTutorial_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_RacingRingsTutorial_Puzzles.json"	"..\OutputJsons\Maps\Mountain_RacingRingsTutorial_Puzzles.json"		|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Mountain_SecretCave_Puzzles.json"			"..\OutputJsons\Maps\Mountain_SecretCave_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Deluxe_Part1_Puzzles.json"					"..\OutputJsons\Maps\Deluxe_Part1_Puzzles.json"						|| goto :error

"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Introduction_Island_Puzzles.json"			"..\OutputJsons\Maps\Introduction_Island_Puzzles.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Maps\Introduction_SecretIsland_Puzzles.json"		"..\OutputJsons\Maps\Introduction_SecretIsland_Puzzles.json"		|| goto :error

"php\php.exe" analyzer.php || goto :error

"php\php.exe" enclaveIconAdder.php || goto :error

:: Convert jsons to uassets.
cd "..\UnrealPakMini"

if not exist	"IslandsofInsight\Content\"																			mkdir "IslandsofInsight\Content\"
if not exist	"IslandsofInsight\Content\ASophia\Data\"															mkdir "IslandsofInsight\Content\ASophia\Data\"
if not exist	"IslandsofInsight\Content\ASophia\Data\Items\"														mkdir "IslandsofInsight\Content\ASophia\Data\Items\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\"														mkdir "IslandsofInsight\Content\ASophia\GameObjects\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"									mkdir "IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\"															mkdir "IslandsofInsight\Content\ASophia\Maps\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\"												mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"								mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"					mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\"								mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\"								mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\RainForestZone\"					mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\RainForestZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\"		mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandEscarpmentZone\"	mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandEscarpmentZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"		mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\"															mkdir "IslandsofInsight\Content\ASophia\Puzzle\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\BlueOrbFragment\"											mkdir "IslandsofInsight\Content\ASophia\Puzzle\BlueOrbFragment"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingBalls\"												mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingBalls"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"									mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rosary\"													mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rosary\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rune\"														mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rune\"
if not exist	"IslandsofInsight\Content\ASophia\UI\HUD\Map\"														mkdir "IslandsofInsight\Content\ASophia\UI\HUD\Map\"
if not exist	"IslandsofInsight\Content\ASophia\UI\HUD\Markers\Textures\"											mkdir "IslandsofInsight\Content\ASophia\UI\HUD\Markers\Textures\"
if not exist	"IslandsofInsight\Content\ASophia\UI\NotificationsAndPopups\Tutorials\"								mkdir "IslandsofInsight\Content\ASophia\UI\NotificationsAndPopups\Tutorials\"
if not exist	"IslandsofInsight\Content\AncientTreasures"															mkdir "IslandsofInsight\Content\AncientTreasures"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials"												mkdir "IslandsofInsight\Content\AncientTreasures\Materials"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials\Instances"										mkdir "IslandsofInsight\Content\AncientTreasures\Materials\Instances"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials\Masters"										mkdir "IslandsofInsight\Content\AncientTreasures\Materials\Masters"
if not exist	"IslandsofInsight\Content\AncientTreasures\Meshes"													mkdir "IslandsofInsight\Content\AncientTreasures\Meshes"
if not exist	"IslandsofInsight\Content\AncientTreasures\Textures"												mkdir "IslandsofInsight\Content\AncientTreasures\Textures"
if not exist	"IslandsofInsight\Content\AncientTreasures\Textures\Utility"										mkdir "IslandsofInsight\Content\AncientTreasures\Textures\Utility"
if not exist	"IslandsofInsight\Content\Localization\Game\de-DE\"													mkdir "IslandsofInsight\Content\Localization\Game\de-DE\"
if not exist	"IslandsofInsight\Content\Localization\Game\en-CA\"													mkdir "IslandsofInsight\Content\Localization\Game\en-CA\"
if not exist	"IslandsofInsight\Content\Localization\Game\en-US\"													mkdir "IslandsofInsight\Content\Localization\Game\en-US\"
if not exist	"IslandsofInsight\Content\Localization\Game\es-ES\"													mkdir "IslandsofInsight\Content\Localization\Game\es-ES\"
if not exist	"IslandsofInsight\Content\Localization\Game\es-MX\"													mkdir "IslandsofInsight\Content\Localization\Game\es-MX\"
if not exist	"IslandsofInsight\Content\Localization\Game\fr-FR\"													mkdir "IslandsofInsight\Content\Localization\Game\fr-FR\"
if not exist	"IslandsofInsight\Content\Localization\Game\it-IT\"													mkdir "IslandsofInsight\Content\Localization\Game\it-IT\"
if not exist	"IslandsofInsight\Content\Localization\Game\ja-JP\"													mkdir "IslandsofInsight\Content\Localization\Game\ja-JP\"
if not exist	"IslandsofInsight\Content\Localization\Game\ko-KR\"													mkdir "IslandsofInsight\Content\Localization\Game\ko-KR\"
if not exist	"IslandsofInsight\Content\Localization\Game\pt-BR\"													mkdir "IslandsofInsight\Content\Localization\Game\pt-BR\"
if not exist	"IslandsofInsight\Content\Localization\Game\zh-CN\"													mkdir "IslandsofInsight\Content\Localization\Game\zh-CN\"
if not exist	"IslandsofInsight\Content\Localization\Game\zh-TW\"													mkdir "IslandsofInsight\Content\Localization\Game\zh-TW\"

@echo on
uassetgui.exe		fromjson "..\OutputJsons\BP_BlueOrbFragmentBeam.json"						"IslandsofInsight\Content\ASophia\Puzzle\BlueOrbFragment\BP_BlueOrbFragmentBeam.uasset"													VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_ItemPickupChest.json"							"IslandsofInsight\Content\ASophia\GameObjects\BP_ItemPickupChest.uasset"																VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_MatchboxRadar.json"								"IslandsofInsight\Content\ASophia\Data\Items\BP_MatchboxRadar.uasset"																	VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_RacingBalls.json"								"IslandsofInsight\Content\ASophia\Puzzle\RacingBalls\BP_RacingBalls.uasset"																VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_RacingRings.json"								"IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\BP_RacingRings.uasset"													VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_Rosary.json"									"IslandsofInsight\Content\ASophia\Puzzle\Rune\BP_Rosary.uasset"																			VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\BP_Rune.json"										"IslandsofInsight\Content\ASophia\Puzzle\Rosary\BP_Rune.uasset"																			VER_UE4_27 || goto :error
UAssetGUI_103.exe	fromjson "..\OutputJsons\WBP_LargeMap.json"									"IslandsofInsight\Content\ASophia\UI\HUD\Map\WBP_LargeMap.uasset"																		VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\PuzzleDatabase.json"								"IslandsofInsight\Content\ASophia\Data\PuzzleDatabase.uasset"																			VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\QuestData_Missions.json"							"IslandsofInsight\Content\ASophia\Data\QuestData_Missions.uasset"																		VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\SandboxProgressionData.json"						"IslandsofInsight\Content\ASophia\Data\SandboxProgressionData.uasset"																	VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\SandboxZones.json"									"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\SandboxZones.uasset"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\ZoneData.json"										"IslandsofInsight\Content\ASophia\Data\ZoneData.uasset"																					VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\MainMap_BetaCampaign_Batched.json"			"IslandsofInsight\Content\ASophia\Maps\MainMap_BetaCampaign_Batched.umap"																VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\RRMod_Objects.json"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RRMod_Objects.umap"																VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_CampaignObjects.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\RainForestZone\Rainforest_CampaignObjects.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_EarnYourWings_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_EarnYourWings_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_HardMatch3_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_HardMatch3_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_Match3_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_Match3_Puzzles.umap"								VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_MazePatternTutorial_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_MazePatternTutorial_Puzzles.umap"					VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Rainforest_PyramidMirrorMaze_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_PyramidMirrorMaze_Puzzles.umap"					VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\SecretSeek5Puzzles.json"						"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\SecretSeek5Puzzles.umap"										VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_CampaignObjects.json"					"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\Central_CampaignObjects.umap"									VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\CentralGridsAndCOWYC_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\CentralGridsAndCOWYC_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_BonusCOWYC_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\Central_BonusCOWYC_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_Enlightenment_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\Central_Enlightenment_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_RollingCubeTutorial_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\Central_RollingCubeTutorial_Puzzles.umap"					VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_SmallMatchbox_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\Central_SmallMatchbox_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Central_TrickoftheWanderer_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralDungeons\Central_TrickoftheWanderer_Puzzles.umap"					VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\CamphorCorridorTemple.json"					"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorCorridorTemple.umap"										VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\CamphorEntrance.json"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorEntrance.umap"												VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverland_CampaignObjects.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandEscarpmentZone\Riverland_CampaignObjects.umap"		VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverlands_Dyads_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_Dyads_Puzzles.umap"				VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverlands_EyeNeedle_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_EyeNeedle_Puzzles.umap"			VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverlands_LockpickTutorial_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_LockpickTutorial_Puzzles.umap"	VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverlands_Yajilin_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_Yajilin_Puzzles.umap"			VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Riverlands_YinYangDungeon_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_YinYangDungeon_Puzzles.umap"		VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_CampaignObjects.json"					"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\Redwood_CampaignObjects.umap"									VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_Kailasa.json"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\Redwood_Kailasa.umap"											VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_1SPIIntro_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\Redwood_1SPIIntro_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_GhostObjects_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\Redwood_GhostObjects_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_GrayNuri_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\Redwood_GrayNuri_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_MirrorSymmetry_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\Redwood_MirrorSymmetry_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Redwood_RosaryDungeon_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodDungeons\Redwood_RosaryDungeon_Puzzles.umap"						VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_CampaignObjects.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\Mountain_CampaignObjects.umap"								VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_CaveTutorial_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\Mountain_CaveTutorial_Puzzles.umap"								VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_HardKlotski_Puzzles.json"			"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\Mountain_HardKlotski_Puzzles.umap"								VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_KlotskiTutorial_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\Mountain_KlotskiTutorial_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_RacingRingsTutorial_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\Mountain_RacingRingsTutorial_Puzzles.umap"						VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Mountain_SecretCave_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\Dungeons\Mountain_SecretCave_Puzzles.umap"								VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Deluxe_Part1_Puzzles.json"					"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\Deluxe_Part1_Puzzles.umap"											VER_UE4_27 || goto :error

uassetgui.exe		fromjson "..\OutputJsons\Maps\Introduction_Island_Puzzles.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\Introduction_Island_Puzzles.umap"									VER_UE4_27 || goto :error
uassetgui.exe		fromjson "..\OutputJsons\Maps\Introduction_SecretIsland_Puzzles.json"		"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\Introduction_SecretIsland_Puzzles.umap"								VER_UE4_27 || goto :error

:: Copy asset files.
copy	"..\Assets\AncientTreasures\Materials\Masters\MM_Master_Material_01a.*"		"IslandsofInsight\Content\AncientTreasures\Materials\Masters\MM_Master_Material_01a.*"						/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Materials\Instances\MI_Chest_02a.*"				"IslandsofInsight\Content\AncientTreasures\Materials\Instances\MI_Chest_02a.*"								/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Meshes\SM_Chest_02b.*"							"IslandsofInsight\Content\AncientTreasures\Meshes\SM_Chest_02b.*"											/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Textures\Utility\TX_Fill_01a_ALB.*"				"IslandsofInsight\Content\AncientTreasures\Textures\Utility\TX_Fill_01a_ALB.*"								/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Textures\Utility\TX_Fill_01a_H.*"				"IslandsofInsight\Content\AncientTreasures\Textures\Utility\TX_Fill_01a_H.*"								/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Textures\Utility\TX_Fill_01a_NRM.*"				"IslandsofInsight\Content\AncientTreasures\Textures\Utility\TX_Fill_01a_NRM.*"								/B/Y >nul || goto :error
copy	"..\Assets\AncientTreasures\Textures\Utility\TX_Fill_01a_RMA.*"				"IslandsofInsight\Content\AncientTreasures\Textures\Utility\TX_Fill_01a_RMA.*"								/B/Y >nul || goto :error
copy	"..\Assets\Camphor\CamphorCorridorTemple_1_HLOD.*"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\CamphorCorridorTemple_1_HLOD.*"	/B/Y >nul || goto :error
copy	"..\Assets\ES_Galaxy_chestmaker.*"											"IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\ES_Galaxy.*"									/B/Y >nul || goto :error
copy	"..\Assets\MI_DesertSand.*"													"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\MI_DesertSand.*"			/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Clouds_Central.*"										"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Clouds_Central.*"											/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Dew_Point.*"												"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Dew_Point.*"													/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Island_DropShadow.*"										"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Island_DropShadow.*"											/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Map_Color.*"												"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Map_Color.*"													/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Map_OuterGlow.*"											"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Map_OuterGlow.*"												/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Map_Zones_DF.*"											"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Map_Zones_DF.*"												/B/Y >nul || goto :error
copy	"..\Assets\MapUI\T_Map_Zones_Hover.*"										"IslandsofInsight\Content\ASophia\UI\HUD\Map\T_Map_Zones_Hover.*"											/B/Y >nul || goto :error
copy	"..\Assets\WBP_ResetTutorial.*"												"IslandsofInsight\Content\ASophia\UI\NotificationsAndPopups\Tutorials\WBP_ResetTutorial.*"					/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\de-DE\Game.locres"								"IslandsofInsight\Content\Localization\Game\de-DE\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\en-CA\Game.locres"								"IslandsofInsight\Content\Localization\Game\en-CA\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\en-US\Game.locres"								"IslandsofInsight\Content\Localization\Game\en-US\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\es-ES\Game.locres"								"IslandsofInsight\Content\Localization\Game\es-ES\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\es-MX\Game.locres"								"IslandsofInsight\Content\Localization\Game\es-MX\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\fr-FR\Game.locres"								"IslandsofInsight\Content\Localization\Game\fr-FR\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\it-IT\Game.locres"								"IslandsofInsight\Content\Localization\Game\it-IT\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\ja-JP\Game.locres"								"IslandsofInsight\Content\Localization\Game\ja-JP\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\ko-KR\Game.locres"								"IslandsofInsight\Content\Localization\Game\ko-KR\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\pt-BR\Game.locres"								"IslandsofInsight\Content\Localization\Game\pt-BR\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\zh-CN\Game.locres"								"IslandsofInsight\Content\Localization\Game\zh-CN\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\Localization\Game\zh-TW\Game.locres"								"IslandsofInsight\Content\Localization\Game\zh-TW\Game.locres"												/B/Y >nul || goto :error
copy	"..\Assets\EnclaveEx.*"														"IslandsofInsight\Content\ASophia\UI\HUD\Markers\Textures\EnclaveEx.*"										/B/Y >nul || goto :error
copy	"..\Assets\ClusterEx.*"														"IslandsofInsight\Content\ASophia\UI\HUD\Markers\Textures\ClusterEx.*"										/B/Y >nul || goto :error

:: Build a pak out of uassets.
cd "Engine\Binaries\Win64"
unrealpak.exe RRMOD_PuzzleFix.pak -Create="..\..\..\IslandsofInsight\Content" /B/Y >nul || goto :error

:: Copy the pak over to the game folder for testing.
copy "RRMOD_PuzzleFix.pak" "C:\Program Files (x86)\Steam\steamapps\common\Islands of Insight\IslandsofInsight\Content\Paks\RRMOD_PuzzleFix.pak" /B/Y >nul || goto :error

:: Copy the puzzle radar settings.
cd /d %~dp0
copy "media\mod\puzzleradar.bin" "C:\Program Files (x86)\Steam\steamapps\common\Islands of Insight\IslandsofInsight\Binaries\Win64\puzzleradar.bin" /B/Y >nul || goto :error
copy "media\mod\puzzleradar.bin" "..\..\SaveStats\media\data\puzzleradar.bin" /B/Y >nul || goto :error

:: That's it.
@echo off
cd /d %~dp0
echo %ESC%[32mLooks OK!%ESC%[0m
@echo on
pause
exit

:error
@echo off
cd /d %~dp0
echo %ESC%[31mError occured - exiting.%ESC%[0m
@echo on
pause
exit
