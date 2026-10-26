# Premier message du sujet (français)

Brouillon. Ne pas publier, ne pas joindre de ZIP, et ne pas remplacer la release 1.0.0 : Jeff valide chaque sortie.

Section prévue, le jour où la sortie est validée : Forum francophone, « Modules et thèmes gratuits ». Joindre alors le fichier `weadushoppingfeed.zip` au message.

---

**Titre :** [MODULE GRATUIT] WeAdU Shopping feed check : vérifier son catalogue avant Google Shopping (1.7.8+)

Bonjour,

Je partage un module gratuit qui vérifie, dans le back-office, les champs du catalogue utilisés par Google Shopping.

**Ce qu'il fait**
- Lit les produits actifs de la boutique (200 articles par défaut, 500 au maximum, déclinaisons comprises).
- Si le catalogue dépasse la limite, la page indique combien d'articles n'ont pas été lus. Le tableau et le CSV ne couvrent alors que les lignes lues.
- Signale par article : titre, description, lien, image, disponibilité ou prix vides ; titre de plus de 150 caractères ; prix nul ; GTIN dont la clé de contrôle est fausse ; référence en double. En avertissement : titre court ou tout en capitales, formule promotionnelle, prix sans devise, marque vide, GTIN et MPN vides.
- Propose un titre qui commence par la marque quand elle manque.
- Contrôle chaque déclinaison, avec un lien de modification par produit.
- Affiche le prix catalogue et, s'il est plus bas, le prix payé par le client. Un prix spécifique qui impose un montant fixe ne masque plus le prix catalogue.
- Les prix sont TTC par défaut. Pour un flux États-Unis ou Canada, il faut choisir « Prix HT » : Merchant Center y demande un prix hors taxe. Le module ne calcule pas la sales tax américaine ou canadienne.
- Exporte le résultat en CSV.

**Ce qu'il ne fait pas**
- Il ne modifie pas vos produits et n'envoie rien à Google ni ailleurs.
- Il n'ajoute pas de paramètres UTM sur les liens. Pour suivre les clics Google Ads, gardez le marquage automatique et un modèle de suivi dans Google Ads.
- Les prix spécifiques sont ceux du groupe client par défaut, dans le contexte du back-office.
- Un article sans constat n'est pas validé pour autant par Google.

**Installation** : Modules, Gestionnaire de modules, Installer un module, choisir le ZIP joint, puis « Configurer ».

Compatibilité déclarée : PrestaShop 1.7.8 et suivantes. Licence AFL-3.0. Code source : LINK

Transparence : je travaille chez WeAdU, l'agence qui a conçu ce module. Il est gratuit.

Les questions et retours sont les bienvenus dans ce sujet.
