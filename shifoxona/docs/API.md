# JSON API

Laravel API `backend`, Vue interfeysi `frontend` papkasida joylashgan. Mahalliy muhitda Vue 5173-portda, Laravel 8000-portda ishlaydi. Vite `/api` so‘rovlarini Laravelga uzatadi. Brauzer sessiya cookie’larini yuboradi; yozish so‘rovlarida `X-CSRF-TOKEN` talab qilinadi. Token `GET /api/csrf` orqali olinadi, login/logout javobidan yangilanadi. Barcha so‘rovlarda `Accept: application/json` yuboring.

## Sessiya

| Metod | Manzil | Vazifa |
| --- | --- | --- |
| GET | `/api/csrf` | Sessiyani boshlash va CSRF token olish; kirish talab qilinmaydi |
| POST | `/api/login` | Email va parol bilan kirish; `user` va `csrf_token` qaytadi |
| GET | `/api/me` | Joriy foydalanuvchi va shifoxonasi |
| POST | `/api/logout` | Sessiyani bekor qilish va CSRF tokenini yangilash |
| GET | `/api/lookups` | Viloyat, tuman, kasallik, ruxsatdagi shifoxona va shifokor ro‘yxatlari |
| GET | `/api/dashboard` | Ruxsatdagi statistika, oxirgi 7 kun va so‘nggi qabullar |

## Resurslar

Quyidagi har bir resurs `GET /api/{resurs}`, `POST /api/{resurs}`, `GET /api/{resurs}/{id}`, `PUT/PATCH /api/{resurs}/{id}`, `DELETE /api/{resurs}/{id}` manzillariga ega.

| Resurs | Maydonlar | Ruxsat |
| --- | --- | --- |
| `patients` | `first_name`, `last_name`, `middle_name`, `passport`, `pin`, `region_id`, `district_id`, `address`, `disease_id`, `registered_at`, `status`, `notes`; admin uchun `doctor_id` | Admin: barcha kartalar CRUD; shifokor: o‘z kartalari CRUD; rahbar: o‘z shifoxonasi kartalarini o‘qish |
| `doctors` | `name`, `email`, `password`, `hospital_id`, `phone`, `specialization` | Admin: barcha shifokorlar; rahbar: o‘z shifoxonasi shifokorlari |
| `directors` | `name`, `email`, `password`, `hospital_id`, `phone` | Admin |
| `hospitals` | `name`, `region_id`, `district_id`, `tin` | Admin: CRUD; rahbar: o‘z shifoxonasini o‘qish va tahrirlash |

`patients` va xodim/hospital ro‘yxatlari 10 tadan sahifalanadi. Javob Laravel paginator shaklida: `data`, `total`, `current_page`, `last_page`, `from`, `to` va havolalar.

## Filtrlar

`patients`: `search`, `status`, `disease_id`, `doctor_id`, `hospital_id`, `date` (`YYYY-MM-DD`), `page`.

`doctors` va `directors`: `search`, `hospital_id`, `page`.

`hospitals`: `search`, `page`.

Filtrlar foydalanuvchi vakolatini kengaytirmaydi. Masalan, boshqa shifoxona identifikatorini yuborish rahbarga uning bemorlarini ko‘rsatmaydi.

## Bemor yaratish namunasi

Quyidagi identifikatorlarni `/api/lookups` javobidan tanlang. `registered_at` Toshkent vaqti bilan yuboriladi. Bazada pasport va PIN matn sifatida saqlanadi; boshidagi nollar yo‘qolmaydi.

```json
{
  "first_name": "Ali",
  "last_name": "Valiyev",
  "middle_name": "Anvar o‘g‘li",
  "passport": "AB1234567",
  "pin": "01234567890123",
  "region_id": 11,
  "district_id": 139,
  "address": "Yunusobod, 10-uy",
  "disease_id": 1,
  "registered_at": "2026-10-07T09:30",
  "status": "waiting",
  "notes": "Birinchi qabul"
}
```

`status`: `waiting` (kutilmoqda), `in_progress` (qabulda), `completed` (yakunlangan).

Shifokor yuborgan `doctor_id`, `hospital_id` va `role` qiymatlari bemor egaligini o‘zgartirmaydi: shifokor va shifoxona serverdagi hisobdan olinadi. Admin tanlagan shifokorning shifoxonasi server tomonidan belgilanadi. Rahbar shifokor yaratsa, shifoxona ham rahbar hisobidan olinadi. Xodim rolini resurs manzili belgilaydi.

## Javob kodlari

| Kod | Ma’nosi |
| --- | --- |
| 200 | O‘qish yoki yangilash bajarildi |
| 201 | Yangi yozuv yaratildi |
| 204 | Yozuv o‘chirildi |
| 401 | Tizimga kirilmagan |
| 403 | Amalga ruxsat yo‘q |
| 404 | Yozuv yoki API manzili topilmadi |
| 419 | CSRF token/sessiya mos emas |
| 422 | Validatsiya xatosi; `errors` obyektida maydon xatolari |
| 429 | Kirish urinishlari chegarasi oshdi |

Yangi xodim paroli kamida 8 belgi. Tahrirlashda parolni bo‘sh qoldirish mavjud parolni saqlaydi. Bemorli shifokorni o‘chirish yoki boshqa shifoxonaga ko‘chirish, xodim/bemorli shifoxonani o‘chirish 422 qaytaradi.
