<?php
/*
 * 后台管理
 */
namespace app\index\controller;
use think\Controller;
use think\Session;

class Sysadmin extends Controller {
	//登录首页
	public function index() {
		if (empty($_POST)) {
			//模板
			return view();
		} else {
			$username = input('post.username');
			$password = md5(input('post.password'));
			$code = input('post.captcha');
			if (!empty($username) and !empty($password) and !empty($code)) {
				//对比数据库
				$cedb = \think\Db::name('admin_user')->where("username = '$username' and password = '$password'")->find();
				if (!empty($cedb)) {
					//对比验证码
					//验证码
					$captcha = new \think\captcha\Captcha();
					if (captcha_check($code)) {
						//对比完成
						Session::set('userid', $cedb['id']);
						Session::set('username', $cedb['username']);
						Session::set('name', $cedb['name']);
						$this->success('登录成功', 'admin');
					} else {
						$this->error('验证码错误，如看不清楚请点击验证码刷新！请重新登录');
					}
				} else {
					$this->error('用户名或密码错误，请重新登录');
				}
			} else {
				$this->error('请输入用户名等相关信息');
			}
		}
	}

	//登录验证
	public function celog($userid, $username, $name) {
		$cedb = \think\Db::name('admin_user')->where("id = '$userid' and username = '$username' and name = '$name'")->find();
		if (!empty($cedb)) {
			return true;
		} else {
			session::clear();
			$this->error('非法登录或已登录超时，请从新登录', 'sysadmin/index');
		}
	}

	//退出系统
	public function logout() {
		session::clear();
		$this->success('退出成功，欢迎下次回来~', 'sysadmin/index');
	}

	//后台首页
	public function admin() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//缓存帖子
			$tmpdb = \think\Db::name('tmpinfo')->cache(true, 120)->count();
			$this->assign('tmpdb', $tmpdb);
			//采集帖子
			$cjdb = \think\Db::name('imginfo')->cache(true, 120)->count();
			$this->assign('cjdb', $cjdb);
			//最新帖子
			$newdb = \think\Db::name('imginfo')->where("str = '0' and imgurl != ''")->order('id desc')->cache(true, 120)->limit(10)->select();
			$this->assign('newdb', $newdb);
			//模板
			$this->assign('name', $name);
			return view();
		}
	}

	//数据管理
	public function admininfo() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//获取数据
			$list = \think\Db::name('imginfo')->where('')->order('id desc')->paginate(20); //20条一页
			// 获取分页显示
			$page = $list->render();
			// 模板变量赋值
			$this->assign('list', $list);
			$this->assign('page', $page);
			//模板
			$this->assign('name', $name);
			return view();
		}
	}

	//停止帖子
	public function stopid($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//关闭帖子
			$list = \think\Db::name('imginfo')->where('id', $id)->update(['str' => '1']);
			if (!empty($list)) {
				//正常
				$this->success('帖子关闭完成');
			} else {
				$this->error('没有找到您要关闭的帖子');
			}
		}
	}

	//恢复帖子
	public function reid($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//关闭帖子
			$list = \think\Db::name('imginfo')->where('id', $id)->find();
			if (!empty($list)) {
				//正常
				$list = \think\Db::name('imginfo')->where('id', $id)->update(['str' => '0']);
				$this->success('帖子恢复完成');
			} else {
				$this->error('没有找到您要恢复的帖子');
			}
		}
	}

	//删除文件夹的方法
	public function deldir($dir) {
		$dh = opendir($dir);
		while ($file = readdir($dh)) {
			if ($file != "." && $file != "..") {
				$fullpath = $dir . "/" . $file;
				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					deldir($fullpath);
				}
			}
		}
		closedir($dh);
		if (rmdir($dir)) {
			return true;
		} else {
			return false;
		}
	}

	//删除数据
	public function deldb($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//删除帖子
			$list = \think\Db::name('imginfo')->where('id', $id)->find();
			$img = $list['imgurl'];
			$imgarr = explode('|', $img);
			foreach ($imgarr as $key => $value) {
				$strimg = $value;
			}
			//组合删除路径
			$arrurl = explode('/', $strimg);
			$link = '././' . $arrurl['1'] . '/' . $arrurl['2'] . '/' . $arrurl['3'] . '/' . $arrurl['4'] . '/' . $arrurl['5'] . '/' . $arrurl['6'];
			$delfile = $this->deldir($link);
			if ($delfile == true) {
				//更新IMG表状态
				$list = \think\Db::name('imginfo')->where('id', $id)->update(['str' => '2']);
				if ($list) {
					$this->success('帖子删除完成，图片文件删除完成');
				} else {
					$this->error('帖子数据库删除失败');
				}
			} else {
				$this->error('图片目录删除失败');
			}
		} else {
			$this->error('没有找到您要删除的帖子');
		}
	}

	//采集配置管理
	public function config() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			$list = \think\Db::name('bdconfig')->where('')->order('id desc')->select();
			$this->assign('list', $list);
			//模板
			$this->assign('name', $name);
			return view();
		}
	}

	//停止采集配置
	public function stopdb($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//关闭帖子
			$list = \think\Db::name('bdconfig')->where('id', $id)->find();
			if (!empty($list)) {
				//正常
				$alist = \think\Db::name('bdconfig')->where('id', $id)->update(['str' => '1']);
				$this->success('配置停止完成');
			} else {
				$this->error('没有找到您要关闭的配置');
			}
		}
	}

	//恢复帖子
	public function redb($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//关闭帖子
			$list = \think\Db::name('bdconfig')->where('id', $id)->find();
			if (!empty($list)) {
				//正常
				$alist = \think\Db::name('bdconfig')->where('id', $id)->update(['str' => '0']);
				$this->success('配置恢复完成');
			} else {
				$this->error('没有找到您要恢复的配置');
			}
		}
	}

	//删除采集配置
	public function deldbconfig($id) {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//关闭帖子
			$list = \think\Db::name('bdconfig')->where('id', $id)->find();
			if (!empty($list)) {
				//正常
				$alist = \think\Db::name('bdconfig')->where('id', $id)->delete();
				$this->success('配置删除完成');
			} else {
				$this->error('没有找到您要删除的配置');
			}
		}
	}

	//新增采集配置
	public function addconfig() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//判断提交
			if (empty(input())) {
				//模板
				$this->assign('name', $name);
				return view();
			} else {
				$post = input();
				//添加数据
				$indb = \think\Db::name('bdconfig')->insert($post);
				if ($indb) {
					$this->success('配置添加完成', 'config');
				} else {
					$this->error('配置添加失败');
				}
			}
		}
	}

	//用户管理
	public function userinfo() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			//判断提交
			if (empty(input())) {
				$cedb = \think\Db::name('admin_user')->where('')->find();
				$this->assign('list', $cedb);
				//模板
				$this->assign('name', $name);
				return view();
			} else {
				$post = input();
				$username = $post['username'];
				$data['username'] = $post['username'];
				$data['password'] = md5($post['password']);
				$data['name'] = $post['name'];
				$cedb = \think\Db::name('admin_user')->where('username', $username)->find();
				if (empty($cedb)) {
					//没有就增加
					$indb = \think\Db::name('admin_user')->insert($data);
					if ($indb) {
						$this->success('管理员新增完成');
					} else {
						$this->error('管理新增失败');
					}
				} else {
					//有就是更新
					$indb = \think\Db::name('admin_user')->where('username', $username)->update($data);
					if ($indb) {
						$this->success('管理员更新完成');
					} else {
						$this->error('管理更新失败');
					}
				}
			}
		}
	}

	//举报帖子,记录举报的方法
	public function getreport($uid, $reportinfo) {
		$indb = \think\Db::name('report')->where("uid = '$uid' and reportstr = 0")->find();
		if (empty($indb)) {
			$data['uid'] = $uid;
			$data['info'] = $reportinfo;
			$data['time'] = time();
			$updb = \think\Db::name('report')->insert($data);
			if ($updb) {
				return true;
			} else {
				return false;
			}
		} else {
			return 'bad';
		}
	}

	//举报列表
	public function report() {
		//登录验证
		$userid = Session::get('userid');
		$username = Session::get('username');
		$name = Session::get('name');
		$celog = $this->celog($userid, $username, $name);
		if ($celog) {
			$list = \think\Db::name('report')->alias('a')->join('tp_imginfo w', 'a.uid = w.id')->group('a.id desc')->paginate(20); //20条一页
			// 获取分页显示
			$page = $list->render();
			// 模板变量赋值
			$this->assign('list', $list);
			$this->assign('page', $page);
			//模板
			$this->assign('name', $name);
			return view();
		}
	}

	//撤销举报
	public function passreport($uid) {
		$list = \think\Db::name('report')->where('uid', $uid)->update(['reportstr' => '1']);
		if ($list) {
			$this->success('投诉撤销完成');
		} else {
			$this->error('投诉撤销失败了');
		}
	}

	//删除举报
	public function delreport($uid) {
		$list = \think\Db::name('report')->where('uid', $uid)->find();
		\think\Db::name('report')->where('uid', $uid)->update(['reportstr' => '1']);
		$id = $list['uid'];
		$this->deldb($id);
	}

}