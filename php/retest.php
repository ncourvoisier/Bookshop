<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
//Démarre les sessions
session_start();

//Inclue les bibliothèques créés
require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';




//Début structure du site
fd_html_debut('BookShop | retest', '../styles/bookshop.css');
fd_bookshop_enseigne_entete(true,'../');


//Fin structure du site
fd_bookshop_pied();
fd_html_fin();

//Ferme les sessions
ob_end_flush();

?>