<?php
namespace MyApp\Web\Page\ProductList;

use Exception;
use PhpApix\Mysql\Db;
use MyApp\App\Translate\Trans;
// use MyApp\Web\Currency;

class ProductBoxCheckout
{
	static function Show()
	{
		$t = new Trans('/src/Web/Page/ProductList/Lang', 'pl');

		$h = '
			<div class="checkout-box">
				<div class="h1"> Checkout </div>

				<div id="shopping-cart" class="animated fadeIn">
					<div id="cart-top">
						<span> <i class="fas fa-shopping-cart"></i> '.$t->Get('PRODUCTS_CART').' </span>
						<div id="close-cart"> <i class="fas fa-times"></i> </div>
						<a href="/checkout"> <div id="cart-checkout"> '.$t->Get('PRODUCTS_ORDER').' </div> </a>
					</div>
					<div id="cart-hover">
						<div class="empty-cart"> '.$t->Get('PRODUCTS_ADD').' </div>
					</div>
				</div>

				<div id="shopping-checkout" class="animated fadeIn">
					<div id="cart-hover-checkout">
						<div class="empty-cart"> '.$t->Get('PRODUCTS_ADD').' </div>
					</div>
				</div>
			</div>
		';
		return $h;
	}

	static function Head()
	{
		return '
		<link rel="stylesheet" href="/src/Web/Page/ProductList/product-box.css">
		<script defer src="/src/Web/Page/ProductList/product-box.js"></script>
		';
	}

	static function GetParams()
	{
		$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		return explode('/', rtrim(ltrim($url, '/'), '/'));
	}

	static function GetProducts($slug = '', int $page = 1, int $perpage = 6, $q = '')
	{
		try
		{
			if($page < 1 ){ $page = 1; }
			$offset = (int) (($page - 1) * $perpage);

				// Get products
			$db = Db::GetInstance();

			if(!empty($q))
			{
				$q = str_replace(' ', '|', $q);
				$sql = "SELECT * FROM product WHERE CONCAT_WS('',name,about) REGEXP :q AND parent = 0 ORDER BY id DESC LIMIT $offset, $perpage";
				$r = $db->Pdo->prepare($sql);
				$r->execute([':q' => $q]);
			}
			else
			{
				// Category id
				$cid = (int) self::GetCategoryId($slug);
				$sql = "SELECT * FROM product WHERE category = $cid AND parent = 0 AND visible = 1 ORDER BY id DESC LIMIT $offset, $perpage";
				if($cid == 0)
				{
					$sql = "SELECT * FROM product WHERE category != $cid AND parent = 0 AND visible = 1 ORDER BY id DESC LIMIT $offset, $perpage";
				}
				$r = $db->Pdo->prepare($sql);
				$r->execute();
			}

			return $r->fetchAll();
		}
		catch(Exception $e)
		{
			echo $e->getMessage();
		}
	}

	static function GetCategoryId($slug = '')
	{
		try
		{
			$db = Db::GetInstance();
			$r = $db->Pdo->prepare("SELECT id FROM category WHERE slug = :slug");
			$r->execute([':slug' => $slug]);
			$o = $r->fetchAll();
			if(!empty($o)){
				return $o[0]['id'];
			}else{
				return 0;
			}
		}
		catch(Exception $e)
		{

		}
	}
}