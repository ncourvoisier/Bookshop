<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//Fonction qui vérifie s'il n'y a pas une tentative de piratage
fdl_control_piratage();

//si utilisateur est non authentifié, redirection vers la page d'authentification
if (!isset($_SESSION['cliID'])){
	fd_redirige('login.php');
}

//Connexion à la base de donnée
$bd = fd_bd_connect();
//Recupère l'id du client
$id_client=$_SESSION['cliID'];
//Recupère l'id du livre
$id_livre=$_GET['livre'];
//Requète pour ajouter le livre à la liste de souhait dans la base de donnée
$sql = "INSERT INTO listes (listIDClient,listIDLivre) VALUES ($id_client,$id_livre)";
//Résultat de la requète
$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
//Ferme la base de donnée
mysqli_close($bd);
//Ferme les sessions
ob_end_flush();
//Retour à la page précédente
page_precedente();

//Fonction local du script
/**
* Fonction qui détecte les tentatives de piratage
*/
function fdl_control_piratage(){
    $nb = count($_GET);
	//Si $nb == 1 alors $_GET est rempli donc il n'y a pas de tentative de piratage
    if ($nb == 1){ // 0 ou 1 argument
        (!isset($_GET['livre']) || !est_entier($_GET['livre'])) && fd_exit_session();
        return;     // => ok, pas de problème détecté
    }
	//Si piratage déconnexion du client et retour sur la page index.php
    fd_exit_session();
}
?>