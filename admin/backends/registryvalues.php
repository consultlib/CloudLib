<?php
require("../backend/config.php");

?>

<FORM action="index2.php?backend=dodeleteregistry" method="post">Registry Viewer. AppID 0 is the system.
</FORM>
<?php

// Connecting, selecting database
$link = mysql_connect($db_host, $db_username, $db_password)
   or die('Could not connect: ' . mysql_error());
mysql_select_db($db_name) or die('Could not select database');

// Performing SQL query
$query = "SELECT * FROM ${db_prefix}registry";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());

// Printing results in HTML
echo "<table width='100%' border='1'>\n";
echo "<thead>\n";
echo "<tr>\n";
echo "<td>\n";
echo "ID\n";
echo "</td>\n";
echo "<td>\n";
echo "UserID\n";
echo "</td>\n";
echo "<td>\n";
echo "AppID\n";
echo "</td>\n";
echo "<td>\n";
echo "Varname\n";
echo "</td>\n";
echo "<td>\n";
echo "Value\n";
echo "</td>\n";
echo "</tr>\n";
echo "</thead>\n";
$count = 1;
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
   echo "\t<tr>\n";

   echo "\t<tr>\n";
   foreach ($line as $col_value) {
       echo "\t\t<td>$col_value</td>\n";
   }
   echo "\t</tr>\n";
}
echo "</table>\n";


// Free resultset
mysql_free_result($result);

// Closing connection
mysql_close($link);
?> 