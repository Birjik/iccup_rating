<?php
    $GLOBALS['space'] = 30;
    $GLOBALS['width'] = 1100;
    $GLOBALS['height'] = 500;
    $GLOBALS['pts'] = array(0, 400, 3000, 6000, 9000, 12000, 15000, 1000000);
    $GLOBALS['colors'] = array('#FFFFFF', '#F57676', '#FFFF66', '#74B2F0', '#7FFC9C', '#CD973A', '#E999F2','#000000');
    $GLOBALS['ratings'] = array();
    $GLOBALS['dates'] = array();
    $GLOBALS['min_rating'] = $GLOBALS['max_rating'] = -1;
    $GLOBALS['min_date'] = $GLOBALS['max_date'] = -1;
    $GLOBALS['one_day_in_pixels'] = 0;
    $GLOBALS['games_on_date'] = array();
    $GLOBALS['days_to_show'] = 8;
    $GLOBALS['start_rating'] = -1;
    function get_html_from($url){
        return file_get_contents($url);
    }
    function get_string_between($s, $begin, $end){
        $n = strlen($s);
        $len = strlen($begin);
        $start = 0;
        for ($i = 0; $i + $len - 1 < $n; ++ $i) {
            if (substr($s, $i, $len) == $begin) {
                $start = $i + $len;
                break;
            }
        }
        $res = "";
        while ($start + strlen($end) - 1 < $n && substr($s, $start, strlen($end)) != $end)
            $res .= $s[$start ++];
        return $res;
    }
    function get_num_of_month($s){
        $months = array('', 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
        for($i = 1;$i <= 12;++ $i){
            if($months[$i] == $s)
                return $i;
        }
        return 0;
    }
    function get_matchlist_changes($s, $nick){
        $n = strlen($s);
        $a = array();
        for($i = 0;$i < $n;++ $i){
            if($i + 8 < $n && substr($s, $i, 8) == 'width50"'){
                $j = $i + 8;
                $coeff = 0;
                do {
                    if($s[$j] == '0')
                        break;
                    if($s[$j] == '-' || $s[$j] == '+') {
                        $coeff = ($s[$j ++] == '-' ? -1 : 1);
                        break;
                    }
                    ++ $j;
                }while(true);
                $upd_rating = 0;
                while('0' <= $s[$j] && $s[$j] <= '9'){
                    $upd_rating = $upd_rating * 10 + ord($s[$j]) - ord('0');
                    ++ $j;
                }
                $upd_rating *= $coeff;
                while($j + strlen($nick) < $n && strtolower(substr($s, $j, strlen($nick))) != strtolower($nick)) // find_the hero with player $nick played
                    ++ $j;
                if(strtolower(substr($s, $j, strlen($nick))) != strtolower($nick))
                    break;
                while(substr($s, $j, 4) != ".png")
                    -- $j;
                -- $j;
                $hero = "";
                while($s[$j] != '/'){
                    $hero .= $s[$j];
                    -- $j;
                }
                while($j + 5 < $n && substr($s, $j, 5) != "Date:" && substr($s, $j, 5) != "Дата:") // find the date
                    ++ $j;

                $j += 6;

                $date = array();
                if(substr($s, $j, 5) == "today"){
                    array_push($date, date("j", time()));
                    array_push($date, date("n", time()));
                    array_push($date, date("Y", time()));
                }
                else if(substr($s, $j, 9) == "yesterday"){
                    array_push($date, date("j", time() - 86400));
                    array_push($date, date("n", time() - 86400));
                    array_push($date, date("Y", time() - 86400));
                }
                else {
                    //  format : 23 Jul 2017
                    $day = 0;
                    $month = "";
                    $year = 0;
                    while($j < $n && '0' <= $s[$j] && $s[$j] <= '9') {
                        $day = $day * 10 + ord($s[$j]) - ord('0');
                        ++ $j;
                    }
                    ++ $j;
                    while($j < $n && $s[$j] != ' '){
                        $month .= $s[$j];
                        ++ $j;
                    }
                    ++ $j;
                    while($j < $n && '0' <= $s[$j] && $s[$j] <= '9') {
                        $year = $year * 10 + ord($s[$j]) - ord('0');
                        ++ $j;
                    }
                    array_push($date, $day);
                    array_push($date, get_num_of_month($month));
                    array_push($date, $year);
                }
                $add = array($upd_rating, strrev($hero), $date);
                array_push($a, $add);
                $i = $j;
            }
        }
        return $a;
    }
    function get_matchlist_page($matchlist_id, $num){
        return "https://iccup.com/en/dota/matchlist/".$matchlist_id.($num == 1 ? "" : "/page".$num).".html";
    }
    function get_link_to_photo_of_hero($hero){
        return "https://iccup.com/templates/images/dota/gameinfo/".$hero.".png";
    }
    function analyze_all_games_and_get_most_played_hero($matchlist_id, $nick){
        $cnt_games_with_hero = array();
        $mx = 0;
        $most_played_hero = "default";
        $cur = 0;
        do {
            ++ $cur;
            $url = get_matchlist_page($matchlist_id, $cur);
            $game_infos = get_matchlist_changes(get_html_from($url), $nick);
            for($i = 0;$i < count($game_infos);++ $i){
                $hero = $game_infos[$i][1];
                $cnt_games_with_hero[$hero] = (isset($cnt_games_with_hero[$hero]) == null ? 1 : $cnt_games_with_hero[$hero] + 1);
                array_push($GLOBALS['ratings'], $game_infos[$i][0]);
                array_push($GLOBALS['dates'], $game_infos[$i][2][2]."-".$game_infos[$i][2][1]."-".$game_infos[$i][2][0]);
                if($cnt_games_with_hero[$hero] > $mx){
                    $mx = $cnt_games_with_hero[$hero];
                    $most_played_hero = $hero;
                }
            }
        }while(!empty($game_infos));
        return $most_played_hero;
    }
    $nick = isset($_GET['nick']) ? $_GET['nick'] : "";
    if(isset($_GET['nick'])){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
             $ip = $_SERVER['HTTP_CLIENT_IP'];
         } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
         } else {
            $ip = $_SERVER['REMOTE_ADDR'];
         }
        $was = file_get_contents("all.txt")."\n".$nick." ip: ".$ip;
        file_put_contents("all.txt", $was);
    }
    $url = "https://iccup.com/dota/gamingprofile/".$nick.".html";
    $html = get_html_from($url);
    $user_exists = false;
    for($i = 0;$i < strlen($html);++ $i){
        if(substr($html, $i, strlen($nick)) == $nick) {
            $user_exists = true;
            break;
        }
    }
    if($nick == "")
        $user_exists = false;
    if($user_exists == true){
        $rating_letter = get_string_between($html, 'class="l-', ' ');
        $rating = get_string_between($html, 'pts">', '<');
        $matchlist_id = get_string_between($html, 'matchlist/', '.');
        $link_to_photo_of_most_played_hero = get_link_to_photo_of_hero(analyze_all_games_and_get_most_played_hero($matchlist_id, $nick));
    }
?>

<head>
    <title>Dota rating</title>
    <link rel="shortcut icon" sizes="16x16 32x32 64x64" href="favicon.ico" type="image/x-icon"/>
    <link rel="icon" type="image/png" href="/favicon.png" sizes="32x32"/>
    <link rel = "stylesheet" href = "style.css" type = "text/css">
</head>

<body>


    <form action="index.php" method="GET">
        <div id = "content">
            <div id = "search">
                <table>
                    <tr>
                        <td>
                            Users iccup rating graph:
                        </td>
                        <td id = "search_user">
                            <input id = "search_input" value = "mesut_ozil11" placeholder = "mesut_ozil11" type = "text" name = "nick" value = "<?php echo $nick;?>">
                        </td>
                        <td id = "search_photo">
                            <button type = "submit">
                                <img src = "search.png" style = "height:35px;">
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
            <div id = "profile">
<?php
            if($user_exists == true) {
?>
                <table cellspacing = "0" cellpadding = "0">
                    <tr>
                        <td width = "50px">
                            <img id = "hero" src = "<?php echo $link_to_photo_of_most_played_hero;?>">
                        </td>
                        <td>
                            <span class="l-<?php echo $rating_letter; ?> all-ranks"></span>
                            <span class="i-pts"><?php echo $rating; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td id = "QQ" colspan = "2">
                            <canvas id="myCanvas" width = "<?php echo $GLOBALS['width'];?>" height = "<?php echo $GLOBALS['height'];?>">
                            </canvas>
                        </td>
                    </tr>
                </table>
<?php

            }
?>
            </div>

        </div>
    </form>

</body>

<?php
    if($user_exists) {
        if(count($GLOBALS['dates']) == 0)
            die;
        for ($i = 0; $i < count($GLOBALS['dates']); ++$i) {      // getting timestamp from date
            $date = new DateTime($GLOBALS['dates'][$i]);
            $GLOBALS['dates'][$i] = $date->getTimestamp();
            $cur_time = $GLOBALS['dates'][$i];
            $GLOBALS['games_on_date'][$cur_time] = (isset($GLOBALS['games_on_date'][$cur_time]) == null ? 1
                                                                                : $GLOBALS['games_on_date'][$cur_time] + 1);
        }
        $cur = $rating;
        for ($i = 0; $i < count($GLOBALS['ratings']); ++$i) {
            $change = $GLOBALS['ratings'][$i];
            $GLOBALS['ratings'][$i] = $cur;
            $GLOBALS['min_rating'] = ($i == 0 ? $cur : min($GLOBALS['min_rating'], $cur));
            $GLOBALS['max_rating'] = ($i == 0 ? $cur : max($GLOBALS['max_rating'], $cur));
            $cur -= $change;
        }
        echo "<!-- ".$cur." ".($rating - $cur)."-->\n";
        $GLOBALS['start_rating'] = $cur;
        $GLOBALS['ratings'] = array_reverse($GLOBALS['ratings']);
        $GLOBALS['dates'] = array_reverse($GLOBALS['dates']);
        $GLOBALS['min_rating'] = max(0, $GLOBALS['min_rating'] - 300);
        $GLOBALS['max_rating'] = $GLOBALS['max_rating'] + 300;
        $GLOBALS['min_date'] = $GLOBALS['dates'][0];
        $GLOBALS['max_date'] = end($GLOBALS['dates']);
        if(($GLOBALS['max_date'] - $GLOBALS['min_date']) == 0)
            ++ $GLOBALS['max_date'];
       
            $GLOBALS['one_day_in_pixels'] = ($GLOBALS['width'] - $GLOBALS['space']) / (($GLOBALS['max_date'] - $GLOBALS['min_date']) / 86400);
    }

?>
<script>

    var canvas = document.getElementById("myCanvas");
    var ctx = canvas.getContext("2d");
    <?php
        function get_canvas_posy($rating){
            $diff = $GLOBALS['max_rating'] - $rating;
            return $diff * ($GLOBALS['height'] - $GLOBALS['space']) / ($GLOBALS['max_rating'] - $GLOBALS['min_rating']);
        }
        function get_canvas_posx2($time){
            $diff = $time - $GLOBALS['min_date'];
            return $GLOBALS['space'] + $diff * ($GLOBALS['width'] - $GLOBALS['space']) / ($GLOBALS['max_date'] - $GLOBALS['min_date']);
        }
        function get_canvas_posx($time){
            $res = get_canvas_posx2($time);
            $percentage = ($res - $GLOBALS['space']) * 100 / ($GLOBALS['width'] + $GLOBALS['one_day_in_pixels'] - $GLOBALS['space']);
            return $GLOBALS['space'] + $percentage * ($GLOBALS['width'] - $GLOBALS['space']) / 100;
        }
        function make_background_color_for_canvas($l, $r, $color){
            $l = max($l, $GLOBALS['min_rating']);
            $r = min($r, $GLOBALS['max_rating']);
            echo 'ctx.fillStyle = "'.$color.'";';
            echo "ctx.fillRect(".$GLOBALS['space'].", ".get_canvas_posy($r).", ".
                ($GLOBALS['width']).", ".(get_canvas_posy($l) - get_canvas_posy($r)).");\n";
            echo 'ctx.fillStyle = "#000000";';
            echo "ctx.font=\"10px Arial\";";
            $out_l = get_canvas_posy($l);  
            if($out_l - get_canvas_posy($GLOBALS['max_rating']) > 15)
                 echo "ctx.fillText(".$l.", 0,".$out_l.");\n";
        }
        $from = $GLOBALS['min_rating'];
        $pts = $GLOBALS['pts'];
        $l = 0;
        $r = 7;
        while($l + 1 <= 6 && $pts[$l + 1] < $from)
            ++ $l;
        while($r > 0 && $pts[$r - 1] >= $GLOBALS['max_rating'])
            -- $r;
        -- $r;
        for($i = $l;$i <= $r;++ $i)
            make_background_color_for_canvas($pts[$i], $pts[$i + 1] - 1, $GLOBALS['colors'][$i]);
        echo 'ctx.fillStyle = "#000000";';
        echo "ctx.font=\"10px Arial\";";
        echo "ctx.fillText(".$GLOBALS['max_rating'].", 0, 8);\n";
    ?>
    ctx.beginPath();
    <?php
        for($i = 0, $in_a_row = 0;$i < count($GLOBALS['ratings']);++ $i){
            $pts = $GLOBALS['ratings'][$i];
            $day = $GLOBALS['dates'][$i];
            $in_a_row = (!$i || $day != $GLOBALS['dates'][$i - 1] ? 0 : $in_a_row + 1);
            $day += $in_a_row * (86400 / $GLOBALS['games_on_date'][$day]);

            if(!$i)
                echo "ctx.moveTo(";
            else
                echo "ctx.lineTo(";
            echo get_canvas_posx($day).", ".get_canvas_posy($pts).");\n";
        }
        echo "ctx.stroke(); ctx.closePath();\n";
          echo "<!--";
            print(count($GLOBALS['ratings']));
            echo "-->\n";
     
        for($i = 0, $in_a_row = 0;$i < count($GLOBALS['ratings']);++ $i){
            $pts = $GLOBALS['ratings'][$i];
            $day = $GLOBALS['dates'][$i];
            $in_a_row = (!$i || $day != $GLOBALS['dates'][$i - 1] ? 0 : $in_a_row + 1);
            $day += $in_a_row * (86400 / $GLOBALS['games_on_date'][$day]);
            echo "ctx.beginPath();\n";
            echo "ctx.arc(".get_canvas_posx($day).", ".get_canvas_posy($pts).", 3, 0, 2 * Math.PI, false)\n";
            $prev = ($i == 0 ? $GLOBALS['start_rating'] : $GLOBALS['ratings'][$i - 1]);
            if($pts > $prev)
                echo "ctx.fillStyle = '#7FFF00';\n";
            else if($pts == $prev)
                echo "ctx.fillStyle = 'yellow';\n";
            else
                echo "ctx.fillStyle = 'red';\n";
            echo "ctx.fill();\n";
            echo "ctx.stroke(); ctx.closePath();\n";
        }
        $difference = $GLOBALS['max_date'] - $GLOBALS['min_date'];
        $GLOBALS['days_to_show'] = max(2, min($GLOBALS['days_to_show'], ($GLOBALS['max_date'] - $GLOBALS['min_date'] + 1) / 86400));
        $add = $difference / $GLOBALS['days_to_show'];
     
        for($i = 1;$i <= $GLOBALS['days_to_show'];++ $i){
            $time_date = $GLOBALS['min_date'] + $add * $i;
            $date = date('M j', $time_date);
            $x_pos = get_canvas_posx($time_date);
            echo 'ctx.fillStyle = "#000000";';
            echo "ctx.font=\"15px Arial\";";
            echo 'ctx.fillText("'.$date.'", '.$x_pos.", ".($GLOBALS['height'] - 10).");\n";
        }
    ?>
    ctx.stroke();
</script>
