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
	// 발송목록 검색하기 버튼 클릭
	$('#submit_btn').on('click', function () {
		$.ajax({
			type: "post",
			url: 'getData.php',
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

				$('#data-table').DataTable().clear();
				$('#data-table').DataTable().rows.add(dataset);
				$('#data-table').DataTable().draw();
			}
		});
	});

	$('#reset_btn').on('click', function () {
		//location.reload();
		$('form').each(function() {this.reset(); });
	});

	// 발송통계검색하기 버튼 클릭
	$('#submit_btn_group').on('click', function () {
		$.ajax({
			type: "post",
			url: 'getData.php',
			async: false,
			data: { 'data': $("#group-form").serialize(), 'first' : first },
			success: function (data) {
				//console.log(data); //test
				first = 'false';
				var obj = $.parseJSON(data);
				var dataset = [];
				var temp = [];
				var labelName = new Array();
				var labelRowName = new Array();
				var labelCnt = new Array();

				 $.each(obj, function (k, v) {
					 temp = [];
					 temp.push(v.sendDate);
					 temp.push(v.searchName);					 
					 temp.push(setComma(v.cnt));
					 temp.push(setComma(v.s_cnt));
					 temp.push(setComma(v.e_cnt));
					 dataset.push(temp);

					 labelName[k] = v.searchName;
					 labelRowName[k] = v.searchName;
					 labelCnt[k] = v.cnt;
				 });

				$('#data-table-group').DataTable().clear();
				$('#data-table-group').DataTable().rows.add(dataset);
				$('#data-table-group').DataTable().draw();

				$("#typeName").text($("#searchName option:selected").text());

/*
				var ctx = document.getElementById('myChart').getContext('2d');
				var colors = ['#007bff','#28a745','#333333','#c3e6cb','#dc3545','#6c757d'];

				var chart = new Chart(ctx, {
					type: 'bar',
					data: {
						labels: labelRowName,
						datasets: [{
							label: labelName,
							data: labelCnt,
							backgroundColor: '#007bff'
						}]
					}

				});
*/


			}
		});
	});

	$('#reset_btn_group').on('click', function () {
		$('form').each(function() {this.reset(); });
		$('#data-table-group').DataTable().clear();
		$('#data-table-group').DataTable().draw();
	});


	$('.nav-tabs').on('click', function () {
		$('form').each(function() {this.reset(); });
		$('#data-table').DataTable().clear();
		$('#data-table').DataTable().draw();
		$('#data-table-group').DataTable().clear();
		$('#data-table-group').DataTable().draw();
	});	

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
				"defaultContent": "<button>More</button>"
			}
		]
	});

	var table_group = $('#data-table-group').DataTable({
		"processing": true,
		"iDisplayLength": 50,
		"lengthMenu": [30, 50, 100, 200],
		"bFilter": false,
		"orderCellsTop": true,
		"bAutoWidth": true,
		"order": [[0, "asc"]]
	});

	$('#data-table tbody').on( 'click', 'button', function () {
		row_data = table.row($(this).parents('tr')).data();
		console.log(row_data); //test
		$.ajax({
			type: "post",
			url: 'getData.php',
			async: false,
			data: {'data': 'IDX=' + row_data[0]+'&dataType=list', 'date_select' : $('#date_select').val() },
			success: function (data) {
				var obj = $.parseJSON(data);
				$('#EXT1_form').hide();
				//console.log(row_data[0]+","+$('#date_select').val()+","+data); //test

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
				$('.bs-example-modal-lg').modal('show');
			}
		});

	});

	$(':button[name="re_send"]').on('click', function() {
		$('.bs-example-modal-lg').modal('hide');
		$('.bs-example-modal-sm').modal('show');
		$('#s_modal_tel').val($('#modal_tel').val());
		$('#s_modal_text').val($('#modal_text').val());
		$('#s_modal_subject').val($('#modal_subject').val());
	});

	$('#go_send').on('click', function() {
		$.ajax({
			type: "post",
			url: 'send_sms.php',
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

				// 발송한여부를 그냥 일단 띄움
				alert(obj[1]);
				$('.bs-example-modal-sm').modal('hide');
			}
		});
	});

	// 이제 조회시작!!!
	//$('#submit_btn').click();
});

var submitAction = function() { return false; };
$('form').bind('submit', submitAction);


