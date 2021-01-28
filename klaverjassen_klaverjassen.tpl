{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Klaverjassen implementation : © Iwan Tomlow <iwan.tomlow@gmail.com>
-- Original Credits to the Belote game implementation: © David Bonnin <david.bonnin44@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
--
-- Icon credits: 
-- dealer-icon.png & taker-icon.png are based on some of the free Magicons created by  Darius Dan: https://www.icono.space/
-------
-->

<div id="whole_table" class="whole_table">

	<div id="special_cards" class="special_cards">
		<div id="visibleCard" class="visibleCard whiteblock">
			<h3>{CARD_TO_TAKE}</h3>
			<div id="cardToTake" class="cardToTake"></div>
		</div>

		<div id="trumpBlock" class="trumpBlock whiteblock">
			<h3>{TRUMP}</h3>
			<div id="trumpColor" class="trumpColor"></div>
		</div>

		<div id="gameOptions" class="gameOptions whiteblock">
			<div class="infotitle">{GAME_OPTIONS}</div>
			<div id="btnCityVariant" class="infobutton bgabutton_gray"><span>{CITY_VARIANT}</span></div>
			<div id="btnGameLength" class="infobutton bgabutton_gray"><span>{GAME_LENGTH}</span></div>
		</div>		
	</div>

	<div id="playertables">
		<!-- BEGIN player -->
		<div class="playertable whiteblock playertable_{DIR} playertable--id--{PLAYER_ID}">
			<div class="playertablename" style="color:#{PLAYER_COLOR}">
				{PLAYER_NAME}			
			<div class="playerTables__bubble"></div>
			</div>
			<div class="playertablecard" id="playertablecard_{PLAYER_ID}">
			</div>
			<div class="playerTables__tricksWon">
				<span class="playerTables__tricksWonIcon"></span>
				<span class="playerTables__tricksWonValue"></span>				
			</div>
			<div class="playerTables__roemScored">
				<span class="playerTables__roemScoredValue"></span>
			</div>
		</div>
		<!-- END player -->
		
		<div id="taker_icon">
			<div id="trumpReminder"></div>
		</div>
		<div id="dealer_icon"></div>
		<div id="orientation"></div>
	</div>
	
	<div id="myhand_wrap" class="myHand whiteblock">
		<h3>{MY_HAND}</h3>
		<div id="myhand">
		</div>
	</div>

	<div id="quickref_wrap" class="quickRef whiteblock">
		<h3>{QUICK_REF}</h3>
		<div id="quickref">
			<div id="scoresTrump" class="quickrefBlock">
				<table class="tableScore">
					<tr><th>{REF_TRUMP}</th><th>{REF_POINTS}</th></tr>
					<tr><td class="highlight">{CARD_J}</td><td>20</td></tr>
					<tr><td class="highlight">{CARD_9}</td><td>14</td></tr>
					<tr><td>{CARD_A}</td><td>11</td></tr>
					<tr><td class="highlight">{CARD_10}</td><td>10</td></tr>
					<tr><td>{CARD_K}</td><td>4</td></tr>
					<tr><td>{CARD_Q}</td><td>3</td></tr>
					<tr><td>{CARD_8}</td><td>0</td></tr>
					<tr><td>{CARD_7}</td><td>0</td></tr>
				</table>
			</div>
			<div id="scoresNonTrump" class="quickrefBlock">
				<table class="tableScore">
					<tr><th>{REF_OTHER}</th><th>{REF_POINTS}</th></tr>
					<tr><td>{CARD_A}</td><td>11</td></tr>
					<tr><td class="highlight">{CARD_10}</td><td>10</td></tr>
					<tr><td>{CARD_K}</td><td>4</td></tr>
					<tr><td>{CARD_Q}</td><td>3</td></tr>
					<tr><td>{CARD_J}</td><td>2</td></tr>
					<tr><td>{CARD_9}</td><td>0</td></tr>
					<tr><td>{CARD_8}</td><td>0</td></tr>
					<tr><td>{CARD_7}</td><td>0</td></tr>
				</table>
			</div>
			<div id="scoresRoem" class="quickrefBlock">
				<table class="tableScore">
					<tr><th>{REF_ROEM}</th><th>{REF_POINTS}</th></tr>
					<tr><td>{ROEM_SEQ3}</td><td>20</td></tr>
					<tr><td>{ROEM_SEQ4}</td><td>50</td></tr>
					<tr><td>{ROEM_4A}</td><td>100</td></tr>
					<tr><td>{ROEM_410}</td><td>100</td></tr>
					<tr><td>{ROEM_4K}</td><td>100</td></tr>
					<tr><td>{ROEM_4Q}</td><td>100</td></tr>
					<tr><td>{ROEM_4J}</td><td>200</td></tr>
					<tr><td>{ROEM_STUK}</td><td>20</td></tr>
				</table>
			</div>	
			<div id="rulesVariants" class="quickrefBlock">
				<table class="tableScore">
					<tr>
						<th width="50%">{VARIANT_ROTTERDAMS}</th>
						<th width="50%">{VARIANT_AMSTERDAMS}</th>
					</tr>
					<tr>
						<td>{ROTTERDAMS_FORCEDTRUMP}</td>
						<td>{AMSTERDAMS_NOUNDERTRUMP}</td>
					</tr>
				</table>
			</div>
			<div id="notePoints" class="quickRefNote">{NOTE_POINTS}</div>
			<div id="noteRoem" class="quickRefNote">{NOTE_ROEM}</div>
		</div>
	</div>

</div>


<script type="text/javascript">

var jstpl_cardontable = '<div class="cardontable ${card_style}" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px">\
                        </div>';

var jstpl_cardvisible = '<div class="cardvisible" id="cardvisible" style="background-position:-${x}px -${y}px">\
                        </div>';


var jstpl_spade = '<div class="spade" id="spade">\
                        </div>';
 
var jstpl_heart = '<div class="heart" id="heart">\
                        </div>';

var jstpl_diamond = '<div class="diamond" id="diamond">\
                        </div>';

var jstpl_club = '<div class="club" id="club">\
                        </div>';

var jstpl_no_trumps = '<div class="no_trumps" id="no_trumps">\
                        </div>';

var jstpl_dealer = '<div class="dealer" id="dealer">\
                        </div>';
var jstpl_taker = '<div class="taker" id="taker">\
                        </div>';

</script>  

{OVERALL_GAME_FOOTER}
