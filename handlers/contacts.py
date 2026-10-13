from aiogram import Router, F, types
from aiogram.fsm.context import FSMContext
from aiogram.fsm.state import State, StatesGroup
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton

from database import (
    get_all_contacts,
    get_contact_by_id,
    delete_contact,
    add_or_update_contact,
    normalize_phone,
)
from keyboards.default import get_admin_main_kb, get_cancel_kb
from keyboards.inline import get_contact_actions_kb

router = Router()

class AddContactState(StatesGroup):
    waiting_for_name = State()
    waiting_for_phone = State()
    waiting_for_tg = State()

def get_contacts_menu_kb() -> types.ReplyKeyboardMarkup:
    return types.ReplyKeyboardMarkup(
        keyboard=[
            [types.KeyboardButton(text="➕ Yangi kontakt qo'shish")],
            [types.KeyboardButton(text="📋 Barcha kontaktlar ro'yxati")],
            [types.KeyboardButton(text="🔙 Asosiy menyu")]
        ],
        resize_keyboard=True
    )

@router.message(F.text == "👥 Kontaktlar")
async def contacts_menu(message: types.Message):
    await message.answer(
        "👥 <b>Kontaktlar bo'limi:</b>\n\n"
        "Quyidagi amallardan birini tanlang:",
        reply_markup=get_contacts_menu_kb(),
        parse_mode="HTML"
    )

@router.message(F.text == "🔙 Asosiy menyu")
async def back_to_main(message: types.Message, state: FSMContext):
    await state.clear()
    await message.answer("Asosiy menyu:", reply_markup=get_admin_main_kb())

# --- KONTAKT QO'SHISH ---
@router.message(F.text == "➕ Yangi kontakt qo'shish")
async def start_add_contact(message: types.Message, state: FSMContext):
    await state.set_state(AddContactState.waiting_for_name)
    await message.answer(
        "Kontakt ismini kiriting (masalan: <i>Ali Valiyev</i>):",
        reply_markup=get_cancel_kb(),
        parse_mode="HTML"
    )

@router.message(AddContactState.waiting_for_name)
async def process_name(message: types.Message, state: FSMContext):
    if message.text == "❌ Bekor qilish":
        await state.clear()
        await message.answer("Bekor qilindi.", reply_markup=get_contacts_menu_kb())
        return

    name = message.text.strip()
    if len(name) < 2:
        await message.answer("Ism juda qisqa. Iltimos, to'liq ism kiriting:")
        return

    await state.update_data(name=name)
    await state.set_state(AddContactState.waiting_for_phone)
    await message.answer(
        f"<b>{name}</b> uchun telefon raqamini kiriting:\n(Masalan: <code>+998901234567</code>)",
        parse_mode="HTML"
    )

@router.message(AddContactState.waiting_for_phone)
async def process_phone(message: types.Message, state: FSMContext):
    if message.text == "❌ Bekor qilish":
        await state.clear()
        await message.answer("Bekor qilindi.", reply_markup=get_contacts_menu_kb())
        return

    raw_phone = message.text.strip()
    clean_phone = normalize_phone(raw_phone)
    if len(clean_phone) < 7:
        await message.answer("Telefon raqam noto'g'ri ko'rinadi. Qayta kiriting (masalan: +998901234567):")
        return

    await state.update_data(phone=clean_phone)
    await state.set_state(AddContactState.waiting_for_tg)
    await message.answer(
        "Foydalanuvchining Telegram username yoki ID sini kiriting:\n"
        "(Agar bilmasangiz, <code>/skip</code> deb yozing yoki o'tkazib yuboring):",
        parse_mode="HTML"
    )

@router.message(AddContactState.waiting_for_tg)
async def process_tg(message: types.Message, state: FSMContext):
    if message.text == "❌ Bekor qilish":
        await state.clear()
        await message.answer("Bekor qilindi.", reply_markup=get_contacts_menu_kb())
        return

    data = await state.get_data()
    name = data["name"]
    phone = data["phone"]
    
    tg_input = message.text.strip()
    telegram_id = None
    username = None

    if tg_input not in ("/skip", "skip", "-"):
        if tg_input.startswith("@"):
            username = tg_input[1:]
        elif tg_input.isdigit():
            telegram_id = int(tg_input)
        else:
            username = tg_input

    contact_id = await add_or_update_contact(
        name=name,
        phone=phone,
        telegram_id=telegram_id,
        username=username
    )

    await state.clear()
    
    tg_info = f"@{username}" if username else (f"ID: {telegram_id}" if telegram_id else "Bog'lanmagan")
    await message.answer(
        f"✅ <b>Kontakt muvaffaqiyatli saqlandi!</b>\n\n"
        f"👤 Ism: <b>{name}</b>\n"
        f"📞 Raqam: <code>{phone}</code>\n"
        f"📱 Telegram: {tg_info}",
        reply_markup=get_contacts_menu_kb(),
        parse_mode="HTML"
    )

# --- KONTAKTLAR RO'YXATI ---
@router.message(F.text == "📋 Barcha kontaktlar ro'yxati")
async def list_contacts(message: types.Message):
    contacts = await get_all_contacts()
    if not contacts:
        await message.answer(
            "Hozircha kontaktlar ro'yxati bo'sh.\n"
            "➕ Yangi kontakt qo'shish tugmasi orqali qo'shishingiz mumkin.",
            reply_markup=get_contacts_menu_kb()
        )
        return

    text = f"<b>📋 Jami kontaktlar: {len(contacts)} ta</b>\n\n"
    for idx, c in enumerate(contacts[:20], 1):
        tg_badge = "📱 (TG bor)" if c.get("telegram_id") else "⚪ (Faqat SMS)"
        text += f"{idx}. <b>{c['name']}</b>: <code>{c['phone_number']}</code> {tg_badge}\n"
    
    if len(contacts) > 20:
        text += f"\n<i>... va yana {len(contacts) - 20} ta kontakt.</i>"

    # Birinchi bir nechta kontaktlarni o'chirish imkoniyati uchun tugmalar
    rows = []
    for c in contacts[:5]:
        rows.append([
            InlineKeyboardButton(
                text=f"🗑 {c['name']} ({c['phone_number']}) ni o'chirish",
                callback_data=f"del_c:{c['id']}"
            )
        ])

    kb = InlineKeyboardMarkup(inline_keyboard=rows) if rows else None
    await message.answer(text, reply_markup=kb, parse_mode="HTML")

@router.callback_query(F.data.startswith("del_c:"))
async def delete_contact_callback(callback: types.CallbackQuery):
    contact_id = int(callback.data.split(":")[1])
    contact = await get_contact_by_id(contact_id)
    if contact:
        await delete_contact(contact_id)
        await callback.answer("Kontakt o'chirildi", show_alert=True)
        await callback.message.edit_text(
            f"🗑 <b>{contact['name']}</b> ({contact['phone_number']}) ro'yxatdan o'chirildi.",
            parse_mode="HTML"
        )
    else:
        await callback.answer("Kontakt topilmadi", show_alert=True)
