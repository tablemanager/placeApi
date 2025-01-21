<?php
require_once ('/home/placedev/cmsapps/common/conf/dbconn.conf.php');
$conn_cms3->query("set names utf8");
$conn_rds->query("set names utf8");

// ACL 확인
$ip = explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]);
$accessip = array(
                  "106.254.252.100",
                  "13.209.232.254",
                  "118.131.208.123",
                  "218.39.39.190",
                  "118.131.208.126",
                  "58.142.38.153"
                  );


if(!in_array(trim($ip[0]),$accessip)){
    echo "IP BLOCK : ".$ip[0];
    exit;
}

$para = $_GET['val']; // URI 파라미터
$apimethod = $_SERVER['REQUEST_METHOD']; // http 메서드
$apiheader = getallheaders(); // http 헤더

// 파라미터
$itemreq = explode("/",$para);
$jsonreq = trim(file_get_contents('php://input'));

$tcode = $itemreq[0];
$ccode = $itemreq[1];

if(strlen($tcode) < 1 ){
    exit;
}
if(strlen($ccode) < 4 ) exit;

$tsql = "select * from cmsdb.nbooking_items where agencyBizItemId = '$ccode'";
$trow = $conn_rds->query($tsql)->fetch_object();
$_json = json_decode($trow->itemOptions);

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[<?=$ccode?>] <?=$trow->itemNm?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <style>
        .json-display {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 1rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">[<?=$ccode?>] <?=$trow->itemNm?></h1>

        <div id="alert" class="alert alert-danger" style="display: none;"></div>

        <form id="jsonForm" class="row g-3">
            <div class="col-md-6">
                <label for="priceId" class="form-label">가격 ID</label>
                <input type="text" class="form-control" id="priceId" required>
            </div>
            <div class="col-md-6">
                <label for="optNm" class="form-label">옵션명</label>
                <input type="text" class="form-control" id="optNm" required>
            </div>
            <div class="col-md-6">
                <label for="facType" class="form-label">시설 유형</label>
                <input type="text" class="form-control" id="facType" required>
            </div>
            <div class="col-md-6">
                <label for="couponCode" class="form-label">쿠폰 코드</label>
                <input type="text" class="form-control" id="couponCode">
            </div>
            <div class="col-md-6">
                <label for="cmsItemCode" class="form-label">CMS 아이템 코드</label>
                <input type="text" class="form-control" id="cmsItemCode" required>
            </div>
            <div class="col-md-6">
                <label for="price" class="form-label">가격</label>
                <input type="number" class="form-control" id="price" required>
            </div>
            <div class="col-md-6">
                <label for="couponUnit" class="form-label">쿠폰 단위</label>
                <input type="text" class="form-control" id="couponUnit">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">옵션 적용</button>
            </div>
        </form>
        <div class="table-responsive">
            <table id="dataTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>가격 ID</th>
                        <th>옵션명</th>
                        <th>시설 유형</th>
                        <th>쿠폰 코드</th>
                        <th>CMS 아이템 코드</th>
                        <th>가격</th>
                        <th>쿠폰 단위</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!--div id="jsonDisplay" class="mt-4 json-display" style="display: none;">
            <pre id="jsonContent"></pre>
        </div-->

        <button id="saveButton" class="btn btn-success mt-3">저장</button>
    </div>

    <script>
        $(document).ready(function() {
            let jsonList = [];

            const initialJson = <?=json_encode($_json,JSON_UNESCAPED_UNICODE)?>;
            loadInitialData(initialJson);

            $('#jsonForm').on('submit', function(e) {
                e.preventDefault();
                const formData = {
                    priceId: $('#priceId').val(),
                    optNm: $('#optNm').val(),
                    facType: $('#facType').val(),
                    couponCode: $('#couponCode').val(),
                    cmsItemCode: $('#cmsItemCode').val(),
                    price: parseInt($('#price').val()),
                    couponUnit: $('#couponUnit').val()
                };

                jsonList.push(formData);
                updateTable();
                updateJsonDisplay();
                this.reset();
            });

            $(document).on('click', '.edit-btn', function() {
                const index = $(this).data('index');
                const data = jsonList[index];
                $('#priceId').val(data.priceId);
                $('#optNm').val(data.optNm);
                $('#facType').val(data.facType);
                $('#couponCode').val(data.couponCode);
                $('#cmsItemCode').val(data.cmsItemCode);
                $('#price').val(data.price);
                $('#couponUnit').val(data.couponUnit);

                jsonList.splice(index, 1);
                updateTable();
                updateJsonDisplay();
            });

            $(document).on('click', '.delete-btn', function() {
                const index = $(this).data('index');
                jsonList.splice(index, 1);
                updateTable();
                updateJsonDisplay();
            });

            $('#saveButton').on('click', function() {

                $.ajax({
                    url: '/internal/naveritems/v2/prc/<?=$ccode?>',
                    method: 'POST',
                    data: JSON.stringify(jsonList),
                    contentType: 'application/json',
                    beforeSend: function() {
                        $('#saveButton').prop('disabled', true).text('저장 중...');
                    },
                    success: function(response) {
                        showAlert('데이터가 성공적으로 저장되었습니다.', 'success');
                    },
                    error: function(xhr, status, error) {
                        showAlert('저장 중 오류가 발생했습니다: ' + error, 'danger');
                    },
                    complete: function() {
                        $('#saveButton').prop('disabled', false).text('저장');
                    }
                });
            });

            function updateTable() {
                const tableBody = $('#dataTable tbody');
                tableBody.empty();
                jsonList.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td>${item.priceId}</td>
                            <td>${item.optNm}</td>
                            <td>${item.facType}</td>
                            <td>${item.couponCode}</td>
                            <td>${item.cmsItemCode}</td>
                            <td>${item.price}</td>
                            <td>${item.couponUnit}</td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-index="${index}">수정</button>
                                <button class="btn btn-sm btn-danger delete-btn" data-index="${index}">삭제</button>
                            </td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            }

            function updateJsonDisplay() {
                $('#jsonContent').text(JSON.stringify(jsonList, null, 2));
                $('#jsonDisplay').show();
            }

            function showAlert(message, type) {
                $('#alert').removeClass().addClass(`alert alert-${type}`).text(message).show();
                setTimeout(() => $('#alert').hide(), 5000);
            }

            function loadInitialData(data) {
                jsonList = data;
                updateTable();
                updateJsonDisplay();
            }
        });
    </script>
</body>
</html>
