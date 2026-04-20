# 🚀 Mise en place des notifications email avec Brevo - Étapes

## 1️⃣ Configuration de base avec Brevo

### Option 1: Développement (Log driver - emails dans les logs)
```bash
# Dans .env
MAIL_MAILER=log
```

### Option 2: Production (Brevo SMTP - emails réels)
```env
# Générer une clé SMTP sur https://app.brevo.com/settings/account/smtp
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=a6a28f001@smtp-brevo.com (votre email Brevo)
MAIL_PASSWORD=xsmtpsib-xxxxx-xxxxx (généré depuis Brevo)
MAIL_FROM_ADDRESS="noreply@afrishop.com"
MAIL_FROM_NAME="AfriShop"
QUEUE_CONNECTION=database
FRONTEND_URL=http://localhost:3000
```

### Étapes pour générer une clé SMTP Brevo :

1. 🔗 Accédez à [https://app.brevo.com](https://app.brevo.com)
2. 📋 Allez à **Paramètres → Compte → SMTP**
3. 🔑 Cliquez sur **"Générer une nouvelle clé SMTP"**
4. 📝 Copiez le **nom d'utilisateur SMTP**
5. 📝 Copiez le **mot de passe SMTP** (généré automatiquement)
6. ✏️ Remplacez `MAIL_USERNAME` et `MAIL_PASSWORD` dans votre `.env`

✅ **Configuration Brevo prête !**

---

## 2️⃣ Vérifier les migrations

```bash
# Vérifier que la table 'jobs' existe (pour la queue)
php artisan migrate --list | grep jobs

# Si pas de migration pour jobs, créer:
php artisan queue:table
php artisan migrate
```

---

## 3️⃣ Tester les emails

### Test 1: Log driver (aucune configuration SMTP)
```bash
# Configurer .env
MAIL_MAILER=log

# Créer une commande pour tester
php artisan email:test

# Vérifier les logs
tail -f storage/logs/laravel.log
```

### Test 2: Brevo SMTP (emails réels en production)
```bash
# Configurer .env avec les paramètres Brevo
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=a6a28f001@smtp-brevo.com
MAIL_PASSWORD=xsmtpsib-xxxxxxxxxxxxx-xxxxxxxxxxxxx

# Lancer le worker queue
php artisan queue:work

# Créer une commande (dans une autre terminal)
php artisan email:test

# Vérifier Brevo: https://app.brevo.com/campaigns/logs
```

---

## 4️⃣ Commandes utiles

### Tester les notifications
```bash
# Tous les types
php artisan email:test

# Seulement confirmation de commande
php artisan email:test order

# Seulement notification vendeur
php artisan email:test vendor

# Seulement vérification vendeur
php artisan email:test verification
```

### Gérer la queue

```bash
# Traiter tous les jobs en queue
php artisan queue:work

# Traiter UNE seule fois et quitter
php artisan queue:work --once

# Voir les jobs en attente
DB::table('jobs')->count();

# Voir les jobs qui ont échoué
php artisan queue:failed

# Retry tous les jobs échoués
php artisan queue:retry all

# Vider les failed jobs
php artisan queue:flush
```

---

## 5️⃣ Flow réel en production

1. **Client crée une commande**
   ```
   POST /api/orders
   → OrderService->createOrderFromCart()
   → Order créée
   → OrderPlaced::dispatch($order)
   → Listeners ajoutent les notifications à la queue
   ```

2. **Worker traite la queue**
   ```bash
   php artisan queue:work --daemon
   ```

3. **Emails envoyés**
   - ✅ Confirmation au client
   - ✅ Notification aux vendeurs
   - ✅ Notification aux admins (si nouveau vendeur)

---

### Production avec Brevo

**Brevo** (recommandé pour production):
```env
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@brevo.com
MAIL_PASSWORD=xsmtpsib-xxxxxxxxxxxxx-xxxxxxxxxxxxx
```

**Avantages Brevo :**
- ✅ 300 emails/jour gratuits
- ✅ Interface intuitif
- ✅ Support multilingue
- ✅ Fiable et rapide
- ✅ Dashboard d'analytics
- ✅ Gestion des listes d'emails

**Mailgun**:
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.example.com
MAILGUN_SECRET=your_secret
```

### 2. Configurer le queue worker

```bash
# Démarrer le worker en arrière-plan
nohup php artisan queue:work --daemon &

# Ou utiliser Supervisor (recommandé)
# Copier la config dans /etc/supervisor/conf.d/laravel.conf
```

**Fichier Supervisor** :
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

### 3. Configurer les cron jobs (si planification)

```bash
# Dans Kernel.php
$schedule->command('queue:restart')->everyMinute();
```

### 4. Monitoring

```bash
# Vérifier le statut du worker
supervisorctl status laravel-worker

# Voir les erreurs
tail -f storage/logs/worker.log
```

---

## 📊 Arborescence des fichiers créés

```
backend/
├── app/
│   ├── Notifications/
│   │   ├── OrderConfirmation.php          ✅ CRÉÉ
│   │   ├── VendorNewOrder.php             ✅ CRÉÉ
│   │   └── VendorVerificationRequest.php  ✅ CRÉÉ
│   ├── Events/
│   │   ├── OrderPlaced.php                ✅ MODIFIÉ
│   │   └── VendorCreated.php              ✅ CRÉÉ
│   ├── Listeners/
│   │   ├── SendOrderConfirmation.php      ✅ MODIFIÉ
│   │   ├── NotifyVendor.php               ✅ MODIFIÉ
│   │   └── SendVendorVerificationNotification.php ✅ CRÉÉ
│   ├── Observers/
│   │   └── VendeurObserver.php            ✅ CRÉÉ
│   ├── Providers/
│   │   ├── EventServiceProvider.php       ✅ CRÉÉ
│   │   └── AppServiceProvider.php         ✅ MODIFIÉ
│   ├── Services/
│   │   └── Order/
│   │       └── OrderService.php           ✅ MODIFIÉ
│   └── Console/
│       └── Commands/
│           └── TestEmailNotifications.php ✅ CRÉÉ
├── NOTIFICATIONS_GUIDE.md                 ✅ CRÉÉ
└── .env.mail.example                      ✅ CRÉÉ
```

---

## 🐛 Dépannage

### Les emails ne s'envoient pas

1. **Vérifier la configuration SMTP**
   ```bash
   php artisan tinker
   > config('mail')
   ```

2. **Vérifier la queue**
   ```bash
   > DB::table('jobs')->get()
   ```

3. **Lancer le worker**
   ```bash
   php artisan queue:work
   ```

4. **Vérifier les logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Erreur: "SMTP Error"

- Vérifier le host/port SMTP
- Vérifier les credentials
- Vérifier le firewall (port 2525 ou 587)
- Essayer avec log driver d'abord

### Les admins ne reçoivent pas les emails

- Vérifier qu'il existe au moins un utilisateur avec `role = 'admin'`
- Tester avec: `php artisan email:test verification`

---

## ✅ Checklist finale

- [ ] Configuration `.env` avec SMTP valide
- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] Queue configurée (`QUEUE_CONNECTION=database`)
- [ ] Commande test créée (`php artisan email:test`)
- [ ] Worker en fonction (`php artisan queue:work`)
- [ ] Logs vérifiant les emails (`tail -f storage/logs/laravel.log`)
- [ ] Bouton "test" en admin pour vérifier les configs
- [ ] Ajouter les variables `.env` à votre `.gitignore`
- [ ] Documentation partagée avec l'équipe

---

**C'est bon ! Les notifications email sont maintenant intégrées.** 🎉
