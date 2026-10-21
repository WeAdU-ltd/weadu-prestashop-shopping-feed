# Premier message du sujet (français)

Section : Forum francophone → « Modules et thèmes gratuits ». Joindre le fichier `weadushoppingfeed.zip` au message.

---

**Titre :** [MODULE GRATUIT] WeAdU Shopping feed check : vérifier son catalogue avant Google Shopping (1.7.8+)

Bonjour,

Je partage un module gratuit qui vérifie, dans le back-office, les champs du catalogue utilisés par Google Shopping.

**Ce qu'il fait**
- Lit les produits actifs de la boutique (200 par défaut, 500 au maximum).
- Signale par produit : titre, description, lien, image, disponibilité ou prix vides ; titre de plus de 150 caractères ; prix nul ; GTIN dont la clé de contrôle est fausse ; référence en double. En avertissement : titre court ou tout en capitales, formule promotionnelle, prix sans devise, marque vide, GTIN et MPN vides.
- Propose un titre qui commence par la marque quand elle manque.
- Contrôle chaque déclinaison, avec prix TTC et prix soldés, et un lien de modification par produit.
- Exporte le résultat en CSV.

**Ce qu'il ne fait pas**
- Il ne modifie pas vos produits et n'envoie rien à Google ni ailleurs.
- Le prix vérifié est le prix de base HT, sans déclinaison ni prix spécifique.
- Un produit sans constat n'est pas validé pour autant par Google.

**Installation** : Modules → Gestionnaire de modules → Installer un module → choisir le ZIP joint, puis « Configurer ».

Compatibilité déclarée : PrestaShop 1.7.8 et suivantes. Licence AFL-3.0. Code source : LINK

Transparence : je travaille chez WeAdU, l'agence qui a conçu ce module. Il est gratuit.

Les questions et retours sont les bienvenus dans ce sujet.
