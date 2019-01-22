<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

//Début structure du site
fd_html_debut('BookShop | Mon panier', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

//Vérifie si on a déjà cliqué sur le bouton commander ou non
if (!isset($_POST['btnResume'])) {
	if (isset($_POST['btnCommander'])) {
		nc_commander();
	}
	//Vérifie si on arrive sur le site avant ou après avoir réalisé une commande
	if (!isset($_SESSION['apresCommander'])) {
		nc_contenu();
	} else {
		//Détecte piratage
		($_POST) &&fd_exit_session();
		//Sinon affiche le délai d'attente pour la réception
		echo '<h4>La commande a bien été enregistré vous recevrez votre colis dans les 7 jours ouvrés (hors pays outre Europe).</h4>';
		//Supprimer cette valeur pour pouvoir accéder a un panier vierge et faire une nouvelle commande si souhaité
		unset($_SESSION['apresCommander']);
	}
} else {
	//Affiche le résumé du panier
	nc_resume_panier();
}

//Fin structure site
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();



//Fonction locales
/**
* Fonction qui résume le détails d'un panier en affichant la somme du panier avec les quantités
*/
function nc_resume_panier() {
	//Initialisation de la somme du panier
	$prixTotalPanier = 0;
	//Vérifie que le tableau contenant les livres est créés
	if (isset($_SESSION['livrePanier'])) {
		//Création d'une variable livre pour concaténation plus tard
		$livre = 'livre';
		//Titre de la page et création du formulaire
		echo '<h2>Résumé de votre panier :</h2>';
		//Boucle pour afficher tous les livres dans le panier
		foreach ($_SESSION['livrePanier'] as $id_livre) {
			//Afficher un livre dans le panier
			fd_afficher_livre($id_livre, 'bcResumePanier', '../');
			//Concaténation avec l'id du livre pour nommer le menu défilant
			$ref = $livre.$id_livre['id'];
			//Cherche la quantite demandé du livre, 1 par défaut
			$quantite = (isset($_POST["$ref"])&&est_entier($_POST["$ref"])) ? $_POST["$ref"] :fd_exit_session();
			echo '<p>Quantité :',$quantite;
			echo '<p>Prix total :',$id_livre['prix']*$quantite,'</div></div>';
			$_SESSION["$ref"] = $quantite;
			//Somme le panier
			$prixTotalPanier += $id_livre['prix']*$quantite;
		}
		//Création du bouton submit pour commander et ferme le formulaire
		echo '<form method="post" action="panier.php">',
			'<div class="boutonSubmit">', fd_form_input(FD_Z_SUBMIT,'btnCommander','Commander !'), '</div>',
			'</form>';
			$_POST['btnResume'] = 1;
		//Affichage de la somme total du panier
		echo '<p>Prix total de la commande: ', $prixTotalPanier, '&euro;</p><br>';
	}
}

/**
* Affiche le contenu d'une commande si ce n'est pas après une commande
*/
function nc_contenu(){
	//Initialisation de la somme du panier
	$prixTotalPanier = 0;
	//Vérifie que le tableau contenant les livres est créés
	if (isset($_SESSION['livrePanier'])) {
		//Création d'une variable livre pour concaténation plus tard
		$livre = 'livre';
		//Titre de la page et création du formulaire
		echo '<h2>Votre panier :</h2><form method="post" action="panier.php">';
		//Boucle pour afficher tous les livres dans le panier
		foreach ($_SESSION['livrePanier'] as $id_livre) {
			//Afficher un livre dans le panier
			fd_afficher_livre($id_livre, 'bcPanier', '../');
			//Concaténation avec l'id du livre pour nommer le menu défilant
			$ref = $livre.$id_livre['id'];
			//Création de menu défilant pour sélectionner une quantité de livre à commander
			echo '<table>', fd_form_ligne('Quantite :', nc_form_opt($ref)), '</table><br></div></div>';
			//id du livre
			$id_du_livre = $id_livre['id'];
			//Cherche la quantite demandé du livre, 1 par défaut
			$quantite = isset($_POST["$id_du_livre"]) ? $_POST["$id_du_livre"] : 1;
			//Somme le panier
			$prixTotalPanier += $id_livre['prix']*$quantite;
		}
		//Création du bouton submit pour commander et ferme le formulaire
		echo '<div class="boutonSubmit">', fd_form_input(FD_Z_SUBMIT,'btnResume','Récapitulatif'), '</div>',
			'</form>';
		//Création d'un lien pour vider le panier
		echo '<a id="vider_panier" href="vider_panier.php" title="Vider le panier">VIDER LE PANIER</a>';
	} else {
		//Sinon affiche que celui-ci est vide
		echo '<h1>Votre panier est vide.</h1>';
	}
}

/**
* fonction gérant la commande avec l'ajout dans la bd
*/
function nc_commander() {
	//On peut remplir le panier sans être connecté mais une fois que l'on clique sur le bouton commander on vérifie si l'utilisateur est connecté
	if (!isset($_SESSION['cliID'])){
		//S'il n'est pas connecté il est redirigé vers le login
		fd_redirige('login.php');
	}
	//Récupère la date du jour
	$dateCourante = date('Ymd');
	//Valeur pour le fuseau horaire du pays
	$timezone  = 2; //(GMT 2:00) fuseau horraire français 
	//Récupère l'heure actuelle
	$heureCourante = (date('H') + $timezone).date('i');
	//Connection à la bd
	$bd = fd_bd_connect();
	//vérification de la saisie de l'adresse de livraison
	nc_verification_livraison($bd);
	//Si l'id de la commande n'est pas traité on rentre dans cette condition
	if (!isset($_SESSION['commandeID'])) {
		//Recupération de l'id
		$id = $_SESSION['cliID'];
		$sql = 	"INSERT INTO commandes (coIDClient, coDate, coHeure) VALUES ($id, $dateCourante, $heureCourante)";
		$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
		//Recupération de l'id de commande autoincrémenté par la base de donnée
		$_SESSION['commandeID'] = (int)mysqli_insert_id($bd);
	}
	//Mise dans une variable
	$commandeID = $_SESSION['commandeID'];
	//Si le tableau de livre est créé (s'il ya des livres dans le tableau)
	//Soit il existe et il ya des livres, soit il n'existe pas ce qui implique qu'il est vide
	if (isset($_SESSION['livrePanier'])) {
		//Initialisation du nombre de livre dans le tableau
		$nbLivreCommande = count($_SESSION['livrePanier']);
		//Initialisation de la requette pour concaténation futur
		$req = 'VALUES';
		//Initialisation de livre pour concaténer ensuite avec l'id pour chercher la quantité
		$livre = 'livre';
		//initialisation du compteur pour déterminer le tour de boucle
		$cpt = 1;
		foreach ($_SESSION['livrePanier'] as $id_livre) {
			//Détecte le dernier tour de boucle pour éviter la ',' en fin de requette
			if ($cpt == $nbLivreCommande) {
				//Recupère l'id du livre
				$id = $id_livre['id'];
				//Recupère le nom du livre avec son id
				$ref = $livre.$id;
				//Vérifie si la quantite a déjà été affecté si oui on l'affecte sinon elle vaut 1
				$quantite = isset($_SESSION["$ref"]) ? $_SESSION["$ref"] : 1;
				//Concaténation de la requette
				$req.=" ($id, $commandeID, $quantite)";
			//Autre tour de boucle
			} else {
				//Recupère l'id du livre
				$id = $id_livre['id'];
				//Recupère le nom du livre avec son id
				$ref = $livre.$id;
				//Vérifie si la quantite a déjà été affecté si oui on l'affecte sinon elle vaut 1
				$quantite = isset($_SESSION["$ref"]) ? $_SESSION["$ref"] : 1;
				//Concaténation de la requette
				$req.=" ($id, $commandeID, $quantite),";
			}
			//Incrémentation du compteur
			$cpt++;
		}
	}
	//Effectue l'insertion de la commande
	$sql = "INSERT INTO compo_commande (ccIDLivre, ccIDCommande, ccQuantite) $req";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	//Ferme la bd
	mysqli_close($bd);
	//Supprime l'id de commande pour pouvoir en réaliser d'autres
	unset($_SESSION['commandeID']);
	//Affectation de cette valeur pour déterminer le retour du vide_panier
	$_SESSION['apresCommander'] = true;
	//Vider le panier pour d'autres commandes
	fd_redirige('vider_panier.php');
}
/**
* fonction qui vérifie si les paramètres de livraison sont rentré dans la base de donnée
*/
function nc_verification_livraison($bd) {
	//Recupère l'id du client
	$id = $_SESSION['cliID'];
	$sql = "SELECT cliAdresse, cliCP, cliVille, cliPays FROM clients WHERE cliID = $id";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	while ($t = mysqli_fetch_assoc($res)) {
		//Regarde si les informations de livraison son rentré dans la base de donnée
		if ($t['cliAdresse'] === 'INVALID' && $t['cliVille'] === 'INVALID' && $t['cliPays'] === 'INVALID' && $t['cliCP'] == 0) {
			//Si elles n'y sont pas on redirige le client vers son compte pour qu'il rentre les informations
			$_SESSION['panier_livraison']=true;
			fd_redirige('compte.php');
		}else {
			//Sinon on retourne dans la fonction précédente pour effectuer le reste
			return;
		}
	}
	//Libère le résultat res
	mysqli_free_result($res);
}
?>