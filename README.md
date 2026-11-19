# 🎓 EduNotes – Plateforme de Banc d'Essai Sécurité XML

> **Projet Académique – Licence 2 Informatique · UIDT (Université Iba Der Thiam de Thiès)**  
> **Module** : Dév Web 2 & Sécurité des Données et Technologies XML  
> **Équipe** : Groupe de 7 étudiants  
> **Diaporama associé** : `Securite_XML_EduNotes.pptx` (16 diapositives)

---

## ⚡ Démarrage Rapide en 1 Commande

Dans le dossier du projet, lancez simplement :

```bash
./start.sh
```

Ou directement avec PHP :

```bash
php -S localhost:8000
```

Puis ouvrez votre navigateur sur : **[http://localhost:8000](http://localhost:8000)**

---

## 🖥️ Architecture Simple en 4 Pages (PHP Natif)

Conformément à la proposition de l'équipe, le site s'articule autour de 4 pages limpides :

| Fichier | Rôle dans l'application | Rôle dans la démonstration |
| :--- | :--- | :--- |
| **`index.php`** | **Page de connexion** (Formulaire local ou bouton SSO SAML 2.0) | Point de départ de la démo SSO avec option d'interception en direct |
| **`idp.php`** | **Fournisseur d'Identité (IdP) Universitaire** | Émet l'assertion SAML signée (clé RSA privée IdP). Intègre l'éditeur MitM live |
| **`dashboard.php`** | **Tableau de bord** (Consomme l'assertion SAML) | **Attaque 1 (Usurpation SAML)** & **Attaque 2 (XXE)** |
| **`notes.php`** | **Page des notes & Relevé signé** | **Attaque 3 (Falsification de note 08/20 → 18/20 en XML-DSig)** |

*Outils supplémentaires :*
- **`lab.php`** : Tour de contrôle avec déclenchement rapide des scénarios.
- **`toggle_mode.php`** : Bascule immédiate entre `⚠️ MODE VULNÉRABLE` et `🛡️ MODE SÉCURISÉ`.
- **`reset.php`** : Remise à zéro instantanée des notes (Mamadou Sow: 08/20).

---

## 🎯 Déroulement des 3 Démonstrations en Soutenance

### 🎬 Démo 1 : Falsification de Relevé Signé (Diapo 7 · SC-01)
1. Ouvrir **`http://localhost:8000/notes.php`** (ou passer par `lab.php`).
2. S'assurer que le bandeau rouge **`⚠️ MODE VULNÉRABLE`** est actif.
3. Constater la note officielle de Mamadou Sow : **08/20**.
4. Dans l'éditeur XML, cliquer sur **« Remplacer 08.0 par 18.0 »** (ou modifier au clavier).
5. Cliquer sur **« Importer et Valider »** :
   - *Résultat en Mode Vulnérable :* Le relevé est accepté ! La note passe physiquement à **18/20** en base.
6. Cliquer sur le bandeau en haut pour passer en **`🛡️ MODE SÉCURISÉ`**.
7. Renvoyer le même XML avec la note à 18 :
   - *Résultat en Mode Sécurisé :* **Rejet d'intégrité (400)** ! L'empreinte SHA-256 recalculée ne correspond plus au condensat signé. La note en base reste à 08/20.

---

### 🎬 Démo 2 : Usurpation d'Identité SAML (Diapo 10 · SC-02)
1. Aller sur la page d'accueil **`http://localhost:8000/index.php`**.
2. Choisir l'étudiant **Mamadou Sow** avec la case *« Interception en direct »* cochée.
3. Cliquer sur **« Se connecter via SSO SAML »**.
4. Sur l'écran d'interception d'`idp.php` :
   - Remplacer au clavier `<saml:AttributeValue>student</saml:AttributeValue>` par `teacher` (ou cliquer sur le bouton raccourci).
   - Cliquer sur **« Transmettre au Service Provider »**.
5. *Résultat en Mode Vulnérable :* Élévation de privilèges réussie ! L'étudiant atterrit sur le tableau de bord enseignant et accède aux notes de toute la promotion.
6. Se déconnecter, basculer en **`🛡️ MODE SÉCURISÉ`**, et rejouer l'attaque :
   - *Résultat en Mode Sécurisé :* **Erreur 403 Forbidden** ! L'assertion modifiée a brisé la signature X.509 de l'IdP.

---

### 🎬 Démo 3 : Injection XXE (Diapo 12 · SC-03)
1. Lors de la connexion SSO sur `idp.php` (ou sur `notes.php`), injecter l'entité :
   ```xml
   <!DOCTYPE samlp:Response [
     <!ENTITY xxe SYSTEM "file:///home/mass/Bureau/edunotes/data/secret.txt">
   ]>
   ```
   et insérer `&xxe;` dans un champ de texte.
2. *Résultat en Mode Vulnérable :* Le parseur XML résout l'entité externe et divulgue les secrets (`FLAG{UIDT_XML_...}`).
3. *Résultat en Mode Sécurisé :* Blocage net : `Attaque XXE bloquée : DTD formellement interdites`.

---

## 🧪 Tests Automatisés

Pour valider le bon fonctionnement de tous les modules :

```bash
php tests/test_simple.php
```

Résultat : **10/10 tests passés avec succès** !
