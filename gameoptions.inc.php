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
 * gameoptions.inc.php
 *
 * Klaverjassen game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in klaverjassen.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    100 => array(
    'name' => totranslate('Game length'),    
    'values' => array(
        1 => array( 'name' => totranslate('1500 points') . ' ' . totranslate('(Classic)') , 
        'tmdisplay' => totranslate('1500 points') ),
        2 => array( 'name' => totranslate('750 points') . ' ' . totranslate('(Short)') ,
        'tmdisplay' => totranslate('750 points') ),
        11 => array( 'name' => totranslate('16 rounds') . ' ' . totranslate('(Classic)') ,
        'tmdisplay' => totranslate('16 rounds') ),
        12 => array( 'name' => totranslate('8 rounds') . ' ' . totranslate('(Short)') ,
        'tmdisplay' => totranslate('8 rounds') ),
        99 => array( 'name' => totranslate('1 round') . ' ' . totranslate('(Test)') ,
        'tmdisplay' => totranslate('1 round') )
        )
    ),
    
    101 => array(
            'name' => totranslate( 'Teams' ),
            'values' => array(
                    1 => array( 'name' => totranslate( 'By table order (1rst/3rd versus 2nd/4th)' )),
                    2 => array( 'name' => totranslate( 'By table order (1rst/2nd versus 3rd/4th)' )),
					3 => array( 'name' => totranslate( 'By table order (1rst/4th versus 2nd/3rd)' )),
                    4 => array( 'name' => totranslate( 'At random' ) ),
            ),
            'default' => 1
    ),

	110 => array(
		'name' => totranslate( 'City variant' ),
		'values' => array(
            1 => array( 'name' => totranslate('Rotterdams'), 
                'description' => totranslate('Always forced to trump'), 
                'tmdisplay' => totranslate('Rotterdams'), 
                'beta' => false),
            2 => array( 'name' => totranslate('Amsterdams'), 
                'description' => totranslate('Optional to trump partner, undertrumping is only allowed when it cannot be avoided'), 
                'tmdisplay' => totranslate('Amsterdams'), 
                'beta' => false),
		),
		'default' => 1
    ),
    
    120 => array(
		'name' => totranslate( 'Trump suit selection' ),
		'values' => array(
            1 => array( 'name' => totranslate('Classic'), 
                'description' => totranslate('Trump suit selected from draw pile, round pass possible'), 
                'tmdisplay' => totranslate('Trump suit draw pile'), 
                'beta' => false),
            2 => array( 'name' => totranslate('Mandatory'), 
                'description' => totranslate('Trump suit is determined by 1st player, who must play'), 
                'tmdisplay' => totranslate('Mandatory play'), 
                'beta' => false),
		),
		'default' => 1
    ),
    
    130 => array(
		'name' => totranslate( 'Jokers in draw pile' ),
		'values' => array(
            1 => array( 'name' => totranslate('Include Jokers'), 
                'description' => totranslate('Jokers are included in Trump suit draw pile, on Joker 1st player chooses trump'), 
                'tmdisplay' => totranslate('Include Jokers in Trump suit draw pile'), 
                'beta' => false),
            2 => array( 'name' => totranslate('Exclude Jokers'), 
                'description' => totranslate('Jokers are excluded from Trump suit draw pile'), 
                'tmdisplay' => totranslate('Exclude Jokers from Trump suit draw pile'), 
                'beta' => false),
		),
		'default' => 1
	),

    //  beta=true => this option is in beta version right now.
    //  nobeginner=true  =>  this option is not recommended for beginners

);

$game_preferences = [
    100 => [
        'name' => totranslate( 'Card styles' ), 
        'needReload' => true,
        'values' => [
            0 => [ 'name' => totranslate( 'English' ) ],
            1 => [ 'name' => totranslate( 'Dutch' ) ]
        ]
    ]
];
