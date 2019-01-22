<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

//si utilisateur non authentifié, redirection vers la page d'authentification
if (!isset($_SESSION['cliID'])){
	fd_redirige('login.php');
}

fd_html_debut('BookShop | Listes des commandes', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');
fdl_contenu();
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();


//Fonction local du script
/** 
*	Fonction qui affiche le contenu des commandes précédentes
*
*/
function fdl_contenu() {
	$bd = fd_bd_connect();
	//récupère l'id du client pour déterminer ses précédentes commandes
	$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
	$sql = "SELECT COUNT(DISTINCT coID) AS nb_commande FROM livres, compo_commande, commandes WHERE liID = ccIDLivre AND ccIDCommande = coID AND coIDClient = $cliID";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	
	//cette condition vérifie si le client à déjà réalisé des commandes ou non en regardant si le résultat de la requête n'est pas vide
	if ($t = mysqli_fetch_assoc($res)) {
		if ($t['nb_commande'] > 0 ) {
			echo '<h3>Voici le contenu de vos précédentes commandes :</h3>';
			$i = $t['nb_commande'];
		} else {
			echo '<h3>Vous n\'avez pas encore réalisé de commande sur notre site.</h3>';
			return;
		}
	}
	mysqli_free_result($res);
	//Initialisation du prix total de la commande à 0 avant affectation
	$prixTotalCommande = 0;
	//boucle affichant les résulats des commandes précédentes
	$sql = "SELECT ccIDLivre, liTitre, liPrix, auPrenom, auNom, ccQuantite, coID, coIDClient, coDate, coHeure FROM commandes, compo_commande, livres, aut_livre, auteurs WHERE coID = ccIDCommande AND ccIDLivre = liID AND liId = al_IDLivre AND al_IDAuteur =auID AND coIDClient = $cliID ORDER BY coID DESC ";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$lastCoID = -1;
	$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		//mb_strimwidth tronque une chaine au caractere choisi
		//on récupere l'année
		$annee = (int)mb_strimwidth($t['coDate'], 0, 4);
		//on récupere le mois
		$mois = (int)mb_strimwidth($t['coDate'], 4, 2);
		//transformations du mois en lettre
		$mois_lettre = fd_get_mois($mois);
		//on récupere le jour
		$jour = (int)mb_strimwidth($t['coDate'], 6, 7);
		//on calcule la taille de l'heure
		$t_heure = (int)mb_strlen($t['coHeure'], 'UTF-8');
		//vérifie s'il ya un 0 ou non devant la chaine renvoyé par la base64_decode
		//s'il ya un 0 la taille de la chaine est alors 3
		if ($t_heure == 3) {
			//récupère l'heure
			$heure = (int)mb_strimwidth($t['coHeure'], 0, 1);
			//récupère les minutes
			$minutes = (int)mb_strimwidth($t['coHeure'], 1, 3);
		} else {
			//récupère l'heure
			$heure = (int)mb_strimwidth($t['coHeure'], 0, 2);
			//récupère les minutes
			$minutes = (int)mb_strimwidth($t['coHeure'], 2, 4);
		}
		//si l'id est différent du précédent on affiche le numéro de la commande du client, la date, l'heure et le numéro de commande fournisseur de celle-ci
		if ($t['ccIDLivre'] != $lastID) {
			if ($lastID != -1) {
				fd_afficher_livre($livre, 'bcCommande', '../');	
			}
			if ($t['coID'] != $lastCoID) {
				if ($lastCoID != -1) {
					echo '<br><div class="bcCommandeAligne">Prix total de la commande : ', $prixTotalCommande, '&euro;</div>';
					$prixTotalCommande = 0;
				}
				echo '<br><h2>Commande numéro ', $i, ' :</h2>';
				echo '<br><div class ="bcCommandeTxt">Commande réalisé le ', $jour, ' ', $mois_lettre, ' ', $annee, ' à ', $heure, 'h', $minutes, ', identifiant fournisseur : ', $t['coID'], '.</div>';
				$i--;
				$lastCoID = $t['coID'];
			}
			$lastID = $t['ccIDLivre'];
			$livre = array(	'id' => $t['ccIDLivre'], 
							'titre' => $t['liTitre'],
							'prix' => $t['liPrix'],
							'quantite' => $t['ccQuantite'],
							'appartientlivre' => false,
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
			$prixTotalCommande += $t['liPrix']*$t['ccQuantite'];
			if(isset($t['appartientLivre'])){
				$livre['appartientlivre']=$t['appartientLivre'];
			} else{
				$livre['appartientlivre']=false;
			}
		} else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}
	}

	fd_afficher_livre($livre, 'bcCommande', '../');
	echo '<br><div class="bcCommandeAligne">Prix total de la commande : ', $prixTotalCommande, '&euro;<br><br></div>';
	//on libère le resultat res et on ferme la base de donnée
	mysqli_free_result($res);
	mysqli_close($bd);
}
?>