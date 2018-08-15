<?php
/**
 * Created by PhpStorm.
 * User: Acer
 * Date: 2018/7/10
 * Time: 9:31
 */

namespace App\Http\Controllers;

use App\Components\AgreeManager;
use App\Components\CompanyManager;
use App\Components\FavoriteManager;
use App\Components\SellDataManager;
use App\Components\SellManager;
use App\Components\SellSearchManager;
use App\Components\CategoryManager;
use App\Components\LLJLManager;
use App\Components\MemberManager;
use App\Components\SystemManager;
use App\Components\TagManager;
use Illuminate\Http\Request;

class SellController
{
	public function getList(Request $request)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		$sells = SellManager::getByCon(['status' => [3]], ['vip', "desc"], true);
//		return ApiResponse::makeResponse(true, $sells, ApiResponse::SUCCESS_CODE);
		foreach ($sells as $sell) {
			$sell = SellManager::getInfo($sell, ['content', 'userinfo', 'tags']);
			$sell->I_agree = AgreeManager::getByCon(
				['item_mid' => ['5'],
					'item_id' => [$sell->itemid],
					'username' => [$user->username]
				])->first() ? true : false;
			$sell->I_favortie = FavoriteManager::getByCon(
				['mid' => ['5'],
					'tid' => [$sell->itemid],
					'userid' => [$user->userid]
				]
			)->first() ? true : false;
		}
		return ApiResponse::makeResponse(true, $sells, ApiResponse::SUCCESS_CODE);
	}
	
	public function edit(Request $request)
	{
		$ret = [];
		$ret['catids'] = array_arrange(CategoryManager::getByCon(['moduleid' => [5]]));
		$ret['tags'] = array_arrange(TagManager::getByCon(['moduleid' => [5]]));
		return ApiResponse::makeResponse(true, $ret, ApiResponse::SUCCESS_CODE);
	}
	
	public function editPost(Request $request)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		if ($user->groupid != 6) {
			return ApiResponse::makeResponse(false, "请先完善资料", ApiResponse::UNKNOW_ERROR);
		}
		//检验参数
		if (checkParam($data, ['title', 'introduce', 'content', 'thumb', 'telephone', 'address'])) {
			
			if (array_key_exists('itemid', $data)) {
				$sell = SellManager::getById($data['itemid']);
				$sell_data = SellDataManager::getById($data['itemid']);
			} else {
				$sell = SellManager::createObject();
				$sell_data = SellDataManager::createObject();
				
				if (!CreditController::changeCredit(
					['userid' => $data['userid'], 'amount' => -1 * SystemManager::getById('4')->value,
						'reason' => '发布供应信息消耗积分', 'note' => '消耗积分'])) {
					return ApiResponse::makeResponse(false, "积分不足", ApiResponse::UNKNOW_ERROR);
				};
			}
			if ($sell == null) {
				return ApiResponse::makeResponse(false, "错误的itemid", ApiResponse::UNKNOW_ERROR);
			}
			
			$sell = SellManager::setUserInfo($sell, $data['userid']);
			$sell = SellManager::setSell($sell, $data);
			$sell->username = $user->username;
			$sell->save();
			
			$sell_data = SellDataManager::setSellData($sell_data, $data);
			$sell_data->itemid = $sell->itemid;
			$sell_data->save();
			
			$searchInfo = SellManager::createSearchInfo($sell);
			if (array_key_exists('keywords', $data)) {
				$searchInfo->content .= $data['keywords'];
			}
			$searchInfo->save();
			
			return ApiResponse::makeResponse(true, $sell, ApiResponse::SUCCESS_CODE);
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
	
	public static function getById(Request $request)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		//检验参数
		if (checkParam($data, ['itemid'])) {
			$sell = SellManager::getById($data['itemid']);
			if ($sell) {
				//增加浏览次数
				$sell->hits++;
				$sell->save();
				$lljl = LLJLManager::createObject($user, $sell, 5);
				$lljl->save();
				$sell = SellManager::getData($sell);
				$sell = SellManager::getInfo($sell, ['content', 'userinfo', 'tags', 'comments']);
				$sell->I_agree = AgreeManager::getByCon(
					['item_mid' => ['5'],
						'item_id' => [$sell->itemid],
						'username' => [$user->username]
					])->first() ? true : false;
				$sell->I_favortie = FavoriteManager::getByCon(
					['mid' => ['5'],
						'tid' => [$sell->itemid],
						'userid' => [$user->userid]
					]
				)->first() ? true : false;
				return ApiResponse::makeResponse(true, $sell, ApiResponse::SUCCESS_CODE);
			} else
				return ApiResponse::makeResponse(false, '未找到对应信息', ApiResponse::UNKNOW_ERROR);
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
	
	public static function searchPost(Request $request)
	{
		$data = $request->all();
		//检验参数
		if (checkParam($data, ['keyword'])) {
			$ret = null;
			$keyword = $data['keyword'];
			$searchResults = SellSearchManager::search($keyword);
			$result_itemids = [];
			if ($searchResults->count() > 0) {
				foreach ($searchResults as $result) {
					array_push($result_itemids, $result->itemid);
				}
				$sells = SellManager::getByCon(['status' => [3], 'itemid' => $result_itemids], ['vip', 'desc'], true);
				foreach ($sells as $sell) {
					$sell->content = SellDataManager::getById($sell->itemid)->content;
					$sell->user = $user = MemberManager::getByUsername($sell->username);
					if ($user) {
						$sell->company = $company = CompanyManager::getById($user->userid);
						$sell->businesscard = BussinessCardController::getByUserid($company->userid);
					}
					$sell->tags = array_arrange(TagManager::getByCon(['tagid' => explode(',', $sell->tag)]));
				}
				return ApiResponse::makeResponse(true, $sells, ApiResponse::SUCCESS_CODE);
			} else
				return ApiResponse::makeResponse(false, $keyword, ApiResponse::SUCCESS_CODE);
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
	
	public static function getByCon(Request $request)
	{
		$data = $request->all();
		$user = MemberManager::getById($data['userid']);
		//检验参数
		if (checkParam($data, ['conditions'])) {
			$ret = "请求成功";
			
			$conditions1 = $data['conditions'];
			$conditions = json_decode($conditions1);
			$Con = [];
			foreach ($conditions->key as $num => $key) {
				$Con[$key] = explode(',', $conditions->value[$num]);
			}
			$sells = SellManager::getByCon($Con, ['vip', "desc"], true);
			foreach ($sells as $sell) {
				$sell = SellManager::getInfo($sell, ['content', 'userinfo', 'tags']);
				$sell->I_agree = AgreeManager::getByCon(
					['item_mid' => ['5'],
						'item_id' => [$sell->itemid],
						'username' => [$user->username]
					])->first() ? true : false;
				$sell->I_favortie = FavoriteManager::getByCon(
					['mid' => ['5'],
						'tid' => [$sell->itemid],
						'userid' => [$user->userid]
					]
				)->first() ? true : false;
			}
			return ApiResponse::makeResponse(true, $sells, ApiResponse::SUCCESS_CODE);
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
}