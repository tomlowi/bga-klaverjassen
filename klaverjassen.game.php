<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Klaverjassen implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
 * Original Credits to the Belote game implementation: © David Bonnin <david.bonnin44@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * klaverjassen.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */
require_once (APP_GAMEMODULE_PATH . 'module/table/table.game.php');

// Local constants
//  - Team pairing options

define("TEAM_1_3", 1); // By table order (1rst/3rd versus 2nd/4th)
define("TEAM_1_2", 2); // By table order (1rst/2nd versus 3rd/4th)
define("TEAM_1_4", 3); // By table order (1rst/4th versus 2nd/3rd)
define("TEAM_RANDOM", 4); // At random
class Klaverjassen extends Table

{
	function __construct()
	{

		// Your global variables labels:
		//  Here, you can assign labels to global variables you are using for this game.
		//  You can use any number of global variables with IDs between 10 and 99.
		//  If your game has options (variants), you also have to associate here a label to
		//  the corresponding ID in gameoptions.inc.php.
		// Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue

		parent::__construct();
		self::initGameStateLabels(array(
			"trickColor" => 11,
			"trickWinner" => 12,
			"trumpColor" => 13,
			"winningCard" => 14,
			"dealer" => 15,
			"firstPlayer" => 16,
			"lastTrickTakenBy" => 17,	// last trick scores bonus points
			"stukScoredBy" => 18, 		// "stuk" = Queen + King of trump suit
			"taker" => 19,
			"passCount" => 20,
			"cardOnTop" => 21,
			"firstRoundTrumpColor" => 22,
			"hands" => 26,
			"trickRoem" => 30, 			// "roem" = special sequences/combinations give bonus
			"gameLength" => 100,
			"playerTeams" => 101,
			"forcedOverTrump" => 110,	// 1 = Rotterdams, 2 = Amsterdams
			"trumpSelection" => 120,	// 1 = Classic draw pile, 2 = Mandatory play
			"includeJokers" => 130,	// 1 = Yes, 2 = No, only for Classic draw pile
			"lastCardAutomatic" => 140,	// 0 = No, 1 = Yes

		));
		$this->cards = self::getNew("module.common.deck");
		$this->cards->init("card");

		$this->trumpCards = self::getNew("module.common.deck");
		$this->trumpCards->init("trumpCard");
	}

	protected
	function getGameName()
	{
		return "klaverjassen";
	}

	/*
	setupNewGame:
	This method is called only once, when a new game is launched.
	In this method, you must setup the game according to the game rules, so that
	the game is ready to be played.
	*/
	protected
	function setupNewGame($players, $options = array())
	{

		// Set the colors of the players with HTML color code

		$default_colors = array(
			"000000",
			"ff0000",
			"000000",
			"ff0000"
		);
		$start_points = 0;
		$end_game = 0;
		$gameLengthOption = self::getGameStateValue('gameLength');
		if ($gameLengthOption == 1) {
			$end_game = 1500;
		}
		else
		if ($gameLengthOption == 2) {
			$end_game = 750;
		}
		else
		if ($gameLengthOption == 11) {
			$end_game = 16;
		}
		else
		if ($gameLengthOption == 12) {
			$end_game = 8;
		}
		else
		if ($gameLengthOption == 99) {
			$end_game = 1;
		}
		else {
			throw new BgaVisibleSystemException("Error, unsupported gameLength value " . $gameLengthOption);
		}

		// Create players
		// Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.

		$sql = "INSERT INTO player (player_id, player_no, player_score, player_color, player_canal, player_name, player_avatar) VALUES ";
		$values = array();
		$counter = 0;
		$random_dealer = bga_rand(1, 4);
		$order_values = array();
		foreach($players as $player_id => $player) {
			$order_values[] = $player["player_table_order"];
		}

		sort($order_values);
		$position = array();
		foreach($order_values as $key => $val) {
			$position[$val] = $key + 1;
		}

		foreach($players as $player_id => $player) {
			$color = "ffffff"; // Default to white (should never be left to white unless the following doesn't work)
			$player_no = 9; // Default to 9 (should never be left to 9 unless the following doesn't work)
			$counter++;
			if (self::getGameStateValue('playerTeams') == TEAM_RANDOM) {
				$color = array_shift($default_colors); // Random since the $players order is random
				$player_no = $counter;
			}
			else
			if (isset($player["player_table_order"])) {

				// By default TEAM_1_3

				$table_order = $position[$player["player_table_order"]];

				// If TEAM_1_2 swap 2 and 3

				if (self::getGameStateValue('playerTeams') == TEAM_1_2) {
					$table_order = ($table_order == 2 ? 3 : ($table_order == 3 ? 2 : $table_order));
				} // If TEAM_1_4 swap 4 and 3
				else
				if (self::getGameStateValue('playerTeams') == TEAM_1_4) {
					$table_order = ($table_order == 3 ? 4 : ($table_order == 4 ? 3 : $table_order));
				}

				if (isset($default_colors[$table_order - 1])) {
					$color = $default_colors[$table_order - 1];
					$player_no = ($table_order >= $random_dealer ? // Adjust player_no for randomizing first player (dealer)
					$table_order - $random_dealer + 1 : 4 - ($random_dealer - $table_order) + 1);
				}
			}

			$values[] = "('" . $player_id . "','" . $player_no . "','$start_points','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
		}

		$sql.= implode(',', $values);
		self::DbQuery($sql);
		self::reloadPlayersBasicInfos();
		/************ Start the game initialization *****/

		// No trick color, trick winner, trump color, trick winning card

		self::setGameStateInitialValue('trickColor', -1);
		self::setGameStateInitialValue('trickWinner', -1);
		self::setGameStateInitialValue('trumpColor', -1);
		self::setGameStateInitialValue('winningCard', -1);

		// No one has got the "last trick bonus" or "stuk" or "roem" yet

		self::setGameStateInitialValue('lastTrickTakenBy', 0);
		self::setGameStateInitialValue('stukScoredBy', 0);
		self::setGameStateInitialValue('trickRoem', 0);

		// No one has taken the visible card, and no one has passed

		self::setGameStateInitialValue('taker', -1);
		self::setGameStateInitialValue('passCount', 0);

		// Visible card not initialized yet

		self::setGameStateInitialValue('cardOnTop', -1);

		// Total number of played hands = 0

		self::setGameStateInitialValue('hands', 0);

		// Score to be obtained to end the game

		self::setGameStateInitialValue('gameLength', $end_game);

		// Init game statistics

		self::initStat("table", "passedHandNbr", 0);
		self::initStat("table", "playedHandNbr", 0);
		self::initStat("player", "takenFirstNbr", 0);
		self::initStat("player", "takenSecondNbr", 0);
		self::initStat("player", "jokerNbr", 0);
		self::initStat("player", "wonHandNbr", 0);
		self::initStat("player", "natHandNbr", 0);
		self::initStat("player", "marsNbr", 0);
		self::initStat("player", "lastTrickTakenNbr", 0);
		self::initStat("player", "stukNbr", 0);
		self::initStat("player", "roemTotal", 0);
		self::initStat("player", "averageScore", 0.0);

		// Create cards

		$cards = array(); //(0 => 0);
		foreach($this->colors as $color_id => $color) // spade, heart, diamond, club
		{
			if ($color_id < 5) {
				for ($value = 7; $value <= 14; $value++) //  7, 8, 9, ... K, A
				{
					$cards[] = array(
						'type' => $color_id,
						'type_arg' => $value,
						'nbr' => 1
					);
				}
			}
		}

		$excludeJokers = self::getGameStateValue('includeJokers') == 2;
		// setup the draw pile
		$drawTrumpCards = array();
		$maxJokerValue = 4; // include 3 jokers = value 2..4
		if ($excludeJokers) {
			$maxJokerValue = 0; // exclude all jokers
		}

		foreach($this->colors as $color_id => $color) // spade, heart, diamond, club + jokers
		{
			if ($color_id <= 5) {
				for ($value = 2; $value <= 6; $value++) //  2 ... 6
				{
					if ($color_id < 5 || $value <= $maxJokerValue)
					{
						$drawTrumpCards[] = array(
							'type' => $color_id,
							'type_arg' => $value,
							'nbr' => 1
						);						
					}
				}
			}
		}

		$this->cards->createCards($cards, 'deck');
		$this->trumpCards->createCards($drawTrumpCards, 'trumpsDeck');
		$this->trumpCards->shuffle('trumpsDeck');
		$this->activeNextPlayer();
		$currentDealer = self::getActivePlayerId();
		self::setGameStateInitialValue('dealer', $currentDealer);
		self::setGameStateInitialValue('firstPlayer', $currentDealer);
		$this->activeNextPlayer();

		$this->trumpCards->shuffle('trumpsDeck');

		/************ End of the game initialization *****/
	}

	/*
	getAllDatas:
	Gather all informations about current game situation (visible by the current player).
	The method is called each time the game interface is displayed to a player, ie:
	_ when the game starts
	_ when a player refreshes the game page (F5)
	*/
	protected
	function getAllDatas()
	{
		$result = array(
			'players' => array()
		);
		
		$result['handsPlayed'] = self::getGameStateValue('hands');
		$result['trump'] = self::getGameStateValue('trumpColor');
		$result['dealer'] = self::getGameStateValue('dealer');
		$result['taker'] = self::getGameStateValue('taker');
		$result['gameLength'] = self::getGameStateValue('gameLength');
		$cardOnTop_id = self::getGameStateValue('cardOnTop');
		$firstRoundTrumpColor = self::getGameStateValue('firstRoundTrumpColor');
		if ($cardOnTop_id != - 1) {
			$cardOnTop = $this->trumpCards->getCard($cardOnTop_id);
			$result['cardOnTop_id'] = $cardOnTop['id'];
			$result['cardOnTop_color'] = $cardOnTop['type'];
			$result['cardOnTop_val'] = $cardOnTop['type_arg'];
		}
		else {
			$result['cardOnTop_id'] = - 1;
			$result['cardOnTop_color'] = - 1;
			$result['cardOnTop_val'] = - 1;
		}

		$current_player_id = self::getCurrentPlayerId(); // !! We must only return informations visible by this player !!

		// Get information about players

		$sql = "SELECT player_id id, player_score score, player_tricks tricks, player_roem roem FROM player ";
		$result['players'] = self::getCollectionFromDb($sql);

		$result['stukScoredBy'] = self::getGameStateValue('stukScoredBy');

		// Cards in player hand
		$result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

		// Cards played on the table
		$result['cardsontable'] = $this->cards->getCardsInLocation('cardsontable');

		return $result;
	}

	/*
	getGameProgression:
	Compute and return the current game progression.
	The number returned must be an integer beween 0 (=the game just started) and
	100 (= the game is finished or almost finished).
	This method is called each time we are in a game state with the "updateGameProgression" property set to true
	(see states.inc.php)
	*/
	function getGameProgression()
	{
		$end = self::getGameStateValue('gameLength');
		// < 100 : we are playing for this number of rounds/hands
		// >= 100 : we are playing until some team reaches this many points 

		if ($end < 100) {
			$handsPlayed = self::getGameStateValue('hands');
			$res = ($handsPlayed / $end) * 100;
		} else {
			$maximumScore = self::getUniqueValueFromDb("SELECT MAX( player_score ) FROM player");
			$minimumScore = self::getUniqueValueFromDb("SELECT MIN( player_score ) FROM player");

			if ($maximumScore >= $end) {
				return 100;
			}

			if ($maximumScore <= 0) {
				return 0;
			}

			$n = 2 * ($end - $maximumScore);
			$res = (100 * ($maximumScore + $minimumScore)) / ($n + $maximumScore + $minimumScore);
		}

		return max(0, min(100, $res)); // Note: 0 => 100
	}

	// ////////////////////////////////////////////////////////////////////////////
	// ////////// Utility functions
	// //////////

	// Game Length option displayed (in points or number of rounds/hands to play)
	function getGameLengthDisplay($handsPlayed, $gameLength)
	{
		// < 100 : we are playing for this number of rounds/hands
		// >= 100 : we are playing until some team reaches this many points 
		if ($gameLength < 100) {
			return $handsPlayed . ' / ' . $gameLength;
		} else {
			return $gameLength;
		}
	}

	// Return players => direction (N/S/E/W) from the point of view
	//  of current player (current player must be on south)

	function getPlayersToDirection()
	{
		$result = array();
		$players = self::loadPlayersBasicInfos();
		$nextPlayer = self::createNextPlayerTable(array_keys($players));
		$current_player = self::getCurrentPlayerId();
		// default: clockwise order
		$directions = array(
			'S',
			'W',
			'N',
			'E'
		);

		if (!isset($nextPlayer[$current_player])) {

			// Spectator mode: take any player for south

			$player_id = $nextPlayer[0];
			$result[$player_id] = array_shift($directions);
		}
		else {

			// Normal mode: current player is on south

			$player_id = $current_player;
			$result[$player_id] = array_shift($directions);
		}

		while (count($directions) > 0) {
			$player_id = $nextPlayer[$player_id];
			$result[$player_id] = array_shift($directions);
		}

		return $result;
	}

	function isCardStronger($card1, $card2, $isTrump)
	{
		if ($isTrump) {
			return ($this->cardToRank['trump'][$card1] > $this->cardToRank['trump'][$card2]);
		}
		else {
			return ($this->cardToRank['normal'][$card1] > $this->cardToRank['normal'][$card2]);
		}
	}

	function checkCardsForStuk($cards, $trumpColor)
	{
		$hasTrumpQ = 0;
		$hasTrumpK = 0;
		foreach($cards as $card)
		{
			if ($card['type'] == $trumpColor && $card['type_arg'] == 12) {
				$hasTrumpQ = 1;
			}
			if ($card['type'] == $trumpColor && $card['type_arg'] == 13) {
				$hasTrumpK = 1;
			}
		}

		return $hasTrumpQ * $hasTrumpK;
	}

	function checkCardsForRoem($cards)
	{
		// first order cards by color/suit and value
		usort($cards, function($a, $b) {
			$sortByColor = $a['type'] <=> $b['type'];
			$retVal = $sortByColor;
			if ($sortByColor == 0) {
				$sortByValue = $a['type_arg'] <=> $b['type_arg'];
				$retVal = $sortByValue;
			}
			return $retVal;
		});
		// check for 4-of-a-kind of Jacks
		// check for 4-of-a-kind of A, K, Q, or 10
		// check for same-color sequences like 7-8-9, 10-J-Q-K, ...
		$nrOfJacks = 0;
		$nrOfAces = 0;
		$nrOfTens = 0;
		$nrOfQueens = 0;
		$nrOfKings = 0;

		$sequenceLength = 0;
		$longestSequence = 0;

		$previousColor = -1;
		$previousValue = -1;

		foreach($cards as $card){
			$currentColor = $card['type'];
			$currentValue = $card['type_arg'];

			if ($currentValue == 10){
				$nrOfTens++;
			}
			if ($currentValue == 11){
				$nrOfJacks++;
			}
			if ($currentValue == 12){
				$nrOfQueens++;
			}
			if ($currentValue == 13){
				$nrOfKings++;
			}
			if ($currentValue == 14){
				$nrOfAces++;
			}

			// keep track of longest sequence
			// as soon as color changes or not correct next card in sequence, restart
			if ($currentColor == $previousColor && $currentValue == $previousValue+1) {
				$sequenceLength++;
			} else {
				if ($sequenceLength > $longestSequence){
					$longestSequence = $sequenceLength;
				}
				$sequenceLength = 1;
			}

			$previousColor = $currentColor;
			$previousValue = $currentValue;
		}

		// check again if last sequence is the longest
		if ($sequenceLength > $longestSequence){
			$longestSequence = $sequenceLength;
		}

		$totalRoem = 0;
		if ($nrOfJacks == 4) {
			$totalRoem += 200;
		}
		if ($nrOfAces == 4) {
			$totalRoem += 100;
		}
		if ($nrOfTens == 4) {
			$totalRoem += 100;
		}
		if ($nrOfKings == 4) {
			$totalRoem += 100;
		}
		if ($nrOfQueens == 4) {
			$totalRoem += 100;
		}
		if ($longestSequence == 4) {
			$totalRoem += 50;
		}
		if ($longestSequence == 3) {
			$totalRoem += 20;
		}

		return $totalRoem;
	}

	function drawpileEmptyCheck() {
		if ($this->trumpCards->countCardInLocation('trumpsDeck') == 0)
		{
			$this->trumpCards->moveAllCardsInLocation('trumpsDiscard', 'trumpsDeck');
			$this->trumpCards->shuffle('trumpsDeck');
		}
	}

	function drawNextTrumpCard()
	{
		$cardOnTop_id = self::getGameStateValue('cardOnTop');
		$trumpColor = self::getGameStateValue('trumpColor');

		$mandatoryPlay = self::getGameStateValue('trumpSelection') == 2;
		$nextTrumpColor = -1;

		if ($mandatoryPlay) {
			$nextTrumpColor = 5; // always force "joker", first player chooses trump
			self::setGameStateValue('cardOnTop', -1);
			self::setGameStateValue('trumpColor', $nextTrumpColor);
		}
		// initial game start: always clubs (hence "klaverjassen"), so no trump card is drawn
		else if ($cardOnTop_id == -1 && $trumpColor == -1){
			$nextTrumpColor = 3;
			self::setGameStateValue('trumpColor', $nextTrumpColor);
		} else {
		//else, draw the next card from the trumps deck
			if ($cardOnTop_id > -1)	{ // discard current trump card
				$this->trumpCards->insertCardOnExtremePosition($cardOnTop_id, 'trumpsDiscard', true);
			}
			$this->drawpileEmptyCheck();
			// new trump selection is done using the low cards 2-6
			$topCard = $this->trumpCards->getCardOnTop('trumpsDeck');
			$nextTrumpColor = $topCard['type'];

			self::setGameStateValue('cardOnTop', $topCard['id']);
			self::setGameStateValue('trumpColor', $nextTrumpColor);
			self::notifyAllPlayers('cardOnTop', '', array(
				'card_id' => $topCard['id'],
				'card_color' => $nextTrumpColor,
				'card_val' => $topCard['type_arg']
			));
		}

		// always: notify about the next trump color
		self::notifyAllPlayers('newTrump',
			clienttranslate('Trump suit is ${card_type}'),
			array(
			'card_color' => $nextTrumpColor,
			'card_type' => $this->icons[$nextTrumpColor]
		));
	}

	function listCardsForNotification($cards) {

		$values_by_color = array();
        foreach($cards as $card) {
            $color = $card['type'];
            $value = $card['type_arg'];
            if (array_key_exists($color, $values_by_color)) {
                $values_by_color[$color][] = $value;
            }
            else {
                $values_by_color[$color] = array($value);
            }
        }
        
        $colors_log_as_array = array();
        $colors_args = array();
        ksort($values_by_color);
        foreach($values_by_color as $color => $values) {
            sort($values);
            $color_key = 'color_'.$color;
            $colors_log_as_array[] = '${'.$color_key.'}';
            
            $values_log_as_array = array();
            $values_args = array();
            $i = 1;
            foreach($values as $value) {
                $value_key = 'card_'.$color.'_'.$value;
                $values_log_as_array[] = '${'.$value_key.'}';
                $values_args[$value_key] = array('log' => ($i == 1 ? '${color_symbol} ${value_symbol}' : '${value_symbol}'), 'args' => array(
                    'value_symbol' => $this->values_label[$value],
                    'color_symbol' => $this->icons[$color]));
                $i++;
			}
			$styleColor = $this->colors[$color]['style'];
            $values_log = '<span style="color:' . $styleColor . '">' . join(' ', $values_log_as_array) . '</span>';
            $colors_args[$color_key] = array('log' => $values_log, 'args' => $values_args);
        }
        $colors_log = join('&nbsp;<br />', $colors_log_as_array);// NOI18N
        
        return array('log' => $colors_log, 'args' => $colors_args);
    }

	// ////////////////////////////////////////////////////////////////////////////
	// ////////// Player actions
	// //////////

	/*
	Each time a player is doing some game action, one of the methods below is called.
	(note: each method below must match an input method in klaverjassen.action.php)
	*/

	// Play a card from player hand

	public function playCard($card_id)
	{
		self::checkAction("playCard");
		$this->_playCard($card_id);
	}

	private function playCardAutomatic($card_id)
	{
		// cannot checkAction here,
		// although the active player is already set correctly in stPlayerTurn,
		// the current player would still be the one that played the last card
		// on the previous before last trick, so check would fail
		$this->_playCard($card_id);
	}

	private function _playCard($card_id)
	{
		$player_id = self::getActivePlayerId();

		// Get all cards in player hand
		// (note: we must get ALL cards in player's hand in order to check if the card played is correct)
		$playerhands = array();
		$playerhands = $this->cards->getCardsInLocation('hand', $player_id);
		$players = self::loadPlayersBasicInfos();
		$nextPlayer = self::createNextPlayerTable(array_keys($players));
		$currentTrickColor = self::getGameStateValue('trickColor');
		$currentTrickWinner = self::getGameStateValue('trickWinner');
		$currentTrumpColor = self::getGameStateValue('trumpColor');
		$currentWinningCard = self::getGameStateValue('winningCard');
		if ($currentWinningCard != - 1) $currentWinningCard = $this->cards->getCard($currentWinningCard);

		$bIsWinnerPartner = ($currentTrickWinner == $nextPlayer[$nextPlayer[$player_id]]);

		// Check that the card is in his hand
		$bIsInHand = false;
		$currentCard = null;
		$bAtLeastOneCardOfCurrentTrickColor = false;
		$bAtLeastOneCardOfCurrentTrickColorOfGreaterValue = false; // Used for all trumps
		$bAtLeastOneCardTrump = false;
		$bAtLeastOneCardNotTrump = false;
		$bAtLeastOneCardTrumpOfGreaterValue = false;
		foreach($playerhands as $card) {
			if ($card['id'] == $card_id) {
				$bIsInHand = true;
				$currentCard = $card;
			}

			if ($card['type'] == $currentTrickColor) {
				$bAtLeastOneCardOfCurrentTrickColor = true;
				if ($currentTrumpColor == 6 /*All trumps */ && $currentWinningCard > 0) {
					if ($this->isCardStronger($card['type_arg'], $currentWinningCard['type_arg'], true)) {
						$bAtLeastOneCardOfCurrentTrickColorOfGreaterValue = true;
					}
				}
			}

			if ($card['type'] != $currentTrumpColor) {
				$bAtLeastOneCardNotTrump = true;
			}

			if ($card['type'] == $currentTrumpColor) {
				$bAtLeastOneCardTrump = true;
				if ($currentWinningCard > 0)
				if ($currentWinningCard['type'] == $currentTrumpColor && $this->isCardStronger($card['type_arg'], $currentWinningCard['type_arg'], true)) {
					$bAtLeastOneCardTrumpOfGreaterValue = true;
				}
			}
		}

		if (!$bIsInHand) throw new BgaUserException("This card is not in your hand");
		if ($currentTrickColor == - 1) {

			// You can play any card

		}
		else {

			// The trick started before => we must check the color

			if ($bAtLeastOneCardOfCurrentTrickColor) {

				// he has to play a card of current trick color, and has at least one

				if ($currentCard['type'] != $currentTrickColor) {
					$cardColor = $this->colors[$currentTrickColor]['nametr'];
					throw new BgaUserException(sprintf(self::_("You must play a %s"), $cardColor ));
				}
			}

			if ($bAtLeastOneCardTrump) {

				$forcedOverTrump = self::getGameStateValue('forcedOverTrump');
				$bMustOverTrump = ($forcedOverTrump == 1); // Rotterdams forces to always (over)trump
				$bAllowUnderTrump = ($forcedOverTrump == 1); // Amsterdams does not allow undertrump

				// he has no card of current color, and he has a trump
				// if his partner is not winning, or if playing Rotterdams variant
				// -> he has to play a trump

				if ((!$bAtLeastOneCardOfCurrentTrickColor) && $currentCard['type'] != $currentTrumpColor
					&& (!$bIsWinnerPartner || $bMustOverTrump)) {

					$bMustPlayTrump = true;
					// another exception: if playing Amsterdams, undertrump is not allowed
					// so if trick is currently being won by a high trump and you have only lower trumps,
					// you are not forced to play a trump card
					if ($currentWinningCard > 0 && $currentWinningCard['type'] == $currentTrumpColor
						&& !$bAllowUnderTrump && !$bAtLeastOneCardTrumpOfGreaterValue) {
						$bMustPlayTrump = false;
					}

					if ($bMustPlayTrump) {
						$trumpColorName = $this->colors[$currentTrumpColor]['nametr'];
						throw new BgaUserException(sprintf(self::_("You must play a Trump card (%s)"), $trumpColorName ));
					}
				}

				// he has to use a trump and owns a trump stronger than the strongest winning card
				// -> he has to play such a trump

				else
				if (
					( (!$bAtLeastOneCardOfCurrentTrickColor && (!$bIsWinnerPartner || $bMustOverTrump))
						|| ($currentTrickColor == $currentTrumpColor) )
						&& $bAtLeastOneCardTrumpOfGreaterValue
						&& $currentWinningCard > 0 && $currentWinningCard['type'] == $currentTrumpColor
						&& !($this->isCardStronger($currentCard['type_arg'], $currentWinningCard['type_arg'], true))
				) {
					$cardValue = $this->values_label[$currentWinningCard['type_arg']];
					$cardColor = $this->colors[$currentTrumpColor]['nametr'];
					throw new BgaUserException(sprintf(self::_("You must play a Trump card stronger than %s of %s"), $cardValue, $cardColor ));
				}

				// if playing Amsterdams, undertrump is not allowed
				// so if you still have any other cards, you are not allowed to play a lower value trump
				else if (!$bAllowUnderTrump
					&& $currentTrickColor != $currentTrumpColor
					&& $currentCard['type'] == $currentTrumpColor
					&& $currentWinningCard > 0 && $currentWinningCard['type'] == $currentTrumpColor
					&& !($this->isCardStronger($currentCard['type_arg'], $currentWinningCard['type_arg'], true))
					&& ($bAtLeastOneCardNotTrump || $bAtLeastOneCardTrumpOfGreaterValue)) {
						throw new BgaUserException(sprintf(self::_("You are not allowed to undertrump as long as you have other cards") ));
				}
			}
		}

		// Checks are done! now we can play our card
		$this->cards->moveCard($card_id, 'cardsontable', $player_id);


		// Set the trick color if it hasn't been set yet
		if ($currentTrickColor == - 1) self::setGameStateValue('trickColor', $currentCard['type']);
		if ($currentWinningCard == - 1) {
			self::setGameStateValue('winningCard', $card_id);
			self::setGameStateValue('trickWinner', $player_id);
		}
		else
		if (($currentCard['type'] == $currentWinningCard['type'] && $this->isCardStronger($currentCard['type_arg'], $currentWinningCard['type_arg'], $currentCard['type'] == $currentTrumpColor || $currentTrumpColor == 6 /*All trumps*/)) || ($currentCard['type'] == $currentTrumpColor && $currentWinningCard['type'] != $currentTrumpColor)) {
			self::setGameStateValue('winningCard', $card_id);
			self::setGameStateValue('trickWinner', $player_id);
		}

		// And notify
		self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${card_value}${card_type}') , array(
			'i18n' => array(
				'card_value'
			) ,
			'card_id' => $card_id,
			'player_id' => $player_id,
			'player_name' => self::getActivePlayerName() ,
			'value' => $currentCard['type_arg'],
			'color' => $currentCard['type'],
			'card_value' => $this->values_label[$currentCard['type_arg']],
			'card_type' => $this->icons[$currentCard['type']]
		));

		// Next player

		$this->gamestate->nextState('playCard');
	}

	function commonAccept($player_id, $trumpColor)
	{
		self::setGameStateValue('hands', 1 + self::getGameStateValue('hands'));
		self::setGameStateValue('taker', $player_id);
		self::setGameStateValue('trumpColor', $trumpColor);

		$gameLengthDisplay = self::getGameLengthDisplay(
			self::getGameStateValue('hands'), self::getGameStateValue('gameLength')
		);
		self::notifyAllPlayers('handsPlayed', '', array(
			'gameLengthDisplay' => $gameLengthDisplay
		));
	}

	function acceptFirstRound()
	{
		self::checkAction("acceptFirstRound");
		$players = self::loadPlayersBasicInfos();
		$player_id = self::getActivePlayerId();
		$topCard = $this->trumpCards->getCard(self::getGameStateValue('cardOnTop'));
		$trumpColor = self::getGameStateValue('trumpColor');
		self::commonAccept($player_id, $trumpColor);
		self::incStat(1, "takenFirstNbr", $player_id);
		self::notifyAllPlayers('takeCard', clienttranslate('${player_name} plays with trump ${card_type}') , array(
			'player_id' => $player_id,
			'player_name' => $players[$player_id]['player_name'],
			'card_type' => $this->icons[$trumpColor],
		));
		$this->gamestate->nextState('accept');
	}

	function pass()
	{
		$player_id = self::getActivePlayerId();
		$players = self::loadPlayersBasicInfos();
		self::notifyAllPlayers('pass', clienttranslate('${player_name} passes') , array(
			'player_id' => $player_id,
			'player_name' => $players[$player_id]['player_name']
		));
		$passCount = self::getGameStateValue('passCount');
		$passCount++;
		self::setGameStateValue('passCount', $passCount);
		$this->gamestate->nextState('pass');
	}

	function passFirstRound()
	{
		self::checkAction("passFirstRound");
		self::pass();
	}

	function passSecondRound()
	{
		self::checkAction("passSecondRound");
		self::pass();
	}

	function startJokerFirstRound($color)
	{
		self::checkAction("startJokerFirstRound");

		$players = self::loadPlayersBasicInfos();
		$player_id = self::getActivePlayerId();

		self::commonAccept($player_id, $color);
		self::incStat(1, "jokerNbr", $player_id);
		self::notifyAllPlayers('takeCard', clienttranslate('${player_name} chooses ${trump_icon} as the trump suit') , array(
			'player_id' => $player_id,
			'player_name' => $players[$player_id]['player_name'],
			'trump_icon' => $this->icons[$color],
			'trump' => $color
		));
		self::notifyAllPlayers('newTrump', '', array(
			'card_color' => $color
		));
		$this->gamestate->nextState('accept');
	}

	function acceptSecondRound()
	{
		self::checkAction("acceptSecondRound");

		$players = self::loadPlayersBasicInfos();
		$player_id = self::getActivePlayerId();
		$topCard = $this->trumpCards->getCard(self::getGameStateValue('cardOnTop'));

		$trumpColor = self::getGameStateValue('trumpColor');
		self::commonAccept($player_id, $trumpColor);
		self::incStat(1, "takenSecondNbr", $player_id);
		self::notifyAllPlayers('takeCard', clienttranslate('${player_name} plays with trump ${card_type}') , array(
			'player_id' => $player_id,
			'player_name' => $players[$player_id]['player_name'],
			'card_type' => $this->icons[$trumpColor],
		));
		$this->gamestate->nextState('accept');
	}

	function startJokerSecondRound($color)
	{
		self::checkAction("startJokerSecondRound");

		$players = self::loadPlayersBasicInfos();
		$player_id = self::getActivePlayerId();
		$firstRoundTrumpColor = self::getGameStateValue('firstRoundTrumpColor');
		if ($firstRoundTrumpColor == $color) {
			throw new BgaUserException(self::_("You cannot choose the same Trump as the trump card passed in the first round"));
		}

		if($color > 4) {
			throw new BgaUserException(self::_("You must choose a valid Trump suit"));
		}

		self::commonAccept($player_id, $color);
		self::incStat(1, "jokerNbr", $player_id);
		self::notifyAllPlayers('takeCard', clienttranslate('${player_name} chooses ${trump_icon} as the Trump suit') , array(
			'player_id' => $player_id,
			'player_name' => $players[$player_id]['player_name'],
			'trump_icon' => $this->icons[$color],
			'trump' => $color
		));
		self::notifyAllPlayers('newTrump', '', array(
			'card_color' => $color
		));
		$this->gamestate->nextState('accept');
	}


	// ////////////////////////////////////////////////////////////////////////////
	// ////////// Game state arguments
	// //////////

	/*
	Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
	These methods function is to return some additional information that is specific to the current
	game state.
	*/
	/*
	Example for game state "MyGameState":
	function argMyGameState()
	{

	// Get some values from the current game situation in database...
	// return values:

	return array(
	'variable1' => $value1,
	'variable2' => $value2,
	...
	);
	}

	*/

	// ////////////////////////////////////////////////////////////////////////////
	// ////////// Game state actions
	// //////////

	/*
	Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
	The action method of state X is called everytime the current game state is set to X.
	*/
	function stNewHand()
	{
		self::setGameStateValue('taker', -1);

		// Take back all cards (from any location => null) to deck

		$this->cards->moveAllCardsInLocation(null, "deck");
		$this->cards->shuffle('deck');
		$players = self::loadPlayersBasicInfos();
		$currentDealer = self::getGameStateValue('dealer');
		if ($currentDealer != - 1) {
			self::notifyAllPlayers('dealCards', clienttranslate('${player_name} deals new hands') , array(
				'player_id' => $currentDealer,
				'player_name' => $players[$currentDealer]['player_name']
			));
		}
		else {
			throw new BgaVisibleSystemException("Error, no one is the dealer");
		}

		self::notifyAllPlayers('startingNewHand', '', array());

		// trump selection is done using the low cards 2-6
		//self::setGameStateValue('trumpColor', 3);
		self::drawNextTrumpCard();
		self::setGameStateValue('firstRoundTrumpColor', self::getGameStateValue('trumpColor'));

		// Deal 8 cards to each players, in groups of 3 - 2 - 3
		// Create deck, shuffle it and give initial cards

		// first 3 cards
		foreach($players as $player_id => $player) {
			$sql = "UPDATE player SET player_tricks=0, player_roem=0
					WHERE player_id='$player_id' ";
			self::DbQuery($sql);
			$cards = $this->cards->pickCards(3, 'deck', $player_id);

			// Notify player about his cards
			self::notifyPlayer($player_id, 'newHand', '', array(
				'cards' => $cards
			));
		}
		// next 2 cards
		foreach($players as $player_id => $player) {
			$cards = $this->cards->pickCards(2, 'deck', $player_id);
			$cards = $this->cards->getPlayerHand($player_id);
			self::notifyPlayer($player_id, 'fillHand', '', array(
				'cards' => $cards
			));
		}
		// final 3 cards
		foreach($players as $player_id => $player) {
			$cards = $this->cards->pickCards(3, 'deck', $player_id);
			$cards = $this->cards->getPlayerHand($player_id);
			self::notifyPlayer($player_id, 'fillHand', '', array(
				'cards' => $cards
			));
		}

		self::setGameStateValue('passCount', 0);
		self::setGameStateValue('stukScoredBy', 0);

		$trumpColor = self::getGameStateValue('trumpColor');
		if ($trumpColor == 5)
		 	$this->gamestate->nextState("joker");
		else
			$this->gamestate->nextState("normal");
	}

	function stNewRound()
	{
		self::setGameStateValue('taker', -1);

		// next trump selection
		$firstRoundTrumpColor = self::getGameStateValue('firstRoundTrumpColor');
		// new trump selection is done using the low cards 2-6
		self::drawNextTrumpCard();
		$nextTrumpColor = self::getGameStateValue('trumpColor');
		// if same trump color that everyone passed 1st round,
		// try one more trump card
		if ($nextTrumpColor == $firstRoundTrumpColor)
		{
			self::drawNextTrumpCard();
			$nextTrumpColor = self::getGameStateValue('trumpColor');
		}
		// if still the same, bad luck, round pass and deal again
		if ($nextTrumpColor == $firstRoundTrumpColor){
			$this->gamestate->nextState("nextHand");
		}
		// else, allow everyone to play or pass again
		else {

			if ($nextTrumpColor == 5)
				$this->gamestate->nextState("joker");
			else
				$this->gamestate->nextState("normal");
		}
	}

	function stNextPlayerFirstRound()
	{
		$passCount = self::getGameStateValue('passCount');
		$player_id = self::activeNextPlayer();
		self::giveExtraTime($player_id);
		if ($passCount >= 4) { // everyone passed once
			$this->gamestate->nextState("nextRound");
		}
		else {
			$this->gamestate->nextState("nextPlayer");
		}
	}

	function stNextPlayerSecondRound()
	{
		$passCount = self::getGameStateValue('passCount');
		if ($passCount >= 8) { // everyone passed twice
			$this->gamestate->nextState("newHand");
		}
		else {
			$player_id = self::activeNextPlayer();
			self::giveExtraTime($player_id);
			$this->gamestate->nextState("nextPlayer");
		}
	}

	function stNewDeal()
	{
		self::incStat(1, "passedHandNbr");
		$players = self::loadPlayersBasicInfos();
		$nextPlayer = self::createNextPlayerTable(array_keys($players));
		self::notifyAllPlayers('noDeal', clienttranslate('No one wants to play.') , array());
		$currentDealer = self::getGameStateValue('dealer');
		$currentDealer = $nextPlayer[$currentDealer];
		self::setGameStateValue('dealer', $currentDealer);
		$this->gamestate->changeActivePlayer($nextPlayer[$currentDealer]);
		$this->gamestate->nextState("");
	}

	function stFinishDealing()
	{
		self::incStat(1, "playedHandNbr");
		$players = self::loadPlayersBasicInfos();
		$currentDealer = self::getGameStateValue('dealer');
		$trumpColor = self::getGameStateValue('trumpColor');

		$nextPlayer = self::createNextPlayerTable(array_keys($players));
		$this->gamestate->changeActivePlayer($nextPlayer[$currentDealer]);
		$this->gamestate->nextState("");
	}

	function stNewTrick()
	{
		self::setGameStateValue('trickColor', -1);
		self::setGameStateValue('trickWinner', -1);
		self::setGameStateValue('winningCard', -1);

		self::setGameStateValue('trickRoem', 0);

		$this->gamestate->nextState();
	}

	function stPlayerTurn()
	{
		// normally, nothing needs to happen here:
		// just go to client and wait for player to select a card to play

		// but if this option is selected, and players have only 1 card left in hand
		// => play it automatically to speed up the last trick
		$lastCardAutomatic = self::getGameStateValue('lastCardAutomatic') == 1;
		if (!$lastCardAutomatic) return;

		$player_id = self::getActivePlayerId();

		$nrCardsInHand = $this->cards->countCardsInLocation('hand', $player_id);
		if ($nrCardsInHand == 1) 
		{
			$cardsInHand = $this->cards->getCardsInLocation('hand', $player_id);
			$card_id = array_keys($cardsInHand)[0];
			$this->playCardAutomatic($card_id);
		}
	}

	function stNextPlayer()
	{

		// Active next player OR end the trick and go to the next trick OR end the hand
		if ($this->cards->countCardInLocation('cardsontable') >= 4) {

			// This is the end of the trick

			$best_value_player_id = self::getGameStateValue('trickWinner');
			if ($best_value_player_id == - 1) throw new BgaVisibleSystemException("Error, nobody wins the trick");

			// Move all cards to "cardswon" of the given player
			$trickCards = $this->cards->getCardsInLocation('cardsontable');
			$this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

			// check if current trick contains "stuk"
			$trumpColor = self::getGameStateValue('trumpColor');
			$hasStuk = self::checkCardsForStuk($trickCards, $trumpColor);
			if ($hasStuk == 1)
			{
				self::setGameStateValue('stukScoredBy', $best_value_player_id);
			}
			// check for "roem" in the current trick cards
			// assign bonus points to the winning player
			$roemInTrick = self::checkCardsForRoem($trickCards);

			$tricks = 0;
			// Update number of tricks won during this hand and roem collected
			$sql = "UPDATE player SET player_tricks=player_tricks+1,
					player_roem=player_roem+$roemInTrick
					WHERE player_id='$best_value_player_id' ";
			self::DbQuery($sql);
			$tricks = self::getUniqueValueFromDb("SELECT player_tricks FROM player WHERE player_id='$best_value_player_id' ");
			$roemForPlayer = self::getUniqueValueFromDb("SELECT player_roem FROM player WHERE player_id='$best_value_player_id' ");
			$stukScoredBy = self::getGameStateValue('stukScoredBy');
			$stukForPlayer = $stukScoredBy == $best_value_player_id;
			if ($stukForPlayer)
				$roemForPlayer += 20;

			// Notify
			// Note: we use 2 notifications here so we can pause the display during the first notification
			//  before we move all cards to the winner (during the second)

			$players = self::loadPlayersBasicInfos();
			$nextPlayer = self::createNextPlayerTable(array_keys($players));
			self::notifyAllPlayers('trickWin', clienttranslate('${player_name} wins the trick:&nbsp;<br />${cards_in_trick}') , array(
				'player_id' => $best_value_player_id,
				'player_name' => $players[$best_value_player_id]['player_name'],
				'tricks' => $tricks,
				'roemForPlayer' => $roemForPlayer,
				'cards_in_trick' => self::listCardsForNotification($trickCards),
			));
			if ($hasStuk > 0) {
				self::incStat(1, "stukNbr", $best_value_player_id);
				self::notifyAllPlayers('stukScored', clienttranslate('${player_name} scores 20 bonus points for King+Queen ${trump_icon}') , array(
					'player_id' => $best_value_player_id,
					'player_name' => $players[$best_value_player_id]['player_name'],
					'trump_icon' => $this->icons[$trumpColor]
				));
			}
			if ($roemInTrick > 0) {
				$roemToCall = $roemInTrick;
				if ($hasStuk > 0) $roemToCall += 20;
				self::incStat($roemInTrick, "roemTotal", $best_value_player_id);
				self::notifyAllPlayers('roemScored', clienttranslate('${player_name} scores ${roem} bonus points for card combination') , array(
					'player_id' => $best_value_player_id,
					'player_name' => $players[$best_value_player_id]['player_name'],
					'roem' => $roemInTrick,
					'roemToCall' => $roemToCall,
				));
			}
			self::notifyAllPlayers('giveAllCardsToPlayer', '', array(
				'player_id' => $best_value_player_id
			));

			if ($this->cards->countCardInLocation('hand') == 0) {
				self::notifyAllPlayers('lastTrickTakenBy', clienttranslate('${player_name} gets the last trick') , array(
					'player_id' => $best_value_player_id,
					'player_name' => $players[$best_value_player_id]['player_name']
				));
				self::setGameStateValue('lastTrickTakenBy', $best_value_player_id);
				self::incStat(1, "lastTrickTakenNbr", $best_value_player_id);
				$currentDealer = self::getGameStateValue('dealer');
				$currentDealer = $nextPlayer[$currentDealer];
				self::setGameStateValue('dealer', $currentDealer);
				$this->gamestate->changeActivePlayer($nextPlayer[$currentDealer]);

				// End of the hand

				$this->gamestate->nextState("endHand");
			}
			else {

				// Active this player => he's the one who starts the next trick

				$this->gamestate->changeActivePlayer($best_value_player_id);

				// End of the trick

				$this->gamestate->nextState("nextTrick");
			}
		}
		else {

			// Standard case (not the end of the trick)
			// => just active the next player

			$player_id = self::activeNextPlayer();
			self::giveExtraTime($player_id);
			$this->gamestate->nextState('nextPlayer');
		}

	}

	function stEndHand()
	{

		// Count and score points, then end the game or go to the next hand.

		$taker = self::getGameStateValue('taker');
		$hands = self::getGameStateValue('hands');
		$players = self::loadPlayersBasicInfos();
		$currentTrumpColor = self::getGameStateValue('trumpColor');
		$nextPlayer = self::createNextPlayerTable(array_keys($players));
		$first_player_id = self::getGameStateValue('firstPlayer');
		$second_player_id = $nextPlayer[$first_player_id];
		$third_player_id = $nextPlayer[$second_player_id];
		$fourth_player_id = $nextPlayer[$third_player_id];
		$player_to_team = array();
		$player_to_team[$first_player_id] = 1;
		$player_to_team[$second_player_id] = 2;
		$player_to_team[$third_player_id] = 1;
		$player_to_team[$fourth_player_id] = 2;
		$first_player = $players[$first_player_id];
		$second_player = $players[$second_player_id];
		$third_player = $players[$third_player_id];
		$fourth_player = $players[$fourth_player_id];
		$team_to_points = array(
			1 => 0,
			2 => 0
		); // Regular points
		$team_to_stuk = array(
			1 => 0,
			2 => 0
		); // Bonus points for Stuk = Q+K of trump
		$team_to_roem = array(
			1 => 0,
			2 => 0
		); // Bonus points for Roem = special sequences/combinations in tricks
		$team_to_lasttrickbonus = array(
			1 => 0,
			2 => 0
		); // last trick bonus
		$team_to_total = array(
			1 => 0,
			2 => 0
		); // sum of previous 3
		$team_to_score = array(
			1 => 0,
			2 => 0
		); // Total Score for team can be changed by
		   // - Mars (one team has all tricks)
		   // - Nat (taking team fails to score at least half of the points + 1)
		$team_no_trick = array(
			1 => true,
			2 => true
		); // To check for Mars
		$cards = $this->cards->getCardsInLocation("cardswon");
		foreach($cards as $card) // Count regular points
		{
			$player_id = $card['location_arg'];
			$team_id = $player_to_team[$player_id];
			$team_no_trick[$team_id] = false; // At least one card = no mars

			if ($card['type'] == $currentTrumpColor) {
				$team_to_points[$team_id]+= $this->cardToPoints['trump'][$card['type_arg']];
			}
			else {
				$team_to_points[$team_id]+= $this->cardToPoints['normal'][$card['type_arg']];
			}
		}

		// add bonus points for team that took the last trick
		$lastTrickTakenBy = self::getGameStateValue('lastTrickTakenBy');
		$team_to_lasttrickbonus[$player_to_team[$lastTrickTakenBy]]+= 10;

		// add bonus points from "stuk"
		$stukScoredBy = self::getGameStateValue('stukScoredBy');
		if ($stukScoredBy > 0) {
			$team_to_stuk[$player_to_team[$stukScoredBy]]+= 20;
		}

		// add bonus point from "roem" collected during player tricks
		$roem1stPlayer = self::getUniqueValueFromDb("SELECT player_roem FROM player WHERE player_id='$first_player_id' ");
		$roem2ndPlayer = self::getUniqueValueFromDb("SELECT player_roem FROM player WHERE player_id='$second_player_id' ");
		$roem3rdPlayer = self::getUniqueValueFromDb("SELECT player_roem FROM player WHERE player_id='$third_player_id' ");
		$roem4thPlayer = self::getUniqueValueFromDb("SELECT player_roem FROM player WHERE player_id='$fourth_player_id' ");

		$team_to_roem[$player_to_team[$first_player_id]]+= $roem1stPlayer;
		$team_to_roem[$player_to_team[$second_player_id]]+= $roem2ndPlayer;
		$team_to_roem[$player_to_team[$third_player_id]]+= $roem3rdPlayer;
		$team_to_roem[$player_to_team[$fourth_player_id]]+= $roem4thPlayer;


		$team_to_total[1] = $team_to_points[1] + $team_to_lasttrickbonus[1]
			+ $team_to_stuk[1] + $team_to_roem[1] ;
		$team_to_total[2] = $team_to_points[2] + $team_to_lasttrickbonus[2]
			+ $team_to_stuk[2] + $team_to_roem[2] ;
		$team_to_score[1] = $team_to_total[1];
		$team_to_score[2] = $team_to_total[2];
		if ($team_to_total[1] < $team_to_total[2]) {
			self::incStat(1, "wonHandNbr", $second_player_id);
			self::incStat(1, "wonHandNbr", $fourth_player_id);
		}
		else
		if ($team_to_total[2] < $team_to_total[1]) {
			self::incStat(1, "wonHandNbr", $first_player_id);
			self::incStat(1, "wonHandNbr", $third_player_id);
		}

		// Log the hand result points in the game log
		// (in turn-based mode, new hand might already be started by 1 player,
		// so another player would not so the score dialog when reconnecting)

		$takerTeam = $player_to_team[$taker]; // who played? team 1 or team 2
		$opponentTeam = 3 - $player_to_team[$taker]; // 1=>2, 2=>1

		$total_taker = $team_to_total[$takerTeam];
		$total_opponents = $team_to_total[$opponentTeam];

		$takerResultIcon = ($total_taker <= $total_opponents) ? 7 : 6; // emote for "nat" or "made it"

		if ($team_to_stuk[$takerTeam] + $team_to_roem[$takerTeam] > 0
			|| $team_to_stuk[$opponentTeam] + $team_to_roem[$opponentTeam] > 0) {
			self::notifyAllPlayers('handResult', clienttranslate('${player_name} result: ${taker_score} (+ ${taker_extra}) vs ${opponent_score} (+ ${opponent_extra})  ${emote}') , array(
				'player_name' => $players[$taker]['player_name'],
				'taker_score' => $team_to_points[$takerTeam] + $team_to_lasttrickbonus[$takerTeam],
				'taker_extra' => $team_to_stuk[$takerTeam] + $team_to_roem[$takerTeam],
				'opponent_score' => $team_to_points[$opponentTeam] + $team_to_lasttrickbonus[$opponentTeam],
				'opponent_extra' => $team_to_stuk[$opponentTeam] + $team_to_roem[$opponentTeam],
				'emote' => $this->icons[$takerResultIcon],
			));
		} else {
			self::notifyAllPlayers('handResult', clienttranslate('${player_name} result: ${taker_score} vs ${opponent_score} ${emote}') , array(
				'player_name' => $players[$taker]['player_name'],
				'taker_score' => $team_to_points[$takerTeam] + $team_to_lasttrickbonus[$takerTeam],
				'opponent_score' => $team_to_points[$opponentTeam] + $team_to_lasttrickbonus[$opponentTeam],
				'emote' => $this->icons[$takerResultIcon],
			));
		}

		// Test for special cases

		if ($team_no_trick[1]) { // No tricks for team 1, Mars played by Team 2 !
			$team_to_score[2]+= 100;
			self::incStat(1, "marsNbr", $second_player_id);
			self::incStat(1, "marsNbr", $fourth_player_id);
			self::notifyAllPlayers('mars', clienttranslate('Team ${second_player_name} and ${fourth_player_name} get all tricks (Mars) !') , array(
				'second_player_name' => $second_player['player_name'],
				'fourth_player_name' => $fourth_player['player_name'],
				'player_A' => $second_player_id,
				'player_B' => $fourth_player_id
			));
		}
		else
		if ($team_no_trick[2]) { // // No tricks for team 2, Mars played by Team 1 !
			$team_to_score[1]+= 100;
			self::incStat(1, "marsNbr", $first_player_id);
			self::incStat(1, "marsNbr", $third_player_id);
			self::notifyAllPlayers('mars', clienttranslate('Team ${first_player_name} and ${third_player_name} get all tricks (Mars) !') , array(
				'first_player_name' => $first_player['player_name'],
				'third_player_name' => $third_player['player_name'],
				'player_A' => $first_player_id,
				'player_B' => $third_player_id
			));
		}

		// taker team needs at least half of points + 1, or fails (goes "nat")
		// in which case ALL points are transferred to the opposing team
		if ($total_taker <= $total_opponents) { // "nat"
			$team_to_score[3 - $player_to_team[$taker]]
				+= $team_to_score[$player_to_team[$taker]];
			$team_to_score[$player_to_team[$taker]] = 0;
			if ($first_player_id == $taker || $third_player_id == $taker) {
				self::incStat(1, "natHandNbr", $first_player_id);
				self::incStat(1, "natHandNbr", $third_player_id);
				self::notifyAllPlayers('nat', clienttranslate('Team ${first_player_team} and ${second_player_team} fails to score enough points (Nat)!') , array(
					'first_player_team' => $first_player['player_name'],
					'second_player_team' => $third_player['player_name'],
					'player_A' => $second_player_id,
					'player_B' => $fourth_player_id
				));
			}
			else
			if ($second_player_id == $taker || $fourth_player_id == $taker) {
				self::incStat(1, "natHandNbr", $second_player_id);
				self::incStat(1, "natHandNbr", $fourth_player_id);
				self::notifyAllPlayers('nat', clienttranslate('Team ${first_player_team} and ${second_player_team} fails to score enough points (Nat)!') , array(
					'first_player_team' => $second_player['player_name'],
					'second_player_team' => $fourth_player['player_name'],
					'player_A' => $first_player_id,
					'player_B' => $third_player_id
				));
			}
			else {

				// error no taker

			}
		}

		// Apply scores to player

		foreach($players as $player_id => $player) {
			$points = $team_to_score[$player_to_team[$player_id]];
			if ($points != 0) {
				$sql = "UPDATE player SET player_score=player_score+$points
                        WHERE player_id='$player_id' ";
				self::DbQuery($sql);
			}
		}

		// ////////// Display table window with results /////////////////

		$table = array();

		// Header line

		$firstRow = array(
			''
		);
		$firstRow[] = array(
			'str' => 'Team ${first_player_name} and ${third_player_name}',
			'args' => array(
				'first_player_name' => $first_player['player_name'],
				'third_player_name' => $third_player['player_name']
			) ,
			'type' => 'header'
		);
		$firstRow[] = array(
			'str' => 'Team ${second_player_name} and ${fourth_player_name}',
			'args' => array(
				'second_player_name' => $second_player['player_name'],
				'fourth_player_name' => $fourth_player['player_name']
			) ,
			'type' => 'header'
		);
		$table[] = $firstRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('Regular Points') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_points[1];
		$newRow[] = $team_to_points[2];
		$table[] = $newRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('Last Trick Bonus') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_lasttrickbonus[1];
		$newRow[] = $team_to_lasttrickbonus[2];
		$table[] = $newRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('King + Queen of trump Bonus (Stuk)') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_stuk[1];
		$newRow[] = $team_to_stuk[2];
		$table[] = $newRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('Card combinations Bonus (Roem)') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_roem[1];
		$newRow[] = $team_to_roem[2];
		$table[] = $newRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('Total Points') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_total[1];
		$newRow[] = $team_to_total[2];
		$table[] = $newRow;

		// Points

		$newRow = array(
			array(
				'str' => clienttranslate('Score of the hand') ,
				'args' => array()
			)
		);
		$newRow[] = $team_to_score[1];
		$newRow[] = $team_to_score[2];
		$table[] = $newRow;
		// 'tableWindow' is a standard BGA notification to display results.
		// but we want to be able to re-display the hand results on click in the game log,
		// because in turn-based games people will often not see tableWindow results as they disappear when new hand dealt (= new state)
		$handResult = array(
			"id" => 'finalScoring',
			"title" => clienttranslate("Result of this hand"),
			"table" => $table,
			"footer" => '',
			"closing" => clienttranslate( "Close" )
		);
		//$this->notifyAllPlayers("tableWindow", '', $handResult);
		$this->notifyAllPlayers("scoreTableDetails", clienttranslate('Result details: ${handResult}'), array(
			'hand' => $hands,
			'handResult' => $handResult
		));
		
		$newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true);
		self::notifyAllPlayers("newScores", '', array(
			'newScores' => $newScores
		));

		// /// Test if this is the end of the game
		$endGameForRounds = false;
		$endGameForPoints = false;		

		$gameLength = self::getGameStateValue('gameLength');
		$handsPlayed = self::getGameStateValue('hands');
		$maxScore = $gameLength;
		if ($gameLength < 100) {
			// end game after nr of rounds / hands played
			$endGameForRounds = ($handsPlayed >= $gameLength);
		} else {
			// or end game when nr of points reached
			foreach($newScores as $player_id => $score) {
				if ($score >= $maxScore) {
					$endGameForPoints = true;
				}
			}
		}

		$endGameTrigger = $endGameForRounds || $endGameForPoints;
				
		if ($endGameTrigger) {
			// Set last stat : average score

			foreach($players as $player_id => $player) {
				$avgScore = $newScores[$player_id] * 1.0 / $hands;
				self::setStat($avgScore, "averageScore", $player_id);
			}

			$team1Score = $newScores[$first_player_id];
			$team2Score = $newScores[$second_player_id];
			// Special case: when playing for points,
			// if both teams go over the end score during the same hand,
			// the "taking" team should win even if it has less points!
			if ($endGameForPoints && $team1Score >= $maxScore && $team2Score >= $maxScore){
				$takerHasWonLastRound = ($total_taker > $total_opponents);
				$takerTeam = $player_to_team[$taker];
				if ($takerHasWonLastRound && $takerTeam == 1 && $team1Score <= $team2Score) {
					// force score of team2 down to allow team1 to be marked as winner
					$team2Score = $maxScore-1;
					$sql = "UPDATE player SET player_score=$team2Score
							WHERE player_id in ('$second_player_id', '$fourth_player_id') ";
					self::DbQuery($sql);
				}
				else if ($takerHasWonLastRound && $takerTeam == 2 && $team2Score <= $team1Score) {
					// force score of team1 down to allow team2 to be marked as winner
					$team1Score = $maxScore-1;
					$sql = "UPDATE player SET player_score=$team1Score
							WHERE player_id in ('$first_player_id', '$third_player_id') ";
					self::DbQuery($sql);
				}
			}
			// After correction check, declare the winner
			if ($team1Score > $team2Score) {
				self::notifyAllPlayers('gameWin', clienttranslate('Team ${first_player_name} and ${third_player_name} wins !') , array(
					'first_player_name' => $first_player['player_name'],
					'third_player_name' => $third_player['player_name']
				));
			}
			else {
				self::notifyAllPlayers('gameWin', clienttranslate('Team ${second_player_name} and ${fourth_player_name} wins !') , array(
					'second_player_name' => $second_player['player_name'],
					'fourth_player_name' => $fourth_player['player_name']
				));
			}

			// Trigger the end of the game !
			$this->gamestate->nextState("endGame");
			return;
		}
	

		// Otherwise... new hand !

		$this->gamestate->nextState("nextHand");
	}






	/*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

	function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345


        // if( $from_version <= 1612019999 )
        // {
		// 	try{
		// 		$sql = "ALTER TABLE `player` ADD `player_tricks` INT UNSIGNED NOT NULL DEFAULT '0';";
		// 		self::DbQuery( $sql );
		// 	}catch(Exception $e){}
		// 	try{
		// 		$sql = "UPDATE player SET player_tricks=0";
		// 		self::DbQuery( $sql );
		// 	}catch(Exception $e){}
        // }

		// if( $from_version <= 1612022005 )
        // {
        //     try{
        //         $sql = "ALTER TABLE `zz_replay1_player` ADD `player_tricks` INT UNSIGNED NOT NULL DEFAULT '0';";
        //         self::DbQuery( $sql );
        //     }catch(Exception $e){}
        //     try{
        //         $sql = "ALTER TABLE `zz_replay2_player` ADD `player_tricks` INT UNSIGNED NOT NULL DEFAULT '0';";
        //         self::DbQuery( $sql );
        //     }catch(Exception $e){}
        //     try{
        //         $sql = "ALTER TABLE `zz_replay3_player` ADD `player_tricks` INT UNSIGNED NOT NULL DEFAULT '0';";
        //         self::DbQuery( $sql );
        //     }catch(Exception $e){}
        // }

    }


	// ////////////////////////////////////////////////////////////////////////////
	// ////////// Zombie
	// //////////

	/*
	zombieTurn:
	This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
	You can do whatever you want in order to make sure the turn of this player ends appropriately
	(ex: pass).
	*/
	function stZombiePlayCard()
	{
		$player_id = self::getActivePlayerId();
		$currentCard = - 1;
		$playerhands = $this->cards->getCardsInLocation('hand', $player_id);
		$currentTrickColor = self::getGameStateValue('trickColor');
		$currentTrumpColor = self::getGameStateValue('trumpColor');
		$currentWinningCard = self::getGameStateValue('winningCard');
		if ($currentWinningCard != - 1) $currentWinningCard = $this->cards->getCard($currentWinningCard);
		$cardOfTrickColor = - 1;
		$bestTrump = - 1;
		foreach($playerhands as $card) {
			if ($currentCard < 0) {
				$currentCard = $card;
			}

			if ($card['type'] == $currentTrickColor && $currentTrickColor != $currentTrumpColor) {
				if (($currentTrumpColor != 6) || ($cardOfTrickColor < 0) || ($currentTrumpColor == 6 && $cardOfTrickColor >= 0 && $this->isCardStronger($card['type_arg'], $currentTrickColor['type_arg'], true))) {
					$cardOfTrickColor = $card;
					$currentCard = $card;
				}
			}

			if ($card['type'] == $currentTrumpColor && $cardOfTrickColor < 0) {
				if ($bestTrump < 0 || ($this->isCardStronger($card['type_arg'], $bestTrump['type_arg'], true))) {
					$bestTrump = $card;
					$currentCard = $card;
				}
			}
		}

		$card_id = $currentCard['id'];

		// Checks are done! now we can play our card

		$this->cards->moveCard($card_id, 'cardsontable', $player_id);

		// Set the trick color if it hasn't been set yet

		if ($currentTrickColor == - 1) self::setGameStateValue('trickColor', $currentCard['type']);
		if ($currentWinningCard == - 1) {
			self::setGameStateValue('winningCard', $card_id);
			self::setGameStateValue('trickWinner', $player_id);
		}
		else
		if (($currentCard['type'] == $currentWinningCard['type'] && $this->isCardStronger($currentCard['type_arg'], $currentWinningCard['type_arg'], $currentCard['type'] == $currentTrumpColor || $currentTrumpColor == 6)) || ($currentCard['type'] == $currentTrumpColor && $currentWinningCard['type'] != $currentTrumpColor)) {
			self::setGameStateValue('winningCard', $card_id);
			self::setGameStateValue('trickWinner', $player_id);
		}

		// And notify

		self::notifyAllPlayers('playCard', clienttranslate('${player_name} plays ${card_value}${card_type}') , array(
			'i18n' => array(
				'card_value'
			) ,
			'card_id' => $card_id,
			'player_id' => $player_id,
			'player_name' => self::getActivePlayerName() ,
			'value' => $currentCard['type_arg'],
			'color' => $currentCard['type'],
			'card_value' => $this->values_label[$currentCard['type_arg']],
			'card_type' => $this->icons[$currentCard['type']]
		));

		// Next player

		$this->gamestate->nextState("");
	}

	function stZombiePassFirstRound()
	{
		self::pass();
	}

	function stZombiePassSecondRound()
	{
		self::pass();
	}

	function zombieTurn($state, $active_player)
	{
		$statename = $state['name'];
		if ($state['type'] == "game") {

			// Should not happen

			return;
		}

		if ($state['type'] == "activeplayer") {
			switch ($statename) {
			case "firstRound":
				$this->gamestate->nextState("zombiePass");
				break;

			case "secondRound":
				$this->gamestate->nextState("zombiePass");
				break;

			case "playerTurn":
				$this->gamestate->nextState("zombiePass");
				break;

			default:
				$this->gamestate->nextState("zombiePass");
				break;
			}

			return;
		}

		if ($state['type'] == "multipleactiveplayer") {

			// Make sure player is in a non blocking status for role turn

			$sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
			self::DbQuery($sql);
			$this->gamestate->updateMultiactiveOrNextState('');
			return;
		}

		throw new BgaUserException("Zombie mode not supported at this game state: " . $statename);
	}
}






