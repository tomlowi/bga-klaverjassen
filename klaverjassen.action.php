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
 * klaverjassen.action.php
 *
 * Klaverjassen main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/klaverjassen/klaverjassen/myAction.html", ...)
 *
 */
  
  
class action_klaverjassen extends APP_GameAction
{ 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
            {
                $this->view = "common_notifwindow";
                $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
            }
  	    else
            {
                $this->view = "klaverjassen_klaverjassen";
                self::trace( "Complete reinitialization of board game" );
            }
  	} 
  	

	public function playCard()
    {
        self::setAjaxMode();     
        $card_id = self::getArg( "id", AT_posint, true );
        // Select a card from the player's hand
        $this->game->playCard( $card_id );
        self::ajaxResponse( );
    }

	public function acceptFirstRound()
    {
        self::setAjaxMode();     
        $this->game->acceptFirstRound();
        self::ajaxResponse( );
    }

	public function passFirstRound()
    {
        self::setAjaxMode();     
        $this->game->passFirstRound();
        self::ajaxResponse( );
    }

    public function startJokerFirstRound()
	{
		self::setAjaxMode();    
		$trump = self::getArg("chosenTrump", AT_int, true);
		$this->game->startJokerFirstRound($trump);
		self::ajaxResponse( );
	}

	public function passSecondRound()
    {
        self::setAjaxMode();     
        $this->game->passSecondRound();
        self::ajaxResponse( );
    }

	public function acceptSecondRound()
	{
		self::setAjaxMode();    
		$this->game->acceptSecondRound();
		self::ajaxResponse( );
    }
    
    public function startJokerSecondRound()
	{
		self::setAjaxMode();    
		$trump = self::getArg("chosenTrump", AT_int, true);
		$this->game->startJokerSecondRound($trump);
		self::ajaxResponse( );
	}
	

}
  

