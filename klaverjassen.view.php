<?php
/**
 *------
 * BGA framework: Â© Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Klaverjassen implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
 * Original Credits to the Belote game implementation: © David Bonnin <david.bonnin44@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * klaverjassen.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in klaverjassen_klaverjassen.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
require_once (APP_BASE_PATH . "view/common/game.view.php");

class view_klaverjassen_klaverjassen extends game_view

{
	function getGameName()
	{
		return "klaverjassen";
	}

	function set_translation_labels()
	{
		$this->tpl['MY_HAND'] = self::_("My Hand");
		$this->tpl['QUICK_REF'] = self::_("Quick Reference");
		$this->tpl['TRUMP'] = self::_("Trump suit");

		$this->tpl['GAME_OPTIONS'] = self::_("Game Options");

		$forcedOverTrump = $this->game->getGameStateValue('forcedOverTrump');
		$gameLength = $this->game->getGameStateValue('gameLength');
		$handsPlayed = $this->game->getGameStateValue('hands');
		$trumpSelection = $this->game->getGameStateValue('trumpSelection');

		$rotterdams = ($forcedOverTrump == 1);
		if ($rotterdams) {
			$this->tpl['CITY_VARIANT'] = self::_("Rotterdams");
		} else {
			$this->tpl['CITY_VARIANT'] = self::_("Amsterdams");
		}

		$this->tpl['GAME_LENGTH'] = $this->game->getGameLengthDisplay($handsPlayed, $gameLength);		
		
		if ($trumpSelection == 2) {
			$this->tpl['CARD_TO_TAKE'] = self::_("Mandatory play");
		} else {
			$this->tpl['CARD_TO_TAKE'] = self::_("Trump card");
		}

		$this->tpl['REF_TRUMP'] = self::_("Trump suit");
		$this->tpl['REF_OTHER'] = self::_("Other suits");
		$this->tpl['REF_ROEM'] = self::_("Roem (in trick)");
		$this->tpl['REF_POINTS'] = self::_("Points");

		$this->tpl['CARD_A'] = self::_("A");
		$this->tpl['CARD_K'] = self::_("K");
		$this->tpl['CARD_Q'] = self::_("Q");
		$this->tpl['CARD_J'] = self::_("J");
		$this->tpl['CARD_10'] = "10";
		$this->tpl['CARD_9'] = "9";
		$this->tpl['CARD_8'] = "8";
		$this->tpl['CARD_7'] = "7";

		$this->tpl['ROEM_SEQ3'] = self::_("Sequence of 3");
		$this->tpl['ROEM_SEQ4'] = self::_("Sequence of 4");
		$this->tpl['ROEM_4A'] = self::_("4 of") . " " . self::_("A");
		$this->tpl['ROEM_410'] = self::_("4 of") . " " . "10";
		$this->tpl['ROEM_4K'] = self::_("4 of") . " " . self::_("K");
		$this->tpl['ROEM_4Q'] = self::_("4 of") . " " . self::_("Q");
		$this->tpl['ROEM_4J'] = self::_("4 of") . " " . self::_("J");
		$this->tpl['ROEM_STUK'] = self::_("K") . " + " . self::_("Q") . " " . self::_("of trump (Stuk)");

		$this->tpl['VARIANT_ROTTERDAMS'] = self::_("Rotterdams");
		$this->tpl['VARIANT_AMSTERDAMS'] = self::_("Amsterdams");

		$this->tpl['ROTTERDAMS_FORCEDTRUMP'] = self::_("Always forced to trump");
		$this->tpl['AMSTERDAMS_NOUNDERTRUMP'] = self::_("Optional to trump partner, undertrumping is only allowed when it cannot be avoided");

		$this->tpl['NOTE_POINTS'] = self::_("Card values and the last trick (+10) add up to a total of 162 points.");
		$this->tpl['NOTE_ROEM'] = self::_("Sequences for Roem must be same suit and follow normal card order, so for example 8+9+10 or J+Q+K+A.");

	}

	function build_page($viewArgs)
	{

		// Get players & players number

		$players = $this->game->loadPlayersBasicInfos();
		$players_nbr = count($players);
		/*********** Place your code below:  ************/

		// Arrange players so that I am on south

		$player_to_dir = $this->game->getPlayersToDirection();
		$this->page->begin_block("klaverjassen_klaverjassen", "player");
		foreach($player_to_dir as $player_id => $dir) {
			$this->page->insert_block("player", array(
				"PLAYER_ID" => $player_id,
				"PLAYER_NAME" => $players[$player_id]['player_name'],
				"PLAYER_COLOR" => $players[$player_id]['player_color'],
				"DIR" => $dir
			));
		}

		// translate game labels
		$this->set_translation_labels();


		/*********** Do not change anything below this line  ************/
	}
}
