<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

$valueArticle = fdl_control_get ();
fd_html_debut('BookShop | Details', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');
fdl_contenu($valueArticle);
fd_bookshop_pied();
fd_html_fin();

ob_end_flush();


//Fonctions locales

function fdl_contenu($valueArticle){

	$bd = fd_bd_connect();
	$valueArticle = fd_bd_protect($bd, $valueArticle);
	if(isset($_SESSION['cliID'])){
		$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
		$sql = 	"SELECT liID,liResume, liTitre, liPrix, liPages, liISBN13,liLangue,liCat,liAnnee, edNom, edWeb, auNom, auPrenom ,listIDLivre AS appartientLivre
			FROM (((livres
				INNER JOIN editeurs ON liIDEditeur = edID)
				INNER JOIN aut_livre ON liID = al_IDLivre)
				INNER JOIN auteurs ON al_IDAuteur = auID)
				LEFT OUTER JOIN listes ON (listIDLivre =$valueArticle AND listIDClient=$cliID)
			WHERE liID=$valueArticle";
	}
	else{
		$sql = 	"SELECT liID,liResume, liTitre, liPrix, liPages, liISBN13,liLangue,liCat,liAnnee, edNom, edWeb, auNom, auPrenom
			FROM (((livres
				INNER JOIN editeurs ON liIDEditeur = edID)
				INNER JOIN aut_livre ON liID = al_IDLivre)
				INNER JOIN auteurs ON al_IDAuteur = auID)
			WHERE liID=$valueArticle";
	}
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$premierpassage=true;
	while ($t = mysqli_fetch_assoc($res)) {
		if($premierpassage===true){
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'ISBN13' => $t['liISBN13'],
							'prix' => $t['liPrix'],
							'langue' => $t['liLangue'],
							'cat' => $t['liCat'],
							'annee' => $t['liAnnee'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
			if(isset($t['appartientLivre'])){
				$livre['appartientlivre']=$t['appartientLivre'];
			}
			else{
				$livre['appartientlivre']=false;
			}
			$premierpassage=false;
		}
		else{
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}
	}
	echo '<h2>Détail du livre :</h2>';
	fd_afficher_livre($livre,'details','../');
	mysqli_free_result($res);
	mysqli_close($bd);
}

function fdl_control_get (){

	(count($_GET) != 1) && fd_exit_session();
	!isset($_GET['article']) && fd_exit_session();

    $valueQ = trim($_GET['article']);
    $notags = strip_tags($valueQ);

    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
	return $valueQ;
}
?>