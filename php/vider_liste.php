<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//Fonction qui détetce les tentatives de piratage
fdl_control_piratage();
//Récupère l'id du client
$id_client=$_SESSION['cliID'];
//Initialise le critère de suppression
$critere='';
if(isset($_GET['livre'])){
	//Recupère l'id du livre à supprimer
	$id_livre=$_GET['livre'];
	//Affecte le critère pour la suppresion dans la base de donnée
	$critere="AND listIDLivre=$id_livre";
}
//Connection à la bd
$bd = fd_bd_connect();
//Requète pour suppresion
$sql = 	"DELETE FROM listes WHERE listIDClient=$id_client $critere";
$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
//Ferme la base de donnée
mysqli_close($bd);
//Ferme les sessions
ob_end_flush();
//Retour à la oage précédente
page_precedente();


//Fonction locales au script
/**
* Fonction qui détecte les tentatives de piratage
*/
function fdl_control_piratage(){
    $nb = count($_GET);
	//Si $nb == 1 alors $_GET est rempli donc il n'y a pas de tentative de piratage
    if ($nb == 1){ // 0 ou 1 argument
        (!isset($_GET['livre']) || !est_entier($_GET['livre'])) && fd_exit_session();
        return;     // => ok, pas de problème détecté
    }elseif ($nb == 0) {
    	 return;
    }
	//Si piratage déconnexion du client et retour sur la page index.php
    fd_exit_session();
}
?>