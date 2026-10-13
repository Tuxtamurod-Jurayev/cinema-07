from aiogram.types import ReplyKeyboardMarkup, KeyboardButton

def get_admin_main_kb() -> ReplyKeyboardMarkup:
    """Admin bosh menyusi"""
    keyboard = [
        [
            KeyboardButton(text="✉️ Xabar yuborish"),
            KeyboardButton(text="👥 Kontaktlar")
        ],
        [
            KeyboardButton(text="📊 Statistika"),
            KeyboardButton(text="⚙️ SMS Gateway holati")
        ]
    ]
    return ReplyKeyboardMarkup(
        keyboard=keyboard,
        resize_keyboard=True
    )

def get_contact_share_kb() -> ReplyKeyboardMarkup:
    """Oddiy foydalanuvchilar o'z raqamini yuborishi uchun klaviatura"""
    keyboard = [
        [
            KeyboardButton(text="📲 Telefon raqamimni ulashish", request_contact=True)
        ]
    ]
    return ReplyKeyboardMarkup(
        keyboard=keyboard,
        resize_keyboard=True,
        one_time_keyboard=True
    )

def get_cancel_kb() -> ReplyKeyboardMarkup:
    """Bekor qilish klaviaturasi"""
    keyboard = [
        [KeyboardButton(text="❌ Bekor qilish")]
    ]
    return ReplyKeyboardMarkup(
        keyboard=keyboard,
        resize_keyboard=True
    )
