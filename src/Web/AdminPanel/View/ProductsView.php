<?php
namespace MyApp\Web\AdminPanel\View;

use Exception;
use PhpApix\Mysql\Db;
use MyApp\App\Component;
use MyApp\App\Menu\Menu;
use MyApp\App\Translate\Trans;
use MyApp\Web\AdminPanel\LeftMenu;
use MyApp\Web\AdminPanel\User;
use MyApp\Web\AdminPanel\TopMenu;
use MyApp\Web\AdminPanel\Footer;
use MyApp\Web\AdminPanel\ProductsList;

class ProductsView extends Component
{
	static public $ErrorUpdate = 0;

	static function Menu()
	{
		$t = new Trans('/src/Web/AdminPanel/Lang', 'pl');

		$t_name = $t->Get('P_CAT');
		$t_title = $t->Get('P_CAT_TITLE');
		$t_add = $t->Get('P_ADD');
		$t_add_title = $t->Get('P_ADD_TITLE');
		$t_edit = $t->Get('P_EDIT');
		$t_edit_title = $t->Get('P_EDIT_TITLE');
		$menu = new Menu('/panel/products', $t_name, $t_title, '<i class="fas fa-shopping-bag"></i>', '<i class="fas fa-shopping-bag"></i>');
		$menu->AddLink('/panel/product/add', $t_add, $t_add_title, '<i class="fas fa-plus"></i>', '<i class="fas fa-plus"></i>');

		if(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH) == '/panel/product/edit')
		{
			$menu->AddLink('/panel/product/edit', $t_edit, $t_edit_title, '<i class="fas fa-edit"></i>', '<i class="fas fa-edit"></i>');
		}
		return $menu;
	}

	static function GetProducts()
	{
		if(empty($_GET['page']) || $_GET['page'] < 1){
			$_GET['page'] = 1;
		}
		$page = (int) $_GET['page'];

		if(empty($_GET['perpage']) || $_GET['perpage'] < 1){
			$_GET['perpage'] = 10;
		}
		$perpage = (int) $_GET['perpage'];

		$offset = $perpage * ($page - 1);
		if($offset < 0){
			$offset = 0;
		}

		try
		{
			// Search
			$q = '';
			$sql = '';
			if(!empty($_GET['q']))
			{
				$q = htmlentities($_GET['q'], ENT_QUOTES, "UTF-8");
				$q = str_replace(' ', '|', $q);
				$q = trim($q);
				$sql = "AND CONCAT_WS(' ', size, name, about, stock_status) REGEXP('".$q."')";
			}

			$db = Db::getInstance();
			$r = $db->Pdo->prepare("SELECT * FROM product WHERE id > 0 ".$sql." ORDER BY id DESC LIMIT :offset,:perpage");
			$r->execute([':offset' => $offset, ':perpage' => $perpage]);
			return $r->fetchAll();
		}
		catch(Exception $e)
		{
			return [];
		}
	}

	static function GetMaxRows()
	{
		try
		{
			// Search
			$q = '';
			$sql = '';
			if(!empty($_GET['q']))
			{
				$q = htmlentities($_GET['q'], ENT_QUOTES, "UTF-8");
				$q = str_replace(' ', '|', $q);
				$q = trim($q);
				$sql = "AND CONCAT_WS(' ', size, name, about, stock_status) REGEXP('".$q."')";
			}

			$db = Db::getInstance();
			$r = $db->Pdo->prepare("SELECT COUNT(*) as cnt FROM product WHERE id > 0 ".$sql);
			$r->execute();
			return $r->fetchAll()[0]['cnt'];
		}
		catch(Exception $e)
		{
			return 1;
		}
	}

	static function Del()
	{
		if(!empty($_GET['delete']))
		{
			try
			{
				$id = (int) $_GET['delete'];

				if($id > 0)
				{
					$db = Db::getInstance();
					$r = $db->Pdo->prepare("DELETE FROM product WHERE id = $id");
					$r->execute();
					$ok = $r->rowCount();

					if($ok > 0)
					{
						$img = 'media/product/'.$id.'.jpg';
						if(file_exists($img)){
							unlink($img);
						}
					}

					return $ok;
				}else{
					return -3;
				}
			}
			catch(Exception $e)
			{
				return -1; // error
			}
		}
	}

	static function Data($arr = null)
	{
		try
		{
			$user = new User(); // Is User logedd

			// If not admin
			if($user->Role() != 'admin' && $user->Role() != 'worker' && $user->Role() != 'driver')
			{
				throw new Exception("Error user privileges", 666);
			}

			if(!empty($_GET['delete']))
			{
				$user->ErrorUpdate = self::Del();
			}
		}
		catch(Exception $e)
		{
			if($e->getCode() == 666){
				// Error user
				header('Location: /logout');
			}else{
				echo $e->getMessage();
			}
		}

		return  $user;
	}

	static function Show($arr = null)
	{
		$t = new Trans('/src/Web/AdminPanel/Lang', 'pl');

		// Get data
		$user = self::Data();

		// Get user data
		$arr['user'] = $user->GetUser();
		$arr['user_info'] = $user->GetUserInfo();
		$arr['error'] = '';
		$arr['trans'] = $t;

		if(!empty($_POST) || !empty($_GET['delete']))
		{
			if($user->ErrorUpdate == 0){
				$arr['error'] = '<span class="green"> '.$t->Get('A_ERR_NOTHING').' </span>';
			}else if($user->ErrorUpdate == 1){
				$arr['error'] = '<span class="green"> '.$t->Get('C_UPDATED').' </span>';
			}else if($user->ErrorUpdate > 0){
				$arr['error'] = '<span class="green"> '.$t->Get('C_UPDATED').' </span>';
			}else if($user->ErrorUpdate == -4){
				$arr['error'] = '<span class="red"> '.$t->Get('C_ERR_EMPTY').' </span>';
			}else if($user->ErrorUpdate == -3){
				$arr['error'] = '<span class="red"> '.$t->Get('C_ERR_DELETE').' </span>';
			}else if($user->ErrorUpdate == -2){
				$arr['error'] = '<span class="red"> '.$t->Get('A_ERR_DUPLICATE').' </span>';
			}else if($user->ErrorUpdate < 0){
				$arr['error'] = '<span class="red"> '.$t->Get('A_ERR_UPDATE').' </span>';
			}
		}

		// Import component
		$menu['top'] = TopMenu::Show($arr);
		$menu['left'] = LeftMenu::Show();
		$menu['footer'] = Footer::Show($arr);

		// Draw list
		$aid = $t->Get('PP_ID');
		$vid = $t->Get('PP_ID_VARIANT');
		$a1 = $t->Get('PP_NAME');
		$a2 = $t->Get('PP_PRICE');
		$a3 = $t->Get('PP_PRICE_SALE');
		$a4 = $t->Get('PP_SIZE');
		$a5 = $t->Get('PP_ACTION');
		$status = $t->Get('PP_STATUS');

		$title = [$aid, $vid, $a1, $a4, $a2.' / '.$a3, $status, $a5];

		$rows =  self::GetProducts();
		$maxrows =  self::GetMaxRows();
		// print_r($maxrows);
		// print_r($rows);
		$menu['list'] = ProductsList::Get($title, $rows, (int) $_GET['page'], $maxrows);

		// Retuen html
		return self::Html($arr, $menu);
	}

	static function Html($arr = null, $html = '')
	{
		return '
		'.$html['top'].'
		<div id="box">
			'.$html['left'].'
			<div id="box-right">
				<h1> '.$arr['trans']->Get('P_TITLE').'  </h1>
				<error id="error">
					' . $arr['error'] . '
				</error>
				<div class="box-wrap">

					<div id="box-fixed" class="animated fadeIn">
						<h3 onclick="Close(this)"> '.$arr['trans']->Get('OR_PRODUCTS').' <i class="fas fa-times close"></i> </h3>
						<form method="GET" action="">
							<label>'.$arr['trans']->Get('PP_SEARCH_TEXT').'</label>
							<input type="text" name="q" placeholder="'.$arr['trans']->Get('EG').' Word">
							<input type="submit" name="add" value="'.$arr['trans']->Get('PP_SEARCH').'" class="btn float-right">
						</form>
					</div>

					<h3> '.$arr['trans']->Get('P_SUB_TITLE').' <a id="btn-search" onclick="OpenOrderSearch(this)"> '.$arr['trans']->Get('PP_SEARCH').' <i class="fas fa-search"></i> </a> <a href="/panel/product/add" id="btn-add-attribute"> '.$arr['trans']->Get('P_ADD_CAT').' <i class="fas fa-plus"></i> </a> </h3>

					'.$html['list'].'

				</div>

			</div>
		</div>
		'.$html['footer'].'
		';
	}

	static function Title()
	{
		return 'Profil';
	}

	static function Description()
	{
		return 'Profil settings.';
	}

	static function Keywords()
	{
		return 'profil, settings';
	}

	static function Head()
	{
		return [
			'<link rel="stylesheet" href="/src/Web/AdminPanel/panel.css">',
			'<script defer src="/src/Web/AdminPanel/panel.js"></script>'
		];
	}
}
?>