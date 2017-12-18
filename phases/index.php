<?php
	include_once('lib/db.inc.php');
	include_once('lib/csrf.php');
	if(!isset($_SESSION))
	{
	session_start();
	}
	session_regenerate_id();
	function loggedin()
	{
		if (!empty($SESSION['t4210']))
			return $_SESSION['t4210']['em'];
		if (!empty($_COOKIE['t4210']))
		{
			// stripslashes returns a string with backslashes stripped off.
			//(\' becomes ' and so on)
			if ($t = json_decode(stripslashes($_COOKIE['t4210']), true))
			{
				if (time() > $t['exp']) return false;
				$db = ierg4210_DB();
				$q = $db->prepare("SELECT * FROM account WHERE email = ?");
				$q->execute(array($t['em']));
				if ($r = $q->fetch())
				{
					$realk = hash_hmac('sha1', $t['exp'] . $r['password'], $r['salt']);
					if ($realk == $t['k']  && $r['flag'] == 1) {
						$_SESSION['t4210'] = $t;
						return $t['em'];
					}
				}
			}
		}
		return false;
	}
	// if (!loggedin()) {
	// 	// redirect to login
	// 	header('Location:login.php');
	// 	exit();
	// }
?>

<?php
	if (loggedin()) {
		$action = 'logout';
		echo "<form method = \"POST\" action = \"auth-process.php?action=".$action."\" >";
			echo "<input class=\"username\" type=\"text\" readonly=\"readonly\" value=\"" . loggedin() . "\" />";
			echo "<input class=\"logoutForm\" type = \"submit\" value = \"Log Out\" />";
			echo "<input type = \"hidden\" name = \"nonce\" value = \"".csrf_getNonce($action)."\" />";
		echo "</form>";

		echo "<form method=\"POST\" action=\"change_psd.php\" >";
			echo "<input class=\"changeForm\" type = \"submit\" value = \"changePasswd\" />";
		echo "</form>";

		echo "<form method=\"POST\" action=\"check_order.php\" >";
			echo "<input class=\"checkOrder\" type = \"submit\" value = \"check recent orders\" />";
		echo "</form>";
	}
	else
	{
		echo "<form method = \"POST\" action = \"login.php\" >";
			echo "<input class=\"logoutForm\" type = \"submit\" value = \"Log In\" />";
		echo "</form>";
	}
?>

<?php
	$db = ierg4210_DB();
	$q = $db->query("SELECT catid FROM categories");
	$catID = $q->fetchAll(PDO::FETCH_COLUMN,0);
	$q = $db->query("SELECT name FROM categories");
	$cat = $q->fetchAll(PDO::FETCH_COLUMN,0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>MainPage</title>
	<link href="Style.css" rel="stylesheet" type="text/css"/>
</head>
<body>
	<h1>
		<p class="center">
			<i>Main Page</i>
		</p>
	</h1>

	<div class="title">
		<p> <a href="index.php" id='renew'>Home</a></p>
	</div>

	<div class="shoplist">
		<nav>
			<p>Shopping List: </p> <p id="totalprice"></p>
      <!-- <form id="payForm" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="POST" onsubmit="return cart_submit(this)"> -->
			<ul id="shopdetails" class="box">
           <!-- add content by json -->
			</ul>
			<form id="payForm" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="POST" onsubmit="return cart_submit(this)">
				   <!-- add content by json -->
			</form>

		</nav>
	</div>

<section id="categoryListPanel">
	<div class="clist">
		<p class="bigfont"><i>Categories:</i></p>
		<ul id="categoryList"></ul>
	</div>
</section>
<!-- <section id="categoryListPanel"> -->

<section id="categoryPanel">
	<div class="plist">
		<ul class="table" id="productList"></ul>
	</div>
</section>

<section id="productPanel">
	<div class="pdetail" id="productDetails">
	</div>
</section>

<script type="text/javascript" src="incl/myLib.js"></script>
<script type="text/javascript">
function updateShoplist() {
	if(localStorage.getItem('totalprice')==0) {
		 el('shopdetails').innerHTML = '';
	 }
	myLib.get({action:'prod_fetchall'}, function(json){
		// loop over the server response json
		//   the expected format (as shown in Firebug):
		var form = " ";
		form += "<input type=\"hidden\" name=\"cmd\" value=\"_cart\">";
		form += "<input type=\"hidden\" name=\"upload\" value=\"1\">";
		form += "<input type=\"hidden\" name=\"business\" value=\"hc015-facilitator@ie.cuhk.edu.hk\">";
		form += "<input type=\"hidden\" name=\"currency_code\" value=\"HKD\">";
		form += "<input type=\"hidden\" name=\"charset\"  value=\"utf-8\">";
    var cont = " ";
		var list_num = 1;
		for (var listItems = [], i = 0, prod; prod = json[i]; i++) {
			if(localStorage.getItem(prod.pid) != null){
				var number = JSON.parse(localStorage.getItem(parseInt(prod.pid))); // get the number of certain product
				if(number>0)
				{
					// updating cart information
					listItems.push('<li>',prod.name.escapeHTML(),': ',parseInt(number),' @',prod.price.escapeHTML()
					,'<button type="button" onclick="butevent','(',parseInt(prod.pid),')','">+</button>',
					'<button type="button" onclick="butevent2','(',parseInt(prod.pid),')','">-</button>',
					'</li>');
					// updating form information
					form += "<input type=\"hidden\" name=\"item_name_"+ list_num +"\" value=\""+ prod.name +"\"  >" ;
					form += "<input type=\"hidden\" name=\"item_number_"+ list_num +"\" value=\""+ prod.pid + "\" >";
					form += "<input type=\"hidden\" name=\"quantity_"+ list_num +"\" value=\""+ number +"\" >";
					form += "<input type=\"hidden\" name=\"amount_"+ list_num +"\" value=\""+ prod.price +"\"  >" ;
					list_num += 1;
				}
				cont = listItems.join('');
			}
		}
		if(localStorage.getItem('totalprice')<=0)
		{
			localStorage.setItem('totalprice')=0;
			el('shopdetails').innerHTML = "";
			el('totalprice').innerHTML=0;
	  }
		else
		{
		el('shopdetails').innerHTML = cont;
	  }

		form += "<input type=\"hidden\" name=\"custom\" value=\"\">";
		form += "<input type=\"hidden\" name=\"invoice\" value=\"\">";
		form += "<input type=\"submit\" id=\"checkout\" value=\"Checkout\">";
		document.getElementById("payForm").innerHTML = form;

		// alert(listItems.join(''));
		// el('shopdetails').innerHTML = listItems.join('');
	});
}
function butevent(id)
{
		myLib.get({action:'prod_select',pid: id}, function(json){
			var prod=json[0];
			if (localStorage.getItem('totalprice') == null) localStorage.setItem('totalprice','0');
			var totalprice=parseInt(localStorage.getItem('totalprice')); //get the totalprice from the local stroage
			totalprice= totalprice+parseInt(prod.price);
			el('totalprice').innerHTML = String(totalprice);
			localStorage.totalprice= String(totalprice);
			alert('New Product Added!');

		});
		if(localStorage.getItem(id) == null)
		{
			localStorage.setItem(id, JSON.stringify(1));
		}
		else{
			var count = JSON.parse(localStorage.getItem(id)) + 1;
			localStorage.setItem(id,  JSON.stringify(count));
		}
  updateShoplist();
}
function butevent2(id)
{
    if(localStorage.getItem('totalprice')>0)
		{
		myLib.get({action:'prod_select',pid: id}, function(json){
		var prod=json[0];
		if (localStorage.getItem('totalprice') == null) localStorage.setItem('totalprice','0');
		var totalprice=parseInt(localStorage.getItem('totalprice')); //get the totalprice from the local stroage
		totalprice= totalprice-parseInt(prod.price);
		localStorage.totalprice= String(totalprice);
		if(localStorage.totalprice>=0)
		{
		el('totalprice').innerHTML = localStorage.totalprice;
		alert('Product Deleted!');
	  }
		else {
			localStorage.totalprice=0;
			el('totalprice').innerHTML = localStorage.totalprice;
			el('shopdetails').innerHTML = "";
			alert('No Product to Delete any more!');
		}

    });
	  }

		if(localStorage.getItem(id) >1)
		{
			var count = JSON.parse(localStorage.getItem(id)) - 1;
			localStorage.setItem(id, count);
		}
		else{
			localStorage.removeItem(id);
		}
	  updateShoplist();
}

function cart_submit(form){
// alert("Yes!");
	var buyList={};
	for(var key in localStorage){
		if(key!='totalprice'){
		buyList[key]=parseInt(localStorage.getItem(key));
		// alert(buyList[key]);
		// alert(key);
	}
		//combine the pid and quantity into array
	}
	myLib.processJSON(
				"checkout-process.php",                                      //para 1
				{action: "handle_checkout", list:JSON.stringify(buyList)},   //para 2
				function(returnValue){                                   //para 3
				form.custom.value=returnValue.digest;
				form.invoice.value=returnValue.invoice;
				alert('you will be redirected to the paypal!');
				form.submit();
				localStorage.clear();
				updateShoplist();
			},
				{method:"POST"});
																					//para 4
	return false;
}

(function(){
	function updateUI() {
		myLib.get({action:'cat_fetchall'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug):
			for (var listItems = [],
					i = 0, cat; cat = json[i]; i++) {
				listItems.push('<li id="cat' , parseInt(cat.catid) , '"><i>' , cat.name.escapeHTML() , '</i></li>');
			}
			el('categoryList').innerHTML = listItems.join('');
		});
		myLib.get({action:'prod_fetchall'}, function(json){
			// loop over the server response json
			//   the expected format (as shown in Firebug):
			for (var listItems = [],
					i = 0, prod; prod = json[i]; i++) {
				listItems.push('<li id="prod' , parseInt(prod.pid) , '">' , '<img src="incl/img/', parseInt(prod.pid), '.jpg"/>', '<p class="center">',prod.name.escapeHTML() , ' - $', prod.price.escapeHTML(),'</p><button type="button" onclick="butevent','(',parseInt(prod.pid),')','">Add to Cart!</button></li>');
			}
			el('productList').innerHTML = listItems.join('');
		});
		if (localStorage.getItem('totalprice') == null) localStorage.setItem('totalprice','0');
		el('totalprice').innerHTML = localStorage.getItem('totalprice');
		updateShoplist();
	}
	updateUI();
	el('categoryList').onclick = function(e) {
		//alert(e.target.parentNode.id.replace(/^cat/, ''));
		var id = e.target.parentNode.id.replace(/^cat/, '');
				myLib.get({action:'cat_select',catid: id}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				for (var listItems = [],
						i = 0, prod; prod = json[i]; i++) {
					listItems.push('<li id="prod' , parseInt(prod.pid) , '">' , '<img src="incl/img/', parseInt(prod.pid), '.jpg"/>', '<p class="center">',prod.name.escapeHTML() , ' - $', prod.price.escapeHTML(),'</p><button type="button" onclick="butevent','(',parseInt(prod.pid),')','">Add to Cart!</button></li>');
					}
				el('productList').innerHTML = listItems.join('');
			});
			el('productPanel').hide();
			el('categoryListPanel').show();
			el('categoryPanel').show();
			// fill in the editing form with existing values
		//handle the click on the category name
	}
	el('productList').onclick = function(e) {
		var id = e.target.parentNode.id.replace(/^prod/, '');
		myLib.get({action:'prod_select',pid: id}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				for (var listItems = [],
						i = 0, prod; prod = json[i]; i++) {
						 listItems.push('<li id="prod' , parseInt(prod.pid) , '" class="pro_place">' , '<img src="incl/img/', parseInt(prod.pid), '.jpg"/>', '<p class="center">',prod.name.escapeHTML() , ' - $', prod.price.escapeHTML(),'</p><p class="center">',prod.Description.escapeHTML(),'</p><button type="button" onclick="butevent','(',parseInt(prod.pid),')','">Add to Cart!</button></li>');
					  }
        // alert('okay!');
				el('productDetails').innerHTML = listItems.join('');
		});
			// fill in the editing form with existing values
    // alert('okay!');
		el('productPanel').show();
		el('categoryListPanel').show();
		el('categoryPanel').hide();
		//handle the click on the category name
	}
})();
</script>
</body>
</html>

<style>
  body{background-image: url("images/background.jpg");}
  .hide{display:none}
	.pro_place{position: absolute; top: 30%; height: 50%; left:15%; width:40%;}
	nav ul{display: none}
	nav:hover ul{display: block}
  ul.box{background-color: #FAFAD2; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);}
	p.right{text-align: right;}
	p.center{text-align: center;font-size:75%;line-height: 75%}
	p.bigfont{font-size: 120%;}
	div.title{position: absolute; top: 20%; height: 20%; left: 15%; width:85%;}
	div.plist{position: absolute; top: 30%; height: 100%; left:15%; width:85%;}
	div.shoplist{position: absolute; top: 15%; height: 15%; left:75%; width:25%;font-size: 90%;line-height: 75%;}
	div.clist{position: absolute; top: 25%; height: 30%; left:0%; width:30%;}
	#renew{cursor:pointer;text-decoration:underline;color:#00F}
	#categoryList{cursor:pointer;text-decoration:underline;color:#00F}
	#productList{color:#00F}
	div.product{display:none}
	div.display_area{display: block}
	ul.table{width:80%;height:90%;margin:0;padding:0;list-style:none;overflow:auto}
	ul.table li{width:30%;height:29%;float:left;border:0.1px solid #CCC;overflow: auto}
	img{max-width: 90%; max-height: 90%; padding-left:5%; padding-right:5%}
</style>
