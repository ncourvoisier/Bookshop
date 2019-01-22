<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

// si $_POST non vide
($_POST) && fdl_control_piratage();

// si utilisateur déjà authentifié, on le redirige sur la page appelante, ou à défaut sur l'index
if (isset($_SESSION['cliID'])){
    $page = '../index.php';
    if (isset($_SERVER['HTTP_REFERER'])){
        $page = $_SERVER['HTTP_REFERER'];
        $nom_page = url_get_nom_fichier($page);
        // suppression des éventuelles boucles de redirection
        if (($nom_page == 'login.php') || ($nom_page == 'inscription.php')){
            $page = '../index.php'; 
        } // si la page appelante n'appartient pas à notre site
        else if (! in_array($nom_page, get_pages_bookshop())){
            $page = '../index.php';
        }  
    }
    fd_redirige($page);
}

$err = isset($_POST['btnSInscrire']) ? fdl_inscription() : array(); 

fd_html_debut('BookShop | Inscription', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(false,'../');

fdl_contenu($err);

fd_bookshop_pied();
fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Affichage du contenu de la page (formulaire d'inscription)
 *	@param 	array	$err	tableau d'erreurs à afficher
 */
function fdl_contenu($err) {

	$email = isset($_POST['email']) ? $_POST['email'] : '';
	$nomprenom = isset($_POST['nomprenom']) ? $_POST['nomprenom'] : '';
	$naiss_j = isset($_POST['naiss_j']) ? $_POST['naiss_j'] : 1;
	$naiss_m = isset($_POST['naiss_m']) ? $_POST['naiss_m'] : 1;
	$naiss_a = isset($_POST['naiss_a']) ? $_POST['naiss_a'] : 2000;

	echo 
		'<H1>Inscription à BookShop</H1>';
		
	if (count($err) > 0) {
		echo '<p class="erreur">Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
		foreach ($err as $v) {
			echo '<br> - ', $v;
		}
		echo '</p>';	
	}
	
    if (isset($_POST['source'])){
        $source = $_POST['source'];
    }
    else if (isset($_SERVER['HTTP_REFERER'])){
        $source = $_SERVER['HTTP_REFERER'];
        $nom_source = url_get_nom_fichier($source);
        // si la page appelante n'appartient pas à notre site
        if (! in_array($nom_source, get_pages_bookshop())){
            $source = '../index.php';
        }
    }
    else{
        $source = '../index.php';
    }
	
	echo 	
		'<form method="post" action="inscription.php">',
			fd_form_input(FD_Z_HIDDEN, 'source', $source),
			'<p>Pour vous inscrire, merci de fournir les informations suivantes. </p>',
			'<table>',
				fd_form_ligne('Votre adresse email :', fd_form_input(FD_Z_TEXT, 'email', $email, 30)),
				fd_form_ligne('Choisissez un mot de passe :', fd_form_input(FD_Z_PASSWORD, 'pass1', '', 30)),
				fd_form_ligne('Répétez le mot de passe :', fd_form_input(FD_Z_PASSWORD, 'pass2', '', 30)),
				fd_form_ligne('Nom et prénom :', fd_form_input(FD_Z_TEXT, 'nomprenom', $nomprenom, 30)),
				fd_form_ligne('Date de naissance :', fd_form_date('naiss', NB_ANNEES_DATE_NAISSANCE, $naiss_j, $naiss_m, $naiss_a)),
				'<tr><td colspan="2" style="padding-top: 10px;" class="centered">', fd_form_input(FD_Z_SUBMIT,'btnSInscrire','Je m\'inscris !'), '</td></tr>',
			'</table>',
		'</form>';
}	

/**
 * Objectif : détecter les tentatives de piratage
 *
 * Si une telle tentative est détectée, la session est détruite et l'utilisateur est redirigée
 * vers la page d'accueil du site
 *
 * @global  array     $_POST
 *
 */
function fdl_control_piratage(){
    $nb = count($_POST);
    if ($nb == 2){
        (! isset($_POST['btnInscription']) || $_POST['btnInscription'] != 'S\'inscrire') && fd_exit_session();
        (! isset($_POST['source'])) && fd_exit_session();
        (strip_tags($_POST['source']) != $_POST['source']) && fd_exit_session();
        return;     // => ok, pas de problème détecté
    }
    if ($nb == 9){
        (! isset($_POST['btnSInscrire']) || $_POST['btnSInscrire'] != 'Je m\'inscris !') && fd_exit_session();
        (! isset($_POST['source'])) && fd_exit_session();
        (strip_tags($_POST['source']) != $_POST['source']) && fd_exit_session();
        (! isset($_POST['email'])) && fd_exit_session();
        (! isset($_POST['pass1'])) && fd_exit_session();
        (! isset($_POST['pass2'])) && fd_exit_session();
        (! isset($_POST['nomprenom'])) && fd_exit_session();
        (! isset($_POST['naiss_j'])) && fd_exit_session();
        (! isset($_POST['naiss_m'])) && fd_exit_session();
        (! isset($_POST['naiss_a'])) && fd_exit_session();
        (!est_entier($_POST['naiss_a']) || !est_entier($_POST['naiss_m']) || !est_entier($_POST['naiss_j'])) && fd_exit_session();
        $aa = date('Y');
        ($_POST['naiss_j'] < 1 || $_POST['naiss_j'] > 31 || $_POST['naiss_m'] < 1 || $_POST['naiss_m'] > 12 ||
        $_POST['naiss_a'] > $aa || $_POST['naiss_a'] <= $aa - NB_ANNEES_DATE_NAISSANCE) && fd_exit_session();
        
        return;     // => ok, pas de problème détecté
    }
    fd_exit_session();
}

/**
 *	Traitement de l'inscription 
 *
 *		Etape 1. vérification de la validité des données
 *					-> return des erreurs si on en trouve
 *		Etape 2. enregistrement du nouvel inscrit dans la base
 *		Etape 3. ouverture de la session et redirection vers la page appelante. 
 *
 * @global  array     $_POST
 *
 * @return array 	tableau assosiatif contenant les erreurs
 */
function fdl_inscription() {
    
	$err = array();
	
	$email = trim($_POST['email']);
	$pass1 = trim($_POST['pass1']);
	$pass2 = trim($_POST['pass2']);
	$nomprenom = trim($_POST['nomprenom']);
	$naiss_j = (int)$_POST['naiss_j'];
	$naiss_m = (int)$_POST['naiss_m'];
	$naiss_a = (int)$_POST['naiss_a'];
	
	// vérification email
    $noTags = strip_tags($email);
    if ($noTags != $email){
        $err['email'] = 'L\'email ne peut pas contenir de code HTML.';
    }
    else {
        $i = mb_strpos($email, '@', 0, 'UTF-8');
        $j = mb_strpos($email, '.', 0, 'UTF-8');
        if ($i === FALSE || $j === FALSE){
            $err['email'] = 'L\'adresse email ne respecte pas le bon format.';	
        }
        // le test suivant rend inutile celui qui précède
        else if (! filter_var($email, FILTER_VALIDATE_EMAIL)){
            $err['email'] = 'L\'adresse email ne respecte pas le bon format.';
        }
    }
    
	// vérification des mots de passe
	if ($pass1 != $pass2) {
		$err['pass1'] = 'Les mots de passe doivent être identiques.';	
	}
	else {
		$nb = mb_strlen($pass1, 'UTF-8');
        $noTags = strip_tags($pass1);
        if (mb_strlen($noTags, 'UTF-8') != $nb) {
            $err['pass1'] = 'La zone Mot de passe ne peut pas contenir de code HTML.';
		}
        else if ($nb < 4 || $nb > 20){
            $err['pass1'] = 'Le mot de passe doit être constitué de 4 à 20 caractères.';
        }
			
	}
	
	// vérification des noms et prenoms
	$noTags = strip_tags($nomprenom);
    if ($noTags != $nomprenom){
        $err['nomprenom'] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
    }
    else if (empty($nomprenom)) {
		$err['nomprenom'] = 'Le nom et le prénom doivent être renseignés.';	
    }
    /*elseif (! preg_match("/^[[:alpha:]][[:alpha:]\- ']{1,99}$/", $nomprenom)) { // ne fct pas avec les accents
        $err['nomprenom'] = 'Le nom et le prénom ne sont pas valides.';
    }*/
    elseif (mb_regex_encoding ('UTF-8') && ! mb_ereg_match("^[[:alpha:]][[:alpha:]\- ']{1,99}$", $nomprenom)) {
        $err['nomprenom'] = 'Le nom et le prénom ne sont pas valides.';
    }
	
    
	// vérification de la date de naissance
	if (! checkdate($naiss_m, $naiss_j, $naiss_a)) {
		$err['date'] = 'La date de naissance est incorrecte.';	
	}	
	else {
		$dateDuJour = getDate();
		if (($naiss_a < $dateDuJour['year'] - 100) ||
            ($naiss_a == $dateDuJour['year'] - 100 && $naiss_m < $dateDuJour['mon']) ||
            ($naiss_a == $dateDuJour['year'] - 100 && $naiss_m == $dateDuJour['mon'] && $naiss_j <= $dateDuJour['mday'])) {
			$err['date'] = 'Vous êtes trop vieux pour trainer sur BookShop.';	
		}
		else if (($naiss_a > $dateDuJour['year'] - 18) || 
				 ($naiss_a == $dateDuJour['year'] - 18 && $naiss_m > $dateDuJour['mon']) || 
				 ($naiss_a == $dateDuJour['year'] - 18 && $naiss_m == $dateDuJour['mon'] && $naiss_j > $dateDuJour['mday'])) {   	
			$err['date'] = 'Votre date de naissance indique vous n\'êtes pas majeur.';
		}
	}

	if (count($err) == 0) {
		// vérification de l'unicité de l'adresse email 
		// (uniquement si pas d'autres erreurs, parce que ça coûte un bras)
		$bd = fd_bd_connect();

		// pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
		$email = fd_bd_protect($bd, $email);
		$sql = "SELECT cliID FROM clients WHERE cliEmail = '$email'"; 
	
		$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
		
		if (mysqli_num_rows($res) != 0) {
			$err['email'] = 'L\'adresse email spécifiée existe déjà.';
            // libération des ressources 
            mysqli_free_result($res);
            mysqli_close($bd);
		}
        else{
            // libération des ressources 
            mysqli_free_result($res);
        }
		
	}
	
	// s'il y a des erreurs ==> on retourne le tableau d'erreurs	
	if (count($err) > 0) { 	
		return $err;	
	}
	
	// pas d'erreurs ==> enregistrement de l'utilisateur
	$nomprenom = fd_bd_protect($bd, $nomprenom);
	$pass = fd_bd_protect($bd, md5($pass1));
	$aaaammjj = $naiss_a*10000  + $naiss_m*100 + $naiss_j;

    // les champs adresse, code postal, ville et pays doivent être spécifiés
    // (Contrainte NON NULL dans la table 'client' sans indiquer de valeur par défaut)
    $invalid = FD_INVALID_STRING;
    $code_postal = FD_INVALID_CODE_POSTAL;
    
	
	$sql = "INSERT INTO clients(cliNomPrenom, cliEmail, cliDateNaissance, cliPassword, cliAdresse, cliCP, cliVille, cliPays) 
			VALUES ('$nomprenom', '$email', $aaaammjj, '$pass', '$invalid', $code_postal, '$invalid', '$invalid')";
            
	mysqli_query($bd, $sql) or fd_bd_erreur($bd, $sql);

	$id = mysqli_insert_id($bd);

	// libération des ressources
	mysqli_close($bd);
	
	// mémorisation de l'ID dans une variable de session 
    // cette variable de session permet de savoir si le client est authentifié
	$_SESSION['cliID'] = $id;
    
    // redirection vers la page d'origine
	fd_redirige($_POST['source']);
}
	


?>