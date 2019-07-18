$(function () {
});
function dbg() {
	var c = (!dbg.caller) ? 'dbg' : dbg.caller.name;
	if (c){
		//console.log('[' + c + ']', ...arguments);	// よもや IE で使えなかったとは…
		console.log('[' + c + ']', Array.from(arguments));
	} else {
		console.log('[dbg]', $.makeArray(arguments));
	}
}
function bsModal(opt){
    var modal = $('<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fa fa-lg fa-times"></i></span><span class="sr-only">Close</span></button><h4 class="h3" /></div><div class="modal-body" /></div></div>');
    var o = { title: '', content: '', header: '', footer: '', size: '' };
    $.extend(o,opt);
    if (o.size){
        $('.modal-dialog', modal).addClass('modal-'+o.size);
    }
    if (o.header){
        $('.modal-header', modal).append( o.header );
    }
    if (o.footer){
        $('.modal-content', modal).append( $('<div class="modal-footer"/>').append(o.footer) );
    }
    $('.modal-header h4', modal).html(o.title);
    $('.modal-body', modal).html(o.content);
    if (!!o.event){
        $.each(o.event, function(k,v){
            //console.log(k,v);
            var ev = Object.keys(v)[0];
            var fn = v[ev];
            //console.log(ev,fn);
            modal.on(ev,fn) });
    }
    modal.on('hidden.bs.modal', function(e){
        $(this).remove();
    }).modal('show');

  return modal;
}

function btnHistoryBack(e){
	window.history.back(-1);
	return false;
}
function btnToggleSidebar(e){
	//btn = $(this);
	var btn = ($(e.target).data('btn'))?$(e.target):$(e.target).closest('[data-btn]');
    dbg('btnToggleLeft',btn,$(this),e,e.target);
	$('.sidebar,.toggle-sidebar').toggleClass('show');
	var $m = $('.main');
	var c = $m.data('origclass');
	$m.data('origclass', $m.prop('class')).prop('class', c);
	dbg('origclass: '+c);
    $('[data-toggletext]', btn).each(function(i,e){
        //console.log('[data-toggletext]',i,e);
        var t = $(e).text();
        $(e).text($(e).data('toggletext'));
		$(e).data('toggletext', t);
		dbg('toggletext: '+t);
    });
    $('[data-toggleicon]', btn).each(function(i,e){
        //console.log('[data-toggleicon]',i,e);
        $.each($(e).data('toggleicon').split(' '), function(i,c){
            $(e).toggleClass(c);
			dbg('toggleicon: '+c);
        });
	});
	document.cookie = 'sidebar='+($('.sidebar').hasClass('show')?'show':'hide')+'; path=/;';
	dbg('sidebar='+($('.sidebar').hasClass('show')?'show':'hide'));
}
$(document).ready(function () {
	$('[data-checklength]').each(setCheckLength);
    $('body').off('click', '[data-btn]').on('click', '[data-btn]', function(e){
        e.preventDefault();
        var f = $.camelCase('btn-'+$(this).data('btn'));
        //console.log('[data-btn]',f);
        if (typeof window[f] == 'function'){
            //window[f]($(this));
            window[f](e);
        } else {
            //console.log(f+': function is not exists.');
        }
    });
});
function checkLength() {
	var $this = $(this);
	var chk = { max: 0, min: 0 };
	var cnt = countTextLengthPerLines($this.val(),$this.data('isvertical'));
	//dbg($this.data('checklength'));
	var opt = (''+($this.data('checklength')||'')).split('-');
	//dbg('opt',opt);
	if (opt.length>1){
		chk.max = 1*opt[1];
		chk.min = 1*opt[0];
	} else {
		chk.max = 1*opt[0];
	}
	var l = 1 * $this.data('checklines');
	var invalid = false;
	var t = $('[data-strlen="' + $this.attr('id') + '"]');
	var o = $this.data('origform') || '';
	var mes = [];
	if (t.length) {
		o = o.split(' ');
	} else {
		t.push($this);
		o.push($this.attr('id'));
		//o = [$this.attr('id')];
	}
	t.each(function (i, e) {
		var c;
		if (o.length>1){
			c = (!!cnt.count[i]) ? cnt.count[i] : 0;
			$('#' + o[i]).val((!!cnt.lines[i]) ? cnt.lines[i] : '');
		} else {
			c = (!!cnt.sum) ? cnt.sum : 0;
			$('#' + o[i]).val((!!cnt.lines[i]) ? cnt.lines[i] : '');
		}
		//$(e).text([c, w].join(' / ')).toggleClass('invalid', (c > w));
		var tglClass = 0;
		if (!!chk.max&&(c > chk.max)){
			invalid = true;
			tglClass = true;
			mes.push('超過：'+(c-chk.max)+'文字');
		} else
		if (!!chk.min&&(c < chk.min)){
			invalid = true;
			tglClass = true;
			mes.push('不足：'+(chk.min-c)+'文字');
		} else {
			tglClass = false;
			mes.push('- - - - - -');
		}
		$(e).text([c, chk.max].join(' / ')).toggleClass('invalid', tglClass);
		//dbg(i, e, chk, invalid,mes);
	});
	invalid |= (cnt.lines.length > l);
	var extraStr = '';
	if (!!l&&(cnt.lines.length > l)){
		mes.push('超過：'+(cnt.lines.length-l)+'行');

		for($i=l; $i<cnt.lines.length;$i++){
			extraStr += cnt.lines[$i] + "\n";
		}
		$this.data('extra',extraStr); //***行あふれ
	}
	//dbg('invalid', invalid,cnt, cnt.lines.length,l,(cnt.lines.length>l),mes);
	$this.data('checklength-error', mes.join('<br>'));
	if (invalid) {
		$this.addClass('invalid');
		var t = $this.offset().top; // ターゲットの位置取得
		var p = t - $(window).height();  // 画面下部からのターゲットの位置
		var w = $(window).scrollTop();
		if ((t > w)&&(w > p)){
			$this.popover('show');
		}
	} else {
		$this.removeClass('invalid');
		$this.popover('hide');
	}
}

function setCheckLength() {
	//console.log(i,el,this);
	//var $el = $(el);
	console.log(this);
	var $el = $(this);
	var w = 1 * $el.data('checklength');
	var l = 1 * $el.data('checklines');
	var o = $el.data('origform');
	/* */
	//var tip = (!!w ? (w + ' 文字' + (!!l ? (' / ' + l + ' 行') : '')) : '');
	//var tip = '<p class="checklengthError"></p>';
	//var tip = function(el){ dbg(el,el.data('checklength-error')); return el.data('checklength-error'); }($el);
	$el.popover({
		container: 'body',
		content: function(){ dbg($el,$el.data('checklength-error')); return $el.data('checklength-error'); },
		html: true,
		placement: 'left',
		trigger: 'manual'
	})
	/* */
	if (!!o) {
		console.log('o', o);
		var v = o.split(' ').map(function (e) { return $('#' + e).val(); }).join('\n').replace(/\n+$/,'');
		$el.val(v);
	}
	$el.on('keyup change', checkLength);//.trigger('change');
	$(window).on('scroll', function(){ $el.trigger('change'); });
	setTimeout(function () { $el.trigger('change'); }, 999);
}
function countTextLengthPerLines(str,is_vertical) {
	var ret = {};
	(typeof str !== 'undefined') || (str = '');
	ret.lines = ('' + str).split('\r').join('').split('\n');

	if(is_vertical == true){
		ret.count = ret.lines.map(function (x) { return x.length; });
	}else{
		ret.count = ret.lines.map(function (x) { return getLengthWithHalfWidth(x); });
	}

	ret.sum = ret.count.reduce(function (x, y) { return x + y; });
	return ret;
}

function getLengthWithHalfWidth(x){
	len = 0;
	str = escape(x);
	for (i=0;i<str.length;i++,len++) {
		if (str.charAt(i) == "%") {
			if (str.charAt(++i) == "u") {
				i += 3;
				len++;
			}
			i++;
		}
	}
	return len / 2;
}