<?php
$host = "localhost";
$user = "root";
$password = "root";
$dbname = "genclass";

mysql_connect($host, $user, $password) or die (print "Error conectando a base de datos");
mysql_select_DB($dbname);

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
	  return $this->$<?php echo "$param[0]";?>; <br/>
      }<br/><br/>
      function set<?php echo "$param[0]"; ?>($value) { <br/>
      	$this->$<?php echo "$param[0]";?> = $value;<br/>
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
		echo "\$this->\$$param = \$$param;<br>";	
	}

	echo "}<br><br>";

	//funcion insert
	echo "function insert() { <br>";
	$values = "\$this->\$" . implode(", \$this->\$", $params);
	echo "
	 return mysql_query(\"insert into $tablename values ($values)\");<br>
	"; 
	echo "}<br><br>";

	//funcion update
	echo "function update(){<br>";

	$assigns = array_diff($params, $keys);
	$assArr = array();
	foreach($assigns as $assign) {
		array_push($assArr, "$assign = '\$this->\$$assign'");
	}
	$assignStr = implode(",", $assArr); 

	$conditions = array();
	foreach ($keys as $key) {
		array_push($conditions, "$key = '\$this->\$$key'");
	}
	$conditionsStr = implode(" and ", $conditions);

	echo "return mysql_query(\"update $tablename set $assignStr where ($conditionsStr)\");<br>";
	echo "}<br><br>";

	//funcion delete
	echo "function delete() {<br>";
	echo "return mysql_query(\"delete from $tablename where ($conditionsStr)\");<br>";
	echo "}<br><br>";

	//funcion select
	echo "function seek(\$" . implode(",\$", $keys) . ") { <br>";
	foreach ($keys as $key) {
		echo "\$this->\$$key = $key;<br/>";
	}
	echo "\$result = mysql_query(\"select * from $tablename where ($conditionsStr)\");<br>";
	$i = 0;
	foreach($params as $param) {
		echo "\$this->\$$param = \$result[$i];<br>";
		$i++;
	}
	echo "}<br><br>";
?>
  } // fin clase<br>
  ?&gt;
</code>
<?php
}
?>

