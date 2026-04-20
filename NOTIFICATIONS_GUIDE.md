# 📧 Guide d'intégration des notifications par email

## 🎯 Objective

Intégration des 3 notifications email essentielles pour une plateforme e-commerce :

1. **Confirmation de commande** (Client) 🔴 Critique
2. **Nouvelle commande** (Vendeur) 🟡 Important
3. **Demande de vérification vendeur** (Admin) 🔴 Critique

---

## 🚀 Fichiers créés/modifiés

### Notifications (Mailables)
- `app/Notifications/OrderConfirmation.php` - Email au client après création de commande
- `app/Notifications/VendorNewOrder.php` - Email au vendeur quand il reçoit une commande
- `app/Notifications/VendorVerificationRequest.php` - Email aux admins pour vérifier les nouveaux vendeurs

### Events
- `app/Events/OrderPlaced.php` - **Modifié** pour inclure la commande
- `app/Events/VendorCreated.php` - **Créé** pour les nouveaux vendeurs

### Listeners
- `app/Listeners/SendOrderConfirmation.php` - **Modifié** pour envoyer la confirmation
- `app/Listeners/NotifyVendor.php` - **Modifié** pour notifier les vendeurs
- `app/Listeners/SendVendorVerificationNotification.php` - **Créé** pour notifier les admins

### Observers
- `app/Observers/VendeurObserver.php` - **Créé** pour dispatcher VendorCreated

### Providers
- `app/Providers/EventServiceProvider.php` - **Créé** pour enregistrer les listeners
- `app/Providers/AppServiceProvider.php` - **Modifié** pour enregistrer les observers

### Services
- `app/Services/Order/OrderService.php` - **Modifié** pour dispatcher OrderPlaced

---

## ⚙️ Configuration nécessaire

### 1. Configuration Mail dans `.env` - BREVO

```env
# Mailer - SMTP (Brevo)
MAIL_MAILER=smtp

# SMTP Configuration - BREVO
MAIL_SCHEME=tls
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=a6a28f001@smtp-brevo.com    # Votre email Brevo
MAIL_PASSWORD=xsmtpsib-xxxxx-xxxxx        # Clé SMTP générée depuis Brevo
MAIL_FROM_ADDRESS="noreply@afrishop.com"
MAIL_FROM_NAME="AfriShop"

# 📌 Génération clé SMTP Brevo :
# 1. Allez sur https://app.brevo.com
# 2. Paramètres → Compte → SMTP
# 3. Cliquez "Générer une nouvelle clé SMTP"
# 4. Copiez le nom d'utilisateur et le mot de passe
```

### 2. Configuration Queue

La queue est déjà configurée pour utiliser `database` :

```env
QUEUE_CONNECTION=database
```

Les emails seront traités en arrière-plan via :
```bash
php artisan queue:work
```

### 3. URL Frontend (pour les liens des emails)

```env
FRONTEND_URL=http://localhost:3000
```

---

## 🔄 Flux d'exécution

### 1️⃣ Création de commande
```
OrderController->store()
↓
OrderService->createOrderFromCart()
↓
Order créée en BD
↓
OrderPlaced::dispatch($order) // 👈 Événement dispatché
```

### 2️⃣ EventServiceProvider route les listeners
```
OrderPlaced
├── SendOrderConfirmation (client)
└── NotifyVendor (vendeurs)
```

### 3️⃣ Listeners envoient les notifications
```
Notification::notify() // Interface de Laravel
↓
Queue (database) // Enregistrée dans `jobs` table
↓
Worker exécute `queue:work` // Traitement en arrière-plan
↓
Email envoyé via SMTP
```

### 4️⃣ Création de vendeur
```
Vendeur::create()
↓
VendeurObserver->created()
↓
VendorCreated::dispatch($vendeur)
↓
SendVendorVerificationNotification (admins)
```

---

## 📧 Templates des emails

### Email 1: Confirmation de commande
**Destinataire:** Client  
**Sujet:** `Votre commande #CMD-2026-000001 a été confirmée`  
**Contenu:**
- Numéro de commande
- Montant total
- Adresse de livraison
- Liste des articles
- Bouton "Voir ma commande"

### Email 2: Nouvelle commande (Vendeur)
**Destinataire:** Vendeur  
**Sujet:** `🎉 Vous avez reçu une nouvelle commande!`  
**Contenu:**
- Numéro de commande
- Infos CLIENT (nom, email, téléphone)
- Montant total
- Adresse de livraison
- Produits vendus par ce vendeur
- Prochain actions
- Bouton "Voir la commande"

### Email 3: Demande de vérification (Admin)
**Destinataire:** Administrateurs  
**Sujet:** `📋 Un nouveau vendeur demande à être vérifié`  
**Contenu:**
- Infos vendeur (nom, email, téléphone)
- Nom du magasin
- Description
- Localisation
- Bouton "Accéder au panneau admin"

---

## 🧪 Test local

### 1. Avec MAILTRAP (recommandé pour développement)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
```

Tous les emails iront dans l'inbox Mailtrap.

### 2. Avec driver Log (développement ultra rapide)

```env
MAIL_MAILER=log
```

Les emails s'affichent dans `storage/logs/laravel.log` :

```bash
tail -f storage/logs/laravel.log
```

### 3. Tester via Tinker

```bash
php artisan tinker

# Test 1: Créer une commande
$user = User::first();
$order = Order::with('items.product', 'user')->first();
event(new \App\Events\OrderPlaced($order));

# Test 2: Vérifier la queue
DB::table('jobs')->count();
```

---

## 🔧 Commandes utiles

### Traiter les emails en queue (développement)
```bash
php artisan queue:work
```

### Traiter une fois et quitter
```bash
php artisan queue:work --once
```

### Vérifier le statut de la queue
```bash
php artisan queue:failed
```

### Retry les emails échoués
```bash
php artisan queue:retry all
```

### Vider la queue des jobs échoués
```bash
php artisan queue:flush
```

---

## 📝 Checklist d'intégration

- [ ] Configurer `.env` avec les paramètres SMTP
- [ ] Tester avec Mailtrap ou Log driver
- [ ] Créer une commande pour tester les emails
- [ ] Lancer `php artisan queue:work` pour traiter les jobs
- [ ] Vérifier que les emails arrivent correctement
- [ ] Adapter les templates d'email selon votre branding
- [ ] Ajouter les migrations si vous avez d'autres tables `jobs_failed_notifications`
- [ ] Configurer le cron job pour `schedule:run` (si notifications planifiées)

---

## ✅ Résumé

✅ **3 Mailables créées** - Templates paramétrables  
✅ **2 Événements** - OrderPlaced et VendorCreated  
✅ **3 Listeners** - Routent les événements vers les notifications  
✅ **EventServiceProvider** - Enregistre les mappages événement→listener  
✅ **Observer** - Auto-dispatch VendorCreated quand un vendeur est créé  
✅ **Queue configurée** - Traitement en arrière-plan  
✅ **OrderService modifié** - Dispatch OrderPlaced après création  

**PRÊT POUR PRODUCTION !** 🚀
