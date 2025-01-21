<?php

////////////////////////////////////////////////////////////////////////////////
// user_id : 이브릿지 API 호출시 사용하는 user id
define('USER_ID', 'placem');
// user_password : 이브릿지 API 호출시 사용하는 user password
define('USER_PASSWORD', 'placem!1');


//////////////////////////////////////////////////////////////////////////////
// REAL 서버
define('EBRIDGE_SVR', 'https://api.theloungemembers.com/');
// TEST 서버
// define('EBRIDGE_SVR', 'https://dev-api.theloungemembers.com/');

// 이용권 발행/판매(티켓) 등록
define('API_ISSUE', EBRIDGE_SVR.'api/v2/coupon/issue');
// 이용권 발행 취소/판매(티켓) 취소(환불)
define('API_ISSUE_CANCEL', EBRIDGE_SVR.'api/v2/coupon/issue_cancel');
// 이용권 정보조회/판매(티켓) 조회
define('API_INFO', EBRIDGE_SVR.'api/v2/coupon/info');
// 이용권 정보 변경-영문 이름만 변경이 가능
define('API_NAME_UPDATE', EBRIDGE_SVR.'api/v2/coupon/update');

?>
