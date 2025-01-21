<?php
require_once ('/home/sparo.cc/api_html/internal/messages/lib/sms_lib.php');

// 접속 토큰 복호화
$ykn = ase_decrypt($_GET['tk']);

if(empty($auth = explode("_", $ykn))){
    // 복호화 실패시 아이피
    checkIP($ykn);
} else {
    // 복호화 성공시 세션 생성
    print_r($auth);
}

function ase_decrypt($val)
{
    if(!$val) return '';

    $salt = 'mIn282##1m1F?@11'; // 암호화 키
    $method = 'AES-128-ECB';    // OpenSSL 암호화 방식

    // 복호화
    $decrypted = openssl_decrypt(
        hex2bin($val), // 16진수 문자열을 바이너리 데이터로 변환
        $method,
        $salt,
        OPENSSL_RAW_DATA
    );

    return rtrim($decrypted, "\0"); // 복호화 결과에서 NULL 문자 제거
}
?>


<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS / KAKAO 조회 <?=$tk?> <?=$ip?></title>
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
</head>

<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">[KAKAO알림톡, TOAST문자] 발송을 좀 더 정확하고 빠른 조회를 하기 위한 페이지..</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">

        </div><!--/.navbar-collapse -->
    </div>
</nav>

<!-- Main jumbotron for a primary marketing message or call to action -->
<div class="jumbotron">
    <div class="container">
        <h2>카카오 알림톡 / TOAST 문자 발송 확인페이지 . <?=$tk?></h2><br>
        <form class="navbar-form">
<!--            <div class="form-group">-->
<!--                <input type="date" name="sdate" placeholder="" value="--><?php //echo date("Y-m-d")?><!--" class="form-control" min="--><?php //echo date("Y-m")."-01" ?><!--" max='--><?php //echo date("Y-m-d")?><!--' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"> ~-->
<!--            </div>-->
<!--            <div class="form-group">-->
<!--                <input type="date" name="edate" placeholder="" value="--><?php //echo date("Y-m-d")?><!--" class="form-control" min="--><?php //echo date("Y-m")."-01" ?><!--" max='--><?php //echo date("Y-m-d")?><!--' pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}">-->
<!--            </div>-->
            <div class="form-group">
                <select class="form-control" name="date_select" id="date_select">
                    <?php
                    for($i = 0; $i <= 14; $i++) $temp[] = date("Y-m", strtotime("first day of -{$i} month"));
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
                <input type="text" name="tel" placeholder="전화번호" class="form-control">
            </div>
            <div class="form-group">
                <input type="text" name="order_Num" placeholder="주문번호" class="form-control">
            </div>
            <div class="form-group">
                <select class="form-control" name="msg_res">
                    <option value="ALL">작업 성공 여부</option>
                    <option value="S">성공</option>
                    <option value="E">실패</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success" id="submit_btn">검색하기</button>
            <button type="button" class="btn btn-warning" id="reset_btn">검색 초기화</button>
        </form>
    </div>
	<div style="float:right"><a href="/internal/msg/admin/msg_list?tk=16C83978C54F6371D42966C29A6DF548A64141772F5A1A2A61F2C9761F0D80FA">Ver 2.0 바로가기</a></div>
</div>

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
                <th>쿠폰쿠폰타입</th>
                <th>성공여부</th>
                <th> - </th>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <hr>
    <footer>
        <p>&copy; Connor - 2018</p>
    </footer>
</div> <!-- /container -->

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
                    <textarea class="form-control noresize" id="modal_text" rows="15" readonly ></textarea>
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 응답 값 </label>
                    <textarea class="form-control noresize" id="modal_results" rows="8" readonly></textarea>
                </div>
                <div class="form-group" id="EXT1_form" style="display: none">
                    <label for="title" class="control-label"> 기타값 </label>
                    <textarea class="form-control noresize" id="EXT1_TEXTAREA" rows="8" readonly></textarea>
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

<script src="https://code.jquery.com/jquery-2.2.4.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

<script>
    var row_data;
    var first = 'true';

    jQuery(document).ready(function() {
        // 데이터 조회 버튼 클릭
        $('#submit_btn').on('click', function () {
            $.ajax({
                type: "post",
                url: 'back_ground.php',
                async: false,
                data: { 'data': $(".navbar-form").serialize(), 'first' : first },
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

        // 초기화하기..
        $('#reset_btn').on('click', function () {
            location.reload();
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

        $('#data-table tbody').on( 'click', 'button', function () {
            row_data = table.row($(this).parents('tr')).data();

            $.ajax({
                type: "post",
                url: 'back_ground.php',
                async: false,
                data: {'data': "IDX=" + row_data[0], 'date_select' : $('#date_select').val() },
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
        $('#submit_btn').click();
    });

    var submitAction = function() { return false; };
    $('form').bind('submit', submitAction);
</script>
</body>
</html>
