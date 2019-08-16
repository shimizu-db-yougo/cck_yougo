		// 掲載順リスト
		function buttonStyle(O){
			var UpButton = document.getElementById('UPBUTTON');
			var DownButton = document.getElementById('DOWNBUTTON');
			var UpButton10 = document.getElementById('UPBUTTON10');
			var DownButton10 = document.getElementById('DOWNBUTTON10');
			for(var i=0;i<O.options.length;i++){
				if(O.options[i].selected){
					if(i!=0) UpButton.disabled = false;
					else UpButton.disabled = true;
					if(i!=(O.options.length-1)) DownButton.disabled = false;
					else DownButton.disabled = true;

					if(O.options.selectedIndex+10<O.options.length){
						DownButton10.disabled = false;
					}else{
						DownButton10.disabled = true;
					}

					if(O.options.selectedIndex+1-10>0){
						UpButton10.disabled = false;
					}else{
						UpButton10.disabled = true;
					}
				}
			}
		}
		function optionMove(MODE,idx) {
			var O = document.getElementById('SEL'+idx);
			for(var i=0;i<O.options.length;i++)
			if(O.options[i].selected) break;
			var tmpOption = O.removeChild(O.options[i]);
			O.insertBefore(tmpOption,O.options[i+MODE])

			buttonStyle(O);
		}

		// 教科書頻度
		$('.js-text-freq').bind('keydown keyup keypress change', function () {

			getTextPoint($(this).val(),"{{ yougo.termId }}");

		});

		// センター頻度
		$(".js-center-target").bind('click', function(e){

			getCenterPoint(e);

		});

		$('.js-center-submit').bind('click', function(e){
			var center_cnt = 0;
			var point_list = $('input[id^="center_main"],input[id^="center_sub"]');
			for(var i in point_list){
				console.log(point_list[i].value);
				if(point_list[i].value !== void 0){center_cnt += Number(point_list[i].value);}
			}

			var term_id = $('#term_id').val();

			$('#center_freq' + term_id + ' #center_freq').text(center_cnt);
			$('#center_freq' + term_id + ' input:hidden[name="center_freq_text"]').val(center_cnt);

			$('#centerModal').modal('hide');
		});

		// 解説内さくいん用語
		$(".js-explain-target").bind('click', function(e){
			// get target
			var target = $(e.currentTarget);
			var main_term_id = target.attr('main-term-id');

			$('#formindex').empty();
			// さくいん用語タグで囲まれた用語を抽出
			var match_regex = /(?<=\《c_SAK》).*?(?=\《\/c_SAK》)/g;
			var match_chr = '';
			var term_explain = $('#term_explain').val();

			match_chr = term_explain.match(match_regex);
			if(match_chr){
				console.log(match_chr);

				for(var i=0; i<match_chr.length; i++){
					$('#formindex').append(
							'<div class="col-lg-12">'
							+'<label class="col-sm-2">'+ match_chr[i] + '</label>'
							+'<div class="col-sm-4"><input class="form-control input-sm" type="text" name="index_kana" value=""></div>'
							+'<div class="col-sm-4"><input class="form-control input-sm" type="text" name="index_add_letter" value=""></div>'
							+'<label class="col-sm-2"></label>'
							+'</div>');
				}


			}

			$('#explainModal').modal();
		});

		// 用語登録
		$('.js-yougo-submit').bind('click', function(e){
			// 主用語
			var mainterm = {};
			mainterm.term_id = $('#form_mainterm [name=term_id]').text();
			mainterm.main_term = $('#form_mainterm [name=main_term]').val();
			mainterm.red_letter = $('#form_mainterm [name=red_letter]').prop("checked");
			mainterm.kana = $('#form_mainterm [name=kana]').val();
			mainterm.text_freq = $('#form_mainterm [name=text_freq]').val();
			mainterm.center_freq = $('#form_mainterm [id=center_freq]').text();
			mainterm.news_exam = $('#form_mainterm [name=news_exam]').prop("checked");
			mainterm.delimiter = $('#form_mainterm [name=delimiter]').val();
			mainterm.western_language = $('#form_mainterm [name=western_language]').val();
			mainterm.birth_year = $('#form_mainterm [name=birth_year]').val();
			mainterm.index_add_letter = $('#form_mainterm [name=index_add_letter]').val();
			mainterm.index_kana = $('#form_mainterm [name=index_kana]').val();
			mainterm.index_original_kana = $('#form_mainterm [name=index_original_kana]').val();
			mainterm.index_original = $('#form_mainterm [name=index_original]').val();
			mainterm.index_abbreviation = $('#form_mainterm [name=index_abbreviation]').val();
			mainterm.nombre = $('#form_mainterm [name=nombre]').text();
			mainterm.term_explain = $('#form_mainterm [id=term_explain]').val();
			mainterm.illust_filename = $('#form_illust [name=illust_filename]').val();
			mainterm.illust_caption = $('#form_illust [name=illust_caption]').val();
			mainterm.illust_kana = $('#form_illust [name=illust_kana]').val();
			mainterm.handover = $('#form_handover [id=handover]').val();

			// サブ用語
			var subterm = {sub_term_id:[],sub_term:[],red_letter:[],kana:[],text_freq:[],center_freq:[],news_exam:[],delimiter:[],delimiter_kana:[],index_add_letter:[],index_kana:[],nombre:[]};
			var form_subterm = $('.form_subterm');
			form_subterm.each(function(i,elem) {
				subterm.sub_term_id.push($(elem).find('#sub_term_id').val());
				subterm.sub_term.push($(elem).find('#sub_term').val());
				subterm.red_letter.push($(elem).find('#red_letter').prop("checked"));
				subterm.kana.push($(elem).find('#kana').val());
				subterm.text_freq.push($(elem).find('#text_freq').val());
				subterm.center_freq.push($(elem).find('#center_freq').text());
				subterm.news_exam.push($(elem).find('#news_exam').prop("checked"));
				subterm.delimiter.push($(elem).find('#delimiter').val());
				subterm.delimiter_kana.push($(elem).find('#delimiter_kana').val());
				subterm.index_add_letter.push($(elem).find('#index_add_letter').val());
				subterm.index_kana.push($(elem).find('#index_kana').val());
				subterm.nombre.push($(elem).find('#nombre').text());
			});

			console.log(subterm);
			mainterm.subterm = subterm;

			// 同対類用語
			var synterm = {syn_term_id:[],synonym_id:[],term:[],red_letter:[],text_freq:[],center_freq:[],news_exam:[],delimiter:[],index_add_letter:[],index_kana:[],nombre:[]};
			var form_synterm = $('.form_synterm');
			form_synterm.each(function(i,elem) {
				synterm.syn_term_id.push($(elem).find('#syn_term_id').val());
				synterm.synonym_id.push($(elem).find('#synonym_id').val());
				synterm.term.push($(elem).find('#syn_term').val());
				synterm.red_letter.push($(elem).find('#red_letter').prop("checked"));
				synterm.text_freq.push($(elem).find('#text_freq').val());
				synterm.center_freq.push($(elem).find('#center_freq').text());
				synterm.news_exam.push($(elem).find('#news_exam').prop("checked"));
				synterm.delimiter.push($(elem).find('#delimiter').val());
				synterm.index_add_letter.push($(elem).find('#index_add_letter').val());
				synterm.index_kana.push($(elem).find('#index_kana').val());
				synterm.nombre.push($(elem).find('#nombre').text());
			});

			console.log(synterm);
			mainterm.synterm = synterm;

			// 指矢印用語
			var refterm = {ref_idx:[],ref_term_id:[],nombre:[]};
			var form_refterm = $('.form_refterm');
			form_refterm.each(function(i,elem) {
				refterm.ref_idx.push($(elem).find('#ref_idx').val());
				refterm.ref_term_id.push($(elem).find('#ref_term').val());
				refterm.nombre.push($(elem).find('#nombre').text());
			});

			console.log(refterm);
			mainterm.refterm = refterm;

			$.post("{{ path('client.edit.save.ajax') }}", mainterm)
			.done(function(r,s){
				console.log('savePost:done',s,r);
				window.location.href = "{{ path('client.yougo.list') }}";
			})
			.fail(function(e,s){
				console.log('savePost:fail',s,e.status);
				alert('DB更新エラー');
			});
		});

		function constructSubForm(record){
			var html =
			'<div class="form-horizontal form_subterm">'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.sub_term"|trans }}</label>'
			+'<div class="col-sm-3"><input class="form-control input-sm" type="text" id="sub_term" name="sub_term" value="' + ((record) ? record.sub_term : '') + '"></div>'
			+'<label class="col-sm-1">{{ "text.yougo.red_letter"|trans }}</label>'
			+'<div class="col-sm-1"><input type="checkbox" id="red_letter" name="red_letter" ' + (((record)&&(record.red_letter == '1')) ? ' checked ' : '') + '></div>'
			+'<label class="col-sm-1">{{ "text.yougo.yomigana"|trans }}</label>'
			+'<div class="col-sm-3"><input class="form-control input-sm" type="text" id="kana" name="kana" value="' + (((record)&&(record.kana)) ? record.kana : '') + '"></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.freq.text"|trans }}</label>'
			+'<div class="col-sm-1"><input type="text" class="form-control input-sm js-text-freq2" term-id="' + ((record) ? record.id : '') + '" id="text_freq" name="text_freq" value="' + ((record) ? record.text_frequency : '') + '"></div>'
			+'<div class="col-sm-1"><div id="' + 'text_rank' + ((record) ? record.id : '') + '" value=""><div id="text_rank"></div></div></div>'
			+'<div class="col-sm-1"><button type="button" class="btn btn-primary btn-sm js-center-target2" term-id="' + ((record) ? record.id : '') + '" yougo-flag="2" data-toggle="modal" center-target="#centerModal">{{ "text.yougo.freq.center"|trans }}</button></div>'
			+'<div class="col-sm-1"><div id="' + 'center_freq' + ((record) ? record.id : '') + '" value=""><div id="center_freq" value="">' + ((record) ? record.center_frequency : '') + '</div><input type="hidden" name="center_freq_text" value="test"></div></div>'
			+'<label class="col-sm-1">{{ "text.yougo.news.exam"|trans }}</label>'
			+'<div class="col-sm-2"><input type="checkbox" id="news_exam" name="news_exam" ' + (((record)&&(record.news_exam == '1')) ? ' checked ' : '') + '></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.delimiter"|trans }}</label>'
			+'<div class="col-sm-1"><select class="form-control input-sm" id="delimiter" name="delimiter">'
					+'<option value="0">なし</option>';

			html += '<option value="1" ' + (((record)&&(record.delimiter == '1')) ? ' selected ' : '') + '>と</option>'
			+'<option value="2" ' + (((record)&&(record.delimiter == '2')) ? ' selected ' : '') + '>，</option>'
			+'<option value="3" ' + (((record)&&(record.delimiter == '3')) ? ' selected ' : '') + '>・</option>'
			+'<option value="4" ' + (((record)&&(record.delimiter == '4')) ? ' selected ' : '') + '>／</option>'
			+'<option value="5" ' + (((record)&&(record.delimiter == '5')) ? ' selected ' : '') + '>（</option>'
			+'<option value="6" ' + (((record)&&(record.delimiter == '6')) ? ' selected ' : '') + '>）</option>';

			html += '</select></div>'

			+'<label class="col-sm-1">{{ "text.yougo.delimiter_kana"|trans }}</label>'
			+'<div class="col-sm-1"><select class="form-control input-sm" id="delimiter_kana" name="delimiter_kana">'
					+'<option value="0">なし</option>';

			html += '<option value="1" ' + (((record)&&(record.delimiter_kana == '1')) ? ' selected ' : '') + '>と</option>'
			+'<option value="2" ' + (((record)&&(record.delimiter_kana == '2')) ? ' selected ' : '') + '>，</option>'
			+'<option value="3" ' + (((record)&&(record.delimiter_kana == '3')) ? ' selected ' : '') + '>・</option>'
			+'<option value="4" ' + (((record)&&(record.delimiter_kana == '4')) ? ' selected ' : '') + '>／</option>'
			+'<option value="5" ' + (((record)&&(record.delimiter_kana == '5')) ? ' selected ' : '') + '>（</option>'
			+'<option value="6" ' + (((record)&&(record.delimiter_kana == '6')) ? ' selected ' : '') + '>）</option>'
			+'<option value="7" ' + (((record)&&(record.delimiter_kana == '7')) ? ' selected ' : '') + '>）（</option>';

			html += '</select></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.index_add_letter"|trans }}</label>'
			+'<div class="col-sm-1"><input type="text" class="form-control input-sm" id="index_add_letter" name="index_add_letter" value="' + (((record)&&(record.index_add_letter)) ? record.index_add_letter : '') + '"></div>'
			+'<label class="col-sm-1">{{ "text.yougo.index_kana"|trans }}</label>'
			+'<div class="col-sm-3"><input type="text" class="form-control input-sm" id="index_kana" name="index_kana" value="' + (((record)&&(record.index_kana)) ? record.index_kana : '') + '"></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.nombre"|trans }}</label>'
			+'<label class="col-sm-1" id="nombre">{{ "' + ((record) ? record.nombre : '') + '" }}</label>'
			+'<div class="col-sm-1"><button type="button" class="btn btn-primary btn-sm js-sub-remove">{{ "btn.remove.subterm"|trans }}</button></div>'
			+'<input type="hidden" class="form-control input-sm" id="sub_term_id" name="sub_term_id" value="' + ((record) ? record.id : '') + '">'
			+'</div></div><hr></div>';
			return html;
		}

		function constructSynForm(record){
			var html =
			'<div class="form-horizontal form_synterm">'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.icon"|trans }}</label>'
			+'<div class="col-sm-1"><select class="form-control input-sm" id="synonym_id" name="synonym_id">'
			+'<option value="1" ' + (((record)&&(record.synonym_id == '1')) ? ' selected ' : '') + '>同</option>'
			+'<option value="2" ' + (((record)&&(record.synonym_id == '2')) ? ' selected ' : '') + '>対</option>'
			+'<option value="3" ' + (((record)&&(record.synonym_id == '3')) ? ' selected ' : '') + '>類</option>'
			+'</select></div>'
			+'<label class="col-sm-1">{{ "text.yougo.syn_term"|trans }}</label>'
			+'<div class="col-sm-3"><input class="form-control input-sm" type="text" id="syn_term" name="syn_term" value="' + ((record) ? record.term : '') + '"></div>'
			+'<label class="col-sm-1">{{ "text.yougo.red_letter"|trans }}</label>'
			+'<div class="col-sm-1"><input type="checkbox" id="red_letter" name="red_letter" ' + (((record)&&(record.red_letter == '1')) ? ' checked ' : '') + '></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.freq.text"|trans }}</label>'
			+'<div class="col-sm-1"><input type="text" class="form-control input-sm js-text-freq3" term-id="' + ((record) ? record.id : '') + '" id="text_freq" name="text_freq" value="' + ((record) ? record.text_frequency : '') + '"></div>'
			+'<div class="col-sm-1"><div id="' + 'text_rank' + ((record) ? record.id : '') + '" value=""><div id="text_rank"></div></div></div>'
			+'<div class="col-sm-1"><button type="button" class="btn btn-primary btn-sm js-center-target3" term-id="' + ((record) ? record.id : '') + '" yougo-flag="3" data-toggle="modal" center-target="#centerModal">{{ "text.yougo.freq.center"|trans }}</button></div>'
			+'<div class="col-sm-1"><div id="' + 'center_freq' + ((record) ? record.id : '') + '" value=""><div id="center_freq" value="">' + ((record) ? record.center_frequency : '') + '</div><input type="hidden" name="center_freq_text" value="test"></div></div>'
			+'<label class="col-sm-1">{{ "text.yougo.news.exam"|trans }}</label>'
			+'<div class="col-sm-2"><input type="checkbox" id="news_exam" name="news_exam" ' + (((record)&&(record.news_exam == '1')) ? ' checked ' : '') + '></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.delimiter"|trans }}</label>'
			+'<div class="col-sm-1"><select class="form-control input-sm" id="delimiter" name="delimiter">'
					+'<option value="0">なし</option>';

			html += '<option value="1" ' + (((record)&&(record.delimiter == '1')) ? ' selected ' : '') + '>全角スペース</option>'
			+'<option value="2" ' + (((record)&&(record.delimiter == '2')) ? ' selected ' : '') + '>（</option>'
			+'<option value="3" ' + (((record)&&(record.delimiter == '3')) ? ' selected ' : '') + '>）</option>'
			+'<option value="4" ' + (((record)&&(record.delimiter == '4')) ? ' selected ' : '') + '>改行</option>'
			+'<option value="5" ' + (((record)&&(record.delimiter == '5')) ? ' selected ' : '') + '>改行＋（</option>'
			+'<option value="6" ' + (((record)&&(record.delimiter == '6')) ? ' selected ' : '') + '>）＋改行</option>'

			html += '</select></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.index_add_letter"|trans }}</label>'
			+'<div class="col-sm-1"><input type="text" class="form-control input-sm" id="index_add_letter" name="index_add_letter" value="' + (((record)&&(record.index_add_letter)) ? record.index_add_letter : '') + '"></div>'
			+'<label class="col-sm-1">{{ "text.yougo.index_kana"|trans }}</label>'
			+'<div class="col-sm-3"><input type="text" class="form-control input-sm" id="index_kana" name="index_kana" value="' + (((record)&&(record.index_kana)) ? record.index_kana : '') + '"></div>'
			+'</div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.nombre"|trans }}</label>'
			+'<label class="col-sm-1" id="nombre">{{ "' + ((record) ? record.nombre : '') + '" }}</label>'
			+'<div class="col-sm-1"><button type="button" class="btn btn-primary btn-sm js-syn-remove">{{ "btn.remove.synterm"|trans }}</button></div>'
			+'<input type="hidden" class="form-control input-sm" id="syn_term_id" name="syn_term_id" value="' + ((record) ? record.id : '') + '">'
			+'</div></div><hr></div>';
			return html;
		}

		function constructRefForm(record){
			var html =
			'<div class="form-horizontal form_refterm">'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.head.hen"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-hen input-sm" name="hen">'
				+'<option value="0">--------------</option>'
				+'{% for hen in hen_list %}'
					+'<option value="{{ hen.hen }}" {{ hen.hen == select_hen ? " selected " : "" }}>{{ hen.name }}</option>'
				+'{% endfor %}'
			+'</select></div>'
			+'<label class="col-sm-1">{{ "text.yougo.head.sho"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-sho input-sm" name="sho" disabled>'
				+'<option value="0">--------------</option>'
				+'{% for sho in sho_list %}'
					+'<option value="{{ sho.sho }}" {{ sho.sho == select_sho ? " selected " : "" }}>{{ sho.name }}</option>'
				+'{% endfor %}'
			+'</select></div>'
			+'<label class="col-sm-1">{{ "text.yougo.head.dai"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-dai input-sm" name="dai" disabled>'
				+'<option value="0">--------------</option>'
				+'{% for dai in dai_list %}'
					+'<option value="{{ dai.dai }}" {{ dai.dai == select_dai ? " selected " : "" }}>{{ dai.name }}</option>'
				+'{% endfor %}'
			+'</select></div></div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.head.chu"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-chu input-sm" name="chu" disabled>'
				+'<option value="0">--------------</option>'
				+'{% for chu in chu_list %}'
					+'<option value="{{ chu.chu }}" {{ chu.chu == select_chu ? " selected " : "" }}>{{ chu.name }}</option>'
				+'{% endfor %}'
			+'</select></div>'
			+'<label class="col-sm-1">{{ "text.yougo.head.ko"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-ko input-sm" name="ko" disabled>'
				+'<option value="0">--------------</option>'
				+'{% for ko in ko_list %}'
					+'<option value="{{ ko.ko }}" {{ ko.ko == select_ko ? " selected " : "" }}>{{ ko.name }}</option>'
				+'{% endfor %}'
			+'</select></div>'
			+'<label class="col-sm-1">{{ "text.yougo.refer_term"|trans }}</label>'
			+'<div class="col-sm-3"><select class="form-control js-term input-sm" name="ref_term" id="ref_term">'
			+'{% for refer_term in main_term_list %}'
				+'<option value="{{ refer_term.termId }}">{{ refer_term.mainTerm }}</option>'
			+'{% endfor %}'
			+'</select></div></div></div>'
			+'<div class="form-group"><div class="col-lg-12">'
			+'<label class="col-sm-1">{{ "text.yougo.nombre"|trans }}</label>'
			+'<label class="col-sm-1" id="nombre">{{ "' + ((record) ? record.nombre : '') + '" }}</label>'
			+'<div class="col-sm-1"><button type="button" class="btn btn-primary btn-sm js-ref-remove">{{ "btn.remove.refterm"|trans }}</button></div>'
			+'<input type="hidden" class="form-control input-sm" id="ref_idx" name="ref_idx" value="' + ((record) ? record.id : '') + '">'
			+'</div></div><hr></div>';
			return html;
		}

		function getTextPoint(value,term_id){
			var rank = '';
			if(value >= 6){
				rank = 'A';
			}else if((value >= 3)&&(value <= 5)){
				rank = 'B';
			}else if((value >= 1)&&(value <= 2)){
				rank = 'C';
			}
			$('#text_rank' + term_id + ' #text_rank').text(rank);
		}

		function getCenterPoint(e){
			// get target
			var target = $(e.currentTarget);
			var term_id = target.attr('term-id');
			var yougo_flag = target.attr('yougo-flag');

			$('#term_id').val(term_id);
			$('#formcenter').empty();

			// センター頻度の取得
			var data = {
				'term_id': term_id,
				'yougo_flag': yougo_flag
			};
			$.ajax({
				url: "{{ path('client.yougo.center.ajax') }}",
				data: data,
				method: 'POST',
				success: function(response){
					var html = '<div class="row"><div class="col-sm-12"><label class="col-sm-4"></label>'
					+'<label class="col-sm-4">本試</label><label class="col-sm-4">追試</label>'
					+'</div>'
					$('#formcenter').append(html);

					$.each(response, function(key, list){
						console.log(key);
						console.log(list.year);

						html = '<div class="row"><div class="col-sm-12"><label class="col-sm-4">' + list.year + '</label>'
						+'<input type="text" class="col-sm-4" id="center_main' + list.year + '" name="main' + list.year + '" value="' + list.mainExam + '">'
						+'<input type="text" class="col-sm-4" id="center_sub' + list.year + '" name="sub' + list.year + '" value="' + list.subExam + '">'
						+'</div>';

						$('#formcenter').append(html);
					});
				},
				error: function(response){
				}
			});

			$('#centerModal').modal();

		}