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
 * states.inc.php
 *
 * Klaverjassen game states description
 *
 */

/*
  Game state machine is a tool used to facilitate game development by doing common stuff that can be set up
  in a very easy way from this configuration file.

  Please check the BGA Studio presentation about game state to understand this, and associated documentation.

  Summary:

  States types:
  _ activeplayer: in this type of state, we expect some action from the active player.
  _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
  _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
  _ manager: special type for initial and final state

  Arguments of game states:
  _ name: the name of the GameState, in order you can recognize it on your own code.
  _ description: the description of the current game state is always displayed in the action status bar on
  the top of the game. Most of the time this is useless for game state with "game" type.
  _ descriptionmyturn: the description of the current game state when it's your turn.
  _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
  _ action: name of the method to call when this game state become the current game state. Usually, the
  action method is prefixed by "st" (ex: "stMyGameStateName").
  _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
  method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
  _ transitions: the transitions are the possible paths to go from a game state to another. You must name
  transitions in order to use transition names in "nextState" PHP method, and use IDs to
  specify the next game state for each transition.
  _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
  client side to be used on "onEnteringState" or to set arguments in the gamestate description.
  _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
  method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 20 )
    ),
    

    /// New hand
    20 => array(
        "name" => "newHand",
        "description" => clienttranslate("Starting a new hand..."),
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,   
        "transitions" => array( "normal" => 21, "joker" => 51 )
    ),  

    /// New round
    50 => array(
        "name" => "newRound",
        "description" => clienttranslate("Starting a new round..."),
        "type" => "game",
        "action" => "stNewRound",
        "updateGameProgression" => true,   
        "transitions" => array( "normal" => 23, "joker" => 52, "nextHand" => 20 )
    ),  

    // First round, players can choose to play with the visible trump card
    // If joker has been drawn as trump card, first player must select trump color and play
    21 => array(
        "name" => "firstRound",
        "description" => clienttranslate('${actplayer} must play this trump or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play this trump or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "acceptFirstRound", "passFirstRound" ),
        "transitions" => array( "accept" => 29, "pass" => 22, "zombiePass" => 81 )
    ),  

    22 => array(
        "name" => "nextPlayerFirstRound",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayerFirstRound",
        "transitions" => array( "nextPlayer" => 21, "nextRound" => 50, "newHand" => 26 )
    ),  

    51 => array(
        "name" => "jokerPlayerFirstRound",
        "description" => clienttranslate('${actplayer} must choose trump color and play'),
        "descriptionmyturn" => clienttranslate('${you} must choose trump color and play'),
        "type" => "activeplayer",
        "possibleactions" => array( "startJokerFirstRound" ),
        "transitions" => array( "accept" => 29 )
    ), 

    // Everyone passed during first round -> second round.
    // New trump card is drawn (must be different than before),
    // and players can again choose to play with this trump.
    // Again, if joker has been drawn as trump card, first player must select trump color and play
    // (but can no longer choose the trump suit from the first draw)
    
    23 => array(
        "name" => "secondRound",
        "description" => clienttranslate('${actplayer} must play this trump or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play this trump or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "acceptSecondRound", "passSecondRound" ),
        "transitions" => array( "accept" => 29, "pass" => 24, "zombiePass" => 82 )
    ),  

    24 => array(
        "name" => "nextPlayerSecondRound",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayerSecondRound",
        "transitions" => array( "nextPlayer" => 23, "newHand" => 26 )
    ),

    52 => array(
        "name" => "jokerPlayerSecondRound",
        "description" => clienttranslate('${actplayer} must choose trump color and play'),
        "descriptionmyturn" => clienttranslate('${you} must choose trump color and play'),
        "type" => "activeplayer",
        "possibleactions" => array( "startJokerSecondRound" ),
        "transitions" => array( "accept" => 29 )
    ), 


    // No one wants to play -> everything is reshuffled, and dealer is changed
    26 => array(
        "name" => "noOneWantsIt",
        "description" => clienttranslate("Cancelling the hand..."),
        "type" => "game",
        "action" => "stNewDeal",
        "transitions" => array( "" => 20 )
    ), 

    // Someone has accepted the trump and wants to play, finish dealing cards before the start
    29 => array(
        "name" => "finishDealing",
        "description" => clienttranslate("Dealing the cards..."),
        "type" => "game",
        "action" => "stFinishDealing",
        "transitions" => array( "" => 30 )
    ), 

    
    // Tricks
    
    30 => array(
        "name" => "newTrick",
        "description" => "",
        "type" => "game",
        "action" => "stNewTrick",
        "transitions" => array( "" => 31 )
    ),       
    31 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card'),
        "type" => "activeplayer",
        "action" => "stPlayerTurn",
        "possibleactions" => array( "playCard" ),
        "transitions" => array( "playCard" => 32 , "zombiePass" => 83)
    ), 
    32 => array(
        "name" => "nextPlayer",
        "description" => clienttranslate("Moving cards..."),
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "nextPlayer" => 31, "nextTrick" => 30, "endHand" => 40 )
    ),

    
    
    
    // End of the hand (scoring, etc...)
    40 => array(
        "name" => "endHand",
        "description" => clienttranslate("Updating the scores..."),
        "type" => "game",
        "action" => "stEndHand",
        "updateGameProgression" => true,
        "transitions" => array( "nextHand" => 20, "endGame" => 99 )
    ),    



    // Zombie actions

    81 => array(
        "name" => "zombiePassFirstRound",
        "description" => "",
        "type" => "game",
        "action" => "stZombiePassFirstRound",
        "transitions" => array( "pass" => 22 )
    ),

    82 => array(
        "name" => "zombiePassSecondRound",
        "description" => "",
        "type" => "game",
        "action" => "stZombiePassSecondRound",
        "transitions" => array( "pass" => 24 )
    ),
    
    83 => array(
        "name" => "zombiePlayCard",
        "description" => "",
        "type" => "game",
        "action" => "stZombiePlayCard",
        "transitions" => array( "" => 32 )
    ),
  
   
    // Final state.
    // Please do not modify.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


