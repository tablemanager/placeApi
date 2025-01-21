var row_data;
var first = 'true';

function setComma(nStr)
{
	str = String(nStr);
	return str.replace(/(\d)(?=(?:\d{3})+(?!\d))/g, '$1,');
}
function unsetComma(str) {
	str = String(str);
	return str.replace(/[^\d]+/g, '');
}

function excelDownload(table){	
	if(table && table.length){
		$(table).table2excel({
			exclude: ".noExl",
			name: "엑셀다운로드",
			filename: "Excel" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
			fileext: ".xlsx",
			exclude_img: true,
			exclude_links: true,
			exclude_inputs: true,
			preserveColors: false
		});
	}
}


jQuery(document).ready(function() {

	//탭클릭하면 초기화
	$('.nav-tabs').on('click', function () {
		$('form').each(function() {this.reset(); });
		$('#data-table').DataTable().clear();
		$('#data-table').DataTable().draw();
		$('#data-table-group').DataTable().clear();
		$('#data-table-group').DataTable().draw();
		$('#data-table-setting').DataTable().clear();
		$('#data-table-setting').DataTable().draw();
		$("#container-group").show();	$("#chart_div").hide();
	});	


	// 발송목록 검색하기 버튼 클릭
	$('#submit_btn').on('click', function () {
		if(
			$("#msg_type option:selected").val()=="" && 
			$("#tel").val()=="" && 
			$("#order_Num").val()=="" && 
			$("#msg_type option:selected").val()==""
		){
			alert("검색조건을 1개 이상 선택하십시오.");
			$("#msg_type").focus();
			return;
		}
		$.ajax({
			type: "post",
			url: './getMsgList',
			async: false,
			data: { 'data': $("#list-form").serialize(), 'first' : first },
			success: function (data) {
				//console.log(data); //test
				first = 'false';
				var obj = $.parseJSON(data);
				var dataset = [];
				var temp = [];

				$.each(obj, function (k, v) {
					temp = [];
					temp.push(v.IDX);
					temp.push(v.DATE);
					temp.push(v.TYPE);
					temp.push(v.TEL);
					temp.push(v.ORDERNO);
					temp.push(v.COUPONNO);
					temp.push(v.COUPON_TYPE);
					temp.push(v.STAT);
					dataset.push(temp);
				});

				 //console.log(dataset); //test

				$('#data-table').DataTable().clear();
				$('#data-table').DataTable().rows.add(dataset);
				$('#data-table').DataTable().draw();
			}
		});
	});


	//발송목록 스타일
	var table = $('#data-table').DataTable({
		"processing": true,
		"iDisplayLength": 30,
		"lengthMenu": [30, 50, 75, 100],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": true,
		"order": [[0, "desc"]],
		"columnDefs": [
			{
				"targets": -1,
				"data": null,
				"defaultContent": "<button><span class='glyphicon glyphicon-zoom-in' aria-hidden='true'></span></button>"
			}
		]
	});



	//발송목록 자세히 보기 클릭
	$('#data-table tbody').on( 'click', 'button', function () {
		row_data = table.row($(this).parents('tr')).data();
		//console.log(row_data); //test
		//console.log(row_data[0]+","+$('#date_select').val()); //test
		$("#IDX").val(row_data[0]);
		$.ajax({
			type: "post",
			url: './getMsgList',
			async: false,
			data: { 'data': $("#list-form").serialize() },
			success: function (data) {
				//console.log(data); //test
				var obj = $.parseJSON(data);
				$('#EXT1_form').hide();
				
				$.each(obj, function (k, v) {
					$('#modal_date').val(v.TYPE + " / " + v.DATE);
					$('#modal_msg_type').val(v.TYPE);
					$('#modal_tel').val(v.TEL);
					$('#modal_callback').val(v.CALLBACK);
					$('#modal_orderno').val(v.ORDERNO);
					$('#modal_couponno').val(v.COUPONNO);
					$('#modal_coupon_type').val(v.COUPON_TYPE);
					$('#modal_stat').val(v.STAT + " / " + v.ERR_MSG);
					$('#modal_subject').val(v.MSG_SUBJECT);
					$('#modal_text').val(v.MSG_TEXT);
					$('#modal_results').val(v.RESULTS);
					$('#modal_profile').val(v.PROFILE);
					$('#modal_ext1').val(v.EXTVAL1);
					$('#modal_ext2').val(v.EXTVAL2);
					$('#modal_ext3').val(v.EXTVAL3);
					$('#modal_ext4').val(v.EXTVAL4);
					$('#modal_file').val(v.FILELOC1);
					// $('#modal_err_msg').val(v.ERR_MSG);
				});

				if ($('#modal_msg_type').val() == "KAKAO") {
					var ext1 = $.parseJSON($('#modal_ext1').val());
					var temp = JSON.stringify(ext1);
					$('#EXT1_TEXTAREA').val(temp);
					$('#EXT1_form').show();
				}

				$('#myModalLabel').text("문자 발송 데이터 ( IDX : " + row_data[0] + " )");
				$('#modalMsgInfo').modal('show');
			}
		});

	});



	//발송목록 재전송 버튼
	$(':button[name="re_send"]').on('click', function() {
		$('.bs-example-modal-lg').modal('hide');
		$('#modalSend').modal('show');
		$('#s_modal_tel').val($('#modal_tel').val());
		$('#s_modal_text').val($('#modal_text').val());
		$('#s_modal_subject').val($('#modal_subject').val());
	});

	//발송목록 메세지 전송하기 버튼
	$('#go_send').on('click', function() {
		if($('#modal_msg_type').val()==""){
			alert("메세지타입이 존재하지 않아서 전송이 불가합니다.");
			return;
		}
		$.ajax({
			type: "post",
			url: './sendMsg',
			async: false,
			data: {
				'tel': $('#s_modal_tel').val(),
				'text': $('#s_modal_text').val(),
				'subject': $('#s_modal_subject').val(),
				'callback': $('#modal_callback').val(),					
				'orderno': $('#modal_orderno').val(),
				'type': $('#modal_msg_type').val(),
				'profile': $('#modal_profile').val(),
				'ext1': $('#modal_ext1').val(),
				'ext2': $('#modal_ext2').val(),
				'ext3': $('#modal_ext3').val(),
				'ext4': $('#modal_ext4').val(),
				'couponno': $('#modal_couponno').val(),
				'coupon_type': $('#modal_coupon_type').val(),
				'file': $('#modal_file').val(),
				
			},
			success: function (data) {
				var obj = $.parseJSON(data);
				alert(obj.msg);

				// 발송한여부를 그냥 일단 띄움
				//alert(obj[1]);
				$('.bs-example-modal-sm').modal('hide');
			}
		});
	});


	///////////////////////////////////////////////////////////////////////////

	// 발송통계 검색하기 버튼 클릭
	$('#submit_btn_group').on('click', function () {
		$.ajax({
			type: "post",
			url: './getMsgList',
			async: false,
			data: { 'data': $("#group-form").serialize(), 'first' : first },
			success: function (data) {
				//console.log(data); //test
				first = 'false';
				var obj = $.parseJSON(data);
				var dataset = [];
				var temp = [];
				var labelColName = new Array();
				var labelKAKAO = new Array();
				var labelLMS = new Array();
				var labelMMS = new Array();
				var labelSMS = new Array();
				var labelCnt = new Array();
				var sendDate = "", row=0;

				$.each(obj, function (k, v) {
					temp = [];
					temp.push(v.sendDate);
					temp.push(v.searchName);					 
					temp.push(setComma(v.cnt));
					temp.push(setComma(v.s_cnt));
					temp.push(setComma(v.e_cnt));
					dataset.push(temp);

					if(v.searchName=="KAKAO"){
						labelKAKAO[row] = eval(v.cnt);
					}else if(v.searchName=="LMS"){
						labelLMS[row] = eval(v.cnt);
					}else if(v.searchName=="MMS"){
						labelMMS[row] = eval(v.cnt);						
					}else if(v.searchName=="SMS"){
						labelSMS[row] = eval(v.cnt);
					}


					if(sendDate != v.sendDate){
						labelColName[row] = v.sendDate.substr(-2);
						if($("#dateFormat  option:selected").val()=="dd"){ // 일별
							row++;					
						}
					}

					if($("#dateFormat  option:selected").val()!="dd"){ // 월별
						labelColName[k] = v.searchName;
						labelCnt[k] = eval(v.cnt);	
					}					

					sendDate = v.sendDate;
				});

				//console.log("labelColName="+labelColName+", labelCnt="+labelCnt); //test

				if($("#viewType option:selected").val()=="list"){ //목록					
					
					$("#container-group").show();	$("#chart_div").hide();
					$('#data-table-group').DataTable().clear();
					$('#data-table-group').DataTable().rows.add(dataset);
					$('#data-table-group').DataTable().draw();
					$("#typeName").text($("#searchName option:selected").text());

				}else{ //차트
					
					$("#container-group").hide(); $("#chart_div").show();
					google.charts.load('current', {'packages':['corechart']});
					google.charts.setOnLoadCallback(drawChart);

					function drawChart() {

						var titleName="";
						var data = new google.visualization.DataTable();
						var dataRow = [];
						if($("#dateFormat  option:selected").val()=="dd"){ // 일별
							data.addColumn('string', 'Task');
							data.addColumn('number', 'KAKAO');
							data.addColumn('number', 'LMS');
							data.addColumn('number', 'MMS');
							data.addColumn('number', 'SMS');
							for(var i=0; i<labelColName.length; i++){								
								dataRow = [labelColName[i], labelKAKAO[i], labelLMS[i], labelMMS[i], labelSMS[i]];								
								data.addRow(dataRow);
							}
							
							titleName = $("#date_select_group  option:selected").val().substr(-2)+"월 일별 ";
						}else{ //월별
							data.addColumn('string', 'Task');
							data.addColumn('number', '문자발송건수');
							for(var i=0; i<labelColName.length; i++){	
								dataRow = [labelColName[i], labelCnt[i]];
								data.addRow(dataRow);
								//console.log(dataRow);
							}
							titleName = $("#date_select_group  option:selected").val().substr(-2)+"월 ";
						}

						var options = {
							title: titleName+'문자 발송 통계',
							fontSize: 13,
							width: '100%',
							animation: {startup: true,duration: 800,easing: 'in' },
							vAxis:{textStyle:{fontSize: 10}},
							hAxis:{textStyle:{fontSize: 10}},
							legend: { position: 'bottom' },
							chartArea: {width: '100%', height: '80%'},
						};

						var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
						chart.draw(data, options);
					}

				}


			}
		});
	});


	//발송통계 스타일
	var table_group = $('#data-table-group').DataTable({
		"processing": true,
		"iDisplayLength": 50,
		"lengthMenu": [30, 50, 100, 200],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": false,
		"order": [[0, "asc"]]
	});


	///////////////////////////////////////////////////////////////////////////

	//사용자관리 목록
	$("#settingTab").on('click', function() {		
		$.ajax({
			type: "post",
			url: './getAuth',
			success: function (data) {
				//console.log(data); //test
				first = 'false';
				var obj = $.parseJSON(data);
				var dataset = [];
				var temp = [];

				 $.each(obj, function (k, v) {
					 temp = [];
					 temp.push(v.IDX);
					 temp.push(v.NAME);					 
					 temp.push(v.PROFILE_ID);
					 temp.push(v.CODE);
					 temp.push(v.REGDATE);
					 temp.push(setComma(v.SEND_CNT));
					 dataset.push(temp);

				 });

				$('#data-table-setting').DataTable().clear();
				$('#data-table-setting').DataTable().rows.add(dataset);
				$('#data-table-setting').DataTable().draw();

			}
		});
	});

	//사용자관리 스타일
	var table_setting = $('#data-table-setting').DataTable({
		"processing": true,
		"iDisplayLength": 50,
		"lengthMenu": [30, 50, 100, 200],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": true,
		"order": [[0, "asc"]],
		"columnDefs": [
			{
				"targets": -1,
				"data": null,
				"defaultContent": "<button><span class='glyphicon glyphicon-zoom-in' aria-hidden='true'></span></button>"
			}
		]
	});

	//사용자관리 자세히 보기 클릭
	$('#data-table-setting tbody').on( 'click', 'button', function () {
		row_data = table_setting.row($(this).parents('tr')).data();
		console.log(row_data); //test
		$.ajax({
			type: "post",
			url: './getAuth',
			async: false,
			data: { 'code': row_data[3] },
			success: function (data) {
				//console.log(data); //test
				var obj = $.parseJSON(data);
				
				$('#u_idx').val(obj.IDX);
				$('#u_name').val(obj.NAME);
				$('#u_profile_id').val(obj.PROFILE_ID);
				$('#u_code').val(obj.CODE);

				$('#modalUserInfo').modal('show');
			}
		});
	});

	//사용자관리 신규등록 클릭
	$('#setting_reg').on( 'click', function () {
		$('#u_idx').val(0);
		$('#u_name').val('');
		$('#u_profile_id').val('');
		$('#u_code').val('');
		$("#key_create").show();

		$('#modalUserInfo').modal('show');
		
	});

	//키생성
	$('#key_create').on( 'click', function () {
		$.ajax({
			type: "get",
			url: './genRandomStr',
			async: false,
			success: function (data) {		
				$('#u_code').val(data);
			}
		});

	});



	//사용자 정보 저장 버튼
	$('#u_send').on('click', function() {
		if($('#u_name').val()==""){
			alert("사용처를 입력하세요.");	return;
		}
		if($('#u_profile_id').val()==""){
			alert("PROFILE ID를 입력하세요.");	return;
		}
		if($('#u_code').val()==""){
			alert("API 키를 입력하세요.");	return;
		}
		$.ajax({
			type: "post",
			url: './setAuth',
			async: false,
			data: {
				'idx': $('#u_idx').val(),
				'name': $('#u_name').val(),
				'profile_id': $('#u_profile_id').val(),
				'code': $('#u_code').val()				
			},
			success: function (data) {
				var obj = $.parseJSON(data);
				//console.log(obj); //test
				alert(obj.msg);
				$('.bs-example-modal-sm').modal('hide');

				$("#settingTab").click();
			}
		});

	});


	///////////////////////////////////////////////////////////////////////////

	//ip 목록
	$("#ipTab").on('click', function() {		
		$.ajax({
			type: "post",
			url: './getIP',
			success: function (data) {
				//console.log(data); //test
				first = 'false';
				var obj = $.parseJSON(data);
				var dataset = [];
				var temp = [];

				 $.each(obj, function (k, v) {
					 temp = [];
					 temp.push(v.IDX);
					 temp.push(v.NAME);					 
					 temp.push(v.IP);
					 temp.push(v.REGDATE);
					 dataset.push(temp);
				 });

				$('#data-table-ip').DataTable().clear();
				$('#data-table-ip').DataTable().rows.add(dataset);
				$('#data-table-ip').DataTable().draw();

			}
		});
	});

	//ip 스타일
	var table_ip = $('#data-table-ip').DataTable({
		"processing": true,
		"iDisplayLength": 50,
		"lengthMenu": [30, 50, 100, 200],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": true,
		"order": [[0, "asc"]],
		"columnDefs": [
			{
				"targets": -1,
				"data": null,
				"defaultContent": "<button><span class='glyphicon glyphicon-zoom-in' aria-hidden='true'></span></button>"
			}
		]
	});

	//ip 자세히 보기 클릭
	$('#data-table-ip tbody').on( 'click', 'button', function () {
		row_data = table_ip.row($(this).parents('tr')).data();
		//console.log(row_data); //test
		$.ajax({
			type: "post",
			url: './getIP',
			async: false,
			data: { 'idx': row_data[0] },
			success: function (data) {
				//console.log(data); //test
				var obj = $.parseJSON(data);
				
				$('#ip_idx').val(obj.IDX);
				$('#ip_name').val(obj.NAME);
				$('#ip_ip').val(obj.IP);

				$('#modalIP').modal('show');
			}
		});
	});

	//ip 신규등록 클릭
	$('#ip_reg').on( 'click', function () {
		$('#ip_idx').val(0);
		$('#ip_name').val('');
		$('#ip_ip').val('');
		$('#modalIP').modal('show');
		
	});

	//ip 저장 버튼
	$('#ip_send').on('click', function() {
		if($('#ip_name').val()==""){
			alert("사용처를 입력하세요.");	return;
		}
		if($('#ip_ip').val()==""){
			alert("IP를 입력하세요.");	return;
		}
		//console.log("idx="+$('#ip_idx').val()+",name="+$('#ip_name').val()+",ip="+$('#ip_ip').val()); //test
		$.ajax({
			type: "post",
			url: './setIP',
			async: false,
			data: {
				'idx': $('#ip_idx').val(),
				'name': $('#ip_name').val(),
				'ip': $('#ip_ip').val()		
			},
			success: function (data) {
				console.log("data="+data); //test
				var obj = $.parseJSON(data);				
				alert(obj.msg);
				$('.bs-example-modal-sm').modal('hide');

				$("#ipTab").click();
			}
		});
	});


	///////////////////////////////////////////////////////////////////////////

	//카카오 템플릿 목록
	var kakao_template=[];
	$("#kakaoTab").on('click', function() {	
		if(!$("#kakao_profile option:selected").val())return;
		$.ajax({
			type: "post",
			url: '../api/KAKAO_TEMPLATE',
			data: {
				'kakao_profile': $("#kakao_profile option:selected").val()
			},
			success: function (data) {
				//console.log(data); //test
				var result = $.parseJSON(data);
				console.log(result); //test
				var dataset = [];
				var temp = [];
				kakao_template = [];
				//console.log("code="+result.code);
				var obj = result.data;
				 $.each(obj, function (k, v) {
					 temp = [];
					 temp.push(k+1);
					 temp.push(v.templateName);					 
					 temp.push(v.templateCode);
					 temp.push(v.status);
					 temp.push(v.createdAt);
					 dataset.push(temp);
					 kakao_template[k+1] = v.templateContent;
				 });
				$('#data-table-kakao').DataTable().clear();
				$('#data-table-kakao').DataTable().rows.add(dataset);
				$('#data-table-kakao').DataTable().draw();
			}
		});
	});

	$("#kakao_profile").on('change', function() {
		$("#kakaoTab").click();
	});

	//카카오 템플릿 스타일
	var table_kakao = $('#data-table-kakao').DataTable({
		"processing": true,
		"iDisplayLength": 50,
		"lengthMenu": [30, 50, 100, 200],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": true,
		"order": [[0, "asc"]],
		"columnDefs": [
			{
				"targets": -1,
				"data": null,
				"defaultContent": "<button><span class='glyphicon glyphicon-zoom-in' aria-hidden='true'></span></button>"
			}
		]
	});

	//카카오 템플릿 자세히 보기 클릭
	$('#data-table-kakao tbody').on( 'click', 'button', function () {
		row_data = table_kakao.row($(this).parents('tr')).data();
		$("#templateName").val(row_data[1]);
		$("#templateCode").val(row_data[2]);
		var templateContent = kakao_template[row_data[0]];
		$("#templateContent").val(templateContent);
		$('#modalKakao').modal('show');
		
	});


	///////////////////////////////////////////////////////////////////////////

	//dev
	$("#devTab").on('click', function() {		
		$.ajax({
			type: "get",
			url: '../assets/help.html?ver=1.0',
			success: function (data) {
				//console.log(data); //test				
				$('#dev').html(data);				
			}
		});
	});


});

var submitAction = function() { return false; };
$('form').bind('submit', submitAction);


