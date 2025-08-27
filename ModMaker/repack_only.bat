:: Enable ESC sequences. Some magic.
@echo off
for /F %%a in ('echo prompt $E ^| cmd') do set "ESC=%%a"

:: Run the modmaker to build the jsons.
cd /d %~dp0

cd /d %~dp0
cd "..\UnrealPakMini"
@echo on
cd "Engine\Binaries\Win64"
unrealpak.exe RRMOD_PuzzleFix.pak -Create="..\..\..\IslandsofInsight\Content" || goto :error

:: Copy the pak over to the game folder for testing.
copy "RRMOD_PuzzleFix.pak" "C:\Program Files (x86)\Steam\steamapps\common\Islands of Insight\IslandsofInsight\Content\Paks\RRMOD_PuzzleFix.pak" /B/Y || goto :error

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