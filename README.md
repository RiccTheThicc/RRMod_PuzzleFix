# RRMod_PuzzleFix
RRMod_PuzzleFix is a .pak mod for Islands of Insight, which is a part of the "Offline Restored Mod", a community driven bugfix and QoL patch.
For the full Offline Restored Mod, this .pak mod is packaged in conjunction with a .dll mod within the releases.
[Source code for the .dll mod](https://github.com/grechnik/islands-of-insight-fix)

The mod does not affect your save file in any way. All progress is shared between vanilla and modded game.

## Latest Release
See [here](https://github.com/RiccTheThicc/RRMod_Puzzlefix/releases) to download the latest release of the mod, and see the latest features.

## Key Features
- All puzzles now stay solved permanently.*
- All puzzles always spawn in properly and match daily counts.
- You can actually 100% the game now.
- Superjump cooldown reduced from 300 to 60 seconds.*
- Implemented automatic save file backups (upon launch + every 30 minutes).*
- Fixed double quest reward / negative spark bug.*
- Sightseer images now have higher quality (no more weird artifacts).*
- Potentially fixed the bug where most hub puzzles fail to spawn upon launch.*
- Tripled the spawnpoints for "cluster" logic grids (east of lucent fast travel, bent into shape, nearsighted).
- All flow orbs always display your all-time best.*
- All glide rings remember your actual best score.*
- Enabled more rare skydrop shapes.
- All Verdant/Lucent/Autumn/Shady puzzles are now mutually compatible.
- Fixed Lucent Waters Movement track awarding a lore fragment twice.
- Fixed zone icons for Shady Wildwoods and Serene Deluge (those tiny icons in hub tracks, they were swapped all along).
- Slightly tweaked positions of numerous puzzles to better fit the environment.
- Fixed the positions of all floor slabs.
- Rotated some Autumn slabs to reduce camera/tree clipping.
- Slightly expanded 3 floor slabs to allow for larger mazes in some cases.
- Moved a wall slab that had a tree branch stuck in it (Serene, at The Highest Viewpoint).
- Boosted a jumppad in Shady (at Divide by Two) by ~4% so that you can actually reach the top.
- Restored 50+ broken sentinel stone puzzles.
- Adjusted glide ring 10410 (last ring lowered down a tiny bit, believed to be an error).
- Fixed two partially broken mazes (#25027, #25037).
- Fixed several really bad Light Motifs drawn on leaves (#5859 #5840 Autumn).
- Fixed several nearly unobtainable Shy Auras (#8061 Shady, #17035 #16943 Serene).
- Fixed a Wandering Echo that couldn't reset properly (#12233 Verdant).
- Fixed a Hidden Ring stuck in a rock (#15386 Shady).
- Fixed two logic grids having multiple solutions (#25122 Cong cluster, #184 Lucent cluster).
- Fixed a particular music grid having a silent instrument (#18700 Serene).
- Added a small indicator for when the mod is properly installed*: [Example of indicator within the game's pause menu](https://github.com/user-attachments/assets/475d8ce6-7c02-43a0-a267-2950d7b20d72)
- Wanderer's Quests (automatic ones) can now be disabled (see dxgi.ini). This is an optional feature not used by default.*
- Implemented the Show Logic Grids feature (see dxgi.ini) that adds a marker to the nearest unsolved Logic Grid. This is an optional feature not used by default.*

*Feature implemented in .dll mod. Some .dll mod features can be disabled/adjusted in dxgi.ini

## Known issues
Daily quests, farming sparks, and XP levelling don't fit well with perma-solvable puzzles - there's no easy way to have both.
We recommend focusing on just solving unique puzzles and enjoying the actual content.

If you still would like to grind for all cosmetics and masteries, you can disable this feature by setting SolvedStaySolved to 0 within the dxgi.ini file.
This re-enables vanilla behavior of puzzles not staying solved past the current game session, but keeps all other mod's fixes (with the exception of Flow Orbs times, see below).
Your progress will not be lost with the feature disabled, and you can re-enable it by setting SolvedStaySolved to 1 at any time.

Other minor issues the mod doesn't fix:
- Some puzzles still belong to the wrong zone (e.g. a particular verdant glide ring located in autumn).
- With SolvedStaySolved disabled, Flow Orbs overwrite your historical best upon re-solving them.
- Skydrop speed challenge overwrites your historical best with current run (modded and vanilla both).
- Superjumps can still fail occasionally.

## Credits
The great majority of the work on this .pak mod, especially so after the initial release, was done by Rushin', along with some help from myself.
The accompanied .dll mod, which made much of what the Offline Restored Mod does possible, was made by Meltdown.

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
Once the assets are converted to .json files, we make the "Base jsons". These are the files that the ModMaker program will work from.
Any changes from the vanilla jsons to the base jsons are relatively small in quantity or would be tedious to code (like addition or removal of objects) and are done by hand.
Some examples of this are adding or changing the zone affiliation of puzzle containers.

After all non-programatic changes are done, we run the .jsons through the uasset_minify.php tool inside ModMaker.
This tool drastically improves readablility, decreases file size, and cleans up the binary data used by the puzzles within the game, while keeping the uasset file UAssetGUI generates from the .json exactly the same.

The majority of the changes are then added to the jsons programmatically by our ModMaker tool.
"ModMaker/modmaker.bat" is run to convert the "Base jsons" into the "Output jsons". Currently only two files are edited by this program, PuzzleDatabase.json and SandboxZones.json.
These two files are quite large (even after minification), which makes their information difficult to parse, so the program also produces readable versions of them split into multiple smaller files.
These readable text files are produced for both the .jsons input to the ModMaker, and the output .jsons produced by the ModMaker.

### Building the pak
I'm currently working on a tool to streamline this process, so you can just run an .exe file to convert from .json to .pak directly, but for now only an explanation for how to compile the source is provided:

To take the source code in this repository and create a pak mod, you first need to transform the jsons back into Unreal Engine uasset files.
To do this, simple open the .json within UAssetGUI and save the file.

We can then repack the .Json files using [UnrealPak](https://github.com/allcoolthingsatoneplace/UnrealPakTool).
The asset files retain the folder structure they had when they were packed, and these locations are how the game references the assets themselves. The exact location is determined relative to the directory that the packing took place.
In order to have our .pak replace an asset file in the game's pak on load, we need to make sure it's location is exactly the same as the one within the vanilla file.

Location of each file within the IslandsOfInsight folder:
- **PuzzleDatabase, Zone_Data, SandboxProgressionData:** "Content\ASophia\Data\"
- **SandboxZones:** "Content\ASophia\Maps\MainMapSubmaps\RiverlandEscarpment\RiverlandPuzzles\"
- **Central_CampaignObjects:** "Content\ASophia\Maps\MainMapSubmaps\Central\CentralZone\"
- **Redwood_Kailasa**: "Content\ASophia\Maps\MainMapSubmaps\Redwood\RedwoodZone"
- **BP_Rosary**: "Content\ASophia\Puzzle\Rosary"


For the version of UE Islands Of Insight was developed with, the packing itself takes place in the Engine/Binaries/Win64 folder within the project root. This results in assets having locations like "..\\..\\..\\IslandsofInsight\\Content\\...", so we pack the IslandsofInsight folder with our new assets from a folder with the same relative location. i.e. The UnrealPak.exe file used for packing is inside "Engine/Binaries/Win64" and the "IslandsofInsight" folder is in the same directory as the "Engine" folder.

The cli command (run within Engine/Binaries/Win64) ends up looking like this:
>./unrealpak RRMod_PuzzleFix.pak -Create="..\..\..\IslandsofInsight\"

Note: The name of the .pak also matters. Paks are loaded in alphabetical order, so since "RRMod..." comes after "pakchunk..." our mod replaces the assets in the vanilla files when the game loads them. There are ways to force load priority outside of alphabetical order, but that is not relevant here.

