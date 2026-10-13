import logging
from aiogram import Router, F, types
from aiogram.filters import CommandStart, Command
from aiogram.fsm.context import FSMContext

from config import ADMIN_IDS
from database import add_or_update_contact, get_stats
from keyboards.default import get_admin_main_kb, get_contact_share_kb
from sms_service import sms_service

router = Router()
logger = logging.getLogger(__name__)

def is_admin(user_id: int) -> bool:
    # Agar ADMIN_IDS bo'sh bo'lsa, birinchi start bosgan odam admin bo'lishi ham mumkin
    if not ADMIN_IDS:
        return True
    return user_id in ADMIN_IDS

@router.message(CommandStart())
async def cmd_start(message: types.Message, state: FSMContext):
    await state.clear()
    user_id = message.from_user.id
    first_name = message.from_user.first_name or "Foydalanuvchi"

    if is_admin(user_id):
        text = (
            f"Assalomu alaykum, <b>{first_name}</b> (Admin)!\n\n"
            "SMS va Telegram xabar yuborish tizimiga xush kelibsiz.\n"
            "Quyidagi menyu orqali boshqarishingiz mumkin:\n\n"
            "• <b>✉️ Xabar yuborish</b> — Kontaktlarni belgilash va SMS + Telegram xabar yuborish\n"
            "• <b>👥 Kontaktlar</b> — Kontaktlar ro'yxati, qo'shish va o'chirish\n"
            "• <b>📊 Statistika</b> — Baza va yuborilgan xabarlar statistikasi\n"
            "• <b>⚙️ SMS Gateway holati</b> — Android telefonga ulanish holati"
        )
        await message.answer(text, reply_markup=get_admin_main_kb(), parse_mode="HTML")
    else:
        # Oddiy foydalanuvchi / mijoz start bosganda
        text = (
            f"Assalomu alaykum, <b>{first_name}</b>!\n\n"
            "Tizimdan xabarlar va bildirishnomalarni Telegram hamda SMS orqali qabul qilish uchun "
            "iltimos, quyidagi tugma orqali telefon raqamingizni yuboring 👇"
        )
        await message.answer(text, reply_markup=get_contact_share_kb(), parse_mode="HTML")

@router.message(F.contact)
async def handle_contact_share(message: types.Message):
    """Foydalanuvchi o'z kontaktini yuborganda uni bazaga yozish"""
    contact = message.contact
    user_id = message.from_user.id

    # Agar boshqa birovning kontaktini ulashgan bo'lsa tekshiramiz
    if contact.user_id and contact.user_id != user_id:
        await message.answer("Iltimos, faqat o'zingizning telefon raqamingizni ulashing.")
        return

    name = f"{contact.first_name or ''} {contact.last_name or ''}".strip()
    if not name:
        name = message.from_user.full_name or "Mijoz"

    phone = contact.phone_number
    username = message.from_user.username

    contact_id = await add_or_update_contact(
        name=name,
        phone=phone,
        telegram_id=user_id,
        username=username
    )

    await message.answer(
        f"✅ Rahmat, <b>{name}</b>!\n"
        f"Telefon raqamingiz (<code>{phone}</code>) muvaffaqiyatli saqlandi va Telegram hisobingizga bog'landi.",
        reply_markup=types.ReplyKeyboardRemove(),
        parse_mode="HTML"
    )

@router.message(F.text == "📊 Statistika")
async def show_stats(message: types.Message):
    if not is_admin(message.from_user.id):
        return

    stats = await get_stats()
    text = (
        "<b>📊 Tizim statistikasi:</b>\n\n"
        f"👥 Jami kontaktlar: <b>{stats['total_contacts']}</b> ta\n"
        f"📱 Telegram bog'langan: <b>{stats['tg_linked_contacts']}</b> ta\n"
        f"📨 Yuborilgan kampaniyalar: <b>{stats['total_broadcasts']}</b> marta\n"
    )
    await message.answer(text, parse_mode="HTML")

@router.message(F.text == "⚙️ SMS Gateway holati")
async def show_sms_status(message: types.Message):
    if not is_admin(message.from_user.id):
        return

    alive, status_msg = await sms_service.check_health()
    icon = "🟢" if alive else "🔴"
    
    mode_text = {
        "mock": "Test rejimi (Mock - SMS hisobisiz sinov)",
        "local": "Local Wi-Fi / IP rejimi (Android telefon)",
        "cloud": "Cloud API rejimi (sms-gate.app)"
    }.get(sms_service.mode, sms_service.mode)

    text = (
        f"<b>⚙️ SMS Gateway Sozlamalari:</b>\n\n"
        f"Rejim: <b>{mode_text}</b>\n"
        f"Holat: {icon} <b>{status_msg}</b>\n\n"
        "<i>Android SMS Gateway orqali telefoningizdagi SIM kartadan to'g'ridan-to'g'ri SMS yuboriladi.</i>"
    )
    await message.answer(text, parse_mode="HTML")

@router.message(F.text == "❌ Bekor qilish")
async def cancel_action(message: types.Message, state: FSMContext):
    await state.clear()
    await message.answer("Amal bekor qilindi.", reply_markup=get_admin_main_kb())
