# 🚀 Telegram Bot: SMS va Telegram orqali xabar yuborish tizimi

Ushbu bot orqali siz:
1. 👥 **Kontaktlar bazasini boshqarishingiz** (qo'lda qo'shish yoki mijozlar botga kirib `/start` bosib telefon raqamini ulashganda avtomatik Telegram profiliga bog'lash).
2. ☑️ **Kontaktlarni interaktiv belgilashingiz** (Checkbox ✅/⬜, barchasini tanlash, sahifalash).
3. 💬 **Xabar yozib**, bir vaqtning o'zida ham **SMS** (telefon raqamiga), ham **Telegram** (profiliga) to'g'ridan-to'g'ri jo'natishingiz mumkin.
4. 📡 **SMS Gateway** sifatida quyidagilarni qo'llab-quvvatlaydi:
   - [capcom6/android-sms-gateway](https://github.com/capcom6/android-sms-gateway) (Android telefoningizdagi SIM kartadan foydalanish)
   - [pentagridsec/smsgate](https://github.com/pentagridsec/smsgate) (GSM USB modemlar hovuzi / XML-RPC)
   - Test (Mock) rejimi — API kalitsiz, bepul darhol sinab ko'rish uchun.

---

## 📁 Loyiha tarkibi

```text
sms/
├── bot.py                # Botning asosiy kirish fayli
├── config.py             # Sozlamalar va .env ni yuklovchi modul
├── database.py           # SQLite ma'lumotlar bazasi (kontaktlar va xabarlar tarixi)
├── sms_service.py        # SMS Gateway (Android REST & Pentagrid XML-RPC)
├── requirements.txt      # Kutubxonalar ro'yxati
├── .env                  # Sizning shaxsiy kalitlaringiz va sozlamalaringiz
├── .env.example          # Namuna sozlamalar
├── handlers/
│   ├── start.py          # /start va avtorizatsiya handlerlari
│   ├── contacts.py       # Kontaktlar boshqaruvi (qo'shish, ko'rish, o'chirish)
│   └── broadcast.py      # Kontaktlarni belgilash va Telegram + SMS yuborish
└── keyboards/
    ├── default.py        # Asosiy tugmalar (Reply)
    └── inline.py         # Ko'p tanlovli (checkbox) tugmalar
```

---

## ⚡️ Tezkor ishga tushirish (3 qadam)

### 1-qadam: Kutubxonalarni o'rnatish
Terminal (PowerShell yoki CMD) orqali ushbu papkaga kiring va o'rnating:
```bash
pip install -r requirements.txt
```

### 2-qadam: `.env` faylini sozlash
`.env` faylini oching va Telegram bot tokeningizni kiriting:
```ini
# @BotFather orqali olingan token
BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz

# O'z Telegram ID ingiz (Telegramda @userinfobot ga yozib bilib olasiz)
ADMIN_IDS=12345678

# SMS provayder (boshlanishiga test uchun 'mock' qoldiring yoki 'android_local')
SMS_PROVIDER=mock
```

### 3-qadam: Botni yurgizish
```bash
python bot.py
```

---

## 📱 SMS Gateway sozlash bo'yicha qo'llanma

### Variant 1: `capcom6/android-sms-gateway` (Android telefon)
1. Telefoningizga [Android SMS Gateway APK](https://github.com/capcom6/android-sms-gateway/releases) ni o'rnating.
2. Ilovani ochib, serverni yoqing (`Start`).
3. Ilova ekranda IP manzilini ko'rsatadi (masalan, `http://192.168.1.50:8080`) va login/parol beradi.
4. `.env` fayliga yozing:
   ```ini
   SMS_PROVIDER=android_local
   SMS_ANDROID_LOCAL_URL=http://192.168.1.50:8080
   SMS_ANDROID_USERNAME=admin
   SMS_ANDROID_PASSWORD=sizning_parolingiz
   SMS_SIM_SLOT=1
   ```

### Variant 2: `pentagridsec/smsgate` (GSM Modemlar)
1. Serverda `pentagridsec/smsgate` xizmati ishlab turgan bo'lsa:
2. `.env` fayliga yozing:
   ```ini
   SMS_PROVIDER=smsgate
   SMSGATE_URL=https://127.0.0.1:8443/
   SMSGATE_TOKEN=sizning_api_tokeningiz
   SMSGATE_VERIFY_SSL=False
   ```

---

## 💡 Botdan qanday foydalaniladi?

1. **Admin sifatida kiring**: `/start` buyrug'ini bering.
2. **Kontakt qo'shish**:
   - `👥 Kontaktlar` -> `➕ Yangi kontakt qo'shish` orqali ism, telefon raqam va telegramini kiriting.
   - Yoki do'stlaringizga/mijozlaringizga bot havolasini yuboring: ular `/start` bosib `📲 Telefon raqamimni ulashish` tugmasini bossa, bot ularning profilini raqami bilan avtomatik bog'laydi.
3. **Xabar yuborish (SMS + Telegram)**:
   - `✉️ Xabar yuborish` tugmasini bosing.
   - `☑️ Kontaktlarni tanlash (Belgilash)` ni tanlang.
   - Ekranda kontaktlar ro'yxati chiqadi — kerakli odamlar ustiga bosib belgilang (`✅`).
   - `🚀 Tayyor` tugmasini bosing.
   - Xabar matnini yozib yuboring.
   - Yuborish usulini tanlang: **`📱 Telegram + 💬 SMS (Ikkalasiga)`**.
   - Tasdiqlang va bot bir zumda belgilangan kontaktlarga xabaringizni yetkazadi!
