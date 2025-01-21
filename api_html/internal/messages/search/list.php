<?php
require_once ('/home/sparo.cc/api_html/internal/messages/lib/sms_lib.php');

$ip = get_ip();
if ($ip != "106.254.252.100" && $ip != "118.131.208.123" && $ip != "115.89.22.27" && $ip != "218.39.39.190"  && $ip != "115.92.242.187" && $ip != "115.92.242.18" ) {
    header("HTTP/1.0 404 Not Found");
    exit;


}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS / KAKAO 조회 <?=$ip?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <style>
        .noresize {
            resize: none; /* 사용자 임의 변경 불가 */
        }

        .navbar {
            min-height: 20px;
            max-height: 45px;
        }

    </style>
	<script src="https://code.jquery.com/jquery-2.2.4.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

	<script src="https://res.cloudinary.com/dxfq3iotg/raw/upload/v1569818907/jquery.table2excel.min.js"></script>

	<script src="../js/common.js"></script>
</head>

<body>
<!--
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
			<button type="button" class="btn btn-primary">발송목록</button>
			<button type="button" class="btn btn-default">발송통계</button>
			<button type="button" class="btn btn-default">환경설정</button>
        </div>
        <div id="navbar" class="navbar-collapse collapse">

        </div>
    </div>
</nav>
-->
<br><br><br>

<ul class="nav nav-tabs" role="tablist" style="margin-left:205px">
    <li role="presentation" class="active"><a href="#list" aria-controls="list" role="tab" data-toggle="tab">발송목록</a></li>
    <li role="presentation"><a href="#static" aria-controls="static" role="tab" data-toggle="tab">발송통계</a></li>
    <!--<li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">환경설정</a></li>-->
</ul>


<div class="tab-content">

	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="list" role="tabpanel" class="tab-pane active">

		<!-- Main jumbotron for a primary marketing message or call to action -->
		<div class="jumbotron">
			<div class="container">
				<!--
				<h2>카카오 알림톡 / TOAST 문자 발송 확인페이지 <?=$ip?></h2><br>
				-->




				<form class="navbar-form" id="list-form">
		<!--            <div class="form-group">-->
		<!--                <input type="date" name="sdate" placeholder="" value="--><?php //echo date("Y-m-d")?><!--" class="form-control" min="--><?php //echo date("Y-m")."-01" ?><!--" max='--><?php //echo date("Y-m-d")?><!--' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"> ~-->
		<!--            </div>-->
		<!--            <div class="form-group">-->
		<!--                <input type="date" name="edate" placeholder="" value="--><?php //echo date("Y-m-d")?><!--" class="form-control" min="--><?php //echo date("Y-m")."-01" ?><!--" max='--><?php //echo date("Y-m-d")?><!--' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">-->
		<!--            </div>-->
					<div class="form-group">
						<select class="form-control" name="date_select" id="date_select">
							<?php
							for($i = 0; $i <= 12; $i++) $temp[] = date("Y-m", strtotime("first day of -{$i} month"));
							foreach ($temp as $v) :
							?>
							<option value="<?=$v?>"><?=$v."월"?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<select class="form-control" name="msg_type">
							<option value="ALL">전체 조회</option>
							<option value="S">SMS</option>
							<option value="M">MMS</option>
							<option value="L">LMS</option>
							<option value="K">KAKAO</option>
						</select>
					</div>
					<div class="form-group">
						<input type="text" name="tel" placeholder="전화번호 뒷자리" class="form-control" maxlength="4" style="width:140px !important;">
					</div>
					<div class="form-group">
						<input type="text" name="order_Num" placeholder="주문번호" class="form-control">
					</div>
					<div class="form-group">
						<select class="form-control" name="msg_res">
							<option value="ALL">성공여부</option>
							<option value="S">성공</option>
							<option value="E">실패</option>
						</select>
					</div>
					<button type="button" class="btn btn-primary" id="submit_btn">검색하기</button>
					<button type="button" class="btn btn-success" onclick="excelDownload($('#data-table'))">Excel</button>
					<input type="hidden" name="dataType" value="list">
				</form>
			</div> <!-- /container -->
		</div> <!-- /jumbotron -->

		<div class="container">
			<hr>
			<div class="row">
				<table class="table table-hover" id="data-table">
					<thead>
						<th class="center">IDX</th>
						<th>발송시간</th>
						<th>메세지 타입</th>
						<th>전화번호</th>
						<th>주문번호</th>
						<th>쿠폰번호</th>
						<th>쿠폰타입</th>
						<th>성공여부</th>
						<th> - </th>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<hr>
			<footer>
				<p></p>
			</footer>
		</div> <!-- /container -->

	</div> <!-- /list -->


	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="static" role="tabpanel" class="tab-pane">

		<div class="jumbotron">
			<div class="container">

				<form class="navbar-form" id="group-form">
		<!--            <div class="form-group">-->
					<div class="form-group">
						<select class="form-control" name="date_select">
							<?php
							for($i = 0; $i <= 12; $i++) $temp[] = date("Y-m", strtotime("first day of -{$i} month"));
							foreach ($temp as $v) :
							?>
							<option value="<?=$v?>"><?=$v."월"?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<select class="form-control" name="dateType">
							<option value="dd">일별</option>
							<option value="mm">월별</option>
						</select>
					</div>
					<div class="form-group">
						<select class="form-control" name="searchName" id="searchName">
							<option value="m">메세지타입</option>
							<option value="c">쿠폰타입</option>
						</select>
					</div>

					<button type="button" class="btn btn-primary" id="submit_btn_group">검색하기</button>
					<button type="button" class="btn btn-success" onclick="excelDownload($('#data-table-group'))">Excel</button>
					<input type="hidden" name="dataType" value="group">
				</form>
			</div> <!-- /container -->
		</div> <!-- /jumbotron -->

		<div class="container">
			<hr>
			<div class="row">
				<table class="table table-hover" id="data-table-group">
					<thead>
						<th>발송일</th>
						<th id="typeName">타입</th>
						<th>발송횟수</th>
						<th>성공횟수</th>
						<th>실패횟수</th>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<hr>
			<footer>
				<p></p>
			</footer>
		</div> <!-- /container -->


<!--
		<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
		<canvas id="myChart"></canvas>


		<script>
			var ctx = document.getElementById('myChart').getContext('2d');
			var chart = new Chart(ctx, {
				// The type of chart we want to create
				type: 'line',

				// The data for our dataset
				data: {
					labels: ['SMS', 'MMS', 'LMS', '카카오'],
					datasets: [{
						label: '발송통계',						
						borderColor: 'rgb(14, 99, 255)',
						data: [1000, 590, 70, 145],
						pointBackgroundColor: '#007bff'
					}]
				},

				// Configuration options go here
				options: {
					scales: {
					 xAxes: [{
						ticks: {
						  beginAtZero: false
						}
					  }]
					},
					legend: {
					  display: false
					},
					responsive: true
				}
			});

		</script>
-->
	</div> <!-- /static -->


	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="settings" role="tabpanel" class="tab-pane">
	환경설정
	</div> <!-- /static -->


</div> <!-- /tab-content -->


<!-- Large Modal -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">문자 발송 데이터 더보기</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="title" class="control-label"> 메세지 타입 / 발송 시간 </label>
                    <input type="text" placeholder="발송 시간" class="form-control" id="modal_date" readonly>
                    <input type="hidden" placeholder="메세지 타입" class="form-control" id="modal_msg_type" readonly>
                    <input type="hidden" placeholder="프로필 키" class="form-control" id="modal_profile" readonly>
                    <input type="hidden" placeholder="EXT1" class="form-control" id="modal_ext1" readonly>
                    <input type="hidden" placeholder="EXT2" class="form-control" id="modal_ext2" readonly>
                    <input type="hidden" placeholder="EXT3" class="form-control" id="modal_ext3" readonly>
                    <input type="hidden" placeholder="EXT4" class="form-control" id="modal_ext4" readonly>
                    <input type="hidden" placeholder="FILE" class="form-control" id="modal_file" readonly>
                </div>
<!--                <div class="form-group">-->
<!--                    <label for="title" class="control-label"> 메세지 타입 </label>-->
<!--                </div>-->
                <div class="form-group">
                    <label for="title" class="control-label"> 전화 번호 </label>
                    <input type="text" placeholder="전화 번호" class="form-control" id="modal_tel" readonly>
                    <input type="hidden" class="form-control" id="modal_callback" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 주문 번호 </label>
                    <input type="text" placeholder="주문 번호" class="form-control" id="modal_orderno" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 쿠폰 번호 </label>
                    <input type="text" placeholder="쿠폰 번호" class="form-control" id="modal_couponno" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 쿠폰 타입 </label>
                    <input type="text" placeholder="쿠폰 타입" class="form-control" id="modal_coupon_type" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 성공 여부 / 에러메세지  </label>
                    <input type="text" placeholder="성공 여부" class="form-control" id="modal_stat" readonly>
                </div>
<!--                <div class="form-group">-->
<!--                    <label for="title" class="control-label"> 에러 메세지 </label>-->
<!--                    <input type="text" placeholder="에러 메세지" class="form-control" id="modal_err_msg" readonly>-->
<!--                </div>-->
                <div class="form-group">
                    <label for="title" class="control-label"> 메세지 제목 </label>
                    <input type="text" placeholder="메세지 제목" class="form-control" id="modal_subject" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 메세지 내용 </label>
                    <textarea class="form-control noresize" id="modal_text" rows="10" readonly ></textarea>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 응답 값 </label>
                    <textarea class="form-control noresize" id="modal_results" rows="3" readonly></textarea>
                </div>
                <div class="form-group" id="EXT1_form" style="display: none">
                    <label for="title" class="control-label"> 기타값 </label>
                    <textarea class="form-control noresize" id="EXT1_TEXTAREA" rows="3" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-o" name="re_send">
                    재전송
                </button>
                <button type="button" class="btn btn-primary btn-o" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Large Modal -->

<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> 알림톡 / 문자 재발송</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="title" class="control-label"> 전화 번호 </label>
                    <input type="text" placeholder="전화 번호" class="form-control" id="s_modal_tel">
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 메세지 제목 </label>
                    <input type="text" placeholder="메세지 제목" class="form-control" id="s_modal_subject" readonly>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 메세지 내용 </label>
                    <textarea class="form-control noresize" id="s_modal_text" rows="20" readonly >fdsafdsafdsafsafsadfsad</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-o" id="go_send">
                    재발송 하기
                </button>
                <button type="button" class="btn btn-primary btn-o" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


    
</body>
</html>
