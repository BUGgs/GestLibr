<?php  
//           CLASS D'ABSTRACTION BASE DE DONNEES : MYSQLi v1.1
//           -------------------------------------------
//                        Romain DUCHENE
//                      romain@duchene.fr
// Les paramètres sont définis dans un fichier de configuration extérieur à l'aide de la fonction define 
// Exemple : 
// define('DB_SERVEUR', 'localhost'); 
// define('DB_USER', 'user'); 
// define('DB_PASS', 'pass'); 
// define('DB_BDD', 'bdd'); 
// define('DB_ADMINMAIL', 'adresse@email.com'); 
// define('DB_NOMSCRIPT', 'Nom du script'); 

Class DataBase { 

   /* Parametrage BDD */ 
    var $DB_CONN =  "";  //ne pas toucher 
    var $DB_BDD = DB_BDD;                  
    var $DB_USER = DB_USER;                    
    var $DB_PASS = DB_PASS; 
    var $DB_SERVEUR = DB_SERVEUR; 
    var $DB_ADMINMAIL = DB_ADMINMAIL; 
    var $DB_NOMSCRIPT = DB_NOMSCRIPT; 
  
    function DbErreur($text,$sql= ''){ 
        $no = @mysqli_errno($this->DB_CONN);  // numéro de l'erreur 
        $msg = @mysqli_error($this->DB_CONN);  // texte de l'erreur 
        ?> 
        	<table bgcolor="#dddeee" cellspacing="0" cellpadding="2" border="0" align="center"> 
        	<tr bgcolor="#000066"> 
        	<td align="CENTER"><font face="arial" color="white" size="2"><b> ERROR ! </b></font></td> 
        	</tr> 
        	<tr> 
        	<td><font face="arial" color="black" size="2"> 
        	A malfunction was detected, the administrator is being mailed<br>
               <?php 
          echo "Error in ".$this->DB_NOMSCRIPT, "[$text] ($no : $msg)\n\nsql : $sql"; 
          echo"<b>[$text]</b> ( $no : $msg )<BR>$sql\n"; 
          mail($this->DB_ADMINMAIL, "Error in ".$this->DB_NOMSCRIPT, "[$text] ($no : $msg)\n\nsql : $sql"); 
        ?>
       </font></td> 
      </tr> 
     </table> 
     <?php  
    }   
     
    function DbInit($user= '',$pass= '',$serveur= '',$bdd= '')  {        
      if($user !=  '') { 
          $this->DB_USER = $user; 
        } 
        if($pass !=  '') { 
          $this->DB_PASS = $pass; 
        } 
        if($serveur !=  '')  { 
          $this->DB_SERVEUR = $serveur; 
        } 
        if($bdd !=  '') { 
          $this->DB_BDD = $bdd; 
        } 
        return true;      
  } 

   
  function DbConnect() { 
    $this->DB_CONN = @mysqli_connect($this->DB_SERVEUR,$this->DB_USER,$this->DB_PASS,$this->DB_BDD) or die($this->DbErreur( "Connection à la base de données impossible".mysqli_connect_errno()));
    mysqli_set_charset ($this->DB_CONN, "utf8");
    return true;     
  } 

  function DbClose() { 
    mysqli_close($this->DB_CONN) or die($this->DbErreur( "Fermeture de la base de données impossible".mysqli_connect_errno()));     
    return true;     
  } 

   
    function DbSelect ($sql, &$nb) {       
        if(!($results = $this->DbQuery($sql))) { 
          @mysqli_free_result($results);
          $nb=0; 
          return false; 
        } 
        $count = 0; 
        $data = array(); 
         // on renvoie les rÈsultats dans un tableau ‡ deux dimensions 
         // du type $data[$ligne][$colonne] (par ex $data[0]["nom"]) 
        while ( $row = @mysqli_fetch_array($results)){ 
            $data[$count] = $row; 
            $count++; 
        }
        $nb=$count; 
        @mysqli_free_result($results); 
        return $data; 
         
    } 

    
    function DbInsert ($sql,$returnid= '') {       
        if(!($results = $this->DbQuery($sql))) { 
          return false; 
        }                  
        if($returnid == 1) { // A utiliser uniquement si il y a une clef 
          $results = @mysqli_insert_id($this->DB_CONN) or die($this->DbErreur( "mysqli_insert_id")); 
        }else{ 
          $results = 1; 
        } 
        return $results;          
    } 

  
    function DbQuery ($sql) {        
        $results = @mysqli_query($this->DB_CONN,$sql) or die($this->DbErreur( "mysql_query",$sql)); 
        return $results; 
         
    } 

}     //    Fin de la classe 
?>
