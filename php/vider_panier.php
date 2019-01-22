<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//Fonction qui vérifie s'il n'y a pas une tentative de piratage
fdl_control_piratage();
//Vérifie si un livre est transporté via l'URL
if(isset($_GET['livre'])){
	//Recupère l'id du livre
	$id_livre=$_GET['livre'];
	//Compte le nombre de livre dans le panier
	$nb = count($_SESSION['livrePanier']);
	//S'il est plus égale à 1 et qu'on veut supprimer ce dernier livre 
	//alors on vide totalement le panier, donc on supprime le tableau super global
	if ($nb == 1) {
		//Suppression du tableau super global
		unset($_SESSION['livrePanier']);
	}
	//Si le tableau super global dans panier existe
	if (isset($_SESSION['livrePanier'])) {
		//On supprime le livre d'id sélectionner avec $_GET
		unset($_SESSION['livrePanier'][$id_livre]);
	}
//Sinon on souhaite vider le panier complet
} else { 
	//Si le tableau super global livrePanier existe
	if (isset($_SESSION['livrePanier'])) {
		//Suppression du tableau super global
		unset($_SESSION['livrePanier']);
	}
}
//Ferme les sessions
ob_end_flush();
//Retourne sur la page précédente
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
    } elseif ($nb == 0) {
		return;
	}
	//Si piratage déconnexion du client et retour sur la page index.php
    fd_exit_session();
}
?>