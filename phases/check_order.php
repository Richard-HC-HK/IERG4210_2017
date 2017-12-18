<?php
    include_once('lib/csrf.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Product List</title>
</head>
<body>
<h1>My Product List</h1>
<section id="pruchasePanel">
  <fieldset>
    <legend>Product list in Recent Transactions</legend>
    <ul id="purchaseinfo" class="prus">
         <!-- add content by json -->
    </ul>
  </fieldset>
</section>

<p><a href="index.php">Go Back</a></p>

<script type="text/javascript" src="incl/myLib.js"></script>
<script type="text/javascript">

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
