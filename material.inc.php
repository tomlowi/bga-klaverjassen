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
 * material.inc.php
 *
 * Klaverjassen game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

// Gives rank of cards, 1 being the weakest and 8 the strongest

$this->cardToRank = array(
	'trump' => array(
		7 => 1,
		8 => 2,
		9 => 7,
		10 => 5,
		11 => 8,
		12 => 3,
		13 => 4,
		14 => 6
	) ,
	'normal' => array(
		7 => 1,
		8 => 2,
		9 => 3,
		10 => 7,
		11 => 4,
		12 => 5,
		13 => 6,
		14 => 8
	)
);

// How many points are each card worth

$this->cardToPoints = array(
	'trump' => array(
		7 => 0,
		8 => 0,
		9 => 14,
		10 => 10,
		11 => 20,
		12 => 3,
		13 => 4,
		14 => 11
	) ,
	'normal' => array(
		7 => 0,
		8 => 0,
		9 => 0,
		10 => 10,
		11 => 2,
		12 => 3,
		13 => 4,
		14 => 11
	) ,
);

// Name of the suits/colors

$this->colors = array(
	1 => array(
		'name' => clienttranslate('spade'),
		'nametr' => self::_('spade'),
		'style' => 'black'
	) ,
	2 => array(
		'name' => clienttranslate('heart'),
		'nametr' => self::_('heart'),
		'style' => 'red'
	) ,
	3 => array(
		'name' => clienttranslate('club'),
		'nametr' => self::_('club'),
		'style' => 'black'
	) ,
	4 => array(
		'name' => clienttranslate('diamond'),
		'nametr' => self::_('diamond'),
		'style' => 'red'
	) ,
	5 => array(
		'name' => clienttranslate('joker') ,
		'nametr' => self::_('joker'),
		'style' => 'none'
	) 
);

// Reverse array from previous one

$this->nameToColors = array(
	'spade' => 1,
	'heart' => 2,
	'club' => 3,
	'diamond' => 4,
	'joker' => 5
);

// HTML Icons for suits/colors

$this->icons = array(
	1 => '<span style="color:black">'.json_decode('"' . '\u2660' . '"').'</span>' , //spade
	2 => '<span style="color:red">'.json_decode('"' . '\u2665' . '"').'</span>' , //heart
	3 => '<span style="color:black">'.json_decode('"' . '\u2663' . '"').'</span>' , //club
	4 => '<span style="color:red">'.json_decode('"' . '\u2666' . '"').'</span>' , //diamond
	5 => "joker",
	6 => '<span>'.json_decode('"' . '\uD83E\uDD29' . '"').'</span>' , // face star eyes = we got it
	7 => '<span>'.json_decode('"' . '\uD83E\uDD28' . '"').'</span>' , // face eyebrow raised = nat	
);

// Short label for each card value

$this->values_label = array(
	7 => '7',
	8 => '8',
	9 => '9',
	10 => '10',
	11 => clienttranslate('J') ,
	12 => clienttranslate('Q') ,
	13 => clienttranslate('K') ,
	14 => clienttranslate('A')
);
