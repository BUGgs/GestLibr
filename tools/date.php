<?php

if (isset($_POST["time"]))
{
  $date=date("d/m/Y",$_POST["time"]);
  $dateform="";
  $time=$_POST["time"];
  $timeform=$_POST["time"];  
}
elseif (isset($_POST["date"]))
{
  $time=strtotime($_POST["date"]);
  $timeform="";
  $date=date("d/m/Y",strtotime($_POST["date"]));
  $dateform=$_POST["date"];
}

echo "
<html>
<body>
<h1>Time Conversion Tool v1.0 par BUGgs (03/03/2005)</h1>
<hr/>
Convertion de Timestamp -> Date <br/>
<p>
<form method='POST'>
<input type='text' name='time' value='$timeform'>
<input type='submit' name='submit' value='convertir en date !'>
</form>
</p>
<hr/>
Convertion de Date -> Timestamp <br/>
(au format AAAA/MM/JJ)
<p>
<form method='POST'>
<input type='text' name='date' value='$dateform'>
<input type='submit' name='submit' value='convertir en timestamp !'>
</form>
</p>
<hr/>
<p>Résultats :</p>
<p>Timestamp = $time<br/>Date = $date (au format francais JJ/MM/AAAA)</p>
</body>
</html>
";

?>