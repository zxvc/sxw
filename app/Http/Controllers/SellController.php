<?php
/**
 * Created by PhpStorm.
 * User: Acer
 * Date: 2018/7/10
 * Time: 9:31
 */

namespace App\Http\Controllers;

use App\Components\SellDataManager;
use App\Components\SellManager;
use App\Components\SellSearchManager;
use App\Components\CategoryManager;
use App\Components\LLJLManager;
use App\Components\MemberManager;
use Illuminate\Http\Request;

class SellController
{
	public function getList(Request $request)
	{
		return ApiResponse::makeResponse(true, SellManager::getList(), ApiResponse::SUCCESS_CODE);
	}
	
	public function edit(Request $request)
	{
		$ret = [];
		$ret['catids'] = CategoryManager::getByCon(['moduleid' => [5]]);
		return ApiResponse::makeResponse(true, $ret, ApiResponse::SUCCESS_CODE);
	}
	
	public function editPost(Request $request)
	{
		$data = $request->all();
		//检验参数
		if (checkParam($data, ['title', 'introduce', 'amount', 'price', 'content', 'thumb', 'telephone'])) {
			
			if (array_key_exists('itemid', $data)) {
				$sell = SellManager::getById($data['itemid']);
				$sell_data = SellDataManager::getById($data['itemid']);
			} else {
				$sell = SellManager::createObject();
				$sell_data = SellDataManager::createObject();
			}
			if($sell==null){
				return ApiResponse::makeResponse(false, "错误的itemid" , ApiResponse::UNKNOW_ERROR);
			}
			
			
			$sell = SellManager::setUserInfo($sell, $data['userid']);
			$sell = SellManager::setSell($sell, $data);
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
			return ApiResponse::makeResponse(false, "缺少参数" , ApiResponse::MISSING_PARAM);
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
			if ($searchResults->count() > 0) {
				foreach ($searchResults as $result) {
					$result->item = SellManager::getById($result->itemid);
				}
				
				return ApiResponse::makeResponse(true, $searchResults, ApiResponse::SUCCESS_CODE);
			} else
				return ApiResponse::makeResponse(false, $keyword, ApiResponse::SUCCESS_CODE);
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
	
	public static function getByCon(Request $request)
	{
		$data = $request->all();
		//检验参数
		if (checkParam($data, ['conditions'])) {
			$ret = "请求成功";
			
			$conditions1 = $data['conditions'];
			$conditions = json_decode($conditions1);
			$Con = [];
			foreach ($conditions->key as $num => $key) {
				$Con[$key] = explode(',', $conditions->value[$num]);
			}
			
			$sells = SellManager::getByCon($Con);
			
			return ApiResponse::makeResponse(true, $sells, ApiResponse::SUCCESS_CODE);
			
		} else {
			return ApiResponse::makeResponse(false, "缺少参数", ApiResponse::MISSING_PARAM);
		}
	}
}