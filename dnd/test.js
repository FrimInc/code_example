$(document).ready(function () {
	window.current_menu = false;
	window.scale = [100, 1500];
	window.SCALE = 100;
	window.LAST_CHAT = 0;
	window.LAST_ME = 0;
	window.FADE_SPEED = 300;
	window.shifted = false;
	window.last_cords = [];
	refreshMess = function () {

		$.ajax({
			'type': 'get',
			url: '/back/getmap.php?GET=MESS',
			success: function (jre) {
				try {
					jre = $.parseJSON(jre);

					_chat = $("#chat");
					_chat.html('<div id="inner_chat"></div>');
					// console.log(jre);
					_messS = $('#inner_chat');
					for (mid in jre.CHAT) {
						_me = jre.CHAT[mid];
						// console.log(_me);
						mess = $('<div>').addClass('chat_message');
						if (_me.IS_MINE) {
							mess.addClass('mine');
						}

						mess.html(_me.MESSAGE);
						_messS.append(mess);

					}

					$("#chat").scrollTop($("#inner_chat").height());
				} catch (e) {

				}

			}
		});

	}

	$(document).on('mousewheel', '#mapcont', function (event) {
		newScale = window.SCALE;
		newScale += event.deltaY > 0 ? 15 : -15;
		// console.log(event);

		_cX = event.clientX;
		_cY = event.clientY;

		if (newScale >= window.scale[0] && newScale <= window.scale[1]) {

			W_F = $('#map').width();
			H_F = $('#map').height();


			scroll_L = $("#mapcont").scrollLeft();
			scroll_T = $("#mapcont").scrollTop();
			D_ = ((newScale - window.SCALE) / window.SCALE) / 2;
			window.SCALE = newScale;

			clPX = (_cX - window.innerWidth / 2) * 0.05;
			clPY = (_cY - window.innerHeight / 2) * 0.05;

			if (newScale < window.SCALE) {
				clPX = clPX * -1;
				clPY = clPY * -1;
			}

			$('#map').css('width', '' + window.SCALE + '%').css('height', '' + window.SCALE + '%');
			$("#mapcont").scrollLeft(scroll_L * ($('#map').width() / W_F) + $("#mapcont").width() * D_ + clPX);
			$("#mapcont").scrollTop(scroll_T * ($('#map').height() / H_F) + $("#mapcont").height() * D_ + clPY);

		}

		return false;
	}).on('keyup', '#chatSend', function (e) {
		if (e.keyCode == 13 && e.ctrlKey) {
			_V = $(this).val();
			$(this).val('');
			doAction('Say', {'MESS': _V});
		}
	});

	sendTextarea = function () {
		_V = $('#chatSend').val();
		$('#chatSend').val('');
		doAction('Say', {'MESS': _V});
	}

	$('#mapcont').addClass('dragscroll');


	drawFIELD = function () {
		$('#oldMap').html($("#map").html()).attr('style', $("#map").attr('style'));
		$('#map').width(1).height(1);
		window.Field = d3.select("#map").html("")
			.append("svg")
			.attr("viewBox", "-35 -35 1450 1450")
			.attr("width", '100%')
			.attr("height", '100%')
			.attr('preserveAspectRatio', 'xMaxYMax');
		window.Field.append("defs");
		window.defs = d3.select("#map svg defs");

		function createPattern(_id, _img, state, filler) {
			// console.log(_id);
			// console.log(_img);
			// console.log();
			if (typeof _img == 'object') {
				_A = window.defs.append('pattern')
					.attr('id', _id + state)
					.attr('patternContentUnits', 'objectBoundingBox')
					.attr('width', '100%')
					.attr('height', '100%');
				for (i in _img) {
					_A.append("image")
						.attr("xlink:href", _img[i])
						.attr('width', 1)
						.attr('height', 1)
						.attr('preserveAspectRatio', 'xMidYMid' + ((_img[i].search('/bg/') > -1) ? ' slice' : ''));
				}
				return;
			}
			window.defs.append('pattern')
				.attr('id', _id + state)
				.attr('patternContentUnits', 'objectBoundingBox')
				.attr('width', '100%')
				.attr('height', '100%')
				.append("image")
				.attr("xlink:href", _img)
				.attr('width', 1)
				.attr('height', 1)
				.attr('preserveAspectRatio', 'xMidYMid' + ((_img.search('/bg/') > -1) ? ' slice' : ''));


		}


		X = 20;
		Y = 20;

		window.Field.D = makeGridDiagram(window.Field,
			Grid.trapezoidalShape(0, X, 0, Y, Grid.oddQToCube))
			.addHexCoordinates(Grid.cubeToOddQ, true)
			.update(70, false);

		window.Field.F = $('#map');

		$.ajax({
			'type': 'get',
			url: '/back/getmap.php?GET=1',
			success: function (jre) {
				try {

					jre = $.parseJSON(jre);
					//MATERIALS
					for (_ptrn in jre.MATERIALS) {
						createPattern(_ptrn, jre.MATERIALS[_ptrn], jre.STATE);
					}

					window.ACTIONS = jre.ACTIONS.ACTIONS;
					window.ACTORS = jre.ACTORS;

					$("#actors").html('');
					for (i in window.ACTORS) {
						actor = window.ACTORS[i];
						$("#actors").append($('<div>').addClass(actor.acting ? 'acting-actor' : '').append('<img src="' + actor.img + '"/>'));
					}

					window.MOVES = jre.MOVES;
					window.MAP = jre.MAP;
					window.POPUPS = jre.POPUPS;

					$('.modal-modal-lol').remove();

					for (pip_i in window.POPUPS) {
						_modC = window.POPUPS[pip_i];
						_modal = $('#modal_proto').clone();
						_modal.attr('id', modId = 'modal_' + pip_i).addClass('modal-modal-lol');
						_modal.find('.modal-header').html(typeof _modC.header != 'undefined' ? _modC.header : '');
						_modal.find('.modal-body').html(typeof _modC.img != 'undefined' && _modC.img ? '<img src="' + _modC.img + '"/><br/>' : '');
						_modal.find('.modal-body').append(typeof _modC.text != 'undefined' ? _modC.text : '');
console.log('pop',_modal.find('.modal-variants'),_modC);
						if(_modC.variants) {
							for(_iVar in _modC.variants) {
								if(_iVar){
									console.log('pops',_modC.variants);
									_modal.find('.modal-variants').append('<a href="javascript:void(0);" data-x="'+_modC.params.X+'" data-y="'+_modC.params.Y+'" data-action="'+_modC.variants[_iVar]+'" class="btn-do-action btn btn-primary" data-dismiss="modal">'+_iVar+'</a>');
								}

							}
						}


						$('body').append(_modal);
						$('#' + modId).modal({show: true});
					}


					//MAP
					for (_X = 0; _X <= 20; _X++) {
						for (_Y = 0; _Y <= 20; _Y++) {
							if (typeof jre.MAP[_X][_Y] == 'undefined') {
								continue;
							}

							tile = jre.MAP[_X][_Y];
							if (tile == 'delete' || tile.bg == 'delete') {
								window.Field.D.G[_X][_Y].T().remove();
							} else {
								actTile = false;
								if (typeof window.ACTIONS != 'undefined' && typeof window.ACTIONS == 'object' && window.ACTIONS != null) {
									if (typeof window.ACTIONS[_X] != 'undefined') {
										if (typeof window.ACTIONS[_X][_Y] != 'undefined') {

											actTile = true;

										}
									}
								}


								tile._bg = tile.bg + jre.STATE;
								__TT = window.Field.D.G[_X][_Y].T();
								__TT.attr('X', _X).attr('Y', _Y).addClass(actTile ? 'actable' : '');

								if (!jre.OPTIONS.BG || tile.bg == 'fog') {
									__TT.find('polygon').attr('fill', 'url(#' + tile._bg + ')').addClass('class-' + tile._bg);
								} else {
									__TT.find('polygon').attr('fill', 'url(#' + 'transparent' + jre.STATE + ')').addClass('class-transparent');
								}

								if (tile.object != null) {
									tile.object = tile.object + jre.STATE;
									// console.log(tile.object, 'on', _X, _Y);
									__TT.find('polygon').after(__TT.find('polygon').clone().attr('fill', 'url(#' + tile.object + ')'));
									__TT.attr('id', tile.id);
								}
								if (tile.canact == false) {
									__TT.find('polygon').after(__TT.find('polygon').clone().attr('fill', 'url(#mfog' + jre.STATE + ')'));
								}

								if (tile.actable) {
									__TT.find('polygon').addClass('class-actable');
									if (tile.move_act) {
										__TT.find('polygon').addClass('class-movable');
									}
								} else {
									__TT.find('polygon').addClass('class-no-actable');
								}
							}


						}
					}
//ACTIONS

					window.ACTION_NAMES = jre.ACTIONS.NAMES;
					window.ACTION_COLORS = jre.ACTIONS.COLORS;
					window.ACTIONS_HL = jre.ACTIONS.HL;

					// console.log(jre.ACTIONS);

					$("#gamename").html(jre.NAME);
					$('#base').html('');

					for (baseName in jre.BASE) {
						if (baseName == 'FreeSkills') {
							continue;
						}
						$('#base').append(
							'<div class="col-xl-3 col-md-3 base-info"><div class="col-xl-6 col-md-6">' + baseName + '</div><div class="col-xl-4 col-md-4">' + jre.BASE[baseName] + '</div></div>'
						);
					}

					$('#special').html('');
					for (specialName in jre.SPECIAL) {


						$('#special').append(
							'<div class="col-xl-6 col-md-12 stat-info"><div class="col-xl-8 col-md-8">' + specialName + '</div><div class="col-xl-4 col-md-4">' + jre.SPECIAL[specialName] + (jre.BASE['FreeSkills'] ? '&nbsp;&nbsp;&nbsp;&nbsp;<a class="upSpecial" href="javascript:void(0);" rel="' + specialName + '"><b>+</b></a>' : '') + '</div></div>'
						);
					}

					$('#skill').html('');
					$('#effect').html('');
					window.SLOT_ACTIONS = jre.SUIT_ACTIONS;

					for (skillName in jre.SKILLS) {
						// console.log(jre.SKILLS[skillName]);
						if (jre.SKILLS[skillName] == false) {
							$('#effect').append(
								'<div class="row stat-info">' + skillName + '</div>'
							);
						} else {
							$('#skill').append(
								'<div class="row stat-info"><div class="col-xl-4 col-md-12">' + skillName + '</div><div class="col-xl-8 col-md-12"><span class="skill-exp"><span class="skill-level">' + jre.SKILLS[skillName].level + '</span><span class="expbg" style="width:' + jre.SKILLS[skillName]['exp%'] + '%;">&nbsp;</span><span class="off">' + jre.SKILLS[skillName].to + '</span><span class="slash">&nbsp;/&nbsp;</span><span class="exp">' + jre.SKILLS[skillName].exp + '</span></span></div></div>'
							);
						}

					}
					window.ITEMS = {};
					$('#items').html('');
					for (_itemId in jre.BAG) {
						// console.log(Item = jre.BAG[_itemId]);
						Item = jre.BAG[_itemId];
						$('#items').append(
							'<div class="item-info" rel="' + Item.Id + '" style="background-image:url(' + Item.img + ');"><div class="item-name">' + Item.Name + '</div><div class="item-cost">Цена: ' + Item.Cost + '</div><div class="item-weight">Вес: ' + Item.Weight + '</div></div>'
						);

						window.ITEMS[Item.Id] = Item;

					}

					$('#suit').html('');

					for (slotName in jre.SUIT) {
						// console.log(jre.SUIT);
						_row = $('<div>').addClass('row').addClass('suit-row');
						_row.append('<div class="rowName">' + slotName + '</div>');
						// console.log(jre.SUIT[slotName]);
						skipNext = 0;
						for (_itemId in jre.SUIT[slotName]) {
							// console.log();
							Item = jre.SUIT[slotName][_itemId];
							_item = $('<div>').addClass('col-xl-2').addClass(' col-md-2').addClass('suit-item');
							if (Item != 'empty') {
								window.ITEMS[Item.Id] = Item;
								_item.addClass('item-info');
								_item.attr('rel', Item.Id);
								_item.attr('slot', slotName);
								_item.css('background-image', 'url(' + Item.img + ')');

								skipNext = Item.slots - 1;
								skipItem = Item;

							} else {
								if (skipNext) {
									skipNext--;
									_item.addClass('item-info');
									_item.attr('rel', skipItem.Id);
									_item.attr('slot', slotName);
									_item.css('background-image', 'url(' + skipItem.img + ')');
								} else {
									_item.css('background-color', '#99999999').append('<div class="item-name">Пусто</div>');
								}

							}


							_row.append(_item);

						}
						$('#suit').append(_row);
					}


					$('.item-info').contextmenu({
						target: "#action_menu",
						before: function (ev, a) {
							_menu = $('#action_menu')
							_menu.html('<ul class="list-group"></ul>');
							_menu = _menu.find('ul');

							try {
								_this = $(a);
								// console.log(_this.attr('rel'));
								if ((ItemID = _this.attr('rel')) && typeof window.ITEMS[ItemID].SUIT_TO != 'undefined') {
									for (slotId in window.ITEMS[ItemID].SUIT_TO) {

										if (window.ITEMS[ItemID].SUIT_TO[slotId] == 'Рюкзак') {
											_text = window.SLOT_ACTIONS[_this.attr('slot')][1];
										} else {
											_text = window.SLOT_ACTIONS[window.ITEMS[ItemID].SUIT_TO[slotId]][0];
										}

										_menu.append($('<li>').attr('class', 'list-group-item d-flex justify-content-between align-items-center').append($('<a>').addClass('action_name').html(_text).attr('slotId', slotId).attr('itemId', ItemID).on('click', function () {
											doAction('SUIT_TO', {
												ItemID: $(this).attr('itemId'),
												Slot: window.ITEMS[$(this).attr('itemId')].SUIT_TO[$(this).attr('slotId')]
											});
										})).append($('<span>').attr('class', 'badge badge-primary badge-pill action_cost').text(1)));
									}

								}

								if (typeof window.ITEMS[ItemID].USE_ACTIONS != 'undefined') {
									for (slotId in window.ITEMS[ItemID].USE_ACTIONS) {

										_menu.append($('<li>').attr('class', 'list-group-item d-flex justify-content-between align-items-center').append($('<a>').addClass('action_name').html(window.ITEMS[ItemID].USE_ACTIONS[slotId]).attr('slotId', slotId).attr('itemId', ItemID).on('click', function () {
											doAction('USE_AS', {
												ItemID: $(this).attr('itemId'),
												UseActions: $(this).attr('slotId')
											});
										})).append($('<span>').attr('class', 'badge badge-primary badge-pill action_cost').text(1)));
									}
								}

								if (_menu.find('li').length) {
									return true;
								} else {
									return false;
								}


							} catch (e) {
								console.log(e);
								ev.preventDefault();
							}
							ev.preventDefault();
							return false;
						}

					});

					$('line').remove();
					for (actionName in window.MOVES) {
						for (__MN in window.MOVES[actionName]) {
							PM = false;
							for (__MM in window.MOVES[actionName][__MN]) {
								if (PM) {
									lineDraw(PM[0], PM[1], window.MOVES[actionName][__MN][__MM][0], window.MOVES[actionName][__MN][__MM][1], actionName);
								}
								PM = window.MOVES[actionName][__MN][__MM];
							}
						}
					}

					$('#map').hide().width('100%').height('100%');
					$('#map').css('width', '' + window.SCALE + '%').css('height', '' + window.SCALE + '%');
					if (jre.OPTIONS.BG) {
						__img = $(document.createElementNS('http://www.w3.org/2000/svg', 'image'));
						__img.attr('href', jre.OPTIONS.BG);
						__img.attr('x', -35);
						__img.attr('y', -75);
						__img.attr('height', 1391);
						__img.attr('width', 1120);
						$("#map > svg > g").prepend(__img);//'<image xlink:href="'+jre.OPTIONS.BG+'" x="-35" y="-75" height="1391px" width="1120px"></image>');
					}

					if (jre.OPTIONS.FOCUS) {
						window.SCALE = 100;
						$('#map').css('width', '' + window.SCALE + '%').css('height', '' + window.SCALE + '%');
						$("#mapcont").scrollLeft(0);
						$("#mapcont").scrollTop(0);
					}

					$("#map").fadeIn(window.FADE_SPEED, function () {

						$('#oldMap').html('');

						touchMap();

					});


				} catch (e) {
					console.log(e);
					alert(e.toString());
				}

				//END OF AJAX
			}
		});

	}

//	drawFIELD();

	lineDraw = function (x1, y1, x2, y2, action) {

		if (!x1 || !y1 || !x2 || !y2) {
			return;
		}

		if (!(FC = $('#map svg > g g.tile[x=' + x1 + '][y=' + y1 + ']').eq(0)) || !(TC = $('#map svg > g g.tile[x=' + x2 + '][y=' + y2 + ']').eq(0))) {
			return;
		}

		if (typeof action == 'undefined') {
			action = 'default';
		}
		if (!window.MAP[x2] || !window.MAP[x2][y2]) {
			return;
		}
		if (window.MAP[x2][y2] && window.MAP[x2][y2].bg && window.MAP[x2][y2].bg.search('fog') > -1) {
			return;
		}

		if (!FC.attr('transform') || !TC.attr('transform')) {
			return;
		}

		action = action + 'way';

		XY1 = FC.attr('transform').replace('translate(', '').replace(')', '').split(',');
		X1 = XY1[0];
		Y1 = XY1[1];
		XY2 = TC.attr('transform').replace('translate(', '').replace(')', '').split(',');
		X2 = XY2[0];
		Y2 = XY2[1];
		line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
		line = $(line).attr('x1', X1).attr('x2', X2).attr('y1', Y1).attr('y2', Y2).addClass('wayline ' + action);
		$('#map svg > g').append(line);

	}

	touchMap = function () {
		$("#map defs").html($("#map defs").html());
	}

	$(document).on('click','a.btn-do-action',function(){
		doAction($(this).data('action'),{'X':$(this).data('x'),'Y':$(this).data('y'),'text':$(this).text()});
	}).on('mouseenter', 'g.actable:not([contextmenuadded])', function () {
		$(this).prop('contextmenuadded', true);
		$(this).contextmenu({
			target: "#action_menu",
			before: function (ev, a, t, menu) {

				_this = $(a);
				$('.actTile').removeClass('actTile');
				_this.addClass('actTile');

				__X = _this.attr('X');
				__Y = _this.attr('Y');

				_menu = $('#action_menu');
				_menu.html('<ul class="list-group"></ul>');
				_menu.attr('X', __X).attr('Y', __Y);

				_menu = _menu.find('ul');

				try {
					if (typeof window.ACTIONS[100] != 'undefined') {
						ACTS = window.ACTIONS[100][100];
						for (actionCode in ACTS) {

							_menu.append($('<li>').attr('class', 'list-group-item d-flex justify-content-between align-items-center').append($('<a>').addClass(window.ACTION_COLORS[actionCode]).addClass('action_name btn').addClass('hl-action').attr('X', __X).attr('Y', __Y).html(window.ACTION_NAMES[actionCode] + (ACTS[actionCode].comment ? ("<span class='comment'>" + ACTS[actionCode].comment + "</span>") : '')).attr('action', actionCode).on('click', function () {
								doAction($(this).attr('action'), {X: __X, Y: __Y});
							})).append($('<span>').attr('class', 'badge badge-primary badge-pill action_cost').text(ACTS[actionCode].cost)).append(''));
						}
					}
					if (typeof window.ACTIONS[_this.attr('X')] == 'undefined' || !window.ACTIONS[_this.attr('X')][_this.attr('Y')] == 'undefined') {
						return false;
					}
					ACTS = window.ACTIONS[__X][__Y];

					// console.log(ACTS);
					if (typeof ACTS == 'undefined') {
						return false;
					}

					for (actionCode in ACTS) {

						_menu.append($('<li>').attr('class', 'list-group-item d-flex justify-content-between align-items-center').append($('<a>').addClass(window.ACTION_COLORS[actionCode]).addClass('action_name btn').addClass('hl-action').attr('X', __X).attr('Y', __Y).html(window.ACTION_NAMES[actionCode] + (ACTS[actionCode].comment ? ("<span class='comment'>" + ACTS[actionCode].comment + "</span>") : '')).attr('action', actionCode).on('click', function () {
							doAction($(this).attr('action'), {X: __X, Y: __Y});
						})).append($('<span>').attr('class', 'badge badge-primary badge-pill action_cost').text(ACTS[actionCode].cost)).append(''));
					}

					// console.log(_menu);
					return true;


				} catch (e) {
					ev.preventDefault();
				}
				ev.preventDefault();
				return false;
			}

		});
	}).on('contextmenu', 'g:not(.actable)', function (e) {
		return false;
	}).on('click', '#mapcont', function (e) {
		$('#action_menu').hide('open');
	}).on('dblclick', 'g.actable', function (e) {
		doAction(window.last_act, {'X': $(this).attr('x'), 'Y': $(this).attr('y')});
	});

	$(document).on('mouseover', 'a.hl-action', function () {

		_a = $(this);
		_X = _a.attr('X');
		_Y = _a.attr('Y');
		Action = _a.attr('action');
		$('.hl-class').removeClassWild('hl-*');
		if (typeof window.ACTIONS_HL != 'undefined')
			if (typeof window.ACTIONS_HL[Action] != 'undefined')
				if (typeof window.ACTIONS_HL[Action][_X] != 'undefined')
					if (typeof window.ACTIONS_HL[Action][_X][_Y] != 'undefined') {
						for (_xy in window.ACTIONS_HL[Action][_X][_Y]) {
							__x = window.ACTIONS_HL[Action][_X][_Y][_xy][0];
							__y = window.ACTIONS_HL[Action][_X][_Y][_xy][1];
							$('#map g[x=' + __x + '][y=' + __y + '] ').addClass('hl-class hl-' + Action);

						}
					}
	}).on('click', 'a.upSpecial', function (e) {
		if (typeof e != 'undefined' && typeof e.preventDefault == 'function') {
			e.preventDefault();
		}
		_this = $(this);
		$.ajax({
			'type': 'post',
			url: '/back/getmap.php?DO=1',
			data: 'action=UP_SPECIAL&special_p=' + _this.attr('rel'),
			success: function (jre) {
				try {
					jre = $.parseJSON(jre);
					// console.log(jre);
					// 	if (jre.result) {
					// //		LAST_ME = jre.result;
					// //		drawFIELD();
					// 	}

				} catch (e) {

				}
			}
		});

		return false;
	});

	doAction = function (action, coords) {

		$('#action_menu').hide();
		$('body').css('cursor', 'wait');
		coords.shiftPressed = window.shifted;
		if(window.last_cords['X'] && window.last_cords['Y']) {
			coords.prevCords = {'X': window.last_cords['X'], 'Y': window.last_cords['Y']};
		}
		window.last_act = action;
		window.last_cords = coords;

		$.ajax({
			'type': 'post',
			url: '/back/getmap.php?DO=1',
			data: 'action=' + action + '&params=' + JSON.stringify(coords),
			success: function (jre) {
				$('body').css('cursor', '');
				try {
					jre = $.parseJSON(jre);
					// console.log(jre);
					// 	if (jre.result) {
					// //		LAST_ME = jre.result;
					// //		drawFIELD();
					// 	}

				} catch (e) {

				}
			}
		});
	}


	$(document).on('keyup keydown', function (e) {
		window.shifted = e.shiftKey
	}).on('click', '#showhideText', function () {
		window.Field.F.toggleClass('no-text');
	}).on('click', '#newGame', function () {
		if (confirm('ВЫ УВЕРЕНЫ ЧТО ХОТИТЕ НАЧАТЬ НОВУЮ ИГРУ?!?!?!?!?')) {
			window.location.href = '/games/';
		}

	});

	(function ($) {
		$.fn.removeClassWild = function (mask) {
			return this.removeClass(function (index, cls) {
				var re = mask.replace(/\*/g, '\\S+');
				return (cls.match(new RegExp('\\b' + re + '', 'g')) || []).join(' ');
			});
		};
	})(jQuery);


	socket = io.connect('46.30.45.60:8081');
	socket.on('connect', function () {
		socket.on('message', function (msg) {

			msg = $.parseJSON(msg);
			console.info(msg);
			if (msg.type == 'chat' && window.LAST_CHAT != msg.data) {
				window.LAST_CHAT = msg.id;
				clearTimeout(window.refreshMessTimeout);
				window.refreshMessTimeout = setTimeout(function () {
					refreshMess();
				}, 100);
			} else if (msg.type == 'game' && window.LAST_ME != msg.data) {
				window.LAST_ME = msg.id;
				clearTimeout(window.drawFIELDTimeout);
				window.drawFIELDTimeout = setTimeout(function () {
					drawFIELD();
				}, 100);
			}
		});
		socket.send(window.USER_ID);
		// console.log(window.GAME_ID);
	});


	// const io = require('socket.io-client');
	// const socket = io('');

// on reconnection, reset the transports option, as the Websocket
// connection may have failed (caused by proxy, firewall, browser, ...)
// 	socket.on('reconnect_attempt', () => {
// 		socket.io.opts.transports = ['polling', 'websocket'];
// });
	drawFIELD();
	refreshMess();


});