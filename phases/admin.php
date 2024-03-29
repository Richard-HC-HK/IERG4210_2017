<?php
session_start();
include_once('lib/db.inc.php');
include_once('lib/csrf.php');

session_regenerate_id();
function loginstate()
{
	if (!empty($SESSION['t4210']))
		return $_SESSION['t4210']['em'];
	if (!empty($_COOKIE['t4210'])) {
		// stripslashes returns a string with backslashes stripped off.
		if ($t = json_decode(stripslashes($_COOKIE['t4210']), true))
		{
			if (time() > $t['exp']) return false;

			$db = ierg4210_DB();
			$q = $db->prepare("SELECT * FROM account WHERE email = ?");
			$q->execute(array($t['em']));
			if ($r = $q->fetch())
			{
				$realk = hash_hmac('sha1', $t['exp'] . $r['password'], $r['salt']);
				if ($realk == $t['k'] && $r['flag'] == 0)
				{
					$_SESSION['t4210'] = $t;
					return $t['em'];
				}
			}
		}
	}
	return false;
}

if (!loginstate()) {
	// redirect to login
	header('Location:login.php');
	exit();
}
?>


<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>IERG4210 Shop - Admin Panel</title>
		<link href="incl/admin.css" rel="stylesheet" type="text/css"/>
	</head>

	<body>
	<h1>IERG4210 Shop - Champion Shop Admin Panel</h1>

	<form method="POST" action="auth-process.php?action=<?php echo ($action = 'logout'); ?>">
    <input type="text" readonly="readonly" value="<?php echo loginstate();?>" />
    <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
    <input type="submit" value="Log Out" />
  </form>
  <br>

	<article id="main">

	<section id="categoryPanel">
		<fieldset>
			<legend>New Category</legend>
			<form id="cat_insert" method="POST" action="admin-process.php?action=cat_insert" onsubmit="return false;">
				<label for="cat_insert_name">Name</label>
				<div><input id="cat_insert_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>

        <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
				<input type="submit" value="Submit" />
			</form>
		</fieldset>

		<!-- Generate the existing categories here -->
		<ul id="categoryList"></ul>
	</section>

	<section id="categoryEditPanel" class="hide">
		<fieldset>
			<legend>Editing Category</legend>
			<form id="cat_edit" method="POST" action="admin-process.php?action=cat_edit" onsubmit="return false;">
				<label for="cat_edit_name">Name</label>
				<div><input id="cat_edit_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>
				<input type="hidden" id="cat_edit_catid" name="catid" />
				<input type="submit" value="Submit" /> <input type="button" id="cat_edit_cancel" value="Cancel" />
			</form>
		</fieldset>
	</section>

	<section id="productPanel">
		<fieldset>
			<legend>New Product</legend>
			<form id="prod_insert" method="POST" action="admin-process.php?action=prod_insert" enctype="multipart/form-data">
				<label for="prod_insert_catid">Category *</label>
				<div><select id="prod_insert_catid" name="catid"></select></div>

				<label for="prod_insert_name">Name *</label>
				<div><input id="prod_insert_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>

				<label for="prod_insert_price">Price *</label>
				<div><input id="prod_insert_price" type="number" name="price" required="true" pattern="^[\d\.]+$" /></div>

				<label for="prod_insert_description">Description</label>
				<div><textarea id="prod_insert_description" name="description" pattern="^[\w\-, ]$"></textarea></div>

				<label for="prod_insert_name">Image *</label>
				<div><input type="file" name="file" required="true" accept="image/jpeg" /></div>

				<input type="submit" value="Submit" id="prod_insert_submit"/>
			</form>
		</fieldset>



		<!-- Generate the corresponding products here -->
		<ul id="productList"></ul>

	</section>

	<section id="productEditPanel" class="hide">
		<!--
			Design your form for editing a product's catid, name, price, description and image
			- the original values/image should be prefilled in the relevant elements (i.e. <input>, <select>, <textarea>, <img>)
			- prompt for input errors if any, then submit the form to admin-process.php (AJAX is not required)
		-->
		<legend>Product Editing</legend>
		<form id="prod_edit" method="POST" action="admin-process.php?action=prod_edit" enctype="multipart/form-data">
			<label for="prod_edit_catid">Category *</label>
			<div><select id="prod_edit_catid" name="catid"></select></div>

			<label for="prod_edit_name">Name *</label>
			<div><input id="prod_edit_name" type="text" name="name" required="true" pattern="^[\w\- ]+$" /></div>

			<label for="prod_edit_price">Price *</label>
			<div><input id="prod_edit_price" type="number" name="price" required="true" pattern="^[\d\.]+$" /></div>

			<label for="prod_edit_description">Description</label>
			<div><textarea id="prod_edit_description" name="description" pattern="^[\w\-, ]$"></textarea></div>

			<label for="prod_edit_name">Image *</label>
			<div><input type="file" name="file" required="true" accept="image/jpeg" /></div>

			<label for="prod_edit_pid">Pid *</label>
			<div><input id="prod_edit_pid" type="number" name="pid" required="true" pattern="^[\d\.]+$" /></div>

			<input type="submit" value="Submit" id="prod_edit_submit"/> <input type="button" id="prod_edit_cancel" value="Cancel" />
		</form>


	</section>


	<section id="transPanel">
		<fieldset>
			<legend>Recent 10 Transactions</legend>
			<ul id="transainfo" class="trans">
           <!-- add content by json -->
			</ul>
		</fieldset>
	</section>

	<section id="pruchasePanel">
		<fieldset>
			<legend>All Products in Recent Transactions</legend>
			<ul id="purchaseinfo" class="prus">
					 <!-- add content by json -->
			</ul>
		</fieldset>
	</section>



	<div class="clear"></div>
	</article>


	<script type="text/javascript" src="incl/myLib.js"></script>
	<script type="text/javascript">
	(function(){

		function updateUI() {
			myLib.get({action:'cat_fetchall'}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				for (var options = [], listItems = [],
						i = 0, cat; cat = json[i]; i++) {
					options.push('<option value="' , parseInt(cat.catid) , '">' , cat.name.escapeHTML() , '</option>');
					listItems.push('<li id="cat' , parseInt(cat.catid) , '"><span class="name">' , cat.name.escapeHTML() , '</span> <span class="delete">[Delete]</span> <span class="edit">[Edit]</span></li>');
				}
				el('prod_insert_catid').innerHTML = '<option></option>' + options.join('');
				el('prod_edit_catid').innerHTML = '<option></option>' + options.join('');
				el('categoryList').innerHTML = listItems.join('');
			});
			el('productList').innerHTML = '';
		}
		updateUI();

		el('categoryList').onclick = function(e) {
			if (e.target.tagName != 'SPAN')
				return false;


			var target = e.target,
				parent = target.parentNode,
				id = target.parentNode.id.replace(/^cat/, ''),
				name = target.parentNode.querySelector('.name').innerHTML;

			// handle the delete click
			if ('delete' === target.className) {
				confirm('Sure?') && myLib.post({action: 'cat_delete', catid: id}, function(json){
					alert('"' + name + '" is deleted successfully!');
					updateUI();
				});

			// handle the edit click
			} else if ('edit' === target.className) {
				// toggle the edit/view display
				el('categoryEditPanel').show();
				el('categoryPanel').hide();

				// fill in the editing form with existing values
				el('cat_edit_name').value = name;
				el('cat_edit_catid').value = id;

			//handle the click on the category name
			} else {
				el('prod_insert_catid').value = id;
				// populate the product list or navigate to admin.php?catid=<id>
				// el('productList').innerHTML = '<li> Product 1 of "' + name + '" [Edit] [Delete]</li><li> Product 2 of "' + name + '" [Edit] [Delete]</li>';
			}
		}


		el('cat_insert').onsubmit = function() {
			return myLib.submit(this, updateUI);
		}
		el('cat_edit').onsubmit = function() {
			return myLib.submit(this, function() {
				// toggle the edit/view display
				el('categoryEditPanel').hide();
				el('categoryPanel').show();
				updateUI();
			});
		}
		el('cat_edit_cancel').onclick = function() {
			// toggle the edit/view display
			el('categoryEditPanel').hide();
			el('categoryPanel').show();
		}

	})();

	(function(){

		function updateUI_prod() {


			myLib.get({action:'prod_fetchall'}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				for (var options = [], listItems = [],
						i = 0, prod; prod = json[i]; i++) {
					options.push('<option value="' , parseInt(prod.catid) , '">' , prod.catid.escapeHTML() , '</option>');
					listItems.push('<li id="prod' , parseInt(prod.pid) , '"><span class="name">' , prod.name.escapeHTML() , '</span> <span class="proddelete">[Delete]</span> <span class="prodedit">[Edit]</span></li>');
				}

				el('productList').innerHTML = listItems.join('');
			});
		}
		updateUI_prod();

		el('productList').onclick = function(e) {
			if (e.target.tagName != 'SPAN')
				return false;


			var target = e.target,
				parent = target.parentNode,
				id = target.parentNode.id.replace(/^prod/, ''),
				name = target.parentNode.querySelector('.name').innerHTML;

			// handle the delete click
			if ('proddelete' === target.className) {
				confirm('Sure?') && myLib.post({action: 'prod_delete', pid: id}, function(json){
					alert('"' + name + '" is deleted successfully!');
					updateUI_prod();
				});

			// handle the edit click
		} else if ('prodedit' === target.className) {
				// toggle the edit/view display
				el('productEditPanel').show();
				el('productPanel').hide();

				// fill in the editing form with existing values
				el('prod_edit_name').value = name;
				el('prod_edit_pid').value = id;

			//handle the click on the category name
			} else {
				//el('prod_insert_catid').value = id;
				// populate the product list or navigate to admin.php?catid=<id>
				//el('productList').innerHTML = '<li> Product 1 of "' + name + '" [Edit] [Delete]</li><li> Product 2 of "' + name + '" [Edit] [Delete]</li>';
			}
		}


		el('prod_insert_submit').onsubmit = function() {
			return myLib.submit(this, updateUI_prod);
		}
		el('prod_edit_submit').onsubmit = function() {
			return myLib.submit(this, function() {
				// toggle the edit/view display
				el('productEditPanel').hide();
				el('productPanel').show();
				updateUI_prod();
			});
		}
		el('prod_edit_cancel').onclick = function() {
			// toggle the edit/view display
			el('productEditPanel').hide();
			el('productPanel').show();
		}

	})();

	(function(){

		function updateTrans() {
			myLib.get({action:'trans_fetchall'}, function(json){
				// loop over the server response json
				//   the expected format (as shown in Firebug):
				for (var listItems1 = [],
						i = 0, ord; ord = json[i]; i++) {
							listItems1.push('<li>','orderid: ',ord.oid.escapeHTML(),'<br>',ord.digest.escapeHTML()
							,'<br>',ord.salt.escapeHTML(),'<br>', 'payment status: ', ord.tid.escapeHTML(),
							'</li>');
				}
				cont = listItems1.join('');
				el('transainfo').innerHTML = cont;
			});

		}
		updateTrans();

	})();

	(function(){

 		function updatePrus() {
 			myLib.get({action:'pru_fetchall'}, function(json){
 				// loop over the server response json
 				//   the expected format (as shown in Firebug):
 				for (var listItems2 = [],
 						i = 0, pru; pru = json[i]; i++) {
 							listItems2.push('<li>','pru_oid: ',parseInt(pru.oid)+6,'<br>','[name]: ',pru.name.escapeHTML(),'<br>'
 							,'[qty]: ',pru.qty.escapeHTML(),' [perprice]: ', pru.price.escapeHTML(),
 							'</li>');
 				}
 				cont2 = listItems2.join('');
 				el('purchaseinfo').innerHTML = cont2;
 			});

 		}
 		updatePrus();

 	})();

	</script>
	</body>
</html>
