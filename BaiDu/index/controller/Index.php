<?php
namespace app\index\controller;
use think\Controller;

class Index extends Controller {
	//主页
	public function index() {
		//判断提交
		//导航栏TGA
		$nav = \think\Db::name('bdconfig')->where("str = 0")->cache(true, 3600)->select();
		$tga = input();
		if (empty($tga['tag']) and empty($tga['search'])) {
			//获取封面列表相关信息
			$db = \think\Db::name('imginfo')->where("str = 0 and imgurl != ''")->order('id desc')->cache(true, 60)->paginate(20); //8条一页
			//组合成需要输出的数据
			foreach ($db as $key => $value) {
				$onepic = explode('|', $value['imgurl']);
				$list[] = [
					'id' => $value['id'],
					'icolumn' => $value['icolumn'],
					'title' => $value['title'],
					'author' => base64_decode($value['author']),
					'baiduid' => $value['baiduid'],
					'uptime' => date('Y-m-d H:i:s', $value['uptime']),
					'Clicks' => $value['score'],
					'imgurl' => $onepic[0],
				];
			}
			if (empty($list)) {
				echo '站点还未生成数据，请稍后访问';
				exit();
			}
			$page = $db->render();
			//模板
			$this->nav(0);
		} elseif (!empty($tga['tag'])) {
			$tag = $tga['tag'];
			//获取封面列表相关信息
			$db = \think\Db::name('imginfo')->where("str = 0 and imgurl != '' and icolumn = '$tag'")->order('id desc')->cache(true, 60)->paginate(20, false, ['query' => ['tag' => $tag]]);
			if ($db) {
				//组合成需要输出的数据
				foreach ($db as $key => $value) {
					$onepic = explode('|', $value['imgurl']);
					$list[] = [
						'id' => $value['id'],
						'icolumn' => $value['icolumn'],
						'title' => $value['title'],
						'author' => base64_decode($value['author']),
						'baiduid' => $value['baiduid'],
						'uptime' => date('Y-m-d H:i:s', $value['uptime']),
						'Clicks' => $value['score'],
						'imgurl' => $onepic[0],
					];
				}
				if (empty($list)) {
					$this->error('这个TAG没有任何内容哦~');
					exit();
				}
				$page = $db->render();
				//模板
				$this->nav(0);
			} else {
				$this->error('这个TAG没有任何内容哦~');
			}
		} elseif (!empty($tga['search'])) {
			$tag = trim($tga['search']);
			//获取封面列表相关信息
			$db = \think\Db::name('imginfo')->where("str = 0")->where("title like '%$tag%'")->order('id desc')->cache(true, 60)->paginate(20, false, ['query' => ['search' => $tag]]);
			if ($db) {
				//组合成需要输出的数据
				foreach ($db as $key => $value) {
					$onepic = explode('|', $value['imgurl']);
					$list[] = [
						'id' => $value['id'],
						'icolumn' => $value['icolumn'],
						'title' => $value['title'],
						'author' => base64_decode($value['author']),
						'baiduid' => $value['baiduid'],
						'uptime' => date('Y-m-d H:i:s', $value['uptime']),
						'Clicks' => $value['score'],
						'imgurl' => $onepic[0],
					];
				}
				if (empty($list)) {
					$this->error('什么也没有搜索到哦....');
					exit();
				}
				$page = $db->render();
				//模板
				$this->nav(0);
			} else {
				$this->error('这个TAG没有任何内容哦~');
			}
		}
		// 模板变量赋值
		$this->assign('nav', $nav);
		$this->assign('list', $list);
		$this->assign('page', $page);
		//模板
		return view();
	}

	//导航栏
	public function nav($str) {
		//模板
		$this->assign('str', $str);
		return view();
	}

	//内容显示
	public function show() {
		$tga = input();
		if (!empty($tga['search'])) {
			//导航栏TGA
			$nav = \think\Db::name('bdconfig')->where("str = 0")->cache(true, 3600)->select();
			$tag = trim($tga['search']);
			//获取封面列表相关信息
			$db = \think\Db::name('imginfo')->where("str = 0")->where("title like '%$tag%'")->order('id desc')->cache(true, 60)->paginate(20, false, ['query' => ['search' => $tag]]);
			if ($db) {
				//组合成需要输出的数据
				foreach ($db as $key => $value) {
					$onepic = explode('|', $value['imgurl']);
					$list[] = [
						'id' => $value['id'],
						'icolumn' => $value['icolumn'],
						'title' => $value['title'],
						'author' => base64_decode($value['author']),
						'baiduid' => $value['baiduid'],
						'uptime' => date('Y-m-d H:i:s', $value['uptime']),
						'Clicks' => $value['score'],
						'imgurl' => $onepic[0],
					];
				}

				$page = $db->render();
				//模板
				$this->nav(0);
				// 模板变量赋值
				$this->assign('nav', $nav);
				$this->assign('list', $list);
				$this->assign('page', $page);
				//模板
				return view('index');
			} else {
				$this->error('什么也没有搜索到哦....');
			}
		} elseif (!empty($tga['reportinfo'])) {
			$uid = $tga['uid'];
			$reportinfo = $tga['reportinfo'];
			$report = \think\Loader::controller('Sysadmin', 'controller');
			$reportinfo = $report->getreport($uid, $reportinfo);
			if ($reportinfo == true) {
				$this->success('感谢您的举报，我们核实后会进行处理...');
			} elseif ($reportinfo == false) {
				$this->error('帖子举报失败，请稍后重试');
			} else {
				$this->error('该帖子已经被举报，正在处理，请耐心等候....');
			}
		} else {
			$id = input('id');
			if (!empty($id)) {
				$db = \think\Db::name('imginfo')->where("id = '$id' and str = 0 and imgurl != ''")->cache(true, 7200)->select();
				//导航栏TGA
				$nav = \think\Db::name('bdconfig')->where("str = 0")->cache(true, 3600)->select();
				if (!empty($db)) {
					//增加一次点击
					\think\Db::name('imginfo')->where('id', $id)->setInc('score');
					foreach ($db as $key => $value) {
						$onepic = explode('|', $value['imgurl']);
						$author = base64_decode($value['author']);
						$time = date('Y-m-d H:i:s', $value['uptime']);
						$title = $value['title'];
						$Clicks = $value['score'];
						$id = $value['id'];
						$apic = $onepic[0];
					}
					$lastid = $id + 1;
					$passid = $id - 1;
					$this->assign('nav', $nav);
					$this->assign('id', $id);
					$this->assign('lastid', $lastid);
					$this->assign('passid', $passid);
					$this->assign('Clicks', $Clicks);
					$this->assign('icolumn', $value['icolumn']);
					$this->assign('author', $author);
					$this->assign('title', $title);
					$this->assign('pic', $onepic);
					$this->assign('time', $time);
					$this->assign('apic', $apic);
					//模板
					$this->nav(1);
					return view();
				} else {
					$this->error('该帖子已删除或正在审核，请换个其它的主题看吧');
				}
			} else {
				$this->error('这样子是看不了帖子的，做人要乖乖的');
			}
		}
	}

	//最热二十图
	public function topnews() {
		$topdb = \think\Db::name('imginfo')->where("str = 0 and imgurl != ''")->order('score desc')->cache(true, 3600)->limit(40)->select();
		//导航栏TGA
		$nav = \think\Db::name('bdconfig')->where("str = 0")->cache(true, 3600)->select();
		//组合成需要输出的数据
		foreach ($topdb as $key => $value) {
			$onepic = explode('|', $value['imgurl']);
			$list[] = [
				'id' => $value['id'],
				'title' => $value['title'],
				'author' => base64_decode($value['author']),
				'imgurl' => $onepic[0],
			];
		}
		$this->assign('nav', $nav);
		$this->assign('list', $list);
		//模板
		$this->nav(2);
		return view();
	}

	//举报
	public function report() {
		$post = input();
		if (!empty($post)) {
			echo 'ok';
		} else {
			echo 'bad';
		}
	}
}
