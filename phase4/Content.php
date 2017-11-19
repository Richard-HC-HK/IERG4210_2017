<?php
	include_once('lib/db.inc.php');
	include_once('lib/csrf.php');
	session_start();
	session_regenerate_id();

	function loggedin()
	{
		if (!empty($SESSION['t4210']))
			return $_SESSION['t4210']['em'];
		if (!empty($_COOKIE['t4210'])) {
			// stripslashes returns a string with backslashes stripped off.
			//(\' becomes ' and so on)
			if ($t = json_decode(stripslashes($_COOKIE['t4210']), true)) {
				if (time() > $t['exp']) return false;
				$db = ierg4210_DB();
				$q = $db->prepare("SELECT * FROM account WHERE email = ?");
				$q->execute(array($t['em']));
				if ($r = $q->fetch()) {
					$realk = hash_hmac('sha1', $t['exp'] . $r['password'], $r['salt']);
					if ($realk == $t['k']  && $r['email'] != "jerica0527@gmail.com") {
						$_SESSION['t4210'] = $t;
						return $t['em'];
					}
				}
			}
		}
		return false;
	}

	if (!loggedin()) {
		// redirect to login
		header('Location:login.php');
		exit();
	}

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

	<form method="POST" action="auth-process.php?action=<?php echo ($action = 'logout'); ?>">
		<input class="username" type="text" readonly="readonly" value="<?php echo loggedin();?>" />
		<input class="logoutForm" type="submit" value="Log Out" />
		<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
  </form>

	<div class="title">
		<p> <a href="Content.php" id='renew'>Home</a></p>
	</div>

	<div class="shoplist">
		<nav>
			<p>Shopping List: </p> <p id="totalprice"></p>
			<ul id="shopdetails" class="box">
				<!-- <li>Ashe: <input size ="2" type="text" value="1"> @450USD <button type="button"> Add </button><button type="button"> Del </button></li>
				<li>Tristana: <input size ="2" type="text" value="1"> @1350USD <button type="button"> Add </button><button type="button"> Del </button></li> -->
			</ul>
			<button type="button" id="check">Check Out</button>

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
		 return;
	 }
	myLib.get({action:'prod_fetchall'}, function(json){
		// loop over the server response json
		//   the expected format (as shown in Firebug):
		for (var listItems = [],i = 0, prod; prod = json[i]; i++) {
			if(localStorage.getItem(prod.pid) != null){
				var number = JSON.parse(localStorage.getItem(parseInt(prod.pid)));
				if(number>0)
				{
					listItems.push('<li>',prod.name.escapeHTML(),': ',parseInt(number),' @',prod.price.escapeHTML()
					,'<button type="button" onclick="butevent','(',parseInt(prod.pid),')','">+</button>',
					'<button type="button" onclick="butevent2','(',parseInt(prod.pid),')','">-</button>',
					'</li>');
				}
				el('shopdetails').innerHTML = listItems.join('');
			}
		}
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
			alert('price: '+String(totalprice));
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
		el('totalprice').innerHTML = String(totalprice);
		localStorage.totalprice= String(totalprice);
		alert('price: '+String(totalprice));
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

	// el('renew').onclick()= function(e){
	// 	//?
	// 	el('productPanel').hide();
	// 	el('categoryListPanel').show();
	// 	el('categoryPanel').show();
	// }

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
