<? 

// Exemple de classe pour gérer les bases de données 
// Permettant de changer de base de données (MySQL->Oracle par ex) facilement 
// et de gérer les différentes erreurs, en envoyant mail à admin 

// Les paramètres sont définis dans un fichier de configuration extérieur à l'aide de la fonction define 
// Exemple : 
// define('DB_SERVEUR', 'localhost'); 
// define('DB_USER', 'user'); 
// define('DB_PASS', 'pass'); 
// define('DB_BDD', 'bdd'); 
// define('DB_ADMINMAIL', 'adresse@email.com'); 
// define('DB_NOMSCRIPT', 'Nom du script'); 

Class DataBase 
{ 

   /* Parametrage BDD */ 
    var $DB_CONN =  "";  //ne pas toucher 
    var $DB_BDD = DB_BDD;                  
    var $DB_USER = DB_USER;                    
    var $DB_PASS = DB_PASS; 
    var $DB_SERVEUR = DB_SERVEUR; 
    var $DB_ADMINMAIL = DB_ADMINMAIL; 
    var $DB_NOMSCRIPT = DB_NOMSCRIPT; 
     
     /* Gestion des erreurs */ 
    function DbErreur($text,$sql= '') 
    { 
        $no = @mysql_errno();  // numéro de l'erreur 
        $msg = @mysql_error();  // texte de l'erreur 
        ?> 
         <table bgcolor="#dddeee" cellspacing="0" cellpadding="2" border="0" align="center"> 
      <tr bgcolor="#000066"> 
       <td align="CENTER"><font face="arial" color="white" size="2"><b> Erreur ! </b></font></td> 
      </tr> 
      <tr> 
       <td><font face="arial" color="black" size="2"> 
        Une erreur s'est produite, l'administrateur a &eacute;t&eacute; pr&eacute;venu.<br>
               <?
							 	 	 	 echo "Erreur dans ".$this->DB_NOMSCRIPT, "[$text] ($no : $msg)\n\nsql : $sql"; 
                     //echo"<b>[$text]</b> ( $no : $msg )<BR>$sql\n"; 
                     /* Envoie un email à l'administrateur */ 
                    mail($this->DB_ADMINMAIL, "Erreur dans ".$this->DB_NOMSCRIPT, "[$text] ($no : $msg)\n\nsql : $sql"); 
                ?>     
       </font></td> 
      </tr> 
     </table> 
     <? 
    }   
     
     /* Initialiser */ 
    function DbInit($user= '',$pass= '',$serveur= '',$bdd= '') 
    { 
       
      if($user !=  '') 
      { 
          $this->DB_USER = $user; 
        } 
        if($pass !=  '') 
        { 
          $this->DB_PASS = $pass; 
        } 
        if($serveur !=  '') 
        { 
          $this->DB_SERVEUR = $serveur; 
        } 
        if($bdd !=  '') 
        { 
          $this->DB_BDD = $bdd; 
        } 

        return true; 
     
  } 

     /* Selectionner bdd */ 
  function DbSelectDb() 
  { 

    @mysql_select_db($this->DB_BDD,$this->DB_CONN) or die($this->DbErreur( "Selection base de donnees impossible")); 
    return true; 
     
  } 

     /* Connecter à la base de données */ 
  function DbConnect() 
  { 

        $this->DB_CONN = @mysql_connect($this->DB_SERVEUR,$this->DB_USER,$this->DB_PASS) or die($this->DbErreur( "Connection à la base de données impossible"));     
    $this->DbSelectDb(); 
    return true; 
     
  } 

     /* Selection */ 
    function DbSelect ($sql, &$nb) 
    { 
       
        if(!($results = $this->DbQuery($sql))) 
        { 
          @mysql_free_result($results);
					$nb=0; 
          return false; 
        } 
        $count = 0; 
        $data = array(); 
         // on renvoie les résultats dans un tableau à deux dimensions 
         // du type $data[$ligne][$colonne] (par ex $data[0]["nom"]) 
        while ( $row = @mysql_fetch_array($results)) 
        { 
            $data[$count] = $row; 
            $count++; 
        }
				$nb=$count; 
        @mysql_free_result($results); 
        return $data; 
         
    } 

     /* Insertion */ 
    function DbInsert ($sql,$returnid= '') 
    { 
       
        if(!($results = $this->DbQuery($sql))) 
        { 
          return false; 
        } 
         
         // Si returnid=1 on renvoie l'id de l'enregistrement 
         // A utiliser uniquement si il y a une clef 
        if($returnid == 1) 
        { 
          $results = @mysql_insert_id() or die($this->DbErreur( "mysql_insert_id")); 
        }else 
        { 
          $results = 1; 
        } 
        return $results; 
         
    } 

     /* Autres requêtes */ 
    function DbQuery ($sql) 
    { 
       
        $results = @mysql_query($sql,$this->DB_CONN) or die($this->DbErreur( "mysql_query",$sql)); 
        return $results; 
         
    } 


}     //    Fin de la classe 
?>