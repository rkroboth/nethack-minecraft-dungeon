# nethack-minecraft-dungeon

A php script to create a nethack-like dungeon in minecraft, so that my kids can play a nethack dungeon inside Minecraft, allowing me to re-live my Nethack glory days through them.

To run the script:

php make_nethack_minecraft_dungeon.php

The script creates a text file full of minecraft commands like /setblock, /fill, /spawn etc etc. This text file can then be used as a Minecraft function, as described here:

https://minecraft.gamepedia.com/Function


Edit the script to change configuration parameters.  The output textfile is configured in the script, by default is "output.txt".


The creation of the dungeon level is abstracted out into a separate class named "level" in case anybody wants to tinker with it.

