# DigitalOcean Dropletga joylash

Bu paket **Ubuntu 24.04 LTS Droplet** uchun tayyorlangan: Nginx, PHP 8.3-FPM va PostgreSQL. Frontend va backend bitta domen orqali ishlaydi. Nginx Vue buildni beradi, `/api` va `/up` so‘rovlarini Laravelga yuboradi.

Quyidagi misollarda `SERVER_IP` o‘rniga Droplet IP manzilini, `shifo.example.com` o‘rniga o‘z domeningizni yozing. `deploy` — serverdagi sudo huquqiga ega foydalanuvchi. Bu yo‘riqnoma yangi server va yangi baza uchun.

## 1. Server va domen

DigitalOcean panelida Ubuntu 24.04 LTS Droplet yarating va SSH kalitingizni tanlang. Domenning A yozuvini Dropletning IPv4 manziliga yo‘naltiring. AAAA yozuvi qo‘shilsa, u ham aynan shu Dropletga tegishli bo‘lsin.

PowerShell orqali serverga kiring:

```powershell
ssh root@SERVER_IP
```

Serverda foydalanuvchi yarating:

```bash
adduser deploy
usermod -aG sudo deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
install -m 600 -o deploy -g deploy /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
```

Yangi PowerShell oynasida `ssh deploy@SERVER_IP` bilan kirishni tekshiring. Keyingi Linux buyruqlarini shu foydalanuvchi bilan bajaring.

## 2. Server dasturlari

```bash
sudo apt update
sudo apt install -y nginx postgresql composer unzip php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath
sudo systemctl enable --now nginx php8.3-fpm postgresql
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

DigitalOcean Cloud Firewall ishlatilsa, kiruvchi SSH 22-portni o‘z IP manzilingiz uchun, HTTP 80 va HTTPS 443-portlarni saytga tashrif buyuruvchilar uchun oching. PostgreSQL 5432-porti tashqariga ochilmaydi.

Serverda Node.js kerak emas: ZIP ichida tayyor `frontend/dist` bor.

## 3. ZIPni yuborish

Kompyuteringizdagi PowerShell oynasida:

```powershell
scp "C:\Users\Feruzjon\Desktop\test\Shifo-DigitalOcean.zip" deploy@SERVER_IP:/home/deploy/
```

Serverda:

```bash
sudo install -d -o deploy -g www-data -m 755 /var/www/shifoxona
sudo unzip /home/deploy/Shifo-DigitalOcean.zip -d /var/www
sudo chown -R deploy:www-data /var/www/shifoxona
sudo find /var/www/shifoxona -type d -exec chmod 2755 {} +
cd /var/www/shifoxona
```

ZIP ichidagi yuqori papka `shifoxona`, shuning uchun loyiha aynan `/var/www/shifoxona`ga tushadi. ZIPda `.env`, parollar, ishchi baza, `vendor` va `node_modules` yo‘q.

## 4. PostgreSQL bazasi

```bash
sudo -u postgres psql
```

`psql` oynasida:

```sql
CREATE ROLE shifo WITH LOGIN;
\password shifo
CREATE DATABASE shifo OWNER shifo;
\q
```

`\password` so‘raganda yangi kuchli parol kiriting. Keyingi qadamdagi `DB_PASSWORD`ga shu parol yoziladi.

## 5. Production .env va Laravel

```bash
cd /var/www/shifoxona
cp backend/.env.production.example backend/.env
nano backend/.env
```

Quyidagi qiymatlarni tahrirlang:

```dotenv
APP_URL=https://sizning-domeningiz.uz
DB_DATABASE=shifo
DB_USERNAME=shifo
DB_PASSWORD="yangi_baza_parolingiz"
```

`APP_KEY=`ni birinchi o‘rnatishda bo‘sh qoldiring: skript uni yaratadi. `APP_ENV=production`, `APP_DEBUG=false` va `SESSION_SECURE_COOKIE=true` qiymatlari tayyor. Login HTTPS yoqilgandan keyin ishlatiladi.

```bash
chmod 640 backend/.env
mkdir -p backend/storage/framework/{cache/data,sessions,views} backend/storage/logs backend/bootstrap/cache backend/resources/views
sudo chown -R deploy:www-data backend/storage backend/bootstrap/cache
find backend/storage backend/bootstrap/cache -type d -exec chmod 2770 {} +
find backend/storage backend/bootstrap/cache -type f -exec chmod 660 {} +
umask 0007
bash deploy/deploy.sh
cd backend
php artisan shifo:create-admin
cd ..
```

Admin buyrug‘i ism, email va kamida 12 belgili parol so‘raydi; parol terminalda ko‘rinmaydi. Brauzerda shu hisob bilan kiring va shifoxona, rahbar hamda shifokorlarni qo‘shing.

Productionda seeder faqat viloyatlar, tumanlar va kasalliklarni yaratadi. `admin1@shifo.uz` kabi demo hisoblar va sun’iy bemorlar yangi production bazaga qo‘shilmaydi. Mahalliy demo hisoblari o‘zgarishsiz ishlaydi.

## 6. Nginx

```bash
sudo cp deploy/nginx/shifoxona-api.conf /etc/nginx/snippets/shifoxona-api.conf
sudo cp deploy/nginx/shifoxona.conf /etc/nginx/sites-available/shifoxona
sudo nano /etc/nginx/sites-available/shifoxona
```

`server_name shifo.example.com;` qatorini o‘z domeningizga almashtiring.

```bash
sudo ln -s /etc/nginx/sites-available/shifoxona /etc/nginx/sites-enabled/shifoxona
sudo unlink /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Yangi Ubuntu serverida `default` havolasi mavjud bo‘ladi. Oldindan sozlangan serverda boshqa saytlarning konfiguratsiyasini o‘chirmang. Loyiha boshqa papkada bo‘lsa, ikkala Nginx faylidagi `/var/www/shifoxona` yo‘llarini ham almashtiring.

## 7. HTTPS

Domen DNS yozuvi server IP manziliga yechilgandan keyin:

```bash
sudo apt install -y snapd
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/local/bin/certbot
sudo certbot --nginx -d sizning-domeningiz.uz --redirect
sudo certbot renew --dry-run
```

Certbot HTTPS sertifikatini va HTTP → HTTPS yo‘naltirishini sozlaydi. Nginx konfiguratsiyasi HTTPdan boshlanadi; mavjud bo‘lmagan sertifikat fayliga murojaat qilmaydi.

## 8. Tekshirish

```bash
curl -I https://sizning-domeningiz.uz
curl -fsS https://sizning-domeningiz.uz/up
curl -fsS https://sizning-domeningiz.uz/api/csrf
cd /var/www/shifoxona/backend
php artisan migrate:status
```

Saytda admin bilan login qiling. Shifoxona → rahbar → shifokor → bemor yarating. Sahifani yangilaganda Vue ichki manzili ochilishi va login sessiyasi saqlanishini tekshiring.

Xatolarni ko‘rish:

```bash
sudo tail -n 60 /var/log/nginx/error.log
sudo journalctl -u php8.3-fpm -n 60 --no-pager
ls /var/www/shifoxona/backend/storage/logs
```

419: domen, HTTPS va cookie sozlamalarini tekshiring. 502: `php8.3-fpm` holatini tekshiring. Baza xatosi: `.env`dagi PostgreSQL qiymatlarini tekshirib, `php artisan config:clear` va `php artisan optimize` bajaring.

## Keyingi yangilash

Kompyuteringizda frontendni qayta build qiling:

```powershell
cd shifoxona/frontend
npm ci
npm run build
```

Serverdagi `backend/.env`, `backend/storage` va PostgreSQL bazasini saqlang. Kod va `frontend/dist`ni yangilang. Avval bazadan zaxira nusxa oling:

```bash
sudo -u postgres pg_dump -Fc shifo > /home/deploy/shifo-before-update.dump
cd /var/www/shifoxona
umask 0007
bash deploy/deploy.sh
sudo systemctl reload php8.3-fpm
```

`deploy.sh` mavjud `APP_KEY`ni saqlaydi, oddiy `migrate` bajaradi va asosiy ma’lumotlarni yangilaydi. `migrate:fresh` ishlatilmaydi. SSL yoqilgandan keyin Nginx server konfiguratsiyasini ZIPdagi HTTP namunasi bilan qayta almashtirmang. Baza zaxirasini alohida joyga ham ko‘chiring.

Rasmiy hujjatlar: [Laravel 12 deployment](https://laravel.com/docs/12.x/deployment), [DigitalOcean SSH ulanishi](https://docs.digitalocean.com/products/droplets/how-to/connect-with-ssh/), [DigitalOcean SSL qo‘llanmasi](https://docs.digitalocean.com/support/how-do-i-install-an-ssl-certificate-on-a-droplet/), [Certbot Nginx o‘rnatish](https://certbot.eff.org/instructions?ws=nginx&os=snap).
