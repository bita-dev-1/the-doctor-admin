<?php 

  $lang_fr = array(
    /********** [Begin]: Title **************/
      "Platform"                                    =>    "Platform", 
      "Platform control panel"                      =>    "Platform control panel",  
    /********** [End]: Title *****************/
    /********** [Begin]: Default **************/
      "export"                                      =>  "exporter",
      "add"                                         =>  "Ajouter",
      "Successfully Deleted"                        =>  "Supprimé avec succès",
      "Added successfully"                          =>  "Ajouté avec succès",
      "Edited successfully"                         =>  "Modifié avec succès",
      "Files not exist"                             =>  "Les fichiers n'existent pas",
      "Successfully Changed"                        =>  "Modifié avec succès",
      "The form is forged"                          =>  "La forme est forgée",
      "Home"                                        =>  "Accueil",
      "save"                                        =>  "Enregistrer",
      "edit"                                        =>  "Modifier",
      "_photo"                                      =>  "Image",
      "icon"                                        =>  "Icon",
      "created_at"                                  =>  "Date de création",
      "delete"                                      =>  "Suprimer",
      "delete_confirmation"                         =>  "Voulez-vous vraiment supprimer cet élément ?",
      "optional"                                    =>  "optionnel",
      "missing_data"                                =>  "Données manquantes",
      "sended successfully"                         =>  "Envoyé avec succès",
      "default"                                     =>  "Par défaut",
   "something went wrong reload page and try again" =>  "une erreur s'est produite recharger la page et réessayer",
      "user"                                        =>  "utilisateur",
      "action"                                      =>  "Action",
      "Details_of"                                  =>  "Détails de",
      "profile"                                     =>  "Profile",
      "My account"                                  =>  "Mon compte",
      "Log Out"                                     =>  "Se déconnecter",
    
    /********** [END]: Default **************/

    /********** [Begin]: login *****************/
      "Welcome"                                     =>    "Bienvenue 👋",
      "Please login to your account"                =>    "Veuillez vous connecter à votre compte",
      "username"                                    =>    "Nom d'utilisateur",
      "password"                                    =>    "Mot de passe",
      "Login"                                       =>    "Connexion",
      "You are logged in successfully"              =>    "Vous êtes connecté avec succès",
      "Incorrect username or password!!"            =>    "Identifiant ou mot de passe incorrect!!",
      "People's Democratic Republic of Algeria"     =>    "République algérienne démocratique et populaire",
      "Signed out"                                  =>    "Se déconnecter",
      "Please enter the same password again."       =>    "Veuillez saisir à nouveau le même mot de passe.",
      "Old password incorrect!!"                    =>    "Ancien mot de passe incorrect !",
      "email"                                       =>    "E-mail",
      "cpassword"                                   =>    "Confirmer mot de passe",
      ""              =>    "",
      ""              =>    "",
      
    /********** [END]: login ******************/

    /********** [Begin]: Header *******************/
      "Alger"                                       =>    "Arabe",
      "English"                                     =>    "Anglais",
      "French"                                      =>    "Français",
      "Notification"                                =>    "Notification",      
    
    /********** [END]: Header **********************/

    /********** [Begin]: project words **************/
      "id"                                          =>  "Réf",
      "full_name"                                   =>  "Nom et prénom",
      "phone"                                       =>  "Téléphone",
      "Dashboard"                                   =>  "Tableau de bord",
      "users"                                       =>  "Utilisateurs",
      "admins"                                      =>  "Administrateurs",
      "admin"                                       =>  "Administrateur",
      "specialty"                                   =>  "Spécialité",
      "commune"                                     =>  "Commune",
      "willaya"                                     =>  "Willaya",
      "doctors"                                     =>  "Médecins",
      "doctor"                                      =>  "Médecin",
      "patients"                                    =>  "Patients",
      "patient"                                     =>  "Patient",
      "specialities"                                =>  "Spécialités",
      "speciality"                                  =>  "Spécialité",
      "namefr"                                      =>  "Nom en français",
      "namear"                                      =>  "Nom en arabe",
      "administration"                              =>  "Administration",
      "firstname"                                   =>  "Prénom",
      "lastname"                                    =>  "Nom",
      "lng"                                         =>  "longitude",
      "lat"                                         =>  "latitude",
      "description"                                 =>  "Déscription",
      "rdv"                                         =>  "Rendez-vous",
      "rdv_num"                                     =>  "Numéro Rendez-vous",
      "date"                                        =>  "Date",
      "hours"                                       =>  "Heurs",
      "state"                                       =>  "État",
      "__rdvstate"                                  =>  "État",
      "created"                                     =>  "Créé",
      "accepted"                                    =>  "Accepté",
      "completed"                                   =>  "Complété",
      "Canceled"                                    =>  "Annulé",
      "Accept"                                      =>  "Accepter",
      "Complete"                                    =>  "Compléter",
      "Cancel"                                      =>  "Annuler",
      "waitingList"                                 =>  "Fil d'attente",
      "Role"                                        =>  "Role",
      "sunday"                                      =>  "Dimanche",
      "monday"                                      =>  "Lundi",
      "tuesday"                                     =>  "Mardi",
      "friday"                                      =>  "Vendredi",
      "saturday"                                    =>  "Samedi",
      "wednesday"                                   =>  "Mercredi",
      "thursday"                                    =>  "Jeudi",
      "Tickets"                                     =>  "Billets",
      "__enableRdv"                                 =>  "L'état des rendez-vous",
      "recomondation"                               =>  "Recommandation",
      "views"                                       =>  "vues",
      ""                            =>  "",
      ""                            =>  "",
      ""                            =>  "",
      ""                            =>  "",
      
    /********** [End]: project words *****************/ 

    /******************* [Begin]: Foot ******************/
      "Copyrights"                                  =>    "Copyrights",
      "All rights reserved"                         =>    "Tous droits réservés",
    /******************* [END]: Foot ********************/


  );

  $lang_en = array( );

  $lang_ar = array( ); 
  

global $language;
$language = $lang_fr;
$rtl = "";
$ar ="fr";
$lang="Francais";

if(isset($_COOKIE['lang']) && !empty($_COOKIE['lang']) && strtolower($_COOKIE['lang']) == 'ar'){
  $language = $lang_ar;
  $rtl = "-rtl";
  $ar ="dz";
  $lang="العربية";
}else if(isset($_COOKIE['lang']) && !empty($_COOKIE['lang']) && strtolower($_COOKIE['lang']) == 'fr'){
  $language = $lang_fr;
  $rtl = "";
  $ar ="fr";
  $lang="Francais";
}
