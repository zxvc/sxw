<?php

/**
 * Created by PhpStorm.
 * User: Zhangli
 * Date: 2018-04-02
 * Time: 10:30
 * 模版Manager
 */

namespace App\Components;

use App\Models\Comment;

class CommentManager
{
	/*
	 * 创建新的对象
	 *
	 * by Zhangli
	 *
	 * 2018/07/05
	 */
	public static function createObject(){
		$comment=new Comment();
		//这里可以对新建记录进行一定的默认设置
		$comment->quotation='';
		$comment->status=3;
		return $comment;
	}
	
	
	/*
	 * 获取comment的list
	 *
	 * By Zhangli
	 *
	 * 2018-04-02
	 */
	public static function getList()
	{
		$comments = Comment::orderby('id', 'desc')->get();
		return $comments;
	}
	
	/*
	 * 根据id获取
	 *
	 * By Zhangli
	 *
	 * 2018-04-02
	 */
	public static function getById($id)
	{
		$comment = Comment::where('id', '=', $id)->first();
		return $comment;
	}
	
	/*
	 * 根据条件数组获取
	 *
	 * By Zhangli
	 *
	 * 2018-04-19
	 */
	public static function getByCon($ConArr, $orderby = ['id', 'asc'])
	{
		$comments = Comment::orderby($orderby['0'], $orderby['1'])->get();
		foreach ($ConArr as $key => $value) {
			$comments = $comments->whereIn($key, $value);
		}
		return $comments;
	}
	
	
	/*
	 * 设置信息，用于编辑
	 *
	 * By Zhangli
	 *
	 * 2018-04-02
	 */
	public static function setComment($comment, $data)
	{
		if (array_key_exists('item_mid', $data)) {
			$comment->item_mid = array_get($data, 'item_mid');
		}
		if (array_key_exists('item_id', $data)) {
			$comment->item_id = array_get($data, 'item_id');
		}
		if (array_key_exists('content', $data)) {
			$comment->content = array_get($data, 'content');
		}
		if (array_key_exists('star', $data)) {
			$comment->star = array_get($data, 'star');
		}
		else{
			$comment->star = 3;
		}
		return $comment;
	}
}