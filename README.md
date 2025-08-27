# RRMod_PuzzleFix
RRMod_PuzzleFix is a .pak mod for Islands of Insight, which is a part of the "Offline Restored Mod", a community driven bugfix and QoL patch.
For the full Offline Restored Mod, this .pak mod is packaged in conjunction with a .dll mod within the releases.
[Source code for the .dll mod](https://github.com/grechnik/islands-of-insight-fix)

Your progress is shared between vanilla and modded game - you can upgrade to using this mod at any time.

## Latest Release
See [here](https://github.com/RiccTheThicc/RRMod_Puzzlefix/releases) to download the latest release of the mod, and see the latest features.

## Key Features
- Almost 500 previously unused puzzles returned to the game, including an extra enclave.
- All daily puzzles now stay solved permanently (like in enclaves).
- All daily puzzles spawn in properly and match daily counts.
- Fixed hundreds of broken/glitched puzzles around the world.
- Puzzle Radar feature: a late-game powerup to help find your last remaining puzzles.
- Automatic save file backups and reduced SSD wear.
- Superjump cooldown: 300 -> 60 seconds.
- Shady Wildwoods Match3 Challenge: 120 -> 180 seconds.
- Clusters spawn more grids and prioritize offering unsolved ones first.
- Flow Orbs, Glide Rings, Skydrop Challenge best times/scores now stick and will not be lost.
- Procedural Skydrops can sometimes spawn in different, interesting shapes.
- Dozens of other bugfixes and QoL improvements to revive this abandoned game.

Note: some of these and many other options can be toggled/adjusted in dxgi.ini.

## Known issues
This mod fixes a major gripe with the game where daily puzzles act as pseudo-infinite content. Instead, daily puzzles remain solved permanently. But this does not bode well with daily quests, sparks, XP levelling as they rely on fake-infinite puzzles.

We recommend focusing on just solving unique puzzles and enjoying the actual content; however, you can still switch back to vanilla behavior by setting SolvedStaySolved=0 in dxgi.ini config. You can grind the same puzzles for sparks/masteries, and none of your progress will be lost.

## Credits
Offline Restored Mod is created by Rushin, RiccTheThicc, and meltdown.

Additional work and support: Krapht, r0màin22222, Wicko, TheHelvetHawk, DoctorXOR, Lysine, semiexp, N-Dorfyn, kcbrad, brolette, randomflyingtaco, Lava, Enzonaki, s7eph4n, Haxton, Hizuriya, Molter, Aexis, Mortimo.

The mod would be far from what it is currently without the amazing community around Islands of Insight.
[Join the Discord!](https://discord.gg/xbC4v3SJHQ)

## Basic Methodology
RRMod_PuzzleFix is a "Pak" mod, meaning it is a modification of the .pak files found in Unreal Engine games. The mod does not alter the vanilla files on your disk in any way.

Pak files are just a compressed version of the game's asset files (.uasset, .uexp, and .umap) that Unreal Engine uses to load textures, meshes, blueprints, and other data into the game.
The .json files in this project are just easily readable/editable versions of these asset files, converted to .json files via the [UAssetGUI tool](https://github.com/atenfyr/UAssetGUI).

All the mod is doing is loading altered asset files into the game *after* the vanilla assets are loaded, which overwrites the vanilla functionality with the one we want. This is how the vanilla game files are able to remain unaffected.

### Getting the jsons
In order to mod the .pak files, we need to unpack them first to get at the asset files within.
I've created another repository to store all of the vanilla game's unpacked asset files, so you never have to go through the trouble of doing so yourself.
See: [IslandsOfInsight-Unpacked-Assets](https://github.com/RiccTheThicc/IslandsOfInsight-Unpacked-Assets)
In case you would like to follow our process from scratch, that repo also contains information about how you can unpack the files yourself.

Once we have the asset files, we can edit them directly with [UAssetGUI](https://github.com/atenfyr/UAssetGUI), but for some files we need to create bulk edits that would be difficult to do by hand, so we convert them to .json files.
To do this, open the .uasset/.umap file in UAssetGUI with File -> Open, and then save the file as a .json with File -> Save As. When saving/opening the file as a .json UAssetGUI takes care of the conversion for you.

### Alterations
The .json files exported with UAssetGUI are first processed with the uasset_minify.php tool inside ModMaker. It drastically improves readability and decreases the file size of such files while maintaining backward compatibility (i.e. they can be imported right back to UAssetGUI). Such files then reside in the BaseJsons folder.

Some changes to the .json assets are done by hand and in-place. Others are performed via ModMaker capabilities. The results go to the OutputJsons folder.

The folders BaseReadable and OutputReadable are largely deprecated now. They contain a non-backward-compatible, human-friendly way of learning the structure of the game's database of puzzles and spawnpoints. It is normally split between two gigantic files that are very hard to work with, especially early on.

### Building the pak
The mod essentially builds itself via the "ModMaker/makemod.bat" script. Its job is to convert BaseJsons to OutputJsons, then OutputJsons to actual asset files residing in their expected folders, and finally create a .pak file out of them. In this repository the script was already executed and all the generated files can be found in UnrealPakMini/IslandsofInsight folder.

The .pak files are made using [UnrealPak](https://github.com/allcoolthingsatoneplace/UnrealPakTool).
For the version of UE Islands Of Insight was developed with, the packing itself takes place in the Engine/Binaries/Win64 folder within the project root. This results in assets having locations like "..\\..\\..\\IslandsofInsight\\Content\\...", so we pack the IslandsofInsight folder with our new assets from a folder with the same relative location. i.e. The UnrealPak.exe file used for packing is inside "Engine/Binaries/Win64" and the "IslandsofInsight" folder is in the same directory as the "Engine" folder.

The cli command (run within Engine/Binaries/Win64) ends up looking like this:
>./unrealpak RRMod_PuzzleFix.pak -Create="..\..\..\IslandsofInsight\"

Note: The name of the .pak also matters. Paks are loaded in alphabetical order, so since "RRMod..." comes after "pakchunk..." our mod replaces the assets in the vanilla files when the game loads them. There are ways to force load priority outside of alphabetical order, but that is not relevant here.
