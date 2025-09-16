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
 * klaverjassen.js
 *
 * Klaverjassen user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock"
    ],
    function(dojo, declare) {
        return declare("bgagame.klaverjassen", ebg.core.gamegui, {
            constructor: function() {
                console.log('klaverjassen constructor');

                this.playerHand = null;
                this.cardwidth = 72;
                this.cardheight = 96;
                this.trumpWidth = 68;
                this.cardOnTop_id = -1;
                this.cardOnTop_val = -1;
                this.cardOnTop_color = -1;
                this.trump = -1;
                this.dealer = -1;
                this.taker = -1;

                this.cardStyle = 0;
            },



            setup: function(gamedatas) {
                console.log("Starting game setup");

                this.trump = gamedatas.trump;
                this.dealer = gamedatas.dealer;
                this.taker = gamedatas.taker;

                this.cardOnTop_id = gamedatas.cardOnTop_id;
                this.cardOnTop_color = gamedatas.cardOnTop_color;
                this.cardOnTop_val = gamedatas.cardOnTop_val;

                var cardStyleImg = 'img/cards.png';
                // Apply personal player preferences to player hand stocks
                this.cardStyle = this.prefs[100].value;                
                if (this.cardStyle == 1) {
                    cardStyleImg = 'img/cards-dutch.png';
                }
                
                // Setting up player boards
                var playerWithStuk = gamedatas.stukScoredBy;
				for( var player_id in gamedatas.players )
				{
					var player = gamedatas.players[player_id];
                            
                    var roemForPlayer = parseInt(player['roem']);
                    if (playerWithStuk == player_id) roemForPlayer += 20;
					// Setting up players boards if needed
					this.updatePlayerTrickCount(player_id, player['tricks'], roemForPlayer);
				}
                this.addTooltipToClass("tricks_icon", _("Tricks won during this hand"), '');
                this.addTooltipToClass("playerTables__tricksWonIcon", _("Tricks won during this hand"), '');
                this.addTooltipToClass("playerTables__roemScoredValue", _("Roem scored during this hand"), '');

                // Player hand
                this.playerHand = new ebg.stock();
                this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
                this.playerHand.image_items_per_row = 8;
                this.playerHand.setSelectionMode(1);
                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

                
                
                // Create cards types:
                for (var color = 1; color <= 4; color++) {
                    for (var value = 7; value <= 14; value++) {
                        // Build card type id
                        var card_val_id = this.getCardUniqueId(color, value);
                        if (color == this.trump || this.trump == 6 /*all trumps*/ ) {
                            this.playerHand.addItemType(card_val_id, this.get_trump_weight(color, value), g_gamethemeurl + cardStyleImg, card_val_id);
                        } else {
                            this.playerHand.addItemType(card_val_id, this.get_normal_weight(color, value), g_gamethemeurl + cardStyleImg, card_val_id);
                        }
                    }
                }


                // Cards in player's hand
                for (var i in this.gamedatas.hand) {
                    var card = this.gamedatas.hand[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                }

                // Cards played on table
                for (i in this.gamedatas.cardsontable) {
                    var card = this.gamedatas.cardsontable[i];
                    var color = card.type;
                    var value = card.type_arg;
                    var player_id = card.location_arg;
                    this.playCardOnTable(player_id, color, value, card.id);
                }

                // Tooltips 
                this.addTooltipToClass("playertablecard", _("Card played on the table"), '');

                this.addTooltip('myhand', '', _('Play a card'));
                this.addTooltip('dealer_icon', _('Dealer for this hand'), '');
                this.addTooltip('taker_icon', _('Player for this hand'), '');


                this.addTooltipToClass("cardToTake", _("Trump card to determine Trump suit"), '');
                this.addTooltip('btnCityVariant', '', _('City variant'));
                if (gamedatas.gameLength < 100) {
                    this.addTooltip('btnGameLength', '', _('Number of rounds'));
                } else {
                    this.addTooltip('btnGameLength', '', _('Points required to win'));
                }                

                if (this.cardOnTop_id >= 0) {
                    this.makeCardVisible(this.cardOnTop_color, this.cardOnTop_val, this.cardOnTop_id);
                }

                if (this.trump > 0) {
                    this.displayTrump(this.trump);
                }

                if (this.dealer > 0) {
                    this.setDealer(this.dealer);
                }
                if (this.taker > 0) {
                    this.setTaker(this.taker);
                } else {
                    this.noTaker();
                }

				var div = document.getElementById("orientation");
				if(typeof div != 'undefined'){
					//div.className = "counterclockwise_icon";
					div.className = "clockwise_icon";
                }        
                            
                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                this.ensureSpecificImageLoading(['../common/point.png']);

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function(stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {


                    case 'playerTurn':

                        var items = this.playerHand.getSelectedItems();
                        if (items.length != 1) {
                            this.playerHand.unselectAll();
                        } else if (this.isCurrentPlayerActive()) {
                            var card_id = items[0].id;

                            this.ajaxcall("/klaverjassen/klaverjassen/playCard.html", {
                                id: card_id,
                                lock: true
                            }, this, function(result) {}, function(is_error) {});
                            this.playerHand.unselectAll();
                        }
                        break;
                        /* Example:
			  
			  case 'myGameState':
			  
			  // Show some HTML block at this game state
			  dojo.style( 'my_html_block_id', 'display', 'block' );
			  
			  break;
		       */


                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function(stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    /* Example:
			  
			  case 'myGameState':
			  
			  // Hide the HTML block we are displaying only during this game state
			  dojo.style( 'my_html_block_id', 'display', 'none' );
			  
			  break;
		       */


                    case 'dummmy':
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function(stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {

                        case 'firstRound':
                            this.addActionButton('acceptFirstRound_button', _('Play'), 'onAcceptFirstRound');
                            this.addActionButton('rejectFirstRound_button', _('Pass'), 'onRejectFirstRound');
                            break;

                        case 'jokerPlayerFirstRound':
                        case 'jokerPlayerSecondRound':
                            this.addActionButton('acceptSpade_button', '<div class="spade_icon" id="spade_icon"></div>', 'onAcceptSpade');
                            this.addActionButton('acceptHeart_button', '<div class="heart_icon" id="spade_icon"></div>', 'onAcceptHeart');
                            this.addActionButton('acceptClub_button', '<div class="club_icon" id="spade_icon"></div>', 'onAcceptClub');
                            this.addActionButton('acceptDiamond_button', '<div class="diamond_icon" id="spade_icon"></div>', 'onAcceptDiamond');
                            break;

                        case 'secondRound':
                            this.addActionButton('acceptSecondRound_button', _('Play'), 'onAcceptSecondRound');
                            this.addActionButton('rejectSecondRound_button', _('Pass'), 'onRejectSecondRound');
                            break;

                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
		 
		 Here, you can defines some utility methods that you can use everywhere in your javascript
		 script.
		 
               */

            new_trump: function(trump) {
                this.trump = trump;
                this.displayTrump(this.trump);
                var myArray = new Array();
                if (trump < 5) {
					for (var col = 1; col <= 4; col++) {
                        for (var value = 7; value <= 14; value++) {
                            var card_val_id = this.getCardUniqueId(col, value);
                            myArray[card_val_id] = this.get_normal_weight(col, value);
                        }
					}
                    for (var value = 7; value <= 14; value++) {
                        var card_val_id = this.getCardUniqueId(trump, value);
                        myArray[card_val_id] = this.get_trump_weight(trump, value);
                    }
                } else if (trump == 6) {
                    for (var col = 1; col <= 4; col++)
                        for (var value = 7; value <= 14; value++) {
                            var card_val_id = this.getCardUniqueId(col, value);
                            myArray[card_val_id] = this.get_trump_weight(col, value);
                        }
                }
                this.playerHand.changeItemsWeight(myArray);

            },

            reset_trump: function() {
                var color = this.trump;
                var name = '';
                if (color > 0) {
					this.trump = -1;
                    var myArray = new Array();
                    for (var col = 1; col <= 4; col++) {
                        for (var value = 7; value <= 14; value++) {
                            var card_val_id = this.getCardUniqueId(col, value);
                            myArray[card_val_id] = this.get_normal_weight(col, value);
                        }
                    }
                    this.playerHand.changeItemsWeight(myArray);
                    if (color == 1) {
                        name = 'spade';
                    } else if (color == 2) {
                        name = 'heart';
                    } else if (color == 3) {
                        name = 'club';
                    } else if (color == 4) {
                        name = 'diamond';
                    } else if (color == 5) {
                        name = 'no_trumps';
                    }
                    var anim = this.slideToObject(name, name);
                    dojo.connect(anim, 'onEnd', function(node) {
                        dojo.destroy(node);
                    });
                    anim.play();
                    // bug: the trump hangs sometimes and sits behind the new one to bid on
                    // just to be sure, clear the trump element completely for when the correct animation failed
                    dojo.empty('trumpColor');
                    dojo.removeClass('trumpReminder');
                }
            },

			// Base order is S-H-C-D
			// Modify order depending on trump, to keep alternate colors
			get_modified_color: function(color) {
				if(this.trump == 2 && color == 4) // trump H -> D is placed between S and C
					return 2;
				else if(this.trump == 3 && color == 1) // trump C -> S is placed between H and D
					return 3;
				else return color; // default
			},

            get_normal_weight: function(color, value) {
                var base = 8 * (this.get_modified_color(color) - 1);
                if (value < 10 || value == 14) {
                    return base + value - 6;
                } else if (value == 10) {
                    return base + 7;
                } else {
                    return base + value - 7;
                }
            },

            get_trump_weight: function(color, value) {
                var base = 32 + 8 * (color - 1);
                if (value == 7 || value == 8) {
                    return base + value - 6;
                } else if (value == 9) {
                    return base + 7;
                } else if (value == 10) {
                    return base + 5;
                } else if (value == 11) {
                    return base + 8;
                } else if (value == 14) {
                    return base + 6;
                } else {
                    return base + value - 9;
                }
            },

            // Get card unique identifier based on its color and value
            getCardUniqueId: function(color, value) {
                return (color - 1) * 8 + (value - 7);
            },

            setDealer: function(player_id) {
                // Slide into position (top left of this player play zone)
                this.slideToObjectPos('dealer_icon', 'playertablecard_' + player_id, -38, -33, 1000).play();
                
                this.showBubble(player_id, _('I dealt'));
                setTimeout(() => this.hideBubble(player_id), 2000);
            },
            updateHandsPlayed: function(gameLength_display) {
                dojo.byId('btnGameLength').textContent = gameLength_display;
            },

            setTaker: function(player_id) {
                // Slide into position (bottom left of this player play zone)
                this.slideToObjectPos('taker_icon', 'playertablecard_' + player_id, -38, 83, 1000).play();

                this.showBubble(player_id, _('Play!'));
                // start new hand, hide all speech bubbles
                setTimeout(() => this.hideAllBubbles(), 500);
            },

            noTaker: function() {
                // Slide into position (bottom left of the card to be taken)
                this.slideToObjectPos('taker_icon', 'cardToTake', -38, 73, 1000).play();
            },

            // Return a player element (with class .playerTables__<suffix>)
            // or the table wrapper if no suffix is given
            getPlayerTableEl: function(playerId, suffix) {
                var selector = '.playertable--id--' + playerId;
                if (suffix) {
                    selector += ' .' + suffix;
                }
                return dojo.query(selector)[0];
            },

            /*
            * Bubble management
            */
            showBubble: function(player, message) {
                const itemId = this.getPlayerTableEl(player, 'playerTables__bubble')
                $(itemId).innerHTML = message;
                dojo.addClass(itemId, 'playerTables__bubble--visible')
            },

            hideBubble: function(player) {
                const itemId = this.getPlayerTableEl(player, 'playerTables__bubble')
                dojo.removeClass(itemId, 'playerTables__bubble--visible')
            },

            hideAllBubbles: function() {
                for (var player in this.gamedatas.players) {
                    this.hideBubble(player);
                }
            },

            updatePlayerTrickCount: function(playerId, tricksWon, roemScored) {
                // Update value
                this.getPlayerTableEl(playerId, 'playerTables__tricksWonValue').innerHTML = tricksWon;
                // Update 'notempty' class on tricks
                var cls = 'playerTables__tricksWon--notEmpty';
                var method = tricksWon > 0 ? 'add' : 'remove';
                this.getPlayerTableEl(playerId, 'playerTables__tricksWon').classList[method](cls);

                // Update Roem value
                this.getPlayerTableEl(playerId, 'playerTables__roemScoredValue').innerHTML = "+" + roemScored;
                // Update 'notempty' class on roem
                var cls = 'playerTables__roemScored--notEmpty';
                var method = roemScored > 0 ? 'add' : 'remove';
                this.getPlayerTableEl(playerId, 'playerTables__roemScored').classList[method](cls);
            },

            playCardOnTable: function(player_id, color, value, card_id) {
                // player_id => direction
                dojo.place(
                    this.format_block('jstpl_cardontable', {
                        x: this.cardwidth * (value - 7),
                        y: this.cardheight * (color - 1),
                        player_id: player_id,
                        card_style: 'cardstyle-' + this.cardStyle
                    }), 'playertablecard_' + player_id);

                if (player_id != this.player_id) {
                    // Some opponent played a card

                    /*
                    // Move card from overall player panel
                    this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
                    */
                    
                    // or would it be better to play card from the player table element?
                    // in Coinche this looks nicer because player table / avatar styling allows it
                    var from = this.getPlayerTableEl(player_id, 'playertablename');
                    this.placeOnObject('cardontable_' + player_id, from);
                    
                } else {
                    // You played a card. If it exists in your hand, move card from there and remove
                    // corresponding item

                    if ($('myhand_item_' + card_id)) {
                        this.placeOnObject('cardontable_' + player_id, 'myhand_item_' + card_id);
                        this.playerHand.removeFromStockById(card_id);
                    }
                }

                // In any case: move it to its final destination
                this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id, 500, 0).play();

            },


            makeCardVisible: function(color, value, card_id) {
                // player_id => direction
                dojo.place(
                    this.format_block('jstpl_cardvisible', {
                        x: this.cardwidth * (value - 2),
                        y: this.cardheight * (color - 1)
                    }), 'cardToTake');

                this.placeOnObject('cardvisible', 'cardToTake');

                // In any case: move it to its final destination
                this.slideToObject('cardvisible', 'cardToTake').play();

            },

            destroyVisibleCard: function() {
                if (this.cardOnTop_id != -1) {
                    var anim = this.slideToObject('cardvisible', 'cardToTake');
                    dojo.connect(anim, 'onEnd', function(node) {
                        dojo.destroy(node);
                    });
                    anim.play();
                    this.cardOnTop_id = -1;
                }
            },

            displayTrump: function(trumpColor) {
                // player_id => direction

                if (trumpColor == 1) {
                    dojo.place(
                        this.format_block('jstpl_spade', {}), 'trumpColor');
                    this.placeOnObject('spade', 'trumpColor');
                    this.slideToObject('spade', 'trumpColor').play();
                    this.addTooltip('spade', _('Spade'), '');

                    dojo.removeClass('trumpReminder');
                    dojo.addClass('trumpReminder', 'trump_spade');

                } else if (trumpColor == 2) {
                    dojo.place(
                        this.format_block('jstpl_heart', {}), 'trumpColor');
                    this.placeOnObject('heart', 'trumpColor');
                    this.slideToObject('heart', 'trumpColor').play();
                    this.addTooltip('heart', _('Heart'), '');

                    dojo.removeClass('trumpReminder');
                    dojo.addClass('trumpReminder', 'trump_heart');
                } else if (trumpColor == 3) {
                    dojo.place(
                        this.format_block('jstpl_club', {}), 'trumpColor');
                    this.placeOnObject('club', 'trumpColor');
                    this.slideToObject('club', 'trumpColor').play();
                    this.addTooltip('club', _('Club'), '');

                    dojo.removeClass('trumpReminder');
                    dojo.addClass('trumpReminder', 'trump_club');
                } else if (trumpColor == 4) {
                    dojo.place(
                        this.format_block('jstpl_diamond', {}), 'trumpColor');
                    this.placeOnObject('diamond', 'trumpColor');
                    this.slideToObject('diamond', 'trumpColor').play();
                    this.addTooltip('diamond', _('Diamond'), '');

                    dojo.removeClass('trumpReminder');
                    dojo.addClass('trumpReminder', 'trump_diamond');
                } else if (trumpColor == 5) {
                    dojo.place(
                        this.format_block('jstpl_no_trumps', {}), 'trumpColor');
                    this.placeOnObject('no_trumps', 'trumpColor');
                    this.slideToObject('no_trumps', 'trumpColor').play();
                    this.addTooltip('no_trumps', _('Joker'), '');

                    dojo.removeClass('trumpReminder');
                    dojo.addClass('trumpReminder', 'trump_none');
                }


            },

            linkToHandResult: function(n, handResult) {
                // HACK: This needs to be done after the element was added to the log, BUT
                // it needs to be done even if the log was shown after a reload (i.e. it
                // isn't sufficient to do it in the handResult notification handler).
                setTimeout(() => dojo.connect(
                    $('result_of_hand_' + n), 'onclick', this, (e) => {
                        e.preventDefault();
                        this.showResultDialog(handResult);
                    }), 0)
                return dojo.string.substitute('<a id="${linkId}">${handResult}</a>', {
                    linkId: 'result_of_hand_' + n,
                    handResult: _('click here')
                })
            },

            // @Override: client side magic to massage log arguments into 
            // displayable localized text
            format_string_recursive: function(log, args) {
                try {
                    if (log && args && !args.processed) {
                        args.processed = true;

                        if (args.handResult !== undefined) {
                            args.originalHandResult = args.handResult; // HACK: Notification handler needs the original table
                            args.handResult = this.linkToHandResult(args.hand, args.handResult);
                        }
                    }
                } catch (e) {
                    console.error('Exception while formatting "%o" with "%o":\n%o', log, args, e);
                }
                return this.inherited(arguments);
            },


            ///////////////////////////////////////////////////
            //// Player's action

            /*
		 
		 Here, you are defining methods to handle player's action (ex: results of mouse click on 
		 game objects).
		 
		 Most of the time, these methods:
		 _ check the action is possible at this game state.
		 _ make a call to the game server
		 
               */



            onPlayerHandSelectionChanged: function() {
                var items = this.playerHand.getSelectedItems();
                if (items.length != 1) {
                    this.playerHand.unselectAll();
                } else if (this.isCurrentPlayerActive()) {
                    if (this.checkAction('playCard', true)) {
                        // Can play a card

                        var card_id = items[0].id;

                        this.ajaxcall("/klaverjassen/klaverjassen/playCard.html", {
                            id: card_id,
                            lock: true
                        }, this, function(result) {}, function(is_error) {});
                        this.playerHand.unselectAll();
                    }

                }
            },


            onAcceptFirstRound: function() {
                if (this.checkAction('acceptFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/acceptFirstRound.html", {
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onRejectFirstRound: function() {
                if (this.checkAction('passFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/passFirstRound.html", {
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onAcceptSecondRound: function() {
                if (this.checkAction('acceptSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/acceptSecondRound.html", {
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onRejectSecondRound: function() {
                if (this.checkAction('passSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/passSecondRound.html", {
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                }
            },


            onAcceptSpade: function() {
                if (this.checkAction('startJokerFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerFirstRound.html", {
                        lock: true,
                        chosenTrump: 1
                    }, this, function(result) {}, function(is_error) {});
                }
                if (this.checkAction('startJokerSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerSecondRound.html", {
                        lock: true,
                        chosenTrump: 1
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onAcceptHeart: function() {
                if (this.checkAction('startJokerFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerFirstRound.html", {
                        lock: true,
                        chosenTrump: 2
                    }, this, function(result) {}, function(is_error) {});
                }
                if (this.checkAction('startJokerSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerSecondRound.html", {
                        lock: true,
                        chosenTrump: 2
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onAcceptClub: function() {
                if (this.checkAction('startJokerFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerFirstRound.html", {
                        lock: true,
                        chosenTrump: 3
                    }, this, function(result) {}, function(is_error) {});
                }
                if (this.checkAction('startJokerSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerSecondRound.html", {
                        lock: true,
                        chosenTrump: 3
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            onAcceptDiamond: function() {
                if (this.checkAction('startJokerFirstRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerFirstRound.html", {
                        lock: true,
                        chosenTrump: 4
                    }, this, function(result) {}, function(is_error) {});
                }
                if (this.checkAction('startJokerSecondRound', true)) {
                    this.ajaxcall("/klaverjassen/klaverjassen/startJokerSecondRound.html", {
                        lock: true,
                        chosenTrump: 4
                    }, this, function(result) {}, function(is_error) {});
                }
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
		 setupNotifications:
		 
		 In this method, you associate each of your game notifications with your local method to handle it.
		 
		 Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                 your klaverjassen.game.php file.
		 
               */
            setupNotifications: function() {
                console.log('notifications subscriptions setup');

                dojo.subscribe('startingNewHand', this, "notif_startingNewHand");
                dojo.subscribe('newHand', this, "notif_newHand");
                dojo.subscribe('fillHand', this, "notif_fillHand"); 
                dojo.subscribe('dealCards', this, "notif_dealCards");
                dojo.subscribe('handsPlayed', this, "notif_handsPlayed");

                dojo.subscribe('cardOnTop', this, "notif_cardOnTop");
                dojo.subscribe('newTrump', this, "notif_newTrump");
                dojo.subscribe('pass', this, "notif_pass");
				dojo.subscribe('takeCard', this, "notif_takeCard");
				dojo.subscribe('noDeal', this, "notif_noDeal");

                dojo.subscribe('playCard', this, "notif_playCard");				
                dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
                dojo.subscribe('trickWin', this, "notif_trickWin");
                dojo.subscribe('roemScored', this, "notif_roemScored");
                dojo.subscribe('stukScored', this, "notif_stukScored");
                
                dojo.subscribe('lastTrickTakenBy', this, "notif_lastTrickTaken");
                dojo.subscribe('newScores', this, "notif_newScores");

                dojo.subscribe('mars', this, "notif_mars");
                dojo.subscribe('nat', this, "notif_nat");
                dojo.subscribe('handResult', this, "notif_handResult");
                dojo.subscribe('scoreTableDetails', this, "notif_scoreTableDetails");
                dojo.subscribe('gameWin', this, "notif_gameWin");

				this.notifqueue.setSynchronous('newScores', 4000);
                this.notifqueue.setSynchronous('trickWin', 1200);
                this.notifqueue.setSynchronous('giveAllCardsToPlayer', 1200);
                this.notifqueue.setSynchronous('fillHand', 500);
                this.notifqueue.setSynchronous('noDeal', 500);
                this.notifqueue.setSynchronous('playCard', 500);
                // if players shout Nat or Mars, make sure people see this
                // before the hand result dialog pops over everything
                this.notifqueue.setSynchronous('mars', 1500);
                this.notifqueue.setSynchronous('nat', 1500);
            },

            notif_startingNewHand: function(notif) {
                this.noTaker();
				for (var player_id in this.gamedatas.players){
					this.updatePlayerTrickCount(player_id, 0, 0);
				}
            },

            notif_newHand: function(notif) {
                // We received a new hand of cards.
                this.playerHand.removeAll();

                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                }
            },

            notif_fillHand: function(notif) {
                // We received a new full hand of 8 cards.
                this.playerHand.removeAll();

                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                }
            },

            notif_takeCard: function(notif) {
                var taker_id = notif.args.player_id;
                if (this.cardOnTop_id >= 0) {
                    var anim = this.slideToObject('cardvisible', 'playertablecard_' + taker_id, 700, 0);
                    dojo.connect(anim, 'onEnd', this, 'fadeOutAndDestroy');
                    /*dojo.connect(anim, 'onEnd', function(node) {
                        dojo.destroy(node);
                    });*/
                    anim.play();
                }
                this.cardOnTop_id = -1;
                this.setTaker(taker_id);                
            },

            notif_playCard: function(notif) {
                // Play a card on the table
                this.playCardOnTable(notif.args.player_id, notif.args.color, notif.args.value, notif.args.card_id);
            },
            notif_trickWin: function(notif) {                
                this.updatePlayerTrickCount(notif.args.player_id, notif.args.tricks, notif.args.roemForPlayer);
                this.hideAllBubbles();
            },
            notif_lastTrickTaken: function(notif) {

            },
            notif_pass: function(notif) {
                this.showBubble(notif.args.player_id, _('Pass'));
            },
            notif_roemScored: function(notif) {
                this.showBubble(notif.args.player_id, notif.args.roemToCall + ' ' + _('Roem!'));
                setTimeout(() => this.hideBubble(notif.args.player_id), 1000);
            },
            notif_stukScored: function(notif) {
                this.showBubble(notif.args.player_id, _('Stuk!'));
                setTimeout(() => this.hideBubble(notif.args.player_id), 1000);
            },
            notif_dealCards: function(notif) {
                this.setDealer(notif.args.player_id);
            },
            notif_handsPlayed: function(notif) {
                this.updateHandsPlayed(notif.args.gameLengthDisplay);
            },
            notif_noDeal: function(notif) {
                
            },
            notif_mars: function(notif) {
                this.showBubble(notif.args.player_A, _('Mars!'));
                this.showBubble(notif.args.player_B, _('Mars!'));
            },            
            notif_nat: function(notif) {
                this.showBubble(notif.args.player_A, _('Nat!'));
                this.showBubble(notif.args.player_B, _('Nat!'));
            },
            notif_handResult: function(notif) {

            },  
            notif_scoreTableDetails: function(notif) {
                // handResult will be transformed into clickable link in the game log
                // display here should use the original result table (originalHandResult)
                // But: after BGA update 2025/09/10, this notification handler just gets
                // the original args, not the adapted args from format_string_recursive!
                this.showResultDialog(notif.args.handResult);
            },
            showResultDialog: function (args) {
                this.scoringDialog = this.displayTableWindow(
                    args.id,
                    args.title,
                    args.table,
                    args.footer,
                    this.format_string_recursive(
                        '<div id="tableWindow_actions"><a id="close_btn" class="bgabutton bgabutton_blue">${close}</a></div>',
                        { close: _(args.closing) }
                    )
                )
                this.scoringDialog.show()
            },
            notif_gameWin: function(notif) {

            },
            notif_cardOnTop: function(notif) {
                this.destroyVisibleCard();                
                this.cardOnTop_id = notif.args.card_id;
                this.cardOnTop_color = notif.args.card_color;
                this.cardOnTop_val = notif.args.card_val;
                this.makeCardVisible(this.cardOnTop_color, this.cardOnTop_val, this.cardOnTop_id);                
            },
            notif_newTrump: function(notif) {
                this.reset_trump();                
                this.new_trump(notif.args.card_color);
                this.hideAllBubbles();
            },
            notif_giveAllCardsToPlayer: function(notif) {
                // Move all cards on table to given player, then destroy them
                var winner_id = notif.args.player_id;
                for (var player_id in this.gamedatas.players) {
                    var anim = this.slideToObject('cardontable_' + player_id, 'playertablecard_' + winner_id, 700, 0);
					
					dojo.connect(anim, 'onEnd', this, 'fadeOutAndDestroy');
                    /*dojo.connect(anim, 'onEnd', function(node) {
                        dojo.destroy(node);
                    });*/
                    anim.play();
					
                }
            },

            notif_newScores: function(notif) {
                // start new hand, hide all speech bubbles
                setTimeout(() => this.hideAllBubbles(), 1000);
                // Update players' scores                
                for (var player_id in notif.args.newScores) {                    
                    this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
                }
            }

        });
    });
