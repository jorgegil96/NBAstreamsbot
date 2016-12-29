<?php

/******************************************************************************
                      GAME THREAD BOT FOR /r/nbastreams
                                    v1.0
       Creates a Game Thread 1 hour before tip-off for every NBA game
******************************************************************************/

/**
* Returns a JSON object from an http source.
*/
function get_json_from_url($url) {
	return json_decode(file_get_contents($url));
}

/**
* Returns game time from a string formatted as:
* "CLE vs GSW (12/25/2016 2:30:00 PM)"
*/
function get_time_from_string($string) {
	$str = substr(substr($string, strpos($string, "(")), 
		strpos(substr($string, strpos($string, "(")), " ") + 1);

	return date("H:i:s", strtotime(substr($str, 0, strlen($str) - 1)));
}

/**
* Return true if $gametime is in the next 50-68 minutes, or false otherwise.
*/
function is_time_to_post($gametime) {
	$diff = (strtotime($gametime) - time()) / 60;
	echo $diff."<br>";
	return ($diff >= 50 && $diff <= 68);
}

/**
* Checks whether this game has already been posted to /r/nbastreams.
*/
function is_already_posted($gameID) {
	$gamesfile = fopen("games.txt", "r");
	if ($gamesfile) {
	    while (($line = fgets($gamesfile)) !== false) {
	        if ($line == $gameID."\n") {
	        	return true;
	        }
	    }
	    fclose($gamesfile);
	}
	return false;
}

/**
* Creates a text submission for the given match in /r/nbastreams
*/
function post_to_reddit($homeTeam, $visitorTeam, $gametime) {
	require_once("Phapper/src/phapper.php");

	$title = get_game_thread_title($homeTeam, $visitorTeam, $gametime);
	$description = "Rules:  \n
* Please only post links inside the appropriate thread, so as to keep things organized.  \n
* Always specify if your stream is HD, SD, etc. \n 
* Please do not ask for certain links to be removed. \n
* Do not ask for any links to be privately messaged";
	$flair_template_id = "318883ac-9b0b-11e6-95bb-0ec02502f634";
	$flair_text = "Game Thread";

	$r = new Phapper();
	$result = $r->submitTextPost("nbastreams", $title, $description, false, false);
	$r->setSuggestedSort($result->json->data->name, qa);
	$r->selectLinkFlair($result->json->data->name, $flair_template_id, $flair_text);
}

/**
* Saves gameID to games.txt to indicate that this games has already been posted
* to /r/nbatsreams.
*/
function save_game_as_posted($gameID) {
	$gamesfile = fopen("games.txt", "a");
	if ($gamesfile) {
		fwrite($gamesfile, $gameID.PHP_EOL);
	}
	fclose($gamesfile);
}

function get_game_thread_title($homeTeam, $visitorTeam, $gametime) {
	return "Game Thread: ".get_team_from_abbr($homeTeam)." vs ".
		get_team_from_abbr($visitorTeam)." (".$gametime." ET)";
}

function get_team_from_abbr($abbr) {
	switch ($abbr) {
		case 'ATL':
			return "Atlanta Hawks";
		case 'BKN':
			return "Brooklyn Nets";
		case 'BOS':
			return "Boston Celtics";
		case 'CHA':
			return "Charlotte Hornets";
		case 'CHI':
			return "Chicago Bulls";
		case 'CLE':
			return "Cleveland Cavaliers";
		case 'DAL':
			return "Dallas Mavericks";
		case 'DEN':
			return "Denver Nuggets";
		case 'DET':
			return "Detroit Pistons";
		case 'GSW':
			return "Golden State Warriors";
		case 'HOU':
			return "Houston Rockets";
		case 'IND':
			return "Indiana Pacers";
		case 'LAC':
			return "Los Angeles Clippers";
		case 'LAL':
			return "Los Angeles Lakers";
		case 'MEM':
			return "Memphis Grizzlies";
		case 'MIA':
			return "Miami Heat";
		case 'MIL':
			return "Milwaukee Bucks";
		case 'MIN':
			return "Minnesota Timberwolves";
		case 'NOP':
			return "New Orleans Pelicans";
		case 'NYK':
			return "New York Knicks";
		case 'OKC':
			return "Oklahoma City Thunder";
		case 'ORL':
			return "Orlando Magic";
		case 'PHI':
			return "Philadelphia 76ers";
		case 'PHX':
			return "Phoenix Suns";
		case 'POR':
			return "Portland Trail Blazers";
		case 'SAS':
			return "San Antonio Spurs";
		case 'SAC':
			return "Sacramento Kings";
		case 'TOR':
			return "Toronto Raptors";
		case 'UTA':
			return "Utah Jazz";
		case 'WAS':
			return "Washington Wizards";
		case 'ATL':
			return "Atla";
		default:
			return $abbr;
	}
}

function print_game_summary($gameID, $homeTeam, $visitorTeam, $gametime) {
	echo $gameID.": ".$homeTeam." @ ".$visitorTeam." (".$gametime.")<br>";
}


date_default_timezone_set('America/New_York');

$today = date("Ymd", time());

$DAILY_LINEUPS_URL = 'http://stats.nba.com/js/data/widgets/daily_lineups_'.$today.'.json';

$jsonObj = get_json_from_url($DAILY_LINEUPS_URL);

$results = $jsonObj->results;
foreach ($results as $game) {
	$gameID = $game->GameID;
	$homeTeam = $game->HomeTeam;
	$visitorTeam = $game->VisitorTeam;
	$gametime = get_time_from_string($game->Game);

	print_game_summary($gameID, $homeTeam, $visitorTeam, $gametime);
	
	if (is_time_to_post($gametime) && !is_already_posted($gameID)) {
		post_to_reddit($homeTeam, $visitorTeam, $gametime);
		save_game_as_posted($gameID);
	}
}
?>