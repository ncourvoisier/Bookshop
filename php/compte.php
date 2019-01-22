<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';


//A mettre en fonction!
if (!isset($_SESSION['cliID'])){
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


//Début structure du site
fd_html_debut('BookShop | Mon compte', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(true,'../');

//Détection si on arrive sur la page avant ou après avoir appuyer sur le bouton valider
if(!isset($_POST['btnModif'])&&!isset($_POST['btnValider'])){
	pc_contenu_compte();
}
else{
	//Détection si on arrive sur la page avant ou après avoir appuyer sur le bouton valider
	if(!isset($_POST['btnValider'])){
		pc_modification_infos();
	}
	else{	
		$a=count($_POST);
		pc_control_piratage();
		$err=array();
		$err=pc_control_erreur();
		pc_contenu($err);
		pc_modification_infos();
	}
}

//Fin structure du site
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();


//Fonction local du script
/** 
* Fonction qui affiche le contenu des commandes précédentes
*
*/
function pc_modification_infos(){
	//Si on est sur la page sans avoir cliqué sur valider, on récupère les valeurs dans le tableau super global $_SESSION
	if(!isset($_POST['btnValider'])){
		$nomprenom=$_SESSION['cliNomPrenom'];
		$jour_naissance=$_SESSION['jour_naissance'];
		$mois_naissance=$_SESSION['mois_naissance'];
		$annee_naissance=$_SESSION['annee_naissance'];
		$adresse=$_SESSION['cliAdresse'];
		$cp=$_SESSION['cliCP'];
		$ville=$_SESSION['cliVille'];
		$pays=$_SESSION['cliPays'];

	}
	//Sinon on récupère les valeurs dans le tableau super global $_POST
	else{
		$nomprenom=$_POST['nomprenom'];
		$jour_naissance=$_POST['naiss_j'];
		$mois_naissance=$_POST['naiss_m'];
		$annee_naissance=$_POST['naiss_a'];
		$adresse=$_POST['adresse'];
		$cp=$_POST['cp'];
		$ville=$_POST['ville'];
		$pays=$_POST['pays'];
	}
	echo 	
		//Création du formulaire pour modifier ou ajouter les informations de livraisons à la base de donnée
		'<form method="post" action="compte.php">',
			'<h2>Modifiez vos informations de login. </h2>',
			'<table>',
				fd_form_ligne('Choisissez un mot de passe (optionnel) :', fd_form_input(FD_Z_PASSWORD, 'pass1', '', 30)),
				fd_form_ligne('Répétez le mot de passe (optionnel):', fd_form_input(FD_Z_PASSWORD, 'pass2', '', 30)),
				fd_form_ligne('Nom et prénom :', fd_form_input(FD_Z_TEXT, 'nomprenom', $nomprenom, 30)),
				fd_form_ligne('Date de naissance :', fd_form_date('naiss', NB_ANNEES_DATE_NAISSANCE, $jour_naissance, $mois_naissance, $annee_naissance)),
			'</table>',
			'<h2>Modifiez vos informations de livraison. </h2>',
			'<table>',
				fd_form_ligne('Adresse de livraison :', fd_form_input(FD_Z_TEXT, 'adresse', $adresse, 30)),
				fd_form_ligne('Code postal :', fd_form_input(FD_Z_TEXT, 'cp', $cp, 30)),
				fd_form_ligne('Ville:', fd_form_input(FD_Z_TEXT, 'ville',$ville, 30)),
				fd_form_ligne('Pays :', fd_form_input(FD_Z_TEXT, 'pays',$pays, 30)),'</table><h2>Mot de passe actuel (obligatoire).</h2><table>',
			fd_form_ligne('Mot de passe actuel :',fd_form_input(FD_Z_PASSWORD, 'pass_actu', '', 30)),
			'<tr><td colspan="2" style="padding-top: 10px;" class="centered">', fd_form_input(FD_Z_SUBMIT,'btnValider','Valider'), '</td></tr>',
			'</table>',
		'</form>';
		echo '<a id=retour href="compte.php" title="Retour">Retour</a>';
}

/** 
* Fonction qui affiche le contenu actuel du compte dans la base de donnée
*
*/
function pc_contenu_compte(){
	if(isset($_SESSION['panier_livraison'])&&$_SESSION['panier_livraison']===true){
		echo'<p class="erreur">Vous devez rentrer votre adresse de livraison afin de passer la commande</p>';
	}
	//Connexion à la base de donnée
	$bd = fd_bd_connect();
	$id=$_SESSION['cliID'];
	//Requète sql pour récupérer les informations du clients
	$sql = "SELECT cliEmail,cliPassword,cliNomPrenom,cliAdresse,cliCP,cliVille,cliPays,cliDateNaissance FROM clients WHERE cliID = '$id'"; 
	//Résultat de la requète
	$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
	if($t = mysqli_fetch_assoc($res)){
		//Affectation des résultats pour pouvoir les modifiers si le client le souhaite
		$_SESSION['cliNomPrenom']=$t['cliNomPrenom'];
		$_SESSION['cliAdresse']=$t['cliAdresse'];
		$_SESSION['cliCP']=$t['cliCP'];
		$_SESSION['cliVille']=$t['cliVille'];
		$_SESSION['cliPays']=$t['cliPays'];
		$cliDateNaissance=$t['cliDateNaissance'];
		//Libère la variable et ferme la base de donnée
		mysqli_free_result($res);
		mysqli_close($bd);
		//Recupère la date de naissance du client
		$_SESSION['annee_naissance']=(int)($cliDateNaissance/10000);
		$_SESSION['mois_naissance']=(int)(($cliDateNaissance%10000)/100);
		$_SESSION['jour_naissance']=(int)($cliDateNaissance%100);
		//Affecte la date de naissance du client
		$date_naissance=$_SESSION['jour_naissance'].'/'.$_SESSION['mois_naissance'].'/'.$_SESSION['annee_naissance'];
		//Affiche les informations de compte du client
		echo '<h2>Informations personnelles.</h2><div id=contenu_infos><br><a href="liste_commande.php" title="liste des commandes">Listes des commandes précédentes.</a>';
			affiche_ligne_infos("Email",$t['cliEmail']);
			affiche_ligne_infos("Nom et prénom",$t['cliNomPrenom']);
			affiche_ligne_infos("Adresse",$t['cliAdresse']);
			affiche_ligne_infos("Ville",$t['cliVille']);
			affiche_ligne_infos("Code postal",$t['cliCP']);
			affiche_ligne_infos("Pays",$t['cliPays']);
			affiche_ligne_infos("Date de naissance",$date_naissance);
		//Crée un bouton modifié pour rediriger le client sur une zone de saisie
		echo '<form method="post" action="compte.php">',fd_form_input(FD_Z_SUBMIT,'btnModif','Modifier'),'</form><br></div>';
	}

}

/** 
* Fonction qui détecte les tentatives de piratage
*
*/
function pc_control_piratage(){
    //Compte les valeurs passé à travers le tableau super global POST
	$nb = count($_POST);
	//S'il est égale à 12 on réalise d'autres vérifications
    if ($nb==12){
        //Vérifie que les noms de variable sont bien les bons
		(! isset($_POST['btnValider']) || $_POST['btnValider'] != 'Valider') && fd_exit_session();
        (! isset($_POST['pass1'])) && fd_exit_session();
        (! isset($_POST['pass2'])) && fd_exit_session();
        (! isset($_POST['nomprenom'])) && fd_exit_session();
        (! isset($_POST['naiss_j'])) && fd_exit_session();
        (! isset($_POST['naiss_m'])) && fd_exit_session();
        (! isset($_POST['naiss_a'])) && fd_exit_session();
		(! isset($_POST['cp'])) && fd_exit_session();
		(! isset($_POST['ville'])) && fd_exit_session();
		(! isset($_POST['pays'])) && fd_exit_session();
		(! isset($_POST['adresse'])) && fd_exit_session();
		(! isset($_POST['pass_actu'])) && fd_exit_session();
		//(isset($_POST['cp']))&&!est_entier($_POST['naiss_m'])&& fd_exit_session();
        //Vérifie les types de variables
		(!est_entier($_POST['naiss_a']) || !est_entier($_POST['naiss_m']) || !est_entier($_POST['naiss_j']));
		//Vérifie la validité de la date
        $aa = date('Y');
        ($_POST['naiss_j'] < 1 || $_POST['naiss_j'] > 31 || $_POST['naiss_m'] < 1 || $_POST['naiss_m'] > 12 ||
        $_POST['naiss_a'] > $aa || $_POST['naiss_a'] <= $aa - NB_ANNEES_DATE_NAISSANCE) && fd_exit_session();
        
        return;// => ok, pas de problème détecté
    }
	//Sinon on déconnecte l'utilisateur et on e redirige
    fd_exit_session();
}

/** 
* Fonction qui vérifient si les informations saisi dans le formulaire sont correctes
*
*
* @return	array	$err	s'il y a des erreurs de saisi on retourne le tableau d'érreur sinon on est redirigé vers la page index.php
*/
function pc_control_erreur(){
	//Création d'un tableau d'érreur
	$err = array();
	//$email = trim($_POST['email']);
	//Vérification qu'il n'y a pas d'espace non voulu
	$pass1 = trim($_POST['pass1']);
	$pass2 = trim($_POST['pass2']);
	//Cryptage au format MD5
	$pass_actu=md5($_POST['pass_actu']);
	//Vérification qu'il n'y a pas d'espace non voulu
	$nomprenom = trim($_POST['nomprenom']);
	//Récupère les valeurs passer dans le tableau super global $_POST
	$naiss_j = (int)$_POST['naiss_j'];
	$naiss_m = (int)$_POST['naiss_m'];
	$naiss_a = (int)$_POST['naiss_a'];
	$cp = $_POST['cp'];
	$ville=$_POST['ville'];
	$adresse=$_POST['adresse'];
	$pays=$_POST['pays'];
	// vérification pour les mots de passe
	$nb = mb_strlen($pass1, 'UTF-8');
	$nb2 =mb_strlen($pass2, 'UTF-8');
	//Si c'est égale à 0, l'utilisateur n'a pas modifié le mot de passe
	if($nb==0){
		$changement_mdp=false;
	}
	else{
		//S'il y a des érreurs on affecte un message dans un tableau
		$changement_mdp=true;
		if ($pass1 != $pass2) {
			$err['pass1'] = 'Les mots de passe doivent être identiques.';	
		}
		else {
			//Vérification qu'il n'ya pas de code HTML dans la zone de saisi
			$noTags = strip_tags($pass1);
			if (mb_strlen($noTags, 'UTF-8') != $nb) {
			    $err['pass1'] = 'La zone Mot de passe ne peut pas contenir de code HTML.';
				}
			else if ($nb < 4 || $nb > 20){
			    $err['pass1'] = 'Le mot de passe doit être constitué de 4 à 20 caractères.';
			}	
		}
	}
	$nb = mb_strlen($pass_actu, 'UTF-8');
	//Vérification qu'il n'ya pas de code HTML dans la zone de saisi
	$noTags = strip_tags($pass_actu);
	if (mb_strlen($noTags, 'UTF-8') != $nb) {
            $err['pass_actu'] = 'La zone Mot de passe actuel ne peut pas contenir de code HTML.';
	}
	// vérification des noms et prenoms
	//Vérification qu'il n'ya pas de code HTML dans la zone de saisi
	$noTags = strip_tags($nomprenom);
	if ($noTags != $nomprenom){
		$err['nomprenom'] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
	}
	//Le champ ne peut pas être vide
	else if (empty($nomprenom)) {
		$err['nomprenom'] = 'Le nom et le prénom doivent être renseignés.';	
	}
	    /*elseif (! preg_match("/^[[:alpha:]][[:alpha:]\- ']{1,99}$/", $nomprenom)) { // ne fct pas avec les accents
		$err['nomprenom'] = 'Le nom et le prénom ne sont pas valides.';
	    }*/
	//Vérification que la saisi correspond à l'expression régulière suivante
	elseif (mb_regex_encoding ('UTF-8') && ! mb_ereg_match("^[[:alpha:]][[:alpha:]\- ']{1,99}$", $nomprenom)) {
		$err['nomprenom'] = 'Le nom et le prénom ne sont pas valides.';
	}
	//Vérification de la date de naissance
	//Vérifie que la date est valide
	if (! checkdate($naiss_m, $naiss_j, $naiss_a)) {
		$err['date'] = 'La date de naissance est incorrecte.';	
	}	
	else {
		$dateDuJour = getDate();
		//Vérifie si l'utilisateur est trop vieux ou trop jeune
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
	//vérification cp
	if(!est_entier($cp)){
		$err['cp']='Le code postal n\'est pas un entier';
	}
	if($cp<0||$cp>100000){
		$err['cp']='Le code postal n\'est pas valide';
	}
	//vérification ville
	//Vérification qu'il n'ya pas de code HTML dans la zone de saisi
	$noTags = strip_tags($ville);
   	 if ($noTags != $ville){
        	$err['ville'] = 'La ville ne peut pas contenir de code HTML.';
   	 }
	//vérification pays
	//Vérification qu'il n'ya pas de code HTML dans la zone de saisi
	$noTags = strip_tags($pays);
   	if ($noTags != $pays){
        $err['pays'] = 'Le pays ne peut pas contenir de code HTML.';
   	}
	//S'il n'y a pas d'érreur on peut faire la requète sql pour remplir la base de donnée
	if (count($err) == 0) {
		$id=$_SESSION['cliID'];
		//Connexion à la base de donnée
		$bd = fd_bd_connect();
		//Protection des chaines de caractères qui vont aller dans la base de donnée
		$pass_actu=fd_bd_protect($bd,$pass_actu);
		//on vérifie que le mdp est bon (uniquement si il y a 0 erreurs de saisi de la part de l'utilisateur)
		$sql = "SELECT cliPassword FROM clients WHERE cliID=$id AND cliPassword='$pass_actu'";
		//Résultat de la requète
		$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
		//Si le résultat est 0 le mot de passe est incorrecte et on quitte la fonction
		if (mysqli_num_rows($res) == 0) {
			$err['email'] = 'Le mot de passe est incorrect';
			mysqli_free_result($res);
           	mysqli_close($bd);
			return $err;
		}
		//Libère le résultat
		mysqli_free_result($res);
		//S'il est juste on continue de remplir la base de donnée avec les informations saisis par l'utilisateur
		//Protection des chaines de caractères qui vont aller dans la base de donnée
		$nomprenom = fd_bd_protect($bd,$nomprenom);
		$cp = fd_bd_protect($bd,$cp);
		$ville=fd_bd_protect($bd,$ville);
		$adresse=fd_bd_protect($bd,$adresse);
		$pays=fd_bd_protect($bd,$pays);
		//La date de naissance est mise au format de la base de donnée
		$aaaammjj = $naiss_a*10000  + $naiss_m*100 + $naiss_j;
		//Si l'utilisateur a changé sont mot de passe on l'affecte également avec les autres données modifiées
		if($changement_mdp===true){
			$pass1 = fd_bd_protect($bd,md5($pass1));
			$sql = "UPDATE clients SET cliPassword='$pass1', cliNomPrenom='$nomprenom', cliAdresse='$adresse' , cliCp=$cp, cliVille='$ville', cliPays='$pays', cliDateNaissance=$aaaammjj WHERE cliID =$id"; 
		}
		//Sinon on modofie que les autres données saisies
		else{
			$sql = "UPDATE clients SET cliNomPrenom='$nomprenom', cliAdresse='$adresse', cliCp=$cp, cliVille='$ville', cliPays='$pays', cliDateNaissance=$aaaammjj WHERE cliID =$id";
		}
		//Résultat de la requète
		$res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
		//On libère la variable et ferme la base de donnée
		mysqli_free_result($res);
        mysqli_close($bd);
        //Si l'utilisateur devait enregistrer son adresse de livraison on le redirige vers le panier.
        if(isset($_SESSION['panier_livraison'])&&$_SESSION['panier_livraison']===true){
        	unset($_SESSION['panier_livraison']);
        	fd_redirige('panier.php');
        }
		//Redirection sur la page index.php
		fd_redirige('../index.php');
	}
	//Si le tableau $err n'est pas vide, il ya des erreurs on retourne donc le tableau d'erreurs;
	if (count($err) > 0) { 	
		return $err;	
	}	
}

/** 
* Fonction qui affiche les erreurs de saisi s'il y en a
*
* @param	array	$err	Tableau qui contient les messages d'érreurs de l'utilisateur pour les affichers
*/
function pc_contenu($err) {
	//Compte les erreurs dans le tableau, si c'est différent de 0 c'est qu'il y a des érreurs
	if (count($err) > 0) {
		//Affichage d'un méssage d'érreur et des érreurs de l'utilisateur
		echo '<p class="erreur">Votre modification n\'a pas pu être réalisée à cause des erreurs suivantes : ';
		foreach ($err as $v) {
			echo '<br> - ', $v;
		}
		echo '</p>';	
	}
}

/** 
* Fonction qui une ligne d'informations
*
* @param	String	$nom		Nom de la ligne d'informations
* @param	int		$valeurs	Valeur de l'informations
*/
function affiche_ligne_infos($nom,$valeurs){
	echo '<p>',$nom,': ',$valeurs,'</p>';
}
?>