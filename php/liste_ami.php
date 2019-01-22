<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//On peut remplir le panier sans être connecté mais une fois que l'on clique sur le bouton commander on vérifie si l'utilisateur est connecté
if (!isset($_SESSION['cliID'])){
	//S'il n'est pas connecté il est redirigé vers le login
	fd_redirige('login.php');
}
//Si l'id de l'ami n'est pas saisi on est redirigé vers la page précédente
if (!isset($_SESSION['ID_ami'])) {
	page_precedente();
}

//Début structure site
fd_html_debut('BookShop | Liste de cadeaux', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(true,'../');

//Connexion à la base de donnée
echo '<h2>Liste de votre ami :</h2>';
$bd = fd_bd_connect();
//Récupère des variables pour la requète sql
$id=fd_bd_protect($bd,$_SESSION['ID_ami']);
$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
//Requète sql
$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom ,l2.listIDLivre AS appartientLivre
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON liID = al_IDLivre
						INNER JOIN auteurs ON al_IDAuteur = auID
						INNER JOIN listes AS l1 ON l1.listIDlivre=liID
						LEFT OUTER JOIN listes AS l2 ON (l2.listIDLivre =liID AND l2.listIDClient=$cliID) WHERE l1.listIDClient=$id";
//Résultat de la requète
$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
//Affichage des livres de la liste de souhait de l'ami, s'il y en a
$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				fd_afficher_livre($livre, 'bcResultat', '../');	
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
			if(isset($t['appartientLivre'])){
					$livre['appartientlivre']=$t['appartientLivre'];
			}
			else{
					$livre['appartientlivre']=false;
			}
		}
		else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}		
	}
if ($lastID != -1) {
	fd_afficher_livre($livre, 'bcResultat', '../');	
	//Création d'un bouton retour pour retourner sur la page liste
	echo '<a id=retour href="liste.php" title="Retour">Retour</a>';
}
else{
	//Sinon on n'affiche un message comme quoi il n'a pas de livre dans sa liste de souhait
	echo '<p>Aucun livre dans la liste de cadeaux de votre ami(e).</p><br><br><h2><a href="liste.php" title="Retour">Retour</a></h2>';
}
//On libère la variable et on ferme la base de donnée
mysqli_free_result($res);
mysqli_close($bd);

//Fin structure site
fd_bookshop_pied();
fd_html_fin();

//On ferme les sessions
ob_end_flush();
?>