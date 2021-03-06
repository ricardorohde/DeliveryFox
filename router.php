<?php
use PhpApix\Router\Router;
use MyApp\Web\Error\ErrorPage;

try
{
	$r = new Router();

	// Namespace path
	$r->Set("/", "MyApp/Web/Home/Homepage", "Index");
	$r->Set("/gallery", "MyApp/Web/Home/Gallery/Gallery", "Index");
	$r->Set("/logout", "MyApp/Web/Logout/Logout", "Index");
	// Products category
	$r->Set("/category", "MyApp/Web/Category/Category", "Index");
	$r->Set("/category/{slug}", "MyApp/Web/Category/Category", "Index");
	// Checkout
	$r->Set("/checkout", "MyApp/Web/Checkout/Checkout", "Index");
	$r->Set("/order", "MyApp/Web/Checkout/OrderStatus", "Index");
	// Include Auth Component route path
	$r->Include("Web/Auth/routes");
	// Admin Panel
	$r->Include("Web/AdminPanel/routes");

	// $r->ErrorPage();
	ErrorPage::Error404();
}
catch(Exception $e)
{
	echo json_encode(["errorMsg" => $e->getMessage(), "errorCode" => $e->getCode()]);
}
?>


<?php
/*
// Redirect uri (on top)
$r->Redirect('/panel', '/panel/profil');

// Only GET
$r->Set('/route1', function($p) {
	echo "WORKS WITH GET " . $p[0] . ' ' .$_GET['id'];
}, ['Param 1'], ['GET']);

// Only POST, PUT
$r->Set('/route2', function($p) {
	echo "WORKS WITH POST " . ' ' . implode(' ', $_POST);
}, 'Func params here', ['POST', 'PUT']);

// Api route
$r->Set("/api/user/{id}", "Api/User/User", "GetId");

// Add route: url, class path, class method
$r->Set("/welcome/email/{id}", "Api/Sample/SampleClass", "Index");

// Or load from controller route.php file
// $r->Include('Api/Sample/route');

*/
?>
