<?php
$host = "localhost";
$user = "root";
$password = "root";
$dbname = "genclass";

mysql_connect($host, $user, $password) or die (print "Error conectando a base de datos");
mysql_select_DB($dbname);

if(!isset($_REQUEST["tablename"])) $_REQUEST["tablename"] = "";

if($_REQUEST["tablename"] == "") {
	?>
	<h1>Generador de clases</h1>
	<form action="<?php echo basename(__FILE__);?>" method="post">
	Select table: <select name="tablename">
	<?php

	$tablelist = mysql_query("show tables from $dbname");
	while($table = mysql_fetch_row($tablelist)) {
	echo "<option value=\"$table[0]\">$table[0]</option>";
}
?>
  </select>
      <input type="submit" name="s" value="Generar clase"/>
    </form>
<?php
} else {
	//generar clase
	$tablename = $_REQUEST["tablename"];
?>
<h2><?php echo "class_$tablename.php" ?></h2>
<code>
  &lt;?php
  class <?php echo $_REQUEST["tablename"]; ?> { <br/>
<?php
	$params = array();
	$keys = array();

	// funciones set y get
	$paramList = mysql_query("show columns from $tablename");
	while($param = mysql_fetch_row($paramList)) {
		echo "var \$$param[0];</br><br/>";
?>
      function get<?php echo "$param[0]"; ?>() { <br/>
	  return $this-><?php echo "$param[0]";?>; <br/>
      }<br/><br/>
      function set<?php echo "$param[0]"; ?>($value) { <br/>
      	$this-><?php echo "$param[0]";?> = $value;<br/>
	}<br/><br/>
<?php
		array_push($params, $param[0]);
		if($param[3] == "PRI")
			array_push($keys, $param[0]);
	}
	
	// crear constructor
	echo "function __construct($";
	echo implode(",$", $params);
	echo ") {<br>";

	foreach($params as $param) {
		echo "\$this->$param = \$$param;<br>";	
	}

	echo "}<br><br>";

	//funcion insert
	echo "function insert() { <br>";
	$values = "'\$this->" . implode("', '\$this->", $params) . "'";
	echo "
	 return mysql_query(\"insert into $tablename values ($values)\") or (print mysql_error());<br>
	"; 
	echo "}<br><br>";

	//funcion update
	echo "function update(){<br>";

	$assigns = array_diff($params, $keys);
	$assArr = array();
	foreach($assigns as $assign) {
		array_push($assArr, "$assign = '\$this->$assign'");
	}
	$assignStr = implode(",", $assArr); 

	$conditions = array();
	foreach ($keys as $key) {
		array_push($conditions, "$key = '\$this->$key'");
	}
	$conditionsStr = implode(" and ", $conditions);

	echo "return mysql_query(\"update $tablename set $assignStr where ($conditionsStr)\");<br>";
	echo "}<br><br>";

	//funcion delete
	echo "function delete() {<br>";
	echo "return mysql_query(\"delete from $tablename where ($conditionsStr)\");<br>";
	echo "}<br><br>";

	// Función seek
	// Encontrar tabla buscando por igualdad en claves.
	echo "function seek(\$" . implode(",\$", $keys) . ") { <br>";
	foreach ($keys as $key) {
		echo "\$this->$key = $key;<br/>";
	}
	echo "\$resultQuery = mysql_query(\"select * from $tablename where ($conditionsStr)\");<br>";
	echo "\$result = mysql_fetch_row(\$resultQuery);<br>";
	$i = 0;
	foreach($params as $param) {
		echo "\$this->$param = \$result[$i];<br>";
		$i++;
	}
	echo "}<br><br>";
	
	//funcion select
	echo "function select(\$" . implode(", \$", $params) . ") {<br>";
	$likeStrArr = array();
	foreach ($params as $p) {
		array_push($likeStrArr, "$p like '\$$p'");
	}
	$likeStr = implode(" and ", $likeStrArr);
	echo "return mysql_query(\"select * from $tablename where ($likeStr)\");<br>";
	echo "}<br><br>";

?>
  } // fin clase<br>
  ?&gt;
</code>


<h2><? echo "$tablename.php"?></h2>
<code>
&lt;?php<br>
include "class_<?php echo "$tablename.php";?>";<br>
<br>

$host = "<?php echo "$host";?>";<br>
$user = "<?php echo "$user";?>";<br>
$password = "<?php echo "$password";?>";<br>
$dbname = "<?php echo "$dbname";?>";<br>
$tablename = "<?php echo "$tablename";?>";<br>
<br>

mysql_connect($host, $user, $password) or die (print "Error conectando a base de datos");<br>
mysql_select_DB($dbname);<br>
<br>

echo "&lt;html&gt;";<br>
echo "&lt;head&gt;";<br>
echo "&lt;link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\"&gt;";<br>
echo "&lt;/head&gt;";<br>
echo "&lt;body&gt;";<br>
<br>
if(!isset($_REQUEST["a"])) $_REQUEST["a"] = "";<br>

if($_REQUEST["a"] == "insert_after") {<br>
	$ob = new <?php echo "$tablename";?>(
	<?php
		$obStr = array();
		foreach($params as $p) {
			array_push($obStr, "\$_REQUEST[\"$p\"]");
		}
		echo implode(",", $obStr);
	?>
	);<br>
	$ob->insert();<br>
}<br>
<br>
if($_REQUEST["a"] == "delete") {<br>
	$ob = new <?php echo "$tablename";?>(
	<?php
		$obStr = array();
		foreach($params as $p) {
			array_push($obStr, "\$_REQUEST[\"$p\"]");
		}
		echo implode(",", $obStr);
	?>);<br>
	$ob->delete();<br>
}<br>
<br>
if($_REQUEST["a"] == "update_after") {<br>
	$ob = new <?php echo "$tablename";?>(
	<?php
		echo implode(",", $obStr);
	?>);<br>
	$ob->update();<br>
}<br>
<br>

	echo "&lt;table&gt;"; <br>
<br>
	$query = mysql_query("select * from $tablename");<br>
	while($row = mysql_fetch_row($query)) { <br>
		echo "&lt;tr&gt;";<br>
		$row_data = array();<br>
<br>
		$i = 0;<br>
		$params = array( <?php echo "\"" . implode("\",\"", $params) . "\""; ?> );<br>
		$keys = array( <?php echo "\"" . implode("\",\"", $keys) . "\""; ?> );<br>
		foreach($params as $p) {<br>
			$row_data[$p] = $row[$i];<br>
			$i++;<br>
			<br>
			echo "&lt;td&gt;"; <br>
			echo "$row_data[$p]";<br>
			echo "&lt;/td&gt;"; <br>
		}<br>
<br>
		echo "&lt;td&gt;";<br>
		<?php
		$deleteArray = array();
		$deleteHref = "$tablename.php?a=delete&";
		foreach($keys as $key) {
			array_push($deleteArray, "$key=\$row_data[$key]");
		}
		$deleteHref .= implode("&", $deleteArray);

		$updateArray = array();
		foreach($params as $p) {
			array_push($updateArray, "$p=\$row_data[$p]");
		}
		$updateHref = "$tablename.php?a=update&" . implode("&", $updateArray);
		?>
		echo "&lt;a href=\"<?php echo $deleteHref; ?>\"&gt;X&lt;/a&gt;";<br>
		echo "&lt;a href=\"<?php echo $updateHref; ?>\"&gt;U&lt;/a&gt;";<br>
		echo "&lt;/gt&gt;";<br>
		
		echo "&lt;/tr&gt;";<br>
	}<br>
<br>
	echo "&lt;/table&gt;";<br>
	echo "&lt;a href=\"<?php echo "$tablename";?>.php?a=insert\"&gt;Insertar&lt;/a&gt;"; <br>
<br>
<br>
<br>
if($_REQUEST["a"] == "insert") {<br>
<br>
	echo "&lt;form action=\"<?php echo "$tablename.php";?>\"&gt;";<br>
<?php
	foreach($params as $param) {
	echo "echo \"&lt;br&gt;$param : &lt;input type=\\\"text\\\" name=\\\"$param\\\"/&gt;\";<br>"; 
	}
	echo "echo \"&lt;input type=\\\"hidden\\\" value=\\\"insert_after\\\" name=\\\"a\\\"/&gt\";<br>";
	echo "echo \"&lt;input type=\\\"submit\\\" value=\\\"Enviar\\\"/&gt\";<br>";
?>
	echo "&lt;/form&gt;";<br>
<br>
}<br>
<br>
if($_REQUEST["a"] == "update") {<br>
	echo "&lt;form action=\"<?php echo "$tablename.php";?>\"&gt;";<br>
<?php
	foreach($params as $param) {
		echo "echo \"&lt;br&gt;$param : &lt;input type=\\\"text\\\" class=\\\"input\\\" name=\\\"$param\\\" value=\\\"\$_REQUEST[$param]\\\"/&gt;\";<br>"; 
	}
	echo "echo \"&lt;input type=\\\"hidden\\\" value=\\\"update_after\\\" name=\\\"a\\\"/&gt\";<br>";
	echo "echo \"&lt;input type=\\\"submit\\\" class=\\\"submit_button\\\" value=\\\"Enviar\\\"/&gt\";<br>";
?>
	echo "&lt;/form&gt;";<br>
}<br>
<br>

echo "&lt;/body&gt;&lt;/html&gt;";<br>
?&gt;
</code>

<h2>style.css</h2>

<pre>
.input {
	//Define here
}

.submit_button {
	//Define here
}
</pre>

<?php
}
?>
