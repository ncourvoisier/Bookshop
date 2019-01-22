<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//Fonction qui vérifie s'il n'y a pas une tentative de piratage
nc_control_piratage();
//Recupère l'id du livre à travers l'URL
$id_livre=$_GET['livre'];
//Contenu principale du script
nc_contenu($id_livre);
//Ferme les sessions
ob_end_flush();
//Retourne sur la page précédente
page_precedente();


//Fonction locales au script
/**
* Fonction qui crée et ajoute des livres dans un tableau enregistré dans le tableau super global $_SESSION
*
* @param int	$id_livre	id du livre
*/
function nc_contenu($id_livre) {
	//Vérifie si le tableau super global est créé pour enregistré les livres du paniers
	if (!isset($_SESSION['livrePanier'])) {
		//Création du tableau
		$_SESSION['livrePanier']= array();
		//Initialise le tableau d'information du livre
		$info = array();
		//Recupère le prix et le titre du livre avec son id
		$info = nc_cherche_info($id_livre);
		//Affectation du livre dans le tableau super global
		$_SESSION['livrePanier']["$id_livre"] = array('id' => $id_livre, 'quantite' => 1, 'prix' => $info['prix'], 'titre' => $info['titre'], 'auteurs' => $info['auteurs']);
	} else {
		//Initialise le tableau d'information du livre
		$info = array();
		//Recupère le prix et le titre du livre avec son id
		$info = nc_cherche_info($id_livre);
		//Affectation du livre dans le tableau super global
		$_SESSION['livrePanier']["$id_livre"] = array('id' => $id_livre, 'quantite' => 1, 'prix' => $info['prix'], 'titre' => $info['titre'], 'auteurs' => $info['auteurs']);
	}
}

/**
* Fonction qui cherche le prix et le titre d'un livre avec son id
*
* @param	int		$id		id du livre
*
* @return 	array	$ret	retourne le prix et le titre du livre dans un tableau
*/
function nc_cherche_info($id) {
	$prix = 0;
	$ret = array();
	$bd = fd_bd_connect();
	// $sql = "SELECT liTitre, liPrix FROM livres WHERE liId = $id";
	$sql = "SELECT liTitre, liPrix, auNom,auPrenom FROM livres, auteurs, aut_livre WHERE liId = al_IDLivre AND al_IDAuteur = auID AND liId = $id";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$i = 0;
	while ($t = mysqli_fetch_assoc($res)) {
		if ( $i == 0) {
			$ret['prix'] = $t['liPrix'];
			$ret['titre'] = $t['liTitre'];
			$ret['auteurs'] = array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']));
			$i = 1;
		} else {
			$ret['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}
	}
	return $ret;
}

/**
* Fonction qui détecte les tentatives de piratage
*/
function nc_control_piratage(){
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