<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

//Initialisation de variable
$valueType='auteur';
$valueQuoi='';

$nb = 0;
$page=1;
($_GET && $_POST) && fd_exit_session();

//Vérification de l'attribut $_GET['p']
if (isset($_GET['p'])) {
	!est_entier($_GET['p'])&&fd_exit_session();
	$page = (int) $_GET['p'];
	if ($page < 1) {
		$page=1;
	}
}

if ($_GET){
	fdl_control_get ();
	//Vérification qu'il n'y a pas d'espace et de code HTML
	$valueType=strip_tags(trim($_GET['type']));
	$valueQuoi=strip_tags(trim($_GET['quoi']));
}
else if ($_POST){
	$valueQuoi = fdl_control_post ($valueType);
}

//Début structure site
fd_html_debut('BookShop | Recherche', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

fdl_contenu($valueType, $valueQuoi,$nb,$page);

//Fin structure site
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Contenu de la page : formulaire de recherche + résultats éventuels 
 *
 * @param   string    $valueType type de recherche (auteur ou titre)
 * @param   string    $valueQuoi partie du nom de l'auteur ou du titre à rechercher
 * @global  array     $_POST
 * @global  array     $_GET
 */
function fdl_contenu($valueType, $valueQuoi,$nb,$page) {
	
	echo '<h3>Recherche par une partie du nom d\'un auteur ou du titre</h3>'; 
	
	/** 3ème version : version "formulaire de recherche" */
	echo '<form action="recherche.php" method="post">',
			'<p class="centered">Rechercher <input type="text" name="quoi" value="', fd_protect_sortie($valueQuoi), '">', 
			' dans ', 
				'<select name="type">', 
					'<option value="auteur" ', $valueType == 'auteur' ? 'selected' : '', '>auteurs</option>', 
					'<option value="titre" ', $valueType == 'titre' ? 'selected' : '','>titre</option>', 
				'</select>', 
			'<input type="submit" value="Rechercher" name="btnRechercher"></p></form>'; 
	
	if (! $_GET && ! $_POST){
        return; // ===> Fin de la fonction (ni soumission du formulaire, ni query string)
    }
	if ( mb_strlen($valueQuoi, 'UTF-8') < 2){
        echo '<p><strong>Le mot recherché doit avoir une longueur supérieure ou égale à 2</strong></p>';
		return; // ===> Fin de la fonction
	}
	
	// affichage des résultats
	
	// ouverture de la connexion, requête
	$bd = fd_bd_connect();
	
	$q = fd_bd_protect($bd, $valueQuoi); 
	if ($valueType == 'auteur') {
        $critere = " WHERE liID in (SELECT al_IDLivre FROM aut_livre INNER JOIN auteurs ON al_IDAuteur = auID WHERE auNom LIKE '%$q%')";
	} 
	else {
		$critere = " WHERE liTitre LIKE '%$q%'";	
	}
	if(isset($_SESSION['cliID'])){
		$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
		$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom,listIDLivre AS appartientLivre
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID
						LEFT OUTER JOIN listes ON (listIDLivre =liID AND listIDClient=$cliID)
			$critere";
	}
	else{
		$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID
			$critere";
	}
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	//, COUNT(liID) AS nbLivre
	$lastID = -1;
	$nbPage=0;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				if($nb>=pagination*($page-1)&&($nb<=(pagination*$page)-1)){
					fd_afficher_livre($livre, 'bcResultat', '../');		
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
    	$nbPage=$nb/pagination+1;
	($page>$nbPage)&&fd_exit_session();
	if ($lastID != -1 && $page===(int)$nbPage) {
		fd_afficher_livre($livre, 'bcResultat', '../');
	}
	if($lastID==-1){
		echo '<p>Aucun livre trouvé</p>';
	}
	else{
		//-- Affichage pagination ---------------------------	
		echo '<p class="pagination">Pages : ';
		$type=urlencode($valueType);
		$quoi=urlencode($valueQuoi);
		for ($i = 1; $i <= $nbPage; $i ++) {
			if ($i == $page) {  // page en cours, pas de lien
				echo "$i ";
			} else {
				echo '<a href="', $_SERVER['PHP_SELF'],
					'?p=', $i,'&type=',$type,'&quoi=',$quoi,'">', 
					$i, '</a> ';
			}
		}

		echo '</p>';

	}
}

/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @global  array     $_GET
 *
 * @return            partie du nom de l'auteur à rechercher            
 */
function fdl_control_get (){
	$count_get=count($_GET);
	(count($_GET) != 2)&&(count($_GET) != 3)&& fd_exit_session();
	(! isset($_GET['type']) || ($_GET['type'] != 'auteur'&&$_GET['type'] != 'titre')) && fd_exit_session();
	(! isset($_GET['quoi'])) && fd_exit_session();
	
	if($count_get==3){
		(! isset($_GET['p']))&& fd_exit_session();
	}
    $valueQ = trim($_GET['quoi']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
	$valueT = trim($_GET['type']);
    $notags = strip_tags($valueT);
	(mb_strlen($notags, 'UTF-8') != mb_strlen($valueT, 'UTF-8')) && fd_exit_session();
}

/**
 *	Contrôle de la validité des informations lors de la soumission du formulaire  
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @param   string    $valueT   type de recherche (auteur ou titre)
 * @global  array     $_POST
 *
 * @return            partie du nom de l'auteur ou du titre à rechercher            
 */
function fdl_control_post (&$valueT){
	(count($_POST) != 3) && fd_exit_session();
	(! isset($_POST['btnRechercher']) || $_POST['btnRechercher'] != 'Rechercher') && fd_exit_session();
	(! isset($_POST['type'])) && fd_exit_session();
	($_POST['type'] != 'auteur' && $_POST['type'] != 'titre') && fd_exit_session();
	(! isset($_POST['quoi'])) && fd_exit_session();
	
	$valueT = $_POST['type'] == 'auteur' ? 'auteur' : 'titre';
	
    $valueQ = trim($_POST['quoi']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
    return $valueQ;
}
?>
