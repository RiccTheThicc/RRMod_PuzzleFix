:: Enable ESC sequences. Some magic.
@echo off
for /F %%a in ('echo prompt $E ^| cmd') do set "ESC=%%a"

:: Run the modmaker to build the jsons.
cd /d %~dp0
"php\php.exe" modmaker.php || goto :error

"php\php.exe" camphor_main.php || goto :error
"php\php.exe" camphor_platform_moverotate.php || goto :error

:: Convert all other jsons.
"php\php.exe"	finalize_uasset.php	"..\BaseJsons\MainMap_BetaCampaign_Batched.json"	"..\OutputJsons\MainMap_BetaCampaign_Batched.json"	|| goto :error

:: Convert jsons to uassets.
cd "..\UnrealPakMini"

if not exist	"IslandsofInsight\Content\"																		mkdir "IslandsofInsight\Content\"
if not exist	"IslandsofInsight\Content\ASophia\Data\"														mkdir "IslandsofInsight\Content\ASophia\Data\"
if not exist	"IslandsofInsight\Content\ASophia\Data\Items\"													mkdir "IslandsofInsight\Content\ASophia\Data\Items\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\"													mkdir "IslandsofInsight\Content\ASophia\GameObjects\"
if not exist	"IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"								mkdir "IslandsofInsight\Content\ASophia\GameObjects\JumpPads\Effects\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\"														mkdir "IslandsofInsight\Content\ASophia\Maps\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"							mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"				mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"					mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"						mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone\"
if not exist	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"	mkdir "IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\"														mkdir "IslandsofInsight\Content\ASophia\Puzzle\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingBalls\"											mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingBalls"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"								mkdir "IslandsofInsight\Content\ASophia\Puzzle\RacingRings\Blueprints\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rosary\"												mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rosary\"
if not exist	"IslandsofInsight\Content\ASophia\Puzzle\Rune\"													mkdir "IslandsofInsight\Content\ASophia\Puzzle\Rune\"
if not exist	"IslandsofInsight\Content\AncientTreasures\Materials\Masters\"									mkdir "IslandsofInsight\Content\AncientTreasures\Materials\Masters\"
if not exist	"IslandsofInsight\Content\ASophia\UI\HUD\Map\"													mkdir "IslandsofInsight\Content\ASophia\UI\HUD\Map\"

@echo on
uassetgui.exe	fromjson "..\OutputJsons\PuzzleDatabase.json"						"IslandsofInsight\Content\ASophia\Data\PuzzleDatabase.uasset"															VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\MainMap_BetaCampaign_Batched.json"			"IslandsofInsight\Content\ASophia\Maps\MainMap_BetaCampaign_Batched.umap"												VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\CamphorCorridorTemple.json"				"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorCorridorTemple.umap"						VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\CamphorEntrance.json"						"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\CamphorEntrance.umap"								VER_UE4_27 || goto :error
uassetgui.exe	fromjson "..\OutputJsons\SandboxZones.json"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\SandboxZones.uasset"			VER_UE4_27 || goto :error

:: Copy asset files.
copy	"..\Assets\Camphor\CamphorCorridorTemple_1_HLOD.*"	"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\HLOD\CamphorCorridorTemple_1_HLOD.*"	/B/Y >nul || goto :error
copy	"..\Assets\MI_DesertSand.*"							"IslandsofInsight\Content\ASophia\Maps\MainMapSubmaps\CamphorPlaytest\Materials\MI_DesertSand.*"			/B/Y >nul || goto :error

:: Build a pak out of uassets.
cd "Engine\Binaries\Win64"
unrealpak.exe RRMOD_PuzzleFix.pak -Create="..\..\..\IslandsofInsight\Content" /B/Y >nul || goto :error

:: Copy the pak over to the game folder for testing.
copy "RRMOD_PuzzleFix.pak" "C:\Program Files (x86)\Steam\steamapps\common\Islands of Insight\IslandsofInsight\Content\Paks\RRMOD_PuzzleFix.pak" /B/Y >nul || goto :error

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
