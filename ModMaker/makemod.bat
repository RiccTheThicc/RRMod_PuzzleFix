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

:: Convert all other jsons.
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_ItemPickupChest.json"						"..\OutputJsons\BP_ItemPickupChest.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_MatchboxRadar.json"						"..\OutputJsons\BP_MatchboxRadar.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_RacingBalls.json"							"..\OutputJsons\BP_RacingBalls.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_RacingRings.json"							"..\OutputJsons\BP_RacingRings.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_Rosary.json"								"..\OutputJsons\BP_Rosary.json"								|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\BP_Rune.json"									"..\OutputJsons\BP_Rune.json"								|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Central_CampaignObjects.json"					"..\OutputJsons\Central_CampaignObjects.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Deluxe_Part1_Puzzles.json"					"..\OutputJsons\Deluxe_Part1_Puzzles.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Introduction_SecretIsland_Puzzles.json"		"..\OutputJsons\Introduction_SecretIsland_Puzzles.json"		|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\MainMap_BetaCampaign_Batched.json"			"..\OutputJsons\MainMap_BetaCampaign_Batched.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Mountain_CampaignObjects.json"				"..\OutputJsons\Mountain_CampaignObjects.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Riverlands_EyeNeedle_Puzzles.json"			"..\OutputJsons\Riverlands_EyeNeedle_Puzzles.json"			|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\QuestData_Missions.json"						"..\OutputJsons\QuestData_Missions.json"					|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\RRMod_Objects.json"							"..\OutputJsons\RRMod_Objects.json"							|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Rainforest_PyramidMirrorMaze_Puzzles.json"	"..\OutputJsons\Rainforest_PyramidMirrorMaze_Puzzles.json"	|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\Redwood_Kailasa.json"							"..\OutputJsons\Redwood_Kailasa.json"						|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\SandboxProgressionData.json"					"..\OutputJsons\SandboxProgressionData.json"				|| goto :error
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\ZoneData.json"								"..\OutputJsons\ZoneData.json"								|| goto :error

"php\php.exe" analyzer.php || goto :error

:: Convert jsons to uassets.
cd "..\UnrealPakMini"

if not exist	"IslandsofInsight\Content\"																		mkdir "IslandsofInsight\Content\"
if not exist	"IslandsofInsight\Content\AncientTreasures"														mkdir "IslandsofInsight\Content\AncientTreasures"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials"											mkdir "IslandsofInsight\Content\AncientTreasures\Materials"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials\Masters"									mkdir "IslandsofInsight\Content\AncientTreasures\Materials\Masters"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials\Instances"									mkdir "IslandsofInsight\Content\AncientTreasures\Materials\Instances"
if not exist	"IslandsofInsight\Content\AncientTreasures\Meshes"												mkdir "IslandsofInsight\Content\AncientTreasures\Meshes"
if not exist	"IslandsofInsight\Content\AncientTreasures\Textures"											mkdir "IslandsofInsight\Content\AncientTreasures\Textures"
if not exist	"IslandsofInsight\Content\AncientTreasures\Textures\Utility"									mkdir "IslandsofInsight\Content\AncientTreasures\Textures\Utility"
if not exist	"IslandsofInsight\Content\ASophia\Data\"														mkdir "IslandsofInsight\Content\ASophia\Data\"
if not exist	"IslandsofInsight\Content\ASophia\Data\Items\"													mkdir "IslandsofInsight\Content\ASophia\Data\Items\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\"													mkdir "IslandsofInsight\Content\ASophia\GameObjects\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"								mkdir "IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\"														mkdir "IslandsofInsight\Content\ASophia\Maps\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\"											mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"					mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"				mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\"					mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"	mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\"	mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\"														mkdir "IslandsofInsight\Content\ASophia\Puzzle\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingBalls\"											mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingBalls"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"								mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rosary\"												mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rosary\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rune\"													mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rune\"
if not exist	"IslandsofInsight\Content\ASophia\UI\HUD\Map\"													mkdir "IslandsofInsight\Content\ASophia\UI\HUD\Map\"
if not exist	"IslandsofInsight\Content\ASophia\UI\NotificationsAndPopups\Tutorials\"							mkdir "IslandsofInsight\Content\ASophia\UI\NotificationsAndPopups\Tutorials\"
if not exist	"IslandsofInsight\Content\Localization\Game\de-DE\"												mkdir "IslandsofInsight\Content\Localization\Game\de-DE\"
if not exist	"IslandsofInsight\Content\Localization\Game\en-CA\"												mkdir "IslandsofInsight\Content\Localization\Game\en-CA\"
if not exist	"IslandsofInsight\Content\Localization\Game\en-US\"												mkdir "IslandsofInsight\Content\Localization\Game\en-US\"
if not exist	"IslandsofInsight\Content\Localization\Game\es-ES\"												mkdir "IslandsofInsight\Content\Localization\Game\es-ES\"
if not exist	"IslandsofInsight\Content\Localization\Game\es-MX\"												mkdir "IslandsofInsight\Content\Localization\Game\es-MX\"
if not exist	"IslandsofInsight\Content\Localization\Game\fr-FR\"												mkdir "IslandsofInsight\Content\Localization\Game\fr-FR\"
if not exist	"IslandsofInsight\Content\Localization\Game\it-IT\"												mkdir "IslandsofInsight\Content\Localization\Game\it-IT\"
if not exist	"IslandsofInsight\Content\Localization\Game\ja-JP\"												mkdir "IslandsofInsight\Content\Localization\Game\ja-JP\"
if not exist	"IslandsofInsight\Content\Localization\Game\ko-KR\"												mkdir "IslandsofInsight\Content\Localization\Game\ko-KR\"
if not exist	"IslandsofInsight\Content\Localization\Game\pt-BR\"												mkdir "IslandsofInsight\Content\Localization\Game\pt-BR\"
if not exist	"IslandsofInsight\Content\Localization\Game\zh-CN\"												mkdir "IslandsofInsight\Content\Localization\Game\zh-CN\"
if not exist	"IslandsofInsight\Content\Localization\Game\zh-TW\"												mkdir "IslandsofInsight\Content\Localization\Game\zh-TW\"

@echo on
uassetgui.exe	fromjson "..\OutputJsons\PuzzleDatabase.json"						"IslandsofInsight\Content\ASophia\Data\PuzzleDatabase.uasset"															VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\SandboxProgressionData.json"				"IslandsofInsight\Content\ASophia\Data\SandboxProgressionData.uasset"													VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\QuestData_Missions.json"					"IslandsofInsight\Content\ASophia\Data\QuestData_Missions.uasset"														VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\ZoneData.json"								"IslandsofInsight\Content\ASophia\Data\ZoneData.uasset"																	VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_MatchboxRadar.json"						"IslandsofInsight\Content\ASophia\Data\Items\BP_MatchboxRadar.uasset"													VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_ItemPickupChest.json"					"IslandsofInsight\Content\ASophia\GameObjects\BP_ItemPickupChest.uasset"												VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\MainMap_BetaCampaign_Batched.json"			"IslandsofInsight\Content\ASophia\Maps\MainMap_BetaCampaign_Batched.umap"												VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\CamphorCorridorTemple.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorCorridorTemple.umap"						VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\CamphorEntrance.json"						"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorEntrance.umap"								VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Central_CampaignObjects.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\Central_CampaignObjects.umap"					VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Deluxe_Part1_Puzzles.json"					"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\DeluxeEdition\Deluxe_Part1_Puzzles.umap"							VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Introduction_SecretIsland_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Introduction\Introduction_SecretIsland_Puzzles.umap"				VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Mountain_CampaignObjects.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Mountain\MountainZone\Mountain_CampaignObjects.umap"				VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Riverlands_EyeNeedle_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandDungeons\Riverlands_EyeNeedle_Puzzles.umap"	VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Rainforest_PyramidMirrorMaze_Puzzles.json"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RainForest\Dungeons\Rainforest_PyramidMirrorMaze_Puzzles.umap"	VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\Redwood_Kailasa.json"						"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\Redwood_Kailasa.umap"							VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\RRMod_Objects.json"						"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RRMod_Objects.umap"												VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\SandboxZones.json"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\SandboxZones.uasset"			VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_RacingBalls.json"						"IslandsofInsight\Content\ASophia\Puzzle\RacingBalls\BP_RacingBalls.uasset"												VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_RacingRings.json"						"IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\BP_RacingRings.uasset"									VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_Rune.json"								"IslandsofInsight\Content\ASophia\Puzzle\Rosary\BP_Rune.uasset"															VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\BP_Rosary.json"							"IslandsofInsight\Content\ASophia\Puzzle\Rune\BP_Rosary.uasset"															VER_UE4_27 || goto :error

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
