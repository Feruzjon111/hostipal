# Shifo — Laravel backend va Vue frontend

Loyiha ikki alohida qismga ajratilgan:

```text
shifoxona/
  backend/                  Laravel API
    app/Http/Controllers/   Login va CRUD amallari
    app/Models/             Baza modellari
    app/Policies/           Bemorlar uchun ruxsatlar
    app/Support/            Rolga qarab ro‘yxatlarni cheklash
    routes/api.php          API manzillari
    database/               Migration, seeder va SQLite baza
    tests/Feature/          Backend testlari
    artisan
    composer.json

  frontend/                 Vue ilovasi
    src/pages/              Kirish, bemorlar, shifokorlar va boshqa sahifalar
    src/components/         Forma, jadval, modal va boshqa qismlar
    src/lib/api.js          Backendga so‘rov yuborish
    src/lib/state.js        Foydalanuvchi va umumiy ma’lumotlar
    src/assets/style.css    Interfeys stillari
    src/router.js           Sahifalar manzillari
    src/main.js             Vue ilovasini boshlash
    index.html
    package.json
    vite.config.js

  docs/                     API va topshiriq tavsifi
  Setup.ps1                 O‘rnatish
  Start-Shifo.cmd            Ikkala serverni ishga tushirish
```

Frontend fayllari Laravel ichiga yig‘ilmaydi. Vue o‘z `dist` papkasiga build qilinadi. Laravel faqat API va ma’lumotlar bazasi bilan ishlaydi.

## Ishga tushirish

DigitalOcean uchun tayyor paket: **`Shifo-DigitalOcean.zip`**. Serverga o‘rnatish bosqichlari [DigitalOcean yo‘riqnomasida](deploy/DIGITALOCEAN.md). Arxiv frontend build, backend kodi va production konfiguratsiyalarini o‘z ichiga oladi.

Shu kompyuterda kutubxonalar va baza tayyor. `Start-Shifo.cmd`ni oching: backend va frontend uchun ikkita terminal ochiladi. Brauzerda **[http://127.0.0.1:5173](http://127.0.0.1:5173)** manziliga kiring.

Qo‘lda ishlatish uchun birinchi terminal:

```powershell
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

Ikkinchi terminal:

```powershell
cd frontend
npm run dev
```

- Frontend: `http://127.0.0.1:5173`
- Backend API: `http://127.0.0.1:8000/api`
- Serverni to‘xtatish: terminalda `Ctrl+C`.

8000-portda interfeys emas, API ishlaydi. Bemorlar va boshqaruv sahifalari 5173-portda ochiladi.

## Test hisoblari

Barcha hisoblar uchun parol: **`Password123!`**.

| Rol | Birinchi hisob | Ikkinchi hisob |
| --- | --- | --- |
| Admin | `admin1@shifo.uz` | `admin2@shifo.uz` |
| Rahbar | `director1@shifo.uz` | `director2@shifo.uz` |
| Shifokor | `doctor1@shifo.uz` | `doctor2@shifo.uz` |

Birinchi rahbar va shifokor Toshkentdagi, ikkinchi hisoblar Samarqanddagi shifoxonaga tegishli. Seeder har shifokorga 12 tadan sun’iy bemor yaratadi.

## Boshqa kompyuterga o‘rnatish

PHP 8.2+, Composer, Node.js 22.12+ yoki 24 va npm kerak. PHP’da SQLite, mbstring, openssl, fileinfo, dom, xml, curl va zip kengaytmalari yoqilgan bo‘lsin.

Loyiha papkasida:

```powershell
powershell -ExecutionPolicy Bypass -File .\Setup.ps1
```

Skript backend kutubxonalarini o‘rnatadi, `.env` va bazani tayyorlaydi, frontend kutubxonalarini o‘rnatib build qiladi. Mavjud ishchi baza o‘chirilmaydi.

Qo‘lda backendni o‘rnatish:

```powershell
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File
php artisan migrate --seed
```

`.env` yoki SQLite fayli mavjud bo‘lsa, ularni qayta yaratmang. Linux/macOS’da nusxalash uchun `cp`, bo‘sh baza faylini yaratish uchun `touch` ishlating.

Frontendni o‘rnatish:

```powershell
cd frontend
npm ci
npm run build
```

Backend boshqa portda bo‘lsa, `frontend/.env.example`dan `.env` yarating va `BACKEND_URL`ni o‘zgartiring. `npm run dev`ni qayta ishga tushiring.

## Kod qanday ishlaydi?

Vue sahifasi `src/lib/api.js` orqali so‘rov yuboradi. Vite `/api` so‘rovlarini Laravel serveriga uzatadi. Laravel route controllerga o‘tadi; controller rolni tekshirib, bazadan ma’lumot oladi va JSON qaytaradi.

Login uchun email va parol ishlatiladi. Foydalanuvchi Laravel sessiyasida saqlanadi. Yozish so‘rovlaridan oldin `/api/csrf` orqali token olinadi. Parollar hashlangan, ruxsatlar backendda tekshiriladi.

Kodda oddiy funksiyalar, `if`, `for`, `try/catch` va standart Laravel controllerlari ishlatilgan. Shifokor va rahbar uchun alohida controller bor. O‘chirish tasdig‘i brauzerning oddiy `confirm` oynasi orqali ko‘rsatiladi.

Admin barcha ma’lumotlarni boshqaradi. Rahbar o‘z shifoxonasi bemorlarini ko‘radi va shifokorlarini boshqaradi. Shifokor o‘z bemorlarini qo‘shadi, ko‘radi, tahrirlaydi va o‘chiradi. Pasport, PIN, STIR va viloyat–tuman mosligi tekshiriladi.

## Testlar

Backend papkasida:

```powershell
php artisan test
```

Frontend papkasida:

```powershell
npm run build
npm run test:e2e
```

PHP testlari xotiradagi bazada, brauzer testlari alohida `backend/database/e2e.sqlite` bazasida ishlaydi. Ishchi baza saqlanadi. Brauzer testlari 8001 va 5174 portlarini ishlatadi.

Windows’da Chrome topilsa, testlar undan foydalanadi. Boshqa kompyuterda `npx playwright install chromium`ni bajaring.

Tayyor frontendni mahalliy ko‘rish uchun `npm run preview` ishlating: `http://127.0.0.1:4173`. Backend ham ishlab turishi kerak. Hostingda frontendning `dist`, backendning `public` papkasi xizmat qiladi; `/api` uchun Laravelga reverse proxy sozlanadi.

## Qo‘shimcha hujjatlar

- [DigitalOcean Dropletga joylash](deploy/DIGITALOCEAN.md) — production `.env`, Nginx, PostgreSQL va HTTPS.
- [API tavsifi](docs/API.md)
- [Topshiriq talablariga moslik](docs/TOPSHIRIQ.md)
- [Tekshiruv natijalari](docs/TEKSHIRUV.md)
- [Backend yo‘riqnomasi](backend/README.md)
- [Frontend yo‘riqnomasi](frontend/README.md)

ZIP arxivga manba kod va frontend build kiritilgan. `vendor`, `node_modules`, `.env` va ishchi baza kiritilmagan; ularni `Setup.ps1` tayyorlaydi.
