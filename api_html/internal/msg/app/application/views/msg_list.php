<br><br><br>

<ul class="nav nav-tabs" role="tablist" style="margin-left:205px">
    <li role="presentation" class="active"><a href="#list" aria-controls="list" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-th-list"></i> 발송목록</a></li>
    <li role="presentation"><a href="#static" aria-controls="static" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-stats"></i> 발송통계</a></li>
    <li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab" id="settingTab"><i class="glyphicon glyphicon-user"></i> 키발급</a></li>
	<li role="presentation"><a href="#ip" aria-controls="ip" role="tab" data-toggle="tab" id="ipTab"><i class="glyphicon glyphicon-info-sign"></i> IP관리</a></li>
	<li role="presentation"><a href="#kakao" aria-controls="ip" role="tab" data-toggle="tab" id="kakaoTab"><i class="glyphicon glyphicon-comment"></i> 카카오탬플릿</a></li>
	<li role="presentation"><a href="#dev" aria-controls="dev" role="tab" data-toggle="tab" id="devTab"><i class="glyphicon glyphicon-eye-open"></i> Dev</a></li>
</ul>

<div class="tab-content" style="float:left;margin-left:210px;">

	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="list" role="tabpanel" class="tab-pane active">

		<!-- Main jumbotron for a primary marketing message or call to action -->
		<div class="jumbotron">
			<div class="container">

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
						<select class="form-control" name="msg_type" id="msg_type">
							<option value="">전체 조회</option>
							<option value="S">SMS</option>
							<option value="M">MMS</option>
							<option value="L">LMS</option>
							<option value="K">KAKAO</option>
						</select>
					</div>
					<div class="form-group">
						<input type="text" name="tel" id="tel" placeholder="전화번호" class="form-control" style="width:140px !important;">
					</div>
					<div class="form-group">
						<input type="text" name="order_Num" id="order_Num" placeholder="주문번호" class="form-control">
					</div>
					<div class="form-group">
						<select class="form-control" name="msg_res" id="msg_res">
							<option value="">성공여부</option>
							<option value="S">성공</option>
							<option value="E">실패</option>
						</select>
					</div>
					<button type="button" class="btn btn-primary" id="submit_btn"><i class="glyphicon glyphicon-search"></i> 검색하기</button>
					<button type="button" class="btn btn-success" onclick="excelDownload($('#data-table'))"><i class="glyphicon glyphicon-save"></i> Excel</button>
					<input type="hidden" name="dataType" value="list">
					<input type="hidden" name="IDX" id="IDX" value="">
					
					
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
						<select class="form-control" name="date_select" id="date_select_group">
							<?php
							for($i = 0; $i <= 12; $i++) $temp[] = date("Y-m", strtotime("first day of -{$i} month"));
							foreach ($temp as $v) :
							?>
							<option value="<?=$v?>"><?=$v."월"?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<select class="form-control" name="dateFormat" id="dateFormat">
							<option value="dd">일통계</option>
							<option value="mm">월통계</option>
						</select>
					</div>
					<div class="form-group">
						<select class="form-control" name="viewType" id="viewType">
							<option value="list">리스트형</option>
							<option value="graph">그래프</option>
						</select>
					</div>
					<button type="button" class="btn btn-primary" id="submit_btn_group"><i class="glyphicon glyphicon-search"></i> 검색하기</button>
					<button type="button" class="btn btn-success" onclick="excelDownload($('#data-table-group'))"><i class="glyphicon glyphicon-save"></i> Excel</button>
					<input type="hidden" name="dataType" value="group">
				</form>
			</div> <!-- /container -->
		</div> <!-- /jumbotron -->

		<div class="container" id="container-group">
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


		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<div id="chart_div" style="width:100%; height: 500px; padding:0px;"></div>

	</div> 


	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="settings" role="tabpanel" class="tab-pane">
		<div class="container">
			<br>
			<hr>
			<br>
			<div class="row">
				<table class="table table-hover" id="data-table-setting">
					<thead>
						<th>No</th>
						<th>사용처</th>
						<th>PROFILE_ID</th>
						<th>API 키</th>
						<th>등록일</th>
						<th>총 발송 건수</th>
						<th> - </th>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<hr>
			<footer>
				<p style="float:right"><button type="button" class="btn btn-primary btn-o" id="setting_reg">
                    <i class="glyphicon glyphicon-pencil"></i> 신규등록
                </button></p>
			</footer>
		</div> <!-- /container -->	
	</div>


	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="ip" role="tabpanel" class="tab-pane">
		<div class="container">
			<hr>
			<div class="row">
				<table class="table table-hover" id="data-table-ip">
					<thead>
						<th>No</th>
						<th>사용처</th>
						<th>IP</th>
						<th>등록일</th>
						<th> - </th>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<hr>
			<footer>
				<p style="float:right"><button type="button" class="btn btn-primary btn-o" id="ip_reg">
                    <i class="glyphicon glyphicon-pencil"></i> 신규등록
                </button></p>
			</footer>
		</div> <!-- /container -->	
	</div>

	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="kakao" role="tabpanel" class="tab-pane">
		<div class="jumbotron" style="height:20px;padding-top:10px;margin-bottom:20px">
			<div class="container">
				<select id="kakao_profile" class="form-control">
					<option value="">카카오톡Key</option>					
					<option value="hamac" selected>hamac</option>
					<option value="high1">high1</option>
					<option value="playdoci">playdoci</option>
					<option value="pension">pension</option>
					<option value="wherego">wherego</option>
				</select>
			</div>
		</div>
		<div class="container">

			<div class="row">
				<table class="table table-hover" id="data-table-kakao">
					<thead>
						<th>No</th>
						<th>탬플릿명</th>
						<th>탬플릿코드</th>
						<th>상태</th>
						<th>등록일</th>
						<th> - </th>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div> <!-- /container -->			
	</div>

	<!--	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■ -->
	<div id="dev" role="tabpanel" class="tab-pane">
		
	</div>





</div> <!-- /tab-content -->




<!-- 발송 내역 상세 모달 -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="modalMsgInfo">
    <div class="modal-dialog modal-dialog modal-lg" style="padding-top:20px;">
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
                <button type="button" class="btn btn-primary btn-o" name="re_send">
                    <i class="glyphicon glyphicon-send"></i> 재전송
                </button>
                <button type="button" class="btn btn-default btn-o" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 문자 발송 모달 -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySendModalLabel" aria-hidden="true" id="modalSend">
    <div class="modal-dialog modal-dialog modal-sm" style="padding-top:20px;">
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
                <button type="button" class="btn btn-primary btn-o" id="go_send">
                    <i class="glyphicon glyphicon-ok"></i> 재발송 하기
                </button>
                <button type="button" class="btn btn-default btn-o" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 키발급(사용자 정보) 모달 -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySendModalLabel" aria-hidden="true" id="modalUserInfo">
    <div class="modal-dialog modal-dialog modal-sm" style="padding-top:50px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> 사용자 정보</h4>
            </div>
            <div class="modal-body">
				<input type="hidden" id="u_idx">
                <div class="form-group">
                    <label for="title" class="control-label"> 사용처 </label>
                    <input type="text" placeholder="사용처" id="u_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> PROFILE ID </label>
                    <input type="text" placeholder="PROFILE ID" class="form-control" id="u_profile_id">
                </div>
				<div class="form-group">
                    <label for="title" class="control-label"> API 키 </label>
                    <input type="text" placeholder="API 키(키 생성으로 생성)" class="form-control" id="u_code">
					<button type="button" class="btn btn-default btn-o" style="margin-top:5px;display:none" id="key_create">
						<i class="glyphicon glyphicon-exclamation-sign"></i> 키생성
					</button>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-o" id="u_send">
                    <i class="glyphicon glyphicon-ok"></i> 저장
                </button>
                <button type="button" class="btn btn-default btn-o" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- IP 정보 모달 -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySendModalLabel" aria-hidden="true" id="modalIP">
    <div class="modal-dialog modal-dialog modal-sm" style="padding-top:50px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> 사용자 정보</h4>
            </div>
            <div class="modal-body">
				<input type="hidden" id="ip_idx">
                <div class="form-group">
                    <label for="title" class="control-label"> 사용처 </label>
                    <input type="text" placeholder="사용처" id="ip_name" class="form-control">
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> IP </label>
                    <input type="text" placeholder="IP" class="form-control" id="ip_ip" maxlength="15">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-o" id="ip_send">
                    <i class="glyphicon glyphicon-ok"></i> 저장
                </button>
                <button type="button" class="btn btn-default btn-o" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 카카오 템플릿 정보 모달 -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySendModalLabel" aria-hidden="true" id="modalKakao">
    <div class="modal-dialog modal-dialog modal-lm" style="padding-top:30px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> 카카오 탬플릿</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="title" class="control-label"> 제목 </label>
                    <input type="text" placeholder="" id="templateName" class="form-control" readonly >
                </div>
				<div class="form-group">
                    <label for="title" class="control-label"> 코드 </label>
                    <input type="text" placeholder="" id="templateCode" class="form-control" readonly >
                </div>
                <div class="form-group">
                    <label for="title" class="control-label"> 내용 </label>
                    <textarea class="form-control noresize" id="templateContent" rows="20"  readonly ></textarea>
                </div>
 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-o" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>