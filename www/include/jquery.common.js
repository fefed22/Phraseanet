$(document).ready(function(){

	$('input.input-button').hover(
			function(){$(this).addClass('hover');},
			function(){$(this).removeClass('hover');}
	);
	var locale = $.cookie('locale');

	p4.lng = locale != null ? locale.split('_').reverse().pop() : 'en';

	$.datepicker.setDefaults({showMonthAfterYear: false});
	$.datepicker.setDefaults($.datepicker.regional[p4.lng]);

	$('a.infoDialog,div.infoDialog').live('click',function(event){
		infoDialog($(this));
	});

	var cache = $('#mainMenu .helpcontextmenu');
	$('.context-menu-item',cache).hover(function(){$(this).addClass('context-menu-item-hover');},function(){$(this).removeClass('context-menu-item-hover');});

	$('#help-trigger').contextMenu('#mainMenu .helpcontextmenu',{openEvt:'click',dropDown:true,theme:'vista', dropDown:true,
		showTransition:'slideDown',
		hideTransition:'hide',
		shadow:false
	});

	$('.subscribe_my_rss').live('click',function(){
		getMyRss(false);
	});

	$('#notification_trigger').bind('mousedown',function(event){
		event.stopPropagation();
		var box = $('#notification_box');
		if($(this).hasClass('open'))
		{
			box.hide();
			$(this).removeClass('open');
			clear_notifications();
		}
		else
		{
			box.show();

			fix_notification_height();

			$(this).addClass('open');
			read_notifications();
		}
	});

	$(this).bind('mousedown',function(){
		var not_trigger = $('#notification_trigger');
		if(not_trigger.hasClass('open'))
			not_trigger.trigger('click');
	});
	$('#notification_box').bind('mousedown',function(event){
			event.stopPropagation();
	});

	$('#notification_box div.notification').live('mouseover',function(){$(this).addClass('hover')});
	$('#notification_box div.notification').live('mouseout',function(){$(this).removeClass('hover')});



	$(this).bind('mousedown',function(){
			var box = $('#notification_box');
			if($('#notification_trigger').hasClass('open'))
			{
				box.hide();
				$('#notification_trigger').removeClass('open');
				clear_notifications();
			}
	});

	set_notif_position();


});


function login(what)
{
	if (confirm(language.confirmRedirectAuth)) {
		if(what != undefined)
		{
			EcrireCookie('last_act',what,null,'/');
		}
		self.location.replace('/login/index.php?postlog=1');
	}
	return false;
}


function EcrireCookie(nom, valeur)
{
	var argv=EcrireCookie.arguments;
	var argc=EcrireCookie.arguments.length;
	var expires=(argc > 2) ? argv[2] : null;
	var path=(argc > 3) ? argv[3] : null;
	var domain=(argc > 4) ? argv[4] : null;
	var secure=(argc > 5) ? argv[5] : false;
	var cook = nom+"="+escape(valeur)+
	((expires==null) ? "" : ("; expires="+expires.toGMTString()))+
	((path==null) ? "" : ("; path="+path))+
	((domain==null) ? "" : ("; domain="+domain))+
	((secure==true) ? "; secure" : "");
	document.cookie = cook;
}

function fix_notification_height()
{
	var box = $('#notification_box');
	var not = $('.notification',box);
	var n = not.length;
	var not_t = $('.notification_title',box);
	var n_t = not_t.length;

	h = not.outerHeight() * n + not_t.outerHeight() * n_t;
	h = h > 350 ? 350 : h;

	box.stop().animate({height:h});
}

function set_notif_position()
{
	var trigger = $('#notification_trigger');
	if(trigger.length == 0)
		return;
	$('#notification_box').css({
		'left':trigger.offset().left
	});
}
$(window).bind('resize', function(){
	set_notif_position();
});


function print_notifications(page)
{


	var buttons = {};

	buttons[language.fermer] = function(){
		$('#notifications-dialog').dialog('close');
	};

	if($('#notifications-dialog').length == 0)
		$('body').append('<div id="notifications-dialog" class="loading"></div>');

	$('#notifications-dialog')
		.dialog({
			title:language.notifications,
			autoOpen:false,
			closeOnEscape:true,
			resizable:false,
			draggable:false,
			modal:true,
			width:500,
			height:400,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.7
			},
			close:function(event,ui)
			{
				$('#notifications-dialog').dialog('destroy').remove();
			}
		}).dialog('option','buttons',buttons)
		.dialog('open');


	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		dataType : 'json',
		data: {
			action: "NOTIFICATIONS_FULL",
			page:page
		},
		error: function(data){
			$('#notifications-dialog').removeClass('loading');
		},
		timeout: function(data){
			$('#notifications-dialog').removeClass('loading');
		},
		success: function(data){
			$('#notifications-dialog').removeClass('loading');
			var cont = $('#notifications-dialog');

			if(page == 0)
				cont.empty()
			else
				$('.notification_next',cont).remove();

			for (i in data.notifications)
			{
				var id = 'notif_date_'+i;
				var date_cont = $('#'+id);
				if(date_cont.length == 0)
				{
					cont.append('<div id="'+id+'"><div class="notification_title">'+data.notifications[i].display+'</div></div>');
					date_cont = $('#'+id);
				}

				for (j in data.notifications[i].notifications)
				{
					var loc_dat = data.notifications[i].notifications[j];
					var html = '<div style="position:relative;" id="notification_'+loc_dat.id+'" class="notification">'+
						'<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr><td style="width:25px;">'+
						loc_dat.icon+
						'</td><td>'+
						'<div style="position:relative;" class="'+loc_dat.classname+'">'+
							loc_dat.text+' <span class="time">'+loc_dat.time+'</span></div>'+
						'</td></tr></table>'+
						'</div>';
					date_cont.append(html);
				}
			}

			var next_ln = $.trim(data.next);

			if(next_ln != '')
			{
				cont.append('<div class="notification_next">'+next_ln+'</div>');
			}

//			'<div style="position:relative;" id="notification_'.$row['id'].'" class="notification '.($row['unread'] == '1' ? 'unread':'').'">'.
//			'<table style="width:100%;" cellspacing="0" cellpadding="0" border="0"><tr><td style="width:25px;">'.
//			'<img src="'.$this->pool_classes[$row['type']]->icon_url().'" style="vertical-align:middle;width:16px;margin:2px;" />'.
//			'</td><td>'.
//			'<div style="position:relative;" class="'.$data['class'].'">'.
//				$data['text'].' <span class="time"></span></div>'.
//			'</td></tr></table>'.
//			'</div>'


		}
	});

}

function read_notifications()
{
	var notifications = [];

	$('#notification_box .unread').each(function(){
		notifications.push($(this).attr('id').split('_').pop());
	});

	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		data: {
			action: "READ_NOTIFICATIONS",
			notifications:notifications.join('_')
		},
		success: function(data){
			$('#notification_trigger .counter').css('visibility','hidden').empty();
		}
	});
}

function clear_notifications()
{
	var unread = $('#notification_box .unread');

	if(unread.length == 0)
		return;

	unread.removeClass('unread');
	$('#notification_trigger .counter').css('visibility','hidden').empty();
}







function getMyRss(renew)
{
	if(typeof(renew)=='undefined')
		renew = 'false';
	else
		renew = renew ? 'true' : 'false';

	var buttons = {};
	buttons[language.renewRss] = function() {
		getMyRss(true);
	};
	buttons[language.fermer] = function() {
		$('#DIALOG').dialog('close');
	};
	var datas = {
			action: "MYRSS"
		};
	datas['renew'] = false;
	if(renew == 'true')
		datas['renew'] = true;

	$.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		dataType: 'json',
		data: datas,
		success: function(data){
			if(data.texte !== false && data.titre !== false)
			{
				$("#DIALOG").attr('title',data.titre)
				.empty()
				.append(data.texte)
				.dialog({

					autoOpen:false,
					closeOnEscape:true,
					resizable:false,
					draggable:false,
					modal:true,
					buttons:buttons,
					draggable:false,
						overlay: {
							backgroundColor: '#000',
							opacity: 0.7
						}
					}).dialog('open');

			}
		}
	});

}

function setPref(name,value)
{
	if(jQuery.data['pref_'+name] && jQuery.data['pref_'+name].abort)
	{
		jQuery.data['pref_'+name].abort();
		jQuery.data['pref_'+name] = false;
	}

	jQuery.data['pref_'+name] = $.ajax({
		type: "POST",
		url: "/prod/prodFeedBack.php",
		data: {
			action: "SAVEPREF",
			prop:name,
			value:value
		},
		timeout: function(){
			jQuery.data['pref_'+name] = false;
		},
		error: function(){
			jQuery.data['pref_'+name] = false;
		},
		success: function(data){
			jQuery.data['pref_'+name] = false;
			return;
		}
	});
}


function infoDialog(el)
{

	$("#DIALOG").attr('title','')
				.empty()
				.append(el.attr('infos'))
				.dialog({

		autoOpen:false,
		closeOnEscape:true,
		resizable:false,
		draggable:false,
		width:600,
		height:400,
		modal:true,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.7
			}
		}).dialog('open').css({'overflow-x':'auto','overflow-y':'auto'});
}
function manageSession(data, showMessages)
{
	if(typeof(showMessages) == "undefined")
		showMessages = false;

	if(data.status == 'disconnected' || data.status == 'session')
	{
			disconnected();
			return false;
	}
	if(showMessages)
	{
		var box = $('#notification_box');
		box.empty().append(data.notifications);

		if(box.is(':visible'))
			fix_notification_height();

		if($('.notification.unread',box).length > 0)
		{
			var trigger = $('#notification_trigger') ;
			$('.counter',trigger)
			.empty()
			.append('<div style="padding:1px 5px 0;">'+$('.notification.unread',box).length+'</div>')
			.css('visibility','visible');

		}
		else
			$('#notification_trigger .counter').css('visibility','hidden').empty();

		if($('#notification_box .reload_baskets').length > 0)
			refreshBaskets('current');
		else
		{
			if(data.changed.length > 0)
			{
				var current_open = $('.SSTT.ui-state-active');
				var current_sstt = current_open.length > 0 ? current_open.attr('id').split('_').pop() : false;
				if($.inArray(current_sstt, data.changed) >= 0)
					refreshBaskets('current');
			}
		}

		if(data.message != "")
		{
			if($('#MESSAGE').length == 0)
				$('body').append('<div id="#MESSAGE"></div>');
			$('#MESSAGE')
				.empty()
				.append(data.message+'<div style="margin:20px;"><input type="checkbox" class="dialog_remove" />'+language.hideMessage+'</div>')
				.attr('title','Global Message')
				.dialog({
					autoOpen:false,
					closeOnEscape:true,
					resizable:false,
					draggable:false,
					modal:true,
					close:function()
					{
						if($('.dialog_remove:checked',$(this)).length > 0)
							setTemporaryPref('message',0);
					}
				})
				.dialog('open');
		}
	}
	return true;
}



function disconnected()
{
	showModal('disconnected',{title:'Disconnection'});
}

function showModal(cas, options){

	var content = '';
	var button = {
			"OK": function(e)
			{
				hideOverlay(3);
				$(this).dialog("close");
				return;
			}};
	var escape = true;
	var onClose = function(){};

	switch (cas) {
		case 'timeout':
			content = language.serverTimeout;
			break;
		case 'error':
			content = language.serverError;
			break;
		case 'disconnected':
			content = language.serverDisconnected;
			escape=false;
			button = {"OK":function(e){
				self.location.replace(self.location.href);
			}};
			onClose = function(){
				self.location.replace(self.location.href);
			};
			break;
		case 'prompt':
			content = "<input type='text' value='' id='" + options.id + "' />";
			escape=false;
			button = {
				"OK":function(e){
					(options.callback)();
					hideOverlay(3);
					$(this).dialog("close");
				},
				"Cancel":function(e){
					hideOverlay(3);
					$(this).dialog("close");
				}
			};
			break;
	};

	var buttons = {"OK": function(e){$(this).dialog('close');}};

	$('#DIALOG').empty().append(content).attr('title',options.title).dialog({
		autoOpen:false,
		buttons: button,
		closeOnEscape :escape,
		resizable:false,
		draggable:false,
		modal:true,
		close:onClose
	}).dialog('open').dialog('option','buttons',buttons);
	return;

}

function showOverlay(n,appendto,callback){

	var div ="OVERLAY";
	if(typeof(n)!="undefined")
		div+=n;
	if($('#'+div).length == 0)
	{
		if(typeof(appendto)=='undefined')
			appendto = 'body';
		$(appendto).append('<div id="'+div+'" style="display:none;">&nbsp;</div>');
	}
	if(typeof(callback) != 'function')
		callback = function(){};
	$('#'+div).css({
		display: 'block',
		opacity: 0,
		right:0,
		bottom:0,
		position:'absolute',
		top:0,
		left:0
	}).addClass('overlay').fadeTo(500, 0.7).bind('click',function(){(callback)();});
	if ($.browser.msie && $.browser.version == '6.0') {
		$('select').css({
			visibility: 'hidden'
		});
	}
}

function hideDwnl(){
	hideOverlay(2);
	$('#MODALDL').css({
		'display': 'none'
	});
}

function hideOverlay(n){
	if ($.browser.msie && $.browser.version == '6.0') {
		$('select').css({
			visibility: 'visible'
		});
	}
	var div = "OVERLAY";
	if(typeof(n)!="undefined")
		div+=n;
	$('#'+div).hide().remove();
}
