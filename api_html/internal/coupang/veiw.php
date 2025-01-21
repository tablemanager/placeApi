<?php
require_once ('/home/sparo.cc/api_html/internal/messages/lib/sms_lib.php');

$ip = get_ip();
if ($ip != "106.254.252.100" && $ip != "118.131.208.123" && $ip != "115.89.22.27") {
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
    <title>쿠팡 취소 List</title>
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
<nav class="navbar navbar-inverse">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
        </div><!--/.navbar-collapse -->
    </div>
</nav>

<!-- Main jumbotron for a primary marketing message or call to action -->
<div class="jumbotron">
    <div class="container container-fluid">
        <h2>[쿠팡 취소접수 현황] 페이지</h2>
        <div class="form-group">
            <select class="form-control" id="msg_type">
                <option value="">사용처리 여부</option>
                <option value="1">사용</option>
                <option value="2">미사용</option>
                <option value="N">확인불가</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success" id="submit_btn">검색하기</button>
        <button type="button" class="btn btn-warning" id="reset_btn">검색 초기화</button>
    </div>
</div>

<div class="col-lg-10 col-lg-offset-1 container container-fluid">
    <hr>
    <div class="row">
        <table class="table table-hover" id="data-table">
            <thead>
            <th>취소건 생성시간</th>
            <th>동기화 시간</th>
            <th>취소 접수일지</th>
            <th>주문번호</th>
            <th>쿠폰번호</th>
            <th>딜명</th>
            <th>옵션명</th>
            <th>주문자명</th>
            <th>휴대폰 번호</th>
            <th>취소 사유</th>
            <th>상태</th>
            <th>사용처리 여부</th>
            </thead><tbody>
            </tbody>
        </table>
    </div>
    <hr>
    <footer>
        <p>&copy; Connor - 2018</p>
    </footer>
</div> <!-- /container -->

<script src="https://code.jquery.com/jquery-2.2.4.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

<script>
    // ordermts => bigo(안보이게)
    // ordermts => dammemo(보이게)

    jQuery(document).ready(function() {

        // 데이터 조회 버튼 클릭
        $('#submit_btn').on('click', function () {
            $.ajax({
                type: "post",
                url: 'get_cancel.php',
                async: false,
                data: { 'go': 'get', 'type' : $('#msg_type').val() },
                success: function (data) {
                    var obj = $.parseJSON(data);
                    var dataset = [];
                    var temp = [];

                    $.each(obj, function (k, v) {
                        temp = [];
                        temp.push(v.CREATED_TIME);
                        temp.push(v.UPDATE_TIME);
                        temp.push(v.IN_DATE);
                        temp.push(v.ORDER_NO);
                        temp.push(v.COUPON_NO);
                        temp.push(v.DEAL_NM);
                        temp.push(v.OPTION_NM);
                        temp.push(v.ORDER_USER);
                        temp.push(v.ORDER_TEL);
                        temp.push(v.REASON);
                        temp.push(v.STATE);
                        temp.push(v.ORDER_SYNC);
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
            "iDisplayLength": 10,
            "lengthMenu": [10, 30, 50, 75, 100],
            "bFilter": true,
            "orderCellsTop": true,
            "bAutoWidth": true,
            "order": [[2, "desc"]],
            "buttons":
                [
                    {
                        extend: 'collection',
                        text: '내보내기',
                        bom: true,
                        buttons: [
                            'copy',
                            'excel',
                        ]
                    }
                ]
        });

        // 이제 조회시작!!!
        $('#submit_btn').click();
    });

    var submitAction = function() { return false; };
    $('form').bind('submit', submitAction);
</script>
</body>
</html>