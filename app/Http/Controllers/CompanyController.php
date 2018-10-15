<?php

namespace App\Http\Controllers;


use App\Components\CategoryManager;
use App\Components\CompanyManager;
use App\Components\CompanyYWLBManager;
use App\Components\Member_updateManager;
use App\Components\MemberManager;
use App\Components\QRManager;
use App\Components\SystemManager;
use App\Components\UpgradeManager;
use App\Components\VertifyManager;
use App\Components\YWLBManager;
use Illuminate\Http\Request;


class CompanyController extends Controller
{
	
	
	public static function edit(Request $request)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		
		$ret = "";
		if (checkParam($data, ['vertify_code', 'mobile'])) {
			$vertify_result = VertifyManager::judgeVertifyCode($data['mobile'], $data['vertify_code']);
			if ($vertify_result) {
				$user->mobile = $data['mobile'];
				$user->save();
				$ret = "手机号码修改成功。";
			} else {
				return ApiResponse::makeResponse(false, "验证码错误！", ApiResponse::UNKNOW_ERROR);
			}
		}
		if (checkParam($data, ['avatarUrl']))
			if ($user->avatarUrl != $data['avatarUrl']) {
				
				$user->avatarUrl = $data['avatarUrl'];
				$user->save();
				//更新名片二维码
				QRManager::refreshCardQRByUserid($user->userid);
				if ($ret)
					$ret .= ",";
				$ret .= "修改头像成功";
			}
		
		if ($user->groupid == 5) {
			return self::upgrade($request, $ret);
		} else if ($user->groupid == 6) {
			$bussinesscard = BussinessCardController::getByUserid($user->userid);
			if (array_get($data, 'company') == $bussinesscard['company']
				&& array_get($data, 'career') == $bussinesscard['career']
				&& array_get($data, 'address') == $bussinesscard['address']
				&& array_get($data, 'introduce') == $bussinesscard['introduce']
				&& array_get($data, 'business') == $bussinesscard['business']
				&& array_get($data, 'truename') == $bussinesscard['truename']
				&& array_get($data, 'ywlb_ids') == $bussinesscard['ywlb_ids']
				&& array_get($data, 'thumb') == $bussinesscard['thumb']
				&& array_get($data, 'avatarUrl') == $bussinesscard['avatarUrl']
				&& array_get($data, 'wxqr') == $bussinesscard['wxqr']
			) {
				if ($ret)
					$ret .= ",";
				$ret .= "信息未修改";
				return ApiResponse::makeResponse(false, $ret, ApiResponse::UNKNOW_ERROR);
			} else
				return self::update($request, $ret);
		} else {
			return ApiResponse::makeResponse(false, "暂不支持", ApiResponse::UNKNOW_ERROR);
		}
	}
	
	private static function upgrade(Request $request, $ret)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		//检验参数
		if (UpgradeManager::getByCon(['userid' => [$user->userid], 'status' => '2'])->count() > 0) {
			return ApiResponse::makeResponse(false, $ret . "已有等待审核的信息，请耐心等待", ApiResponse::UNKNOW_ERROR);
		}
		if (checkParam($data, ['truename', 'mobile', 'company', 'career', 'ywlb_ids', 'address', 'business', 'introduce'])) {
			
			$company = CompanyManager::getById($user->userid);
			$user = MemberManager::setMember($user, $data);
			$company = CompanyManager::setCompany($company, $data, $user);
			
			$upgrade = UpgradeManager::createObject();
			$upgrade = UpgradeManager::setUpgrade($upgrade, $user);
//			$user->groupid=6;
			$upgrade->groupid = 6;
			$upgrade->groupid = 6;
			$ywlbs = explode(',', $data['ywlb_ids']);
			CompanyManager::setYWLB($company, $ywlbs, 3);
			$company = CompanyManager::setKeyWords($company, $ywlbs, $user);
			
			$user->save();
			$company->save();
			$upgrade->save();
			
			if ($ret)
				$ret .= "。";
			$ret .= "修改信息申请已提交，请等待审核";
//			$user->ywlb = CompanyYWLBManager::getByCon(['userid' => $user->userid]);
//			$ret = ['user' => $user, 'company' => $company, 'upgrade' => $upgrade];
			return ApiResponse::makeResponse(true, $ret, ApiResponse::SUCCESS_CODE);
			
		} else {
			return ApiResponse::makeResponse(false, $ret . "修改资料时缺少参数" . array_keys($data), ApiResponse::MISSING_PARAM);
		}
	}
	
	private static function update(Request $request, $ret)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		if (Member_updateManager::getByCon(['userid' => [$user->userid], 'status' => '2'])->count() > 0) {
			return ApiResponse::makeResponse(false, $ret . "已有等待审核的信息，请耐心等待", ApiResponse::UNKNOW_ERROR);
		}
		//检验参数
		if (checkParam($data, ['truename', 'mobile', 'company', 'career', 'ywlb_ids', 'address', 'business', 'introduce'])) {
			
			$update = Member_updateManager::createObject();
			$update = Member_updateManager::setMember_update($update, $data, $user);
			$update->history = json_encode(Member_updateManager::getHistory($update->userid));
			$update->save();
			
			if ($ret)
				$ret .= ".";
			if (SystemManager::getById(12)->value == '0') {
				$userid = $update->userid;
				$member = MemberManager::getById($userid);
				$company = CompanyManager::getById($userid);
				Member_updateManager::setMember($member, $update)->save();
				Member_updateManager::setCompany($company, $update)->save();
				$update->status = 3;
				$update->save();
				
				$ywlbs = explode(',', $update->ywlb_ids);
				CompanyManager::setYWLB($company, $ywlbs);
				$company = CompanyManager::setKeyWords($company, $ywlbs, $member);
				$company->save();
				
				MessageController::sendSystemMessage([
					'title' => "个人信息审核结果通知",
					'content' => "尊敬的会员：<br/>您的个人信息升级审核已通过！<br/>感谢您的支持！",
					'touser' => $member->username
				]);
			}
			$ret .= "修改信息申请已提交，请等待审核";
			return ApiResponse::makeResponse(true, $ret, ApiResponse::SUCCESS_CODE);
			
		} else {
			return ApiResponse::makeResponse(false, $ret . "修改信息时缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
	
	public static function getEditInfo(Request $request)
	{
		$data = $request->all();
		$ret = [];
		$user = MemberManager::getById($data['userid']);
		$ret['ywlb'] = YWLBManager::getByCon(['status' => '3']);
		if ($user->groupid == 6)
			$ret['businesscard'] = BussinessCardController::getByUserid($data['userid']);
		return ApiResponse::makeResponse(true, $ret, ApiResponse::SUCCESS_CODE);
	}
}
