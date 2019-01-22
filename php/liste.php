<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//si utilisateur est non authentifié, redirection vers la page d'authentification
if (!isset($_SESSION['cliID'])){
	fd_redirige('login.php');
}
//Détection du clique sur le bouton GO
$mauvais_email = false;
if(isset($_POST['btnListe'])){
	//Fonction qui vérifie si l'email saisi est dans la base de donnée
	$mauvais_email = pc_erreur();
}

//Initialisation de variable
$nb = 0;
$page = 1;

//Vérification pour pagination
$_GET&&(count($_GET)!=1||!isset($_GET['p']))&&fd_exit_session();

//Vérification pour pagination
if (isset($_GET['p'])) {
	//Vérifie que l'information transmise est un entier
	!est_entier($_GET['p'])&&fd_exit_session();
	$page = (int) $_GET['p'];
	if ($page < 1) {
		$page=1;
	}
}

//Début de la structure du site
fd_html_debut('BookShop | Liste de cadeaux', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(true,'../');

//Fonction principale pour afficher le contenu du site
fdl_contenu($mauvais_email,$nb,$page);

//Fin de la structure du site
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();


//Fonction locales
/**
* Fonction qui vérifie si l'email est dans la base de donnée
*
* @return boolean si la fonction est retourné l'email n'est pas dans la base de donnée
*/
function pc_erreur(){
	//Compte ce qu'il y a dans poste pour détecter les tentatives de piratage
	$nb = count($_POST);
	if ($nb == 2){
		//Si piratage, on déconnecte l'utilisateur
		($_POST['btnListe'] != 'Go') && fd_exit_session();
		(!isset($_POST['email'])) && fd_exit_session();
	}
	//Suppression des espaces dans l'email
	$email = trim($_POST['email']);
	//Ouverture de la base de donnée
	$bd = fd_bd_connect();
	//Protection des chaines de caractère pour la recherche
	$email = fd_bd_protect($bd, $email);
	//Requète sql
	$sql = "SELECT cliID FROM clients WHERE cliEmail = '$email'";
	//Résultat de la requète
	$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
	//Si le résultat existe
	if ($t = mysqli_fetch_assoc($res)) {
		//On affecte l'id client de l'ami
		$_SESSION['ID_ami'] = $t['cliID'];
		//Libére le résultat et ferme la base de donnée
		mysqli_free_result($res);
		mysqli_close($bd);
		//Et redirection sur la page liste_ami pour afficher sa liste de souhait
		fd_redirige('liste_ami.php');
	}
	//Libére le résultat et ferme la base de donnée
	mysqli_free_result($res);
	mysqli_close($bd);
	//Si le résultat de larequète on return true pour dire qu'il y a une erreur et afficher un message d'erreur
	return true;

}

/**
* Fonction qui vérifie si l'email est dans la base de donnée
*
* @param boolean		$mauvais_email  Si l'email existe dans la base de donnée ou non
* @param int			$pagination     Nombre de pagination
* @param int			$nb      		Nombre
* @param int			$page			Nombre de page pour la pagination
*/
function fdl_contenu($mauvais_email,$nb,$page){
	echo '<h2>Consultez la liste de cadeau d\'un ami :</h2><br>';
	//Si l'email n'existe pas dans la base de donnée on affiche un message d'érreur
	if($mauvais_email===true){
		echo '<p class="erreur">Mauvaise adresse email<p>';
	}
	//Création du'n formulaire pour saisir l'adresse email de l'ami et créer le bouton Go
	echo '<form action=liste.php method=post><table>',fd_form_ligne('Adresse email de votre ami:', fd_form_input(FD_Z_TEXT, 'email', '', 30)),'<tr><td colspan="2" style="padding-top: 10px;" class="centered">', fd_form_input(FD_Z_SUBMIT,'btnListe','Go'), '</td></tr></table></form>';
	echo '<h2>Votre liste de cadeaux :</h2>';
	// ouverture de la connexion
	$bd = fd_bd_connect();
	//Récupération de l'id du client
	$id=$_SESSION['cliID'];
	//Requète sql pour récupérer la liste de souhait du client avec l'id ci-dessus
	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON liID = al_IDLivre
						INNER JOIN auteurs ON al_IDAuteur = auID
						INNER JOIN listes ON liID = listIDLivre
			WHERE listIDClient=$id";
	//Résultat de la reuqète
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	//Initialisation à -1 pour récupérer les id des livres qui vont être affiché
	$lastID = -1;
	//Initialisation à 0 pour la pagination
	$nbPage=0;
	//Boucle pour gérer les résulats de la base de donnée
	while ($t = mysqli_fetch_assoc($res)) {
		//Affecte les livres dans un tableau
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				if($nb>=pagination*($page-1)&&($nb<=(pagination*$page)-1)){
					fd_afficher_livre($livre, 'liste', '../');
				}
				$nb++;
			}
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							//'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'ISBN13' => $t['liISBN13'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
		}
		else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}
	}
	// libération des ressources et ferme la base de donnée
	mysqli_free_result($res);
	mysqli_close($bd);
	//Calcul pour déterminer le nombre de page pour réaliser la pagination
	$nbPage=$nb/pagination+1;
	if($page>$nbPage){
		fd_redirige('liste.php');
	}
	if($lastID==-1){
		echo '<p>Aucun livre dans votre liste de cadeaux</p>';
	}
	else{
		//Affiche les livres
		if ($page===(int)$nbPage){
			fd_afficher_livre($livre, 'liste', '../');	
		}
		//Bouton pour vider totalement la liste de souhait
		echo '<a id=vider_liste href="vider_liste.php" title="Vider la liste">VIDER LA LISTE</a>';
		//Affichage des numéros de pages pour circuler entre les pages de la pagination
		echo '<p class="pagination">Pages : ';
		//Boucle pour les pages
		for ($i = 1; $i <= $nbPage; $i ++) {
			if ($i == $page) {  // page en cours, pas de lien
				echo "$i ";
			} else {
				echo '<a href="', $_SERVER['PHP_SELF'],
					'?p=', $i,'">', 
					$i, '</a> ';
			}
		}
		echo '</p>';
	}
}
?>
