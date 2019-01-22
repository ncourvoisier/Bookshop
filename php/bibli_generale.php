<?php
require_once 'bibli_bookshop.php';

/*********************************************************
 *        Bibliothèque de fonctions génériques           *
 *********************************************************/

 // Paramètres pour accéder à la base de données
define('BS_SERVER', 'localhost');
define('BS_DB', 'bookshop_poncot');
define('BS_USER', 'u_poncot');
define('BS_PASS', 'p_poncot');

// define('BS_DB', 'bookshop_db');
// define('BS_USER', 'root');
// define('BS_PASS', '');

//---------------------------------------------------------------
// Définition des types de zones de saisies
//---------------------------------------------------------------
define('FD_Z_TEXT', 'text');
define('FD_Z_PASSWORD', 'password');
define('FD_Z_SUBMIT', 'submit');
define('FD_Z_HIDDEN', 'hidden');


/**
 *	Fonction affichant le début du code HTML d'une page.
 *
 *  @param 	String	$titre	Titre de la page
 *	@param 	String	$css	Chemin relatif vers la feuille de style CSS.
 */
function fd_html_debut($titre, $css) {
	$css = ($css == '') ? '' : "<link rel=\"stylesheet\" type=\"text/css\" href=\"$css\">";
	echo 
		'<!doctype html>',
		'<html lang="fr">',
			'<head>',
				'<title>', $titre, '</title>', 
				'<meta charset="UTF-8">',
			   	$css,
			'</head>',
			'<body>';
}


/**
 *	Fonction affichant la fin du code HTML d'une page.
 */
function fd_html_fin() {
	echo '</body></html>';
}



//____________________________________________________________________________
/** 
 *	Ouverture de la connexion à la base de données
 *
 *	@return objet 	connecteur à la base de données
 */
function fd_bd_connect() {
    $conn = mysqli_connect(BS_SERVER, BS_USER, BS_PASS, BS_DB);
    if ($conn !== FALSE) {
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8') 
        or fd_bd_erreurExit('<h4>Erreur lors du chargement du jeu de caractères utf8</h4>');
        return $conn;     // ===> Sortie connexion OK
    }
    // Erreur de connexion
    // Collecte des informations facilitant le debugage
    $msg = '<h4>Erreur de connexion base MySQL</h4>'
            .'<div style="margin: 20px auto; width: 350px;">'
            .'BD_SERVER : '. BS_SERVER
            .'<br>BS_USER : '. BS_USER
            .'<br>BS_PASS : '. BS_PASS
            .'<br>BS_DB : '. BS_DB
            .'<p>Erreur MySQL numéro : '.mysqli_connect_errno()
            .'<br>'.htmlentities(mysqli_connect_error(), ENT_QUOTES, 'ISO-8859-1')  
            //appel de htmlentities() pour que les éventuels accents s'affiche correctement
            .'</div>';
    fd_bd_erreurExit($msg);
}

//____________________________________________________________________________
/**
 * Arrêt du script si erreur base de données 
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 * 		- lors de la phase de connexion au serveur MySQL
 *		- ou indirectement lorsque l'envoi d'une requête échoue
 *
 * @param string	$msg	Message d'erreur à afficher
 */
function fd_bd_erreurExit($msg) {
    ob_end_clean();	// Supression de tout ce qui a pu être déja généré
    ob_start('ob_gzhandler'); // nécessaire sur saturnin quand compression avec ob_gzhandler
    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>',
            'Erreur base de données</title>',
            '<style>table{border-collapse: collapse;}td{border: 1px solid black;padding: 4px 10px;}</style>',
            '</head><body>',
            $msg,
            '</body></html>';
    exit(1);
}


//____________________________________________________________________________
/**
 * Gestion d'une erreur de requête à la base de données.
 *
 * A appeler impérativement quand un appel de mysqli_query() échoue 
 * Appelle la fonction fd_bd_erreurExit() qui affiche un message d'erreur puis termine le script
 *
 * @param objet		$bd		Connecteur sur la bd ouverte
 * @param string	$sql	requête SQL provoquant l'erreur
 */
function fd_bd_erreur($bd, $sql) {
    $errNum = mysqli_errno($bd);
    $errTxt = mysqli_error($bd);

    // Collecte des informations facilitant le debugage
    $msg =  '<h4>Erreur de requête</h4>'
            ."<pre><b>Erreur mysql :</b> $errNum"
            ."<br> $errTxt"
            ."<br><br><b>Requête :</b><br> $sql"
            .'<br><br><b>Pile des appels de fonction</b></pre>';

    // Récupération de la pile des appels de fonction
    $msg .= '<table>'
            .'<tr><td>Fonction</td><td>Appelée ligne</td>'
            .'<td>Fichier</td></tr>';

    $appels = debug_backtrace();
    for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
        $msg .= '<tr style="text-align: center;"><td>'
                .$appels[$i]['function'].'</td><td>'
                .$appels[$i]['line'].'</td><td>'
                .$appels[$i]['file'].'</td></tr>';
    }

    $msg .= '</table>';

    fd_bd_erreurExit($msg);	// => ARRET DU SCRIPT
}


/** 
 *	Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *		- de saisies de l'utilisateur (formulaires)
 *		- de la bdD
 *	Permet de se protéger contre les attaques XSS (Cross site scripting)
 * 	Convertit tous les caractères éligibles en entités HTML, notamment :
 *		- les caractères ayant une signification spéciales en HTML (<, >, ...)
 *		- les caractères accentués
 *
 *	@param	string 	$text	la chaine à protéger	
 * 	@return string 	la chaîne protégée
 */
function fd_protect_sortie($str) {
	$str = trim($str);
	return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

/*
 * Protection des chaînes avant insertion dans une requête SQL
 *
 * Avant insertion dans une requête SQL, toutes les chaines contenant certains caractères spéciaux (", ', ...) 
 * doivent être protégées. En particulier, toutes les chaînes provenant de saisies de l'utilisateur doivent l'être. 
 * Echappe les caractères spéciaux d'une chaîne (en particulier les guillemets) 
 * Permet de se protéger contre les attaques de type injections SQL
 *
 * @param 	objet 		$bd 	La connexion à la base de données
 * @param 	string 		$str 	La chaîne à protéger
 * @return 	string 				La chaîne protégée
 */
function fd_bd_protect($bd, $str) {
	$str = trim($str);
	return mysqli_real_escape_string($bd, $str);
}


/**
 * Redirige l'utilisateur sur une page
 *
 * @param string	$page		Page où l'utilisateur est redirigé
 */
function fd_redirige($page) {
	header("Location: $page");
	exit();
}


/**
 * Arrête une session et effectue une redirection vers la page index.php
 *
 * Elle utilise :
 *   -   la fonction session_destroy() qui détruit la session existante
 *   -   la fonction session_unset() qui efface toutes les variables de session
 * Puis, le cookie de session est supprimé
 * 
 */
function fd_exit_session() {
	session_destroy();
	session_unset();
	$cookieParams = session_get_cookie_params();
	setcookie(session_name(), 
			'', 
			time() - 86400,
         	$cookieParams['path'], 
         	$cookieParams['domain'],
         	$cookieParams['secure'],
         	$cookieParams['httponly']
    	);
	
	header('Location: ../index.php');
	exit();
}

/**
 * Teste si une valeur est une valeur entière
 *
 * @param mixed     $x  valeur à tester
 * @return boolean  TRUE si entier, FALSE sinon
*/
function est_entier($x) {
    return is_numeric($x) && ($x == (int) $x);
}

//_______________________________________________________________
//
//		FONCTIONS UTILISEES DANS LES FORMULAIRES
//_______________________________________________________________

/**
* Génére le code d'une ligne de formulaire :
*
* @param string		$gauche		Contenu de la colonne de gauche
* @param string 	$droite		Contenu de la colonne de droite
*
* @return string 	Code HTML représentant une ligne de tableau
*/
function fd_form_ligne($gauche, $droite) {
    $gauche =  fd_protect_sortie($gauche);
    return "<tr><td>{$gauche}</td><td>{$droite}</td></tr>";
}

//_______________________________________________________________
/**
* Génére le code d'une zone input de formulaire (type input) :
*
* @param String		$type	Type de l'input ('text', 'hidden', ...).
* @param string		$name	Nom de la zone (attribut name).
* @param String		$value	Valeur par défaut (attribut value).
* @param integer	$size	Taille du champ (attribut size).
*
* @return string Code HTML de la zone de formulaire
*/
function fd_form_input($type, $name, $value, $size=0) {
   $value =  fd_protect_sortie($value);
   $size = ($size == 0) ? '' : "size='{$size}'";
   return "<input type='{$type}' name='{$name}' {$size} value='{$value}'>";
}

/**
 * Renvoie le nom d'un mois.
 *
 * @param integer	$numero		Numéro du mois (entre 1 et 12)
 *
 * @return string 	Nom du mois correspondant
 */
function fd_get_mois($numero) {
	$numero = (int) $numero;
	($numero < 1 || $numero > 12) && $numero = 0;

	$mois = array('Erreur', 'Janvier', 'F&eacute;vrier', 'Mars',
				'Avril', 'Mai', 'Juin', 'Juillet', 'Ao&ucirc;t',
				'Septembre', 'Octobre', 'Novembre', 'D&eacute;cembre');

	return $mois[$numero];
}

function nc_form_opt($name, $nbQuantite=0) {
	$nbQuantite=(int)$nbQuantite;
	$res = "<select name='{$name}'>";
	for ($i=1; $i <= 100 ; $i++){
        $selected = ($i == $nbQuantite) ? 'selected' : '';
		$res .= "<option value='$i' $selected>$i</option>";
	}
	$res .= '</select>';
	return $res;	
}

/**
* Génére le code pour un ensemble de trois zones de sélection représentant une date : jours, mois et années
*
* @param string		$nom	    Préfixe pour les noms des zones
* @param integer    $nb_annees  Nombre d'années à afficher
* @param integer	$jour 	    Le jour sélectionné par défaut
* @param integer	$mois 	    Le mois sélectionné par défaut
* @param integer	$annee	    L'année sélectionnée par défaut
*
* @return string 	Le code HTML des 3 zones de liste
*/
function fd_form_date($name, $nb_annees, $jsel=0, $msel=0, $asel=0){
	$jsel=(int)$jsel;
	$msel=(int)$msel;
	$asel=(int)$asel;
	$d = date('Y-m-d');
	list($aa, $mm, $jj) = explode('-', $d);
	($jsel==0) && $jsel = $jj;
	($msel==0) && $msel = $mm;
	($asel==0) && $asel = $aa;
	
	$res = "<select name='{$name}_j'>";
	for ($i=1; $i <= 31 ; $i++){
        $selected = ($i == $jsel) ? 'selected' : '';
		$res .= "<option value='$i' $selected>$i</option>";
	}
	$res .= "</select> <select name='{$name}_m'>"; 
	for ($i=1; $i <= 12 ; $i++){
		$selected = ($i == $msel)? 'selected' : '';
		$res .= "<option value='$i' $selected>".fd_get_mois($i).'</option>';
	}
	$res .= "</select> <select name='{$name}_a'>";
	for ($i=$aa; $i > $aa - $nb_annees ; $i--){
		$selected = ($i == $asel) ? 'selected' : '';
		$res .= "<option value='$i' $selected>$i</option>";
	}
	$res .= '</select>';
	return $res;		
}

/**
* Extrait et renvoie le nom du fichier cible contenu dans une URL
*
* Exemple : si la fonction reçoit l'URL
*    http://localhost/bookshop/php/page1.php?nom=valeur&name=value
* elle renvoie 'page1.php'
*  
* @param string		$url        URL à traiter
*
* @return string 	Le nom du fichier cible
*/
function url_get_nom_fichier($url){
    $nom = basename($url);
    $pos = mb_strpos($nom, '?', 0, 'UTF-8');
    if ($pos !== false){
        $nom = mb_substr($nom, 0, $pos, 'UTF-8');
    }
    return $nom;
}

/**
* Retourne sur la page précédente
*/
function page_precedente(){
    if(isset($_SERVER['HTTP_REFERER'])){
        $source=$_SERVER['HTTP_REFERER'];
        $nom_source = url_get_nom_fichier($source);
        // si la page appelante n'appartient pas à notre site
        if (! in_array($nom_source, get_pages_bookshop())){
            $source = '../index.php';
        }
    }
    else{
        $source='../index.php';
    }
    fd_redirige($source);
}
?>
