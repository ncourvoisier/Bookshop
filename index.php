<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session

require_once './php/bibli_generale.php';
require_once './php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

fd_html_debut('BookShop | Bienvenue', './styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'./');

fdl_contenu();

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/** 
 *	Affichage du contenu de la page (i.e. contenu de l'élément section)
 */
function fdl_contenu() {
	
	echo 
		'<h1>Bienvenue sur BookShop !</h1>',
		'<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
		'<p>Nouveau venu sur BookShop ? Consultez notre <a href="./html/presentation.html">page de présentation</a> !',
		'<h2>Dernières nouveautés </h2>',
		'<p>Voici les 4 derniers articles ajoutés dans notre boutique en ligne :</p>';
		
		
	$bd = fd_bd_connect();
	//requete cherchant les id des 4 derniers livres ajoutés sur le site
	if(isset($_SESSION['cliID'])){
		$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
		$sql = "SELECT livres.liID,liTitre,auPrenom,auNom,listIDLivre AS appartientLivre 
				FROM livres 
				INNER JOIN aut_livre ON al_IDLivre = liID
				INNER JOIN auteurs ON al_IDAuteur = auID
				INNER JOIN (SELECT liID 
							FROM livres
							GROUP BY liID
							ORDER BY liID DESC LIMIT 0,4) as li2 ON li2.liID=livres.liID
				LEFT OUTER JOIN listes ON (listIDLivre =livres.liID AND listIDClient=$cliID)";
	}
	else{
		$sql = "SELECT livres.liID,liTitre,auPrenom,auNom 
				FROM livres
				INNER JOIN aut_livre ON al_IDLivre = liID
				INNER JOIN auteurs ON al_IDAuteur = auID
				INNER JOIN (SELECT liID 
							FROM livres
							GROUP BY liID
							ORDER BY liID DESC LIMIT 0,4) as li2 ON li2.liID=livres.liID";
	}
	$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
	$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				fd_afficher_livre($livre, 'bcArticle', './');	
			}
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
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
	// libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
	fd_afficher_livre($livre, 'bcArticle', './');	
	
	echo 
		'<h2>Top des ventes</h2>', 
		'<p>Voici les 4 articles les plus vendus :</p>';
	$bd = fd_bd_connect();
	//requete cherchant les id des 4 livres les plus vendu sur le site
	if(isset($_SESSION['cliID'])){
		$sql = "SELECT  DISTINCT listIDLivre AS appartientLivre,livres.liID, liTitre,auPrenom,auNom
				FROM livres 
				INNER JOIN aut_livre ON al_IDLivre = liID
				INNER JOIN auteurs ON al_IDAuteur = auID
				INNER JOIN compo_commande ON ccIDLivre = liID
				INNER JOIN (SELECT liID,SUM(ccQuantite) AS nb_livre
							FROM livres
							INNER JOIN compo_commande ON ccIDLivre = liID
							GROUP BY liID
							ORDER BY nb_livre
							DESC LIMIT 0, 4) as li2 ON li2.liID=livres.liID
				LEFT OUTER JOIN listes ON (listIDLivre =livres.liID AND listIDClient=$cliID)";
	}else{
		$sql = "SELECT livres.liID, liTitre,auPrenom,auNom
				FROM livres 
				INNER JOIN aut_livre ON al_IDLivre = liID
				INNER JOIN auteurs ON al_IDAuteur = auID
				INNER JOIN (SELECT liID,SUM(ccQuantite) AS nb_livre
							FROM livres
							INNER JOIN compo_commande ON ccIDLivre = liID
							GROUP BY liID
							ORDER BY nb_livre
							DESC LIMIT 0, 4) as li2 ON li2.liID=livres.liID";
	}
	$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
	$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				fd_afficher_livre($livre, 'bcArticle', './');	
			}
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
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
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
	fd_afficher_livre($livre, 'bcArticle', './'); 

}
?>
