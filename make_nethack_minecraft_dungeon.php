<?php

ini_set('xdebug.max_nesting_level', 2000);


$mcm = new minecraft_dungeon_maker();

$num_levels = 20;
$rooms_per_level = 4;
$room_height = 4;
$size = 50;
$x_size = $size;
$y_size = $size;

$output_file = "output.txt";
$commands = $mcm->create_dungeon(
    $num_levels,
    $rooms_per_level,
    $room_height,
    $x_size,
    $y_size,
    $output_file
);
print "wrote " . count($commands) . " commands\n";

exit;

class minecraft_dungeon_maker{

    private $cmds;

    private $x_offset = 10;
    private $y_offset = 10;

    private function get_relative_z($z){

        // relative to player
//        return "~" . $z;

        $z_offset = 2;
        return "" . ($z + $z_offset);
    }

    public function create_dungeon(
        $num_levels,
        $rooms_per_level,
        $room_height,
        $x_size,
        $y_size,
        $output_file = "output.txt"
    ){

        $this->cmds = array();

        $min_room_size = 8;
        $max_room_size = 18;

        $stair_room_x = 20;
        $stair_room_y = 20;
        $stair_room_xs = 10;
        $stair_room_ys = 10;
        $up_stair_position = null;


        // generate walls of dungeon
        $dungeon_floor = 0;
        $level_height = $room_height + 4;
        $dungeon_height = $level_height * $num_levels;
        $dungeon_ceiling = $dungeon_height - 1;

        //south
        $this->fill(-1, $dungeon_floor, -1, $x_size + 1, $dungeon_ceiling, -1, "mossy_cobblestone");
        //north
        $this->fill(-1, $dungeon_floor, $y_size + 1, $x_size + 1, $dungeon_ceiling, $y_size + 1, "mossy_cobblestone");
        // west
        $this->fill(-1, $dungeon_floor, -1, -1, $dungeon_ceiling, $y_size + 1, "mossy_cobblestone");
        // east
        $this->fill($x_size + 1, $dungeon_floor, -1, $x_size + 1, $dungeon_ceiling, $y_size + 1, "mossy_cobblestone");

        // stairs, start at top, work down
        $this->place_dungeon_exterior_stairs($x_size, $y_size, $dungeon_ceiling, $dungeon_floor);

        // entry point
        $this->place_sign(
            $stair_room_x - 5,
            $dungeon_ceiling + 1,
            $stair_room_y - 5,
            6,
            array(
                "text" => "Welcome to",
            ),
            array(
                "text" => "Dad's Diamond",
                "color" => "dark_aqua",
            ),
            array(
                "text" => "Dungeon",
                "color" => "dark_aqua",
            )
        );

        $this->place_sign(
            $stair_room_x - 1,
            $dungeon_ceiling + 1,
            $stair_room_y - 1,
            6,
            array(
                "text" => "Many Enter,",
                "color" => "red",
            ),
            array(
                "text" => "Few Leave",
                "color" => "red",
            ),
            array(
                "text" => "ALIVE!",
                "color" => "red",
            )
        );

        for ($level_depth = 1; $level_depth <= $num_levels; $level_depth++){

            print "working on generating level $level_depth\n";

            $level = level::generate_level(
                $x_size,
                $y_size,
                $stair_room_x,
                $stair_room_y,
                $stair_room_xs,
                $stair_room_ys,
                $rooms_per_level,
                $min_room_size,
                $max_room_size
            );

            $level->print_level();

            print "working on minecraft command for level $level_depth\n";

            list ($stair_room, $down_stair_position) = $this->place_level(
                $level,
                $num_levels,
                $level_depth,
                $up_stair_position,
                $room_height
            );

            if ($stair_room){
                $stair_room_x = $stair_room->x1;
                $stair_room_y = $stair_room->y1;
                $stair_room_xs = $stair_room->x2 - $stair_room->x1 + 1;
                $stair_room_ys = $stair_room->y2 - $stair_room->y1 + 1;
                $up_stair_position = $down_stair_position;
            }

        }

        if ($output_file){
            file_put_contents($output_file, implode("\n", $this->cmds));
        }
        return $this->cmds;

    }

    private function place_dungeon_exterior_stairs($x_size, $y_size, $dungeon_ceiling, $dungeon_floor){

        $stair_height = $dungeon_ceiling;
        $y_offset = 1;
        $x_offset = 1;
        $last_step_was_broken = false;
        $broken_step_chance = 10;

        // 1 thru 100, higher is steeper
        $slope = 20;

        while(true){
            $y = 0 - $y_offset - 1;
            for ($x = 0 - $x_offset; $x <= $x_size + $x_offset + 1; $x++){
                if ($stair_height <= $dungeon_floor){
                    return;
                }
                $fill_to = $stair_height;
                if (!$last_step_was_broken && $stair_height > $dungeon_floor + 30 && rand(1,100) < $broken_step_chance){
                    $last_step_was_broken = true;
                    $fill_to = $stair_height - rand(10, 30);
                }
                else {
                    $last_step_was_broken = false;
                }
                $this->fill($x, $fill_to, $y, $x, $dungeon_floor, $y, "mossy_cobblestone");
                if (!$last_step_was_broken && rand(1, 100) < $slope){
                    $stair_height--;
                }
            }
            $x = $x_size + $x_offset + 1;
            for ($y = 0 - $y_offset; $y <= $y_size + $y_offset + 1; $y++){
                if ($stair_height <= $dungeon_floor){
                    return;
                }
                $fill_to = $stair_height;
                if (!$last_step_was_broken && $stair_height > $dungeon_floor + 30 && rand(1,100) < $broken_step_chance){
                    $last_step_was_broken = true;
                    $fill_to = $stair_height - rand(10, 30);
                }
                else {
                    $last_step_was_broken = false;
                }
                $this->fill($x, $fill_to, $y, $x, $dungeon_floor, $y, "mossy_cobblestone");
                if (!$last_step_was_broken && rand(1, 100) < $slope){
                    $stair_height--;
                }
            }
            $y = $y_size + $y_offset + 1;
            for ($x = $x_size + $x_offset; $x >= 0 - $x_offset - 1; $x--){
                if ($stair_height <= $dungeon_floor){
                    return;
                }
                $fill_to = $stair_height;
                if (!$last_step_was_broken && $stair_height > $dungeon_floor + 30 && rand(1,100) < $broken_step_chance){
                    $last_step_was_broken = true;
                    $fill_to = $stair_height - rand(10, 30);
                }
                else {
                    $last_step_was_broken = false;
                }
                $this->fill($x, $fill_to, $y, $x, $dungeon_floor, $y, "mossy_cobblestone");
                if (!$last_step_was_broken && rand(1, 100) < $slope){
                    $stair_height--;
                }
            }
            $x = 0 - $x_offset - 1;
            for ($y = $y_size + $y_offset; $y >= 0 - $y_offset - 1; $y--){
                if ($stair_height <= $dungeon_floor){
                    return;
                }
                $fill_to = $stair_height;
                if (!$last_step_was_broken && $stair_height > $dungeon_floor + 30 && rand(1,100) < $broken_step_chance){
                    $last_step_was_broken = true;
                    $fill_to = $stair_height - rand(10, 30);
                }
                else {
                    $last_step_was_broken = false;
                }
                $this->fill($x, $fill_to, $y, $x, $dungeon_floor, $y, "mossy_cobblestone");
                if (!$last_step_was_broken && rand(1, 100) < $slope){
                    $stair_height--;
                }
            }
            $x_offset++;
            $y_offset++;
        }

    }

    private static $room_types = array(

        // standard old room
        array(
            "wall" => "concrete 1",
            "floor" => "planks 5",
            "ceiling" => "planks 5",
            "torches" => true,
            "chest" => true,
        ),

        // standard old room with mob
        array(
            "wall" => "concrete 1",
            "floor" => "planks 5",
            "ceiling" => "planks 5",
            "torches" => true,
            "chest" => true,
            "mob" => true,
        ),

        // saloon
        array(
            "wall" => "brick_block",
            "floor" => "planks 2",
            "ceiling" => "concrete 7", // grey
            "torches" => true,
            "chest" => true,
        ),

        // saloon with mob
        array(
            "wall" => "brick_block",
            "floor" => "planks 2",
            "ceiling" => "concrete 7", // grey
            "torches" => true,
            "chest" => true,
            "mob" => true,
        ),

        // fancy
        array(
            "wall" => "quartz_block",
            "floor" => "quartz_block",
            "ceiling" => "glowstone",
            "torches" => false,
        ),

        // fancy with mob
        array(
            "wall" => "quartz_block",
            "floor" => "quartz_block",
            "ceiling" => "glowstone",
            "torches" => false,
            "mob" => true,
        ),

        // library
        array(
            "wall" => "bookshelf",
            "floor" => "bookshelf",
            "ceiling" => "bookshelf",
            "torches" => true,
            "chest" => true,
        ),

        // library with librarian
        array(
            "wall" => "bookshelf",
            "floor" => "bookshelf",
            "ceiling" => "bookshelf",
            "torches" => true,
            "chest" => true,
            "mob" => true,
        ),

        // mob spawning room
        array(
            "wall" => "mossy_cobblestone",
            "floor" => "mossy_cobblestone",
            "ceiling" => "mossy_cobblestone",
            "torches" => false,
            "mob_spawner" => true,
        ),

        // mob spawning room with chest
        array(
            "wall" => "mossy_cobblestone",
            "floor" => "mossy_cobblestone",
            "ceiling" => "mossy_cobblestone",
            "torches" => false,
            "chest" => true,
            "mob_spawner" => true,
        ),
    );

    public function place_level($level, $num_levels, $level_depth, $up_stair_position = null, $room_height = 3){

        if (!$up_stair_position){
            $up_stair_position = $this->get_random_stair_position($level->rooms[0]);
        };

        if ($room_height < 3){
            print "min room height is 3\n";
            exit;
        }

        // the more percent complete, the harder the dungeon
        $percent_complete = round($level_depth * 100 / $num_levels);

        $tunnel_height = 2;
        $level_height = $room_height + 4;

        // for testing, create space between levels
//        $level_floor = ($num_levels - $level_depth) * ($level_height + 2);

        $level_floor = ($num_levels - $level_depth) * $level_height;
        $floor = $level_floor + 2;
        $tunnel_ceiling = $floor + $tunnel_height - 1;
        $level_ceiling = $level_floor + $level_height - 1;
        $room_ceiling = $floor + $room_height  - 1;

        // the level includes:
        // 0 ($level_floor) => bedrock bottom border of level
        // 1 => bedrock which can be replaced by nicer flooring
        // 2 ($floor) => bottom blocks (air) of rooms and tunnels (this is $floor)
        // 2 thru 4 => room space
        // 4 ($room_ceiling) => top blocks (air) of rooms
        // 5 => bedrock which can be replaced by nicer ceilings
        // 6 => bedrock top border of level
        $this->fill(0, $level_floor, 0, $level->xs, $level_ceiling, $level->ys, "bedrock");

        // make horizontal tunnels
        for ($y = 0; $y < $level->ys; $y++){
            $start_x = null;
            for ($x = 0; $x < $level->xs; $x++){
                if ($level->grid[$x][$y] == level::TUNNEL){
                    if ($start_x === null){
                        $start_x = $x;
                    }
                }
                else {
                    if ($start_x && $x - 1 > $start_x){
                        $this->fill($start_x, $floor, $y, $x - 1, $tunnel_ceiling, $y, "air");
                    }
                    $start_x = null;
                }
            }
        }
        // make vertical tunnels
        for ($x = 0; $x < $level->xs; $x++){
            $start_y = null;
            for ($y = 0; $y < $level->ys; $y++){
                if ($level->grid[$x][$y] == level::TUNNEL){
                    if ($start_y === null){
                        $start_y = $y;
                    }
                }
                else {
                    if ($start_y && $y - 1 > $start_y){
                        $this->fill($x, $floor, $start_y, $x, $tunnel_ceiling, $y - 1, "air");
                    }
                    $start_y = null;
                }
            }
        }

        // place torches throughout dungeon
        $percent_torches = (25 / 100) * (100 - $percent_complete);
        $num_tunnel_blocks = 0;
        $torch_eligible_tunnel_blocks = array();
        for ($x = 0; $x < $level->xs; $x++){
            for ($y = 0; $y < $level->ys; $y++){
                if ($level->grid[$x][$y] == level::TUNNEL){
                    $num_tunnel_blocks++;
                    if (
                        $level->grid[$x-1][$y] == level::BEDROCK
                        || $level->grid[$x][$y-1] == level::BEDROCK
                        || $level->grid[$x+1][$y] == level::BEDROCK
                        || $level->grid[$x][$y+1] == level::BEDROCK
                    ){
                        $torch_eligible_tunnel_blocks[] = array($x, $y);
                    }
                }
            }
        }

        $num_torches_needed = round($num_tunnel_blocks * ($percent_torches / 100));
        shuffle($torch_eligible_tunnel_blocks);
        for ($i = 0; $i < $num_torches_needed; $i++){
            list($x, $y) = $torch_eligible_tunnel_blocks[$i];
            $directions = array();
            if ($level->grid[$x-1][$y] == level::BEDROCK){
                $directions[] = "west";
            }
            if ($level->grid[$x+1][$y] == level::BEDROCK){
                $directions[] = "east";
            }
            if ($level->grid[$x][$y-1] == level::BEDROCK){
                $directions[] = "south";
            }
            if ($level->grid[$x][$y+1] == level::BEDROCK){
                $directions[] = "north";
            }
            $direction = $directions[rand(0, count($directions) - 1)];
            $this->place_torch($x, $floor + 1, $y, $direction);
        }

        // make rooms
        foreach ($level->rooms as $room_num => $room){

            $room_details = self::$room_types[rand(0, count(self::$room_types) - 1)];

            if ($level_depth == $num_levels && $room_num == count($level->rooms) - 1){
                // this is the last room at the bottom level -- the diamond room
                $room_details = array(
                    "wall" => "diamond_ore",
                    "floor" => "diamond_ore",
                    "ceiling" => "diamond_ore",
                    "torches" => true,
                    "diamond_chest" => true,
                );
            }

            foreach (array(
                         "torches",
                         "chest",
                         "diamond_chest",
                         "mob_spawner",
                         "mob",
                     ) as $prop){
                if (!isset($room_details[$prop])){
                    $room_details[$prop] = false;
                }
            }

            // outside walls
            if ($room_details['wall']){
                $this->fill($room->x1 + 1, $floor, $room->y1 + 1, $room->x2 - 1, $room_ceiling, $room->y2 - 1, $room_details['wall']);
            }
            $this->fill($room->x1 + 2, $floor, $room->y1 + 2, $room->x2 - 2, $room_ceiling, $room->y2 - 2, "air");

            // floor
            if ($room_details['floor']){
                $this->fill($room->x1 + 2, $floor - 1, $room->y1 + 2, $room->x2 - 2, $floor - 1, $room->y2 - 2, $room_details['floor']);
            }

            // ceiling
            if ($room_details['ceiling']){
                $this->fill($room->x1 + 2, $room_ceiling + 1, $room->y1 + 2, $room->x2 - 2, $room_ceiling + 1, $room->y2 - 2, $room_details['ceiling']);
            }


            // north/south wall doors
            foreach (array($room->y2 - 1, $room->y1 + 1) as $y){
                for ($x = $room->x1 + 2; $x <= $room->x2 - 2; $x++){
                    if (
                        $level->is_open_space($x, $y - 1)
                        && $level->is_open_space($x, $y + 1)
                    ){
                        $hinge_side = "right";
                        if ($y == $room->y2 - 1){
                            $hinge_side = "left";
                        }

                        $this->place_door($x, $floor, $y, "vertical", $hinge_side, "dark_oak_door");

                        // place torch above door, and on opposite side of room
                        if ($room_details['torches']){
                            $inverse_x = $room->x2 - ($x - $room->x1);
                            if ($y == $room->y2 - 1){
                                $this->place_torch($x, $floor + 2, $room->y2 - 2, "north");
                                $this->place_torch($inverse_x, $floor + 2, $room->y1 + 2, "south");
                            }
                            else {
                                $this->place_torch($x, $floor + 2, $room->y1 + 2, "south");
                                $this->place_torch($inverse_x, $floor + 2, $room->y2 - 2, "north");
                            }
                        }
                    }
                }
            }
            // east/west wall doors
            foreach (array($room->x2 - 1, $room->x1 + 1) as $x){
                for ($y = $room->y1 + 2; $y <= $room->y2 - 2; $y++){
                    if (
                        $level->is_open_space($x - 1, $y)
                        && $level->is_open_space($x + 1, $y)
                    ){
                        $hinge_side = "right";
                        if ($x == $room->x2 - 1){
                            $hinge_side = "left";
                        }

                        $this->place_door($x, $floor, $y, "horizontal", $hinge_side, "dark_oak_door");

                        // place torch above door, and on opposite side of room
                        if ($room_details['torches']){
                            $inverse_y = $room->y2 - ($y - $room->y1);
                            if ($x == $room->x2 - 1){
                                $this->place_torch($room->x2 - 2, $floor + 2, $y, "east");
                                $this->place_torch($room->x1 + 2, $floor + 2, $inverse_y, "west");
                            }
                            else {
                                $this->place_torch($room->x1 + 2, $floor + 2, $y, "west");
                                $this->place_torch($room->x2 - 2, $floor + 2, $inverse_y, "east");
                            }
                        }

                    }
                }
            }

            $taken_spaces = array();
            if ($room_details['mob_spawner']){
                do {
                    $x = rand($room->x1 + 2, $room->x2 - 2);
                    $y = rand($room->y1 + 2, $room->y2 - 2);
                } while (isset($taken_spaces[$x . "-" . $y]));
                $taken_spaces[$x . "-" . $y] = 1;
                $this->place_spawner($x, $floor, $y, $percent_complete);
            }

            if ($room_details['mob']){
                do {
                    $x = rand($room->x1 + 2, $room->x2 - 2);
                    $y = rand($room->y1 + 2, $room->y2 - 2);
                } while (isset($taken_spaces[$x . "-" . $y]));
                $taken_spaces[$x . "-" . $y] = 1;
                $this->place_mob($x, $floor, $y, $percent_complete);
            }

            if ($room_details['chest']){
                do {
                    $x = rand($room->x1 + 2, $room->x2 - 2);
                    $y = rand($room->y1 + 2, $room->y2 - 2);
                } while (isset($taken_spaces[$x . "-" . $y]));
                $taken_spaces[$x . "-" . $y] = 1;
                $this->place_loot_chest($x, $floor, $y, $percent_complete);
            }

            if ($room_details['diamond_chest']){
                do {
                    $x = rand($room->x1 + 2, $room->x2 - 2);
                    $y = rand($room->y1 + 2, $room->y2 - 2);
                } while (isset($taken_spaces[$x . "-" . $y]));
                $taken_spaces[$x . "-" . $y] = 1;
                $this->place_diamond_chest($x, $floor, $y, $percent_complete);
            }
        }

        // place stairs going up to the level above
        for ($h = 0; $h < $room_height + 2; $h++){
            $this->place_ladder($up_stair_position['x'], $floor + $h, $up_stair_position['y'], $up_stair_position['wall']);
        }

        // top block of stairs going down into the next level
        $end_room = null;
        $stair_position = null;
        if ($level_depth < $num_levels){
            $end_room = $level->rooms[count($level->rooms) - 1];
            $stair_position = $this->get_random_stair_position($end_room);
            $this->place_ladder($stair_position['x'], $level_floor + 1, $stair_position['y'], $stair_position['wall']);
            $this->place_ladder($stair_position['x'], $level_floor, $stair_position['y'], $stair_position['wall']);
        }

        return array($end_room, $stair_position);
    }


    private static function generate_food($percent_dungeon_complete){

        $items = array(
            "apple",
            "carrot",
            "bread",
            "beetroot",
            "beetroot_soup",
            "cake",
            "cookie",
            "melon",
            "speckled_melon",
            "mushroom_stew",
            "potato",
            "baked_potato",
            "poisonous_potato",
            "pumpkin_pie",
            "cooked_rabbit",
            "rabbit",
            "cooked_porkchop",
//            "porkchop",
//            "mutton",
            "cooked_mutton",
//            "beef",
            "cooked_beef",
            "spider_eye",
//            "fish",
            "cooked_fish",
            "rotten_flesh",
        );
        if ($percent_dungeon_complete > 20){
            foreach (array(
                "golden_carrot",
                "golden_apple",
             ) as $item){
                $items[] = $item;
            }
        }

        $count = 1;
        if ($percent_dungeon_complete > 20){
            $count = rand(1, 2);
        }
        if ($percent_dungeon_complete > 50){
            $count = rand(1, 3);
        }

        $item = $items[rand(0, count($items) - 1)];
        return array(
            array(
                "id" => $item,
            ),
            $count
        );
    }

    private function generate_arrow($percent_dungeon_complete){

        $potion = $this->generate_potion_name($percent_dungeon_complete);

        // {Slot:0,id:tipped_arrow,Count:1,tag:{Potion:long_night_vision}}

        $item = array(
            "id" => "tipped_arrow",
            "tag" => array(
                "Potion" => $potion,
            ),
        );

        $min = round((20 / 100) * $percent_dungeon_complete);
        $max = (round((40 / 100) * $percent_dungeon_complete)) + $min;

//        20 => 4, 12
//        50 => 10, 30
//        80 => 16, 48

        $count = rand($min, $max);

        return array(
            $item,
            $count
        );

    }


    private function generate_tool($percent_dungeon_complete){
        $tools = array(
            "bucket" => array(1, 1),
            "bowl" => array(1, 1),
            "milk_bucket" => array(1, 1),
            "tripwire_hook" => array(1, 1),

            "torch" => array(1, 1),
            "bone" => array(1, 10),
            "coal" => array(1, 10),
            "stick" => array(1, 10),
        );

        if ($percent_dungeon_complete > 20){
            $tools["torch"] = array(1, 5);
        }
        if ($percent_dungeon_complete > 40){
            $tools["torch"] = array(1, 10);
        }
        if ($percent_dungeon_complete > 60){
            $tools["torch"] = array(5, 10);
        }

        if ($percent_dungeon_complete > 50){
            $tools["water_bucket"] = array(1, 1);
            $tools["lava_bucket"] = array(1, 1);
        }

        $tool_keys = array_keys($tools);
        $tool = $tool_keys[rand(0, count($tool_keys) - 1)];
        list($min, $max) = $tools[$tool];
        $count = rand($min, $max);
        return array(
            array(
                "id" => $tool,
            ),
            $count
        );
    }

    private function generate_potion_name($percent_dungeon_complete){
        $potions = array(
            "fire_resistance",
            "harming",
            "healing",
            "invisibility",
            "leaping",
            "luck",
            "night_vision",
            "poison",
            "regeneration",
            "slowness",
            "strength",
            "swiftness",
            "water_breathing",
            "weakness",
        );

        if ($percent_dungeon_complete > 30){
            $potions[] = "strong_harming";
            $potions[] = "strong_healing";
            $potions[] = "strong_leaping";
            $potions[] = "strong_regeneration";
            $potions[] = "strong_poison";
            $potions[] = "strong_strength";
            $potions[] = "strong_swiftness";
        }

        if ($percent_dungeon_complete > 50){
            $potions[] = "long_fire_resistance";
            $potions[] = "long_invisibility";
            $potions[] = "long_leaping";
            $potions[] = "long_night_vision";
            $potions[] = "long_regeneration";
            $potions[] = "long_slowness";
            $potions[] = "long_strength";
            $potions[] = "long_swiftness";
            $potions[] = "long_water_breathing";
            $potions[] = "long_weakness";
            $potions[] = "long_poison";
        }

        $potion = $potions[rand(0, count($potions) - 1)];
        return $potion;

    }

    private function generate_potion($percent_dungeon_complete){

        $types = array("potion", "splash_potion");
        $type = $types[rand(0, count($types) - 1)];

        $potion = $this->generate_potion_name($percent_dungeon_complete);

//        /setblock ~ ~ ~1 chest 0 replace {Items:[{Slot:0,id:splash_potion,Count:1,tag:{Potion:strong_harming}}]}

        $item = array(
            "id" => $type,
            "tag" => array(
                "Potion" => $potion,
            ),
        );

        return array(
            $item,
            1
        );
    }

    private function generate_weapon($percent_dungeon_complete){
        $types = array("sword", "axe", "bow");
        $type = $types[rand(0, count($types) - 1)];

        $materials[] = "golden";
        $materials = array("wooden");
        if ($percent_dungeon_complete >= 15){
            $materials[] = "stone";
        }
        if ($percent_dungeon_complete >= 30){
            $materials[] = "iron";
        }
        if ($percent_dungeon_complete >= 45){
            $materials[] = "diamond";
        }
        $material = $materials[rand(0, count($materials) - 1)];

        $enchantments = array(
            // bane of arthropods
            array(
                "id" => 18,
                "max_level" => 5,
                "applies_to" => array("sword", "axe"),
            ),
            // fire aspect
            array(
                "id" => 20,
                "max_level" => 2,
                "applies_to" => array("sword"),
            ),

            // knock back
            array(
                "id" => 19,
                "max_level" => 2,
                "applies_to" => array("sword"),
            ),
            // looting
            array(
                "id" => 21,
                "max_level" => 3,
                "applies_to" => array("sword"),
            ),
            // sharpness
            array(
                "id" => 16,
                "max_level" => 5,
                "applies_to" => array("sword", "axe"),
            ),
            // smite
            array(
                "id" => 17,
                "max_level" => 5,
                "applies_to" => array("sword", "axe"),
            ),
            // sweeping edge
            array(
                "id" => 22,
                "max_level" => 3,
                "applies_to" => array("sword"),
            ),
            // unbreaking
            array(
                "id" => 34,
                "max_level" => 3,
                "applies_to" => array("sword", "axe", "bow"),
            ),
            //flame
            array(
                "id" => 50,
                "max_level" => 1,
                "applies_to" => array("bow"),
            ),
            // infinity
            array(
                "id" => 51,
                "max_level" => 1,
                "applies_to" => array("bow"),
            ),
            // power
            array(
                "id" => 48,
                "max_level" => 5,
                "applies_to" => array("bow"),
            ),
            // punch
            array(
                "id" => 49,
                "max_level" => 2,
                "applies_to" => array("bow"),
            ),
        );

        $has_enchantment = rand(0, 100) < $percent_dungeon_complete;

        $enchantment = null;
        if ($has_enchantment){
            $possible_enchantments = array();
            foreach ($enchantments as $enchantment){
                if (in_array($type, $enchantment['applies_to'])){
                    $possible_enchantments[] = $enchantment;
                }
            }
            $enchantment = $possible_enchantments[rand(0, count($possible_enchantments) - 1)];
            $enchantment_level = 1;
            if ($percent_dungeon_complete >= 20){
                $enchantment_level = rand(1, $enchantment['max_level']);
            }
            $enchantment = array(
                "id" => $enchantment['id'],
                "lvl" => $enchantment_level,
            );
        }

        $item = array();
        if ($type == "bow"){
            $item['id'] = "bow";
        } else {
            $item['id'] = $material . "_" . $type;
        }
        if ($has_enchantment){
            $item['tag'] = array(
                "ench" => array(
                    $enchantment
                ),
            );
        }
        return array($item, 1);

    }

    private function generate_armor($percent_dungeon_complete){

        $types = array("boots", "helmet");
        if ($percent_dungeon_complete >= 10){
            $types[] = "leggings";
        }
        if ($percent_dungeon_complete >= 25){
            $types[] = "chestplate";
        }

        $type = $types[rand(0, count($types) - 1)];

        $materials = array("leather");
        if ($percent_dungeon_complete >= 10){
            $materials[] = "golden";
        }
        if ($percent_dungeon_complete >= 25){
            $materials[] = "chainmail";
        }
        if ($percent_dungeon_complete >= 40){
            $materials[] = "iron";
        }
        if ($percent_dungeon_complete >= 55){
            $materials[] = "diamond";
        }
        $material = $materials[rand(0, count($materials) - 1)];

        $enchantments = array(
            // aqua affinity
            array(
                "id" => 6,
                "max_level" => 1,
                "applies_to" => array("helmet"),
            ),
            // respiration
            array(
                "id" => 5,
                "max_level" => 3,
                "applies_to" => array("helmet"),
            ),

            // blast protection
            array(
                "id" => 3,
                "max_level" => 4,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // curse of binding
            array(
                "id" => 10,
                "max_level" => 1,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // feather falling
            array(
                "id" => 2,
                "max_level" => 4,
                "applies_to" => array("boots"),
            ),
            // fire protection
            array(
                "id" => 1,
                "max_level" => 4,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // frost walker
            array(
                "id" => 9,
                "max_level" => 2,
                "applies_to" => array("boots"),
            ),
            // projectile protection
            array(
                "id" => 4,
                "max_level" => 4,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // protection
            array(
                "id" => 0,
                "max_level" => 4,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // thorns
            array(
                "id" => 7,
                "max_level" => 3,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
            // unbreaking
            array(
                "id" => 34,
                "max_level" => 3,
                "applies_to" => array("boots", "helmet", "leggings", "chestplate"),
            ),
        );

        $has_enchantment = rand(0, 100) < $percent_dungeon_complete;
        $enchantment = null;
        if ($has_enchantment){
            $possible_enchantments = array();
            foreach ($enchantments as $enchantment){
                if (in_array($type, $enchantment['applies_to'])){
                    $possible_enchantments[] = $enchantment;
                }
            }
            $enchantment = $possible_enchantments[rand(0, count($possible_enchantments) - 1)];
            $enchantment_level = 1;
            if ($percent_dungeon_complete >= 20){
                $enchantment_level = rand(1, $enchantment['max_level']);
            }
            $enchantment = array(
                "id" => $enchantment['id'],
                "lvl" => $enchantment_level,
            );
        }

//        {id:chainmail_boots,tag:{ench:[{id:3,lvl:1}]}}

        $item = array(
            "id" => $material . "_" . $type,
        );
        if ($has_enchantment){
            $item['tag'] = array(
                "ench" => array(
                    $enchantment
                ),
            );
        }
        return array($item, 1);

    }

    private function place_mob($x, $z, $y, $percent_dungeon_complete){

        $mobs = array(
            array(
                "id" => "zombie",
                "HandItems" => true,
                "ArmorItems" => true,
            ),
            array(
                "id" => "zombie",
                "IsBaby" => 1,
                "HandItems" => true,
                "ArmorItems" => true,
            ),
            array(
                "id" => "zombie",
                "HandItems" => true,
                "ArmorItems" => true,
                "IsBaby" => 1,
                "Riding" => "chicken",
            ),
            array(
                "id" => "zombie_villager",
                "HandItems" => true,
                "ArmorItems" => true,
            ),
            array(
                "id" => "zombie_villager",
                "IsBaby" => 1,
                "HandItems" => true,
                "ArmorItems" => true,
            ),
            array(
                "id" => "skeleton",
                "HandItems" => true,
                "ArmorItems" => true,
            ),
            array(
                "id" => "skeleton",
                "HandItems" => true,
                "ArmorItems" => true,
                "Riding" => "spider",
            ),
            array(
                "id" => "slime",
                "Size" => rand(0,6),
            ),
            array(
                "id" => "witch",
            ),
            array(
                "id" => "creeper",
            ),
            array(
                "id" => "silverfish",
            ),
        );

        if ($percent_dungeon_complete > 20){
            $mobs[] = array(
                "id" => "husk",
            );
            $mobs[] = array(
                "id" => "stray",
            );
            $mobs[] = array(
                "id" => "creeper",
                "powered" => 1
            );
            $mobs[] = array(
                "id" => "zombie_pigman",
                "HandItems" => true,
                "ArmorItems" => true,
            );
            $mobs[] = array(
                "id" => "zombie_pigman",
                "IsBaby" => 1,
                "HandItems" => true,
                "ArmorItems" => true,
            );
        }
        if ($percent_dungeon_complete > 70){
            $mobs[] = array(
                "id" => "evoker",
            );
            $mobs[] = array(
                "id" => "ghast",
            );
            $mobs[] = array(
                "id" => "magma_cube",
            );
            $mobs[] = array(
                "id" => "blaze",
            );
        }
        if ($percent_dungeon_complete > 40){
            $mobs[] = array(
                "id" => "vex",
            );
            $mobs[] = array(
                "id" => "illusion_illager",
            );
            $mobs[] = array(
                "id" => "vindication_illager",
            );
            $mobs[] = array(
                "id" => "wither_skeleton",
                "HandItems" => true,
                "ArmorItems" => true,
            );
            $mobs[] = array(
                "id" => "wither",
            );
        }

        $mob = $mobs[rand(0,count($mobs) - 1)];

        $mob_props = array(
            "PersistenceRequired" => 1,
        );
        $riding_on = null;
        foreach ($mob as $key => $val){
            if ($key == "id"){
                continue;
            }

            else if ($key == "HandItems"){
                $rnd = rand(1,3);
                if ($rnd == 1){
                    continue;
                }
                list($item1, $count) = $this->generate_weapon($percent_dungeon_complete);
                $item1["Count"] = 1;
                $item2 = new stdClass();
                if ($rnd == 3){
                    list($item2, $count) = $this->generate_weapon($percent_dungeon_complete);
                    $item2["Count"] = 1;
                }
                $mob_props["HandItems"] = array(
                    $item1,
                    $item2
                );
                $mob_props["HandDropChances"] = array(
                    "1.0f",
                    ($rnd == 3 ? "1.0f" : "0.0f")
                );
            }

            else if ($key == "ArmorItems"){
                $num_armor_pieces = rand(0,1);
                if ($percent_dungeon_complete > 20){
                    $num_armor_pieces = rand(0,2);
                }
                if ($percent_dungeon_complete > 40){
                    $num_armor_pieces = rand(1,3);
                }
                if ($percent_dungeon_complete > 60){
                    $num_armor_pieces = rand(1,4);
                }
                if ($percent_dungeon_complete > 80){
                    $num_armor_pieces = rand(2,4);
                }
                $armor_items = array();
                $infinite_loop_breaker_count = 0;
                while (count($armor_items) < $num_armor_pieces){
                    $infinite_loop_breaker_count++;
                    if ($infinite_loop_breaker_count > 500){
                        break;
                    }
                    list($item, $count) = $this->generate_armor($percent_dungeon_complete);
                    list($material, $type) = explode("_", $item['id']);
                    $armor_items[$type] = $item;
                }
                foreach (array("boots", "leggings", "chestplate", "helmet", ) as $type){
                    if (!isset($armor_items[$type])){
                        $armor_items[$type] = new stdClass();
                        continue;
                    }
                    $armor_items[$type]['Count'] = 1;
                }
                $mob_props["ArmorItems"] = array(
                    $armor_items["boots"],
                    $armor_items["leggings"],
                    $armor_items["chestplate"],
                    $armor_items["helmet"],
                );
                $mob_props["ArmorDropChances"] = array(
                    (is_array($armor_items["boots"]) ? "1.0f" : "0.0f"),
                    (is_array($armor_items["leggings"]) ? "1.0f" : "0.0f"),
                    (is_array($armor_items["chestplate"]) ? "1.0f" : "0.0f"),
                    (is_array($armor_items["helmet"]) ? "1.0f" : "0.0f"),
                );

            }
            else if ($key == "Riding"){
                $riding_on = $val;
            }
            else {
                $mob_props[$key] = $val;
            }
        }

        $mob_name = $mob['id'];

        if ($riding_on){
            $mob_name = $riding_on;
            $mob_props['id'] = $mob['id'];
            $mob_props = array(
                "PersistenceRequired" => 1,
                "Passengers" => array(
                    $mob_props
                ),
            );
        }

        $mob_props = json_encode($mob_props);
        $mob_props = preg_replace('#"1\.0f"#', '1.0f', $mob_props);
        $mob_props = preg_replace('#"0\.0f"#', '0.0f', $mob_props);

        $this->cmds[] = "summon " . $mob_name
            . " ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " " . $mob_props;

    }


    // https://www.digminecraft.com/generators/mob_spawner.php
    private function place_spawner($x, $z, $y, $percent_dungeon_complete){

        $types = array(
            array(
                "id" => "zombie",
                "Silent" => 1,
            ),
            array(
                "id" => "zombie",
                "IsBaby" => 1,
                "Silent" => 1,
            ),
            array(
                "id" => "chicken",
                "Passengers" => array(
                    "id" => "zombie",
                    "IsBaby" => 1,
                    "Silent" => 1,
                )
            ),
            array(
                "id" => "skeleton",
            ),
            array(
                "id" => "spider",
                "Passengers" => array(
                    "id" => "skeleton",
                )
            ),
            array(
                "id" => "cave_spider",
            ),
            array(
                "id" => "creeper",
            )

        );

        $type = array();
        $type['SpawnData'] = $types[rand(0, count($types) - 1)];
        $type['SpawnRange'] = 2;
        $type['SpawnCount'] = round((10 / 100) * $percent_dungeon_complete);
        $type['Delay'] = 299;

        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " mob_spawner 0"
            . ' replace ' . json_encode($type);

    }

    private function place_diamond_chest($x, $z, $y){

        $loot = array(
            "pickaxe" => array(
                "num" => 2,
                "id" => "diamond_pickaxe",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 16,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 32,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 35,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Pickaxe of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this pickaxe survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "sword" => array(
                "num" => 2,
                "id" => "diamond_sword",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 16,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 19,
                            "lvl" => 2,
                        ),
                        array(
                            "id" => 21,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 22,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Sword of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this sword survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "bow" => array(
                "num" => 2,
                "id" => "bow",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 48,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 49,
                            "lvl" => 2,
                        ),
                        array(
                            "id" => 50,
                            "lvl" => 1,
                        ),
                        array(
                            "id" => 51,
                            "lvl" => 1,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Bow of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this bow survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),
            "arrow" => array(
                "num" => 1,
                "id" => "spectral_arrow",
                "Slot" => 0,
                "Count" => 64,
            ),
            "axe" => array(
                "num" => 2,
                "id" => "diamond_axe",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 16,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 32,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 33,
                            "lvl" => 1,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Axe of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this axe survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "helmet" => array(
                "num" => 2,
                "id" => "diamond_helmet",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 0,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 5,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 6,
                            "lvl" => 1,
                        ),
                        array(
                            "id" => 7,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Helmet of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this helmet survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "chestplate" => array(
                "num" => 2,
                "id" => "diamond_chestplate",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 0,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 7,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Chestplate of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this chestplate survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "leggings" => array(
                "num" => 2,
                "id" => "diamond_leggings",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 1,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 7,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Pants of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of these pants survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "boots" => array(
                "num" => 2,
                "id" => "diamond_boots",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 1,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 2,
                            "lvl" => 4,
                        ),
                        array(
                            "id" => 7,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 9,
                            "lvl" => 2,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Boots of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of these boots survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),

            "shovel" => array(
                "num" => 2,
                "id" => "diamond_shovel",
                "tag" => array(
                    "ench" => array(
                        array(
                            "id" => 32,
                            "lvl" => 5,
                        ),
                        array(
                            "id" => 35,
                            "lvl" => 3,
                        ),
                        array(
                            "id" => 34,
                            "lvl" => 3,
                        ),
                    )
                ),
                "display" => array(
                    "Name" => "Shovel of the Diamond Dungeon",
                    "Lore" => array(
                        "The owner of this shovel survived the Diamond Dungeon"
                    ),
                ),
                "Slot" => 0,
                "Count" => 1,
            ),
        );

        $items = array();
        $slot_num = 0;
        foreach ($loot as $item){
            for ($i = 0; $i < $item['num']; $i++){
                $item['Slot'] = $slot_num;
                $items[] = $item;
                $slot_num++;
            }
        }

        $items = array(
            "Items" => $items,
            "display" => array(
                "Name" => "The Diamond Chest",
                "Lore" => array(
                    "You reached the bottom alive, it's a miracle!"
                ),
            ),
        );



        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " chest 0"
            . ' replace ' . json_encode($items);

    }

    // https://minecraftcommand.science/prefilled-chest-generator#deleted
    // the higher percent complete, the better look is possible
    private function place_loot_chest($x, $z, $y, $percent_dungeon_complete){

        $items = array();

        $num_slots_to_fill = rand(1, round((20 / 100) * $percent_dungeon_complete) + 5);
//        print "filling chest with " . $num_slots_to_fill . " items\n";
        for ($slot_num = 1; $slot_num <= $num_slots_to_fill; $slot_num++){

            $types = array("weapon", "arrow", "armor", "potion", "tool", "food", "food", "food", "food", "food");
            $type = $types[rand(0, count($types) - 1)];
            $item = null;
            $count = 0;
            switch($type){
                case "weapon":
                    list($item, $count) = $this->generate_weapon($percent_dungeon_complete);
                    break;

                case "arrow":
                    list($item, $count) = $this->generate_arrow($percent_dungeon_complete);
                    break;

                case "armor":
                    list($item, $count) = $this->generate_armor($percent_dungeon_complete);
                    break;

                case "potion":
                    list($item, $count) = $this->generate_potion($percent_dungeon_complete);
                    break;

                case "tool":
                    list($item, $count) = $this->generate_tool($percent_dungeon_complete);
                    break;

                case "food":
                    list($item, $count) = $this->generate_food($percent_dungeon_complete);
                    break;

            }

            $item["Slot"] = $slot_num;
            $item["Count"] = $count;
            $items[] = $item;
        }

//        /setblock ~1 ~ ~ chest 0 replace {Items:[{id:"wooden_sword",Slot:0,Count:1},{id:"diamond_sword",Slot:1,Count:1}]}

        $items = array(
            "Items" => $items,
        );
//        print " items in chest: " . print_r($items, true) . "\n";
        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " chest 0"
            . ' replace ' . json_encode($items);

    }

    private function get_random_stair_position($room){

        $room_xs = $room->x2 - $room->x1;
        $room_ys = $room->y2 - $room->y1;
        if (rand(0,1)){
            $x = $room->x1 + rand(2, $room_xs - 2);
            $y = $room->y1 + 2;
            $wall = "south";
            if (rand(0,1)){
                $y = $room->y2 - 2;
                $wall = "north";
            }
        } else {
            $y = $room->y1 + rand(2, $room_ys - 2);
            $x = $room->x1 + 2;
            $wall = "west";
            if (rand(0,1)){
                $x = $room->x2 - 2;
                $wall = "east";
            }
        }
        return array(
            "x" => $x,
            "y" => $y,
            "wall" => $wall
        );

    }

    private function place_ladder($x, $z, $y, $direction){

        $directions = array(
            "east" => 4,
            "west" => 5,
            "south" => 3,
            "north" => 2,
        );

        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " ladder " . $directions[$direction];

    }

    private function place_sign($x, $z, $y, $direction, $text1, $text2, $text3){

        $sign_info = array(
            "Text1" => json_encode($text1),
            "Text2" => json_encode($text2),
            "Text3" => json_encode($text3),
        );

        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " standing_sign " . $direction . " replace " . json_encode($sign_info);

// /setblock ~ 18 ~1 minecraft:standing_sign 3 replace {Text1:"{\"text\":\"Dungeon\"}", Text2:"{\"text\":\"line2\"}"}

    }

    private function place_torch($x, $z, $y, $direction = null){
        $cmd = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " torch";

        $directions = array(
            "east" => 2,
            "west" => 1,
            "south" => 3,
            "north" => 4,
        );
        if (isset($directions[$direction])){
            $cmd .= " " . $directions[$direction];
        }

        $this->cmds[] = $cmd;
    }

    function place_door($x, $z, $y, $alignment, $hinge_side, $door_name){
        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z) . " ~" . ($y + $this->y_offset)
            . " " . $door_name . " " . (strtolower($alignment[0]) == "h" ? "0" : "1");
        $this->cmds[] = "setblock ~" . ($x + $this->x_offset) . " " . $this->get_relative_z($z + 1) . " ~" . ($y + $this->y_offset)
            . " " . $door_name . " " . (strtolower($hinge_side[0]) == "l" ? "8" : "9");
    }

    function fill($x1, $z1, $y1, $x2, $z2, $y2, $block){
        $this->cmds[] = "fill ~" . ($x1 + $this->x_offset) . " " . $this->get_relative_z($z1) . " ~" . ($y1 + $this->y_offset)
            . " ~" . ($x2 + $this->x_offset) . " " . $this->get_relative_z($z2) . " ~" . ($y2 + $this->y_offset) . " " . $block;
    }


}


class level {

    const BEDROCK = 0;
    const TUNNEL = 1;
    const ROOM = 2;
    const ROOM_WALL = 3;

    public $xs;
    public $ys;
    public $grid;
    public $rooms;


    // don't let the entrance point fall on/near the corner:
    // 0 == exactly on the corner, 1 == 1 space from the corner, etc etc
    private $min_door_distance_from_room_corner = 3;

    public function is_open_space($x, $y){
        return in_array($this->grid[$x][$y], array(
            // blocks that are considered open air
            self::TUNNEL,
            self::ROOM,
        ));
    }

    private function reset_grid(){
        $this->rooms = array();
        $this->grid = array();
        for ($x=0; $x < $this->xs; $x++){
            $this->grid[$x] = array();
            for ($y=0; $y < $this->ys; $y++){
                $this->grid[$x][$y] = self::BEDROCK;
            }
        }
    }

    public static function generate_level(
        $x_size = 35,
        $y_size = 35,
        $start_room_x = 1,
        $start_room_y = 1,
        $start_room_xs = 5,
        $start_room_ys = 5,
        $num_rooms = 4,
        $min_room_size = 3,
        $max_room_size = 10
    ){

        $level = new level();
        $level->xs = $x_size;
        $level->ys = $y_size;

//        $level->reset_grid();
//        $level->draw_maze(15, 15);
//        $level->print_level();
//        exit;

        // find maze start point somewhere on the edge of the start room
        $start_room = new room($start_room_x, $start_room_y, $start_room_x + $start_room_xs - 1, $start_room_y + $start_room_ys - 1);
        $possible_maze_start_points = $level->find_maze_start_points($start_room);

        // generate a map that has $num_rooms rooms
        while (count($level->rooms) != $num_rooms){

            // make a maze with $num_rooms - 1 (desired num rooms minus the start room) dead ends to put rooms at
            $num_deadends = 0;
            while ($num_deadends != $num_rooms - 1){
                $level->reset_grid();
                $level->add_room($start_room);

                $random_start_point = $possible_maze_start_points[rand(0, count($possible_maze_start_points) - 1)];
                list ($x, $y) = $random_start_point;
                $level->draw_maze($x, $y);
                $num_deadends = $level->reduce_dead_ends($num_rooms - 1);
            }

            // put rooms at the end of each of the dead ends where space is available
            $level->add_rooms_in_spaces_at_dead_ends($min_room_size, $max_room_size);
        }


        return $level;

    }

    private function find_maze_start_points($room){
        $possible_maze_start_points = array();
        // points along south edge of room
        if ($room->y1 > 2){
            for ($x = $room->x1 + $this->min_door_distance_from_room_corner; $x <= $room->x2 - $this->min_door_distance_from_room_corner; $x++){
                $possible_maze_start_points[] = array($x, $room->y1);
            }
        }
        // points along north edge of room
        if ($room->y2 < $this->ys - 3){
            for ($x = $room->x1 + $this->min_door_distance_from_room_corner; $x <= $room->x2 - $this->min_door_distance_from_room_corner; $x++){
                $possible_maze_start_points[] = array($x, $room->y2);
            }
        }
        // points along west edge of room
        if ($room->x1 > 2){
            for ($y = $room->y1 + $this->min_door_distance_from_room_corner; $y <= $room->y2 - $this->min_door_distance_from_room_corner; $y++){
                $possible_maze_start_points[] = array($room->x1, $y);
            }
        }
        // points along east edge of room
        if ($room->x2 < $this->xs - 3){
            for ($y = $room->y1 + $this->min_door_distance_from_room_corner; $y <= $room->y2 - $this->min_door_distance_from_room_corner; $y++){
                $possible_maze_start_points[] = array($room->x2, $y);
            }
        }

        if (!$possible_maze_start_points){
            throw new Exception("start room is too small to start a maze on it, make it bigger");
        }

        return $possible_maze_start_points;
    }


    private function add_rooms_in_spaces_at_dead_ends($min_room_size, $max_room_size){
        $dead_ends = $this->get_deadends();
        foreach ($dead_ends as $dead_end){
            list($x, $y, $open_side) = $dead_end;
            $room_possibilities = array();
            foreach (array("W", "E", "S", "N") as $dir){
                if ($dir == $open_side){
                    continue;
                }
                $x_look = $x;
                $y_look = $y;
                switch($dir){
                    case "W":
                        $x_look = $x - 1;
                        break;
                    case "E":
                        $x_look = $x + 1;
                        break;
                    case "S":
                        $y_look = $y - 1;
                        break;
                    case "N":
                        $y_look = $y + 1;
                        break;

                }
                if (
                    $x_look < 0
                    || $x_look >= $this->xs
                    || $y_look < 0
                    || $y_look >= $this->ys
                ){
                    continue;
                }
                $room_possibility = $this->generate_room($x_look, $y_look, $min_room_size, $max_room_size);
                if ($room_possibility){
                    $room_possibilities[$dir] = array(
                        $x_look,
                        $y_look,
                        $room_possibility
                    );
                }
            }
            if ($room_possibilities){
                $dirs = array_keys($room_possibilities);
                $dir = $dirs[rand(0, count($dirs) - 1)];
                list($door_x, $door_y, $room) = $room_possibilities[$dir];
                $this->add_room($room);
                // add the door to the room
                $this->grid[$door_x][$door_y] = self::TUNNEL;
            }
        }

        // add some additional doors to the existing rooms
        foreach ($this->rooms as $room){
            // north wall
            $y = $room->y2;
            for ($x = $room->x1 + $this->min_door_distance_from_room_corner; $x <= $room->x2 - $this->min_door_distance_from_room_corner; $x++){
                if (
                    $this->grid[$x - 1][$y] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }
            for ($x = $room->x2 - $this->min_door_distance_from_room_corner; $x >= $room->x1 + $this->min_door_distance_from_room_corner; $x--){
                if (
                    $this->grid[$x - 1][$y] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }

            // south wall
            $y = $room->y1;
            for ($x = $room->x1 + $this->min_door_distance_from_room_corner; $x <= $room->x2 - $this->min_door_distance_from_room_corner; $x++){
                if (
                    $this->grid[$x - 1][$y] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::ROOM_WALL
                    && $this->grid[$x][$y - 1] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }
            for ($x = $room->x2 - $this->min_door_distance_from_room_corner; $x >= $room->x1 + $this->min_door_distance_from_room_corner; $x--){
                if (
                    $this->grid[$x - 1][$y] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::ROOM_WALL
                    && $this->grid[$x][$y - 1] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }


            // east wall
            $x = $room->x2;
            for ($y = $room->y1 + $this->min_door_distance_from_room_corner; $y <= $room->y2 - $this->min_door_distance_from_room_corner; $y++){
                if (
                    $this->grid[$x][$y - 1] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }
            for ($y = $room->y2 - $this->min_door_distance_from_room_corner; $y >= $room->y1 + $this->min_door_distance_from_room_corner; $y--){
                if (
                    $this->grid[$x][$y - 1] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::ROOM_WALL
                    && $this->grid[$x + 1][$y] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }

            // west wall
            $x = $room->x1;
            for ($y = $room->y1 + $this->min_door_distance_from_room_corner; $y <= $room->y2 - $this->min_door_distance_from_room_corner; $y++){
                if (
                    $this->grid[$x][$y - 1] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::ROOM_WALL
                    && $this->grid[$x - 1][$y] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }
            for ($y = $room->y2 - $this->min_door_distance_from_room_corner; $y >= $room->y1 + $this->min_door_distance_from_room_corner; $y--){
                if (
                    $this->grid[$x][$y - 1] == self::ROOM_WALL
                    && $this->grid[$x][$y + 1] == self::ROOM_WALL
                    && $this->grid[$x - 1][$y] == self::TUNNEL
                ){
                    $this->grid[$x][$y] = self::TUNNEL;
                    break;
                }
            }


        }


    }

    private function generate_room($entry_point_x, $entry_point_y, $min_size, $max_size){

        $largest_area_that_fits = null;
        $largest_room = null;
        for ($w_test = 0; $w_test <= 10; $w_test++){
            for ($e_test = 0; $e_test <= 10; $e_test++){
                for ($s_test = 0; $s_test <= 10; $s_test++){
                    for ($n_test = 0; $n_test <= 10; $n_test++){
                        $x1 = $entry_point_x - $w_test;
                        $y1 = $entry_point_y - $s_test;
                        $x2 = $entry_point_x + $e_test;
                        $y2 = $entry_point_y + $n_test;

                        // off the grid
                        if (
                            $x1 < 1
                            || $x1 >= $this->xs -1
                            || $x2 < 1
                            || $x2 >= $this->xs -1
                            || $y1 < 1
                            || $y1 >= $this->ys -1
                            || $y2 < 1
                            || $y2 >= $this->ys -1
                        ){
                            continue;
                        }

                        // room too small or too big
                        if (
                            $x2 - $x1 < $min_size
                            || $y2 - $y1 < $min_size
                            || $x2 - $x1 > $max_size
                            || $y2 - $y1 > $max_size
                        ){
                            continue;
                        }

                        if (
                            (
                                $entry_point_x < $x1 + $this->min_door_distance_from_room_corner
                                && $entry_point_y < $y1 + $this->min_door_distance_from_room_corner
                            )
                            || (
                                $entry_point_x < $x1 + $this->min_door_distance_from_room_corner
                                && $entry_point_y > $y2 - $this->min_door_distance_from_room_corner
                            )
                            || (
                                $entry_point_x > $x2 - $this->min_door_distance_from_room_corner
                                && $entry_point_y < $y1 + $this->min_door_distance_from_room_corner
                            )
                            || (
                                $entry_point_x > $x2 - $this->min_door_distance_from_room_corner
                                && $entry_point_y > $y2 - $this->min_door_distance_from_room_corner
                            )
                        ){
                            continue;
                        }

                        $area = ($x2 - $x1) * ($y2 - $y1);
                        if (!$area){
                            continue;
                        }
                        if ($largest_area_that_fits && $area < $largest_area_that_fits){
                            continue;
                        }
                        $available = true;
                        for ($x_test = $x1; $x_test <= $x2; $x_test++){
                            for ($y_test = $y1; $y_test <= $y2; $y_test++){
                                if ($this->grid[$x_test][$y_test] != self::BEDROCK){
                                    $available = false;
                                    break;
                                }
                            }
                        }
                        if (!$available){
                            continue;
                        }

                        $largest_area_that_fits = $area;
                        $largest_room = new room($x1, $y1, $x2, $y2);
                    }
                }
            }
        }
        return $largest_room;
    }

    private function draw_maze($x, $y, $prev_x = null, $prev_y = null){

        while(true){
            $this->grid[$x][$y] = self::TUNNEL;
            if ($prev_x){
                if ($prev_x < $x){
                    $this->grid[$x - 1][$y] = self::TUNNEL;
                } else if ($prev_x > $x){
                    $this->grid[$x + 1][$y] = self::TUNNEL;
                } else if ($prev_y < $y){
                    $this->grid[$x][$y - 1] = self::TUNNEL;
                } else if ($prev_y > $y){
                    $this->grid[$x][$y + 1] = self::TUNNEL;
                }
            }

            $allowed_directions = array();
            if (
                $x > 2
                && $this->grid[$x - 1][$y] == self::BEDROCK
                && $this->grid[$x - 2][$y] == self::BEDROCK
            ){
                $allowed_directions[] = "W";
            }
            if (
                $x < $this->xs - 3
                && $this->grid[$x + 1][$y] == self::BEDROCK
                && $this->grid[$x + 2][$y] == self::BEDROCK
            ){
                $allowed_directions[] = "E";
            }
            if (
                $y > 2
                && $this->grid[$x][$y - 1] == self::BEDROCK
                && $this->grid[$x][$y - 2] == self::BEDROCK
            ){
                $allowed_directions[] = "S";
            }
            if (
                $y < $this->ys - 3
                && $this->grid[$x][$y + 1] == self::BEDROCK
                && $this->grid[$x][$y + 2] == self::BEDROCK
            ){
                $allowed_directions[] = "N";
            }

            if (!$allowed_directions){
                return;
            }

            // if we can go any of three directions, the prefer straight...
            $weighted_direction = null;
            if (
                in_array("W", $allowed_directions)
                && in_array("N", $allowed_directions)
                && in_array("E", $allowed_directions)
            ){
                $weighted_direction = "N";
            }
            else if (
                in_array("N", $allowed_directions)
                && in_array("E", $allowed_directions)
                && in_array("S", $allowed_directions)
            ){
                $weighted_direction = "E";
            }
            else if (
                in_array("E", $allowed_directions)
                && in_array("S", $allowed_directions)
                && in_array("W", $allowed_directions)
            ){
                $weighted_direction = "S";
            }
            else if (
                in_array("S", $allowed_directions)
                && in_array("W", $allowed_directions)
                && in_array("N", $allowed_directions)
            ){
                $weighted_direction = "W";
            }

            if ($weighted_direction){
                for ($i = 0; $i < 10; $i++){
                    $allowed_directions[] = $weighted_direction;
                }
            }

            $dir = rand(0, count($allowed_directions) - 1);
            switch($allowed_directions[$dir]){
                case "W":
                    $this->draw_maze($x - 2, $y, $x, $y);
                    break;
                case "E":
                    $this->draw_maze($x + 2, $y, $x, $y);
                    break;
                case "S":
                    $this->draw_maze($x, $y - 2, $x, $y);
                    break;
                case "N":
                    $this->draw_maze($x, $y + 2, $x, $y);
                    break;
            }
        }

    }

    private function get_deadends(){
        $dead_ends = array();
        for ($x = 0; $x < $this->xs; $x++){
            for ($y = 0; $y < $this->ys; $y++){
                if ($this->grid[$x][$y] == self::BEDROCK){
                    continue;
                }

                $open_sides = array();
                if ($x > 0 && $this->grid[$x - 1][$y] != self::BEDROCK){
                    $open_sides[] = "W";
                }
                if ($x < $this->xs - 1 && $this->grid[$x + 1][$y] != self::BEDROCK){
                    $open_sides[] = "E";
                }
                if ($y > 0 && $this->grid[$x][$y - 1] != self::BEDROCK){
                    $open_sides[] = "S";
                }
                if ($y < $this->ys - 1 && $this->grid[$x][$y + 1] != self::BEDROCK){
                    $open_sides[] = "N";
                }

                if (count($open_sides) == 1){
                    $dead_ends[] = array($x, $y, $open_sides[0]);
                }
            }
        }
        return $dead_ends;
    }


    private function reduce_dead_ends($max_deadends = 1){

        while(true){
            $dead_ends = $this->get_deadends();

            if (count($dead_ends) <= $max_deadends){
                return count($dead_ends);
            }

            foreach ($dead_ends as $dead_end){
                list($x, $y, $open_side) = $dead_end;
                $this->grid[$x][$y] = self::BEDROCK;
                switch ($open_side){
                    case "W":
                        $this->grid[$x - 1][$y] = self::BEDROCK;
                        break;
                    case "E":
                        $this->grid[$x + 1][$y] = self::BEDROCK;
                        break;
                    case "S":
                        $this->grid[$x][$y - 1] = self::BEDROCK;
                        break;
                    case "N":
                        $this->grid[$x][$y + 1] = self::BEDROCK;
                        break;
                }
            }
        }
    }

    public function print_level(){

        $blocks = array(
            self::TUNNEL => "[]", // tunnel
            self::BEDROCK => "  ", // bedrock
            self::ROOM => "..", // room
            self::ROOM_WALL => "##", // room wall
        );
        for ($y = $this->ys - 1; $y >= 0; $y--){
            for ($x = 0; $x < $this->xs; $x++){
                $b = $this->grid[$x][$y];
                print $blocks[$b];
            }
            print "\n";
        }
        print "\n";

//        foreach ($this->rooms as $room){
//            print $room->x1 . ", " . $room->y1 . ", " . $room->x2 . ", " . $room->y2 . "\n";
//        }

    }


    public static function get_distance($x1, $y1, $x2, $y2){
        return sqrt(
            pow($x2 - $x1, 2)
            + pow($y2 - $y1, 2)
        );
    }

    private function add_room($x1, $y1 = null, $x2 = null, $y2 = null){
        if ($x1 instanceof room){
            $room = $x1;
        }
        else {
            $room = new room($x1, $y1, $x2, $y2);
        }
        $this->rooms[] = $room;
        $this->fill_room($room);

    }

    private function fill_room($room){
        for ($x = $room->x1; $x <= $room->x2; $x++){
            for ($y = $room->y1; $y <= $room->y2; $y++){
                if (
                    $x == $room->x1
                    || $x == $room->x2
                    || $y == $room->y1
                    || $y == $room->y2
                ){
                    $this->grid[$x][$y] = self::ROOM_WALL;
                }
                else {
                    $this->grid[$x][$y] = self::ROOM;
                }
            }
        }
    }

}


class room {
    public $x1;
    public $y1;
    public $x2;
    public $y2;

    public function __construct($x1, $y1, $x2, $y2){
        $this->x1 = $x1;
        $this->x2 = $x2;
        $this->y1 = $y1;
        $this->y2 = $y2;
    }

    public function is_same($room){
        return (
            $this->x1 == $room->x1
            && $this->x2 == $room->x2
            && $this->y1 == $room->y1
            && $this->y2 == $room->y2
        );
    }

    public function get_center(){
        $x = $this->x1 + round(($this->x2 - $this->x1) / 2);
        $y = $this->y1 + round(($this->y2 - $this->y1) / 2);
        return array($x, $y);
    }

    public function get_area(){
        return ($this->x2 - $this->x1) * ($this->y2 - $this->y1);
    }

    public function get_distance($room){
        $min = null;
        $d = array();
        for ($px1 = 1; $px1 <= 2; $px1++){
            for ($py1 = 1; $py1 <= 2; $py1++){
                for ($px2 = 1; $px2 <= 2; $px2++){
                    for ($py2 = 1; $py2 <= 2; $py2++){
                        $x1 = $room->{"x" . $px1};
                        $y1 = $room->{"y" . $py1};
                        $x2 = $this->{"x" . $px2};
                        $y2 = $this->{"y" . $py2};
                        $d[] = level::get_distance($x1, $y1, $x2, $y2);
                    }
                }
            }
        }
        sort($d, SORT_NUMERIC);
        return $d[0];
    }


    public function overlaps_room($room){
        for ($x = $room->x1; $x <= $room->x2; $x++){
            for ($y = $room->y1; $y <= $room->y2; $y++){
                if ($this->overlaps_point($x, $y)){
                    return true;
                }
            }
        }
        return false;
    }

    public function overlaps_point($x, $y){
        if (
            $x >= $this->x1 - 2
            && $x <= $this->x2 + 2
            && $y >= $this->y1 - 2
            && $y <= $this->y2 + 2
        ){
            return true;
        }
        return false;
    }
}

