<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)
fd_html_debut('BookShop | Inscription', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(false,'../');

	$bd = fd_bd_connect();
	//récupère l'id du client pour déterminer ses précédentes commandes
	$cliID= fd_bd_protect($bd,$_SESSION['cliID']);
	$sql = "SELECT ccIDLivre, ccQuantite, coID, coIDClient, coDate, coHeure, liTitre, liPrix FROM livres, compo_commande, commandes WHERE liID = ccIDLivre AND ccIDCommande = coID AND coIDClient = $cliID";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$i = 1;
	$lastCoID = -1;
	//cette condition vérifie si le client à déjà réalisé des commandes ou non en regardant si le résultat de la requête n'est pas vide
	if ($t = mysqli_fetch_assoc($res)) {
		//regarde sile reusltat de la requête est vide
		if (!empty($t['ccIDLivre'])) {
			echo '<h3>Voici le contenu de vos précédentes commandes :</h3>';
		}
	} else {
		echo '<h3>Vous n\'avez pas encore réalisé de commande sur notre site.</h3>';
	}
	//Initialisation du prix total de la commande à 0 avant affectation
	$prixTotalCommande = 0;
	//Détéction du premier tour de boucle
	$premierTourDeBoucle = -1;
	//boucle affichant les résulats des commandes précédentes
	$nb = 0;
	echo $cliID;echo '<pre>', print_r($t, true), '</pre>';
	while ($t = mysqli_fetch_assoc($res)) {
		
		/*//on met toutes les informatiosn relatives au livre dans un tableau
		$livre = array('id' => $t['ccIDLivre'],
						'titre' => $t['liTitre'],
						'prix' => $t['liPrix'],
						'quantite' => $t['ccQuantite'],
						'appartientlivre' => false,
						'auteurs' => array(array('prenom' => ' ', 'nom' => ' ')));
		
		$prixTotalCommande += $t['liPrix']*$t['ccQuantite'];
		//affichage du livre
		fd_afficher_livre($livre, 'bcCommande', '../');
		//affectation de l'id en cours qui va devenir l'id précédent
		$lastCoID = $t['coID'];
		echo "'",$nb,"'";
		$nb ++;*/
		
		$livre = array('ccIDLivre' => $t['ccIDLivre'],
						'liTitre' => $t['liTitre'],
						'liPrix' => $t['liPrix'],
						'ccQuantite' => $t['ccQuantite']);
		
		// fd_afficher_livre($t, 'bcCommande', '../');
		
		// echo '<pre>', print_r($livre, true), '</pre>';
		// echo '<pre>', print_r($t, true), '</pre>';
		
		
	}
	fd_afficher_livre($t, 'bcCommande', '../');
	//affichage du dernier livre de la commande
	echo '<div class="bcCommandeAligne">Prix total de la commande : ', $prixTotalCommande, '&euro;</div>';
	echo $nb;
	//on libère le resultat res et on ferme la base de donnée
	mysqli_free_result($res);
	mysqli_close($bd);


fd_bookshop_pied();
fd_html_fin();
ob_end_flush();
?>