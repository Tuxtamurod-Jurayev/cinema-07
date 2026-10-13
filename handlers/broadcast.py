import asyncio
import logging
from typing import Set, List, Dict, Any

from aiogram import Router, F, types, Bot
from aiogram.fsm.context import FSMContext
from aiogram.fsm.state import State, StatesGroup
from aiogram.exceptions import TelegramAPIError

from database import (
    get_all_contacts,
    get_contacts_by_ids,
    log_broadcast_history
)
from keyboards.default import get_admin_main_kb, get_cancel_kb
from keyboards.inline import (
    get_contacts_selection_kb,
    get_channel_choice_kb,
    get_confirm_send_kb
)
from sms_service import sms_service

router = Router()
logger = logging.getLogger(__name__)

class BroadcastState(StatesGroup):
    selecting_contacts = State()
    waiting_for_text = State()
    choosing_channel = State()
    confirming = State()

def get_recipient_type_kb() -> types.ReplyKeyboardMarkup:
    return types.ReplyKeyboardMarkup(
        keyboard=[
            [types.KeyboardButton(text="☑️ Kontaktlarni tanlash (Belgilash)")],
            [types.KeyboardButton(text="👥 Barcha kontaktlarga yuborish")],
            [types.KeyboardButton(text="❌ Bekor qilish")]
        ],
        resize_keyboard=True
    )

@router.message(F.text == "✉️ Xabar yuborish")
async def start_broadcast(message: types.Message, state: FSMContext):
    contacts = await get_all_contacts()
    if not contacts:
        await message.answer(
            "⚠️ Hozircha bazada birorta ham kontakt mavjud emas.\n"
            "Avval '👥 Kontaktlar' bo'limidan kontakt qo'shing yoki mijozlarga botingiz havolasini yuboring.",
            reply_markup=get_admin_main_kb()
        )
        return

    await state.clear()
    await message.answer(
        f"✉️ <b>Xabar yuborish bo'limi:</b>\n\n"
        f"Bazada jami <b>{len(contacts)}</b> ta kontakt mavjud.\n"
        "Xabarni kimlarga yubormoqchisiz?",
        reply_markup=get_recipient_type_kb(),
        parse_mode="HTML"
    )

@router.message(F.text == "👥 Barcha kontaktlarga yuborish")
async def select_all_broadcast(message: types.Message, state: FSMContext):
    contacts = await get_all_contacts()
    all_ids = {c["id"] for c in contacts}
    await state.update_data(selected_ids=list(all_ids))
    await state.set_state(BroadcastState.waiting_for_text)
    
    await message.answer(
        f"✅ <b>Barcha kontaktlar ({len(all_ids)} ta) tanlandi.</b>\n\n"
        "Endi yubormoqchi bo'lgan <b>xabar matnini</b> kiriting:",
        reply_markup=get_cancel_kb(),
        parse_mode="HTML"
    )

@router.message(F.text == "☑️ Kontaktlarni tanlash (Belgilash)")
async def select_custom_broadcast(message: types.Message, state: FSMContext):
    contacts = await get_all_contacts()
    await state.update_data(selected_ids=[], current_page=0)
    await state.set_state(BroadcastState.selecting_contacts)

    kb = get_contacts_selection_kb(contacts=contacts, selected_ids=set(), page=0)
    await message.answer(
        "👇 <b>Quyidagi ro'yxatdan kerakli kontaktlarni belgilang:</b>\n"
        "Har bir kontakt ustiga bosib belgilashingiz (✅) mumkin.\n"
        "Tanlab bo'lgach <b>'🚀 Tayyor'</b> tugmasini bosing.",
        reply_markup=kb,
        parse_mode="HTML"
    )

# --- INLINE CHECKBOX CALLBACKS ---
@router.callback_query(BroadcastState.selecting_contacts, F.data.startswith("sel_c:"))
async def toggle_contact_select(callback: types.CallbackQuery, state: FSMContext):
    _, c_id_str, page_str = callback.data.split(":")
    contact_id = int(c_id_str)
    page = int(page_str)

    data = await state.get_data()
    selected_ids: Set[int] = set(data.get("selected_ids", []))

    if contact_id in selected_ids:
        selected_ids.remove(contact_id)
    else:
        selected_ids.add(contact_id)

    await state.update_data(selected_ids=list(selected_ids), current_page=page)

    contacts = await get_all_contacts()
    kb = get_contacts_selection_kb(contacts=contacts, selected_ids=selected_ids, page=page)
    try:
        await callback.message.edit_reply_markup(reply_markup=kb)
    except Exception:
        pass
    await callback.answer()

@router.callback_query(BroadcastState.selecting_contacts, F.data.startswith("sel_all:"))
async def select_all_callback(callback: types.CallbackQuery, state: FSMContext):
    page = int(callback.data.split(":")[1])
    contacts = await get_all_contacts()
    all_ids = {c["id"] for c in contacts}
    await state.update_data(selected_ids=list(all_ids), current_page=page)

    kb = get_contacts_selection_kb(contacts=contacts, selected_ids=all_ids, page=page)
    try:
        await callback.message.edit_reply_markup(reply_markup=kb)
    except Exception:
        pass
    await callback.answer(f"Barcha ({len(all_ids)} ta) kontakt belgilandi")

@router.callback_query(BroadcastState.selecting_contacts, F.data.startswith("desel_all:"))
async def deselect_all_callback(callback: types.CallbackQuery, state: FSMContext):
    page = int(callback.data.split(":")[1])
    contacts = await get_all_contacts()
    await state.update_data(selected_ids=[], current_page=page)

    kb = get_contacts_selection_kb(contacts=contacts, selected_ids=set(), page=page)
    try:
        await callback.message.edit_reply_markup(reply_markup=kb)
    except Exception:
        pass
    await callback.answer("Barcha belgilar olib tashlandi")

@router.callback_query(BroadcastState.selecting_contacts, F.data.startswith("page_c:"))
async def change_page_callback(callback: types.CallbackQuery, state: FSMContext):
    page = int(callback.data.split(":")[1])
    data = await state.get_data()
    selected_ids: Set[int] = set(data.get("selected_ids", []))
    await state.update_data(current_page=page)

    contacts = await get_all_contacts()
    kb = get_contacts_selection_kb(contacts=contacts, selected_ids=selected_ids, page=page)
    try:
        await callback.message.edit_reply_markup(reply_markup=kb)
    except Exception:
        pass
    await callback.answer()

@router.callback_query(BroadcastState.selecting_contacts, F.data == "sel_done")
async def finish_selection_callback(callback: types.CallbackQuery, state: FSMContext):
    data = await state.get_data()
    selected_ids = data.get("selected_ids", [])
    if not selected_ids:
        await callback.answer("⚠️ Kamida 1 ta kontaktni belgilashingiz kerak!", show_alert=True)
        return

    await state.set_state(BroadcastState.waiting_for_text)
    await callback.message.edit_text(
        f"✅ <b>{len(selected_ids)} ta kontakt tanlandi.</b>\n\n"
        "Endi yubormoqchi bo'lgan <b>xabar matnini</b> yozib yuboring:",
        parse_mode="HTML"
    )
    await callback.answer()

@router.callback_query(BroadcastState.selecting_contacts, F.data == "sel_cancel")
async def cancel_selection_callback(callback: types.CallbackQuery, state: FSMContext):
    await state.clear()
    await callback.message.edit_text("Amal bekor qilindi.")
    await callback.message.answer("Asosiy menyu:", reply_markup=get_admin_main_kb())
    await callback.answer()

# --- XABAR MATNINI QABUL QILISH ---
@router.message(BroadcastState.waiting_for_text)
async def process_broadcast_text(message: types.Message, state: FSMContext):
    if message.text == "❌ Bekor qilish":
        await state.clear()
        await message.answer("Bekor qilindi.", reply_markup=get_admin_main_kb())
        return

    text = message.text.strip()
    if not text:
        await message.answer("Xabar matni bo'sh bo'lishi mumkin emas. Iltimos matn kiriting:")
        return

    await state.update_data(broadcast_text=text)
    await state.set_state(BroadcastState.choosing_channel)
    
    await message.answer(
        "Qaysi usul orqali yubormoqchisiz?",
        reply_markup=get_channel_choice_kb()
    )

# --- KANALNI TANLASH ---
@router.callback_query(BroadcastState.choosing_channel, F.data.startswith("chan:"))
async def process_channel_choice(callback: types.CallbackQuery, state: FSMContext):
    channel = callback.data.split(":")[1]
    if channel == "cancel":
        await state.clear()
        await callback.message.edit_text("Xabar yuborish bekor qilindi.")
        await callback.message.answer("Asosiy menyu:", reply_markup=get_admin_main_kb())
        await callback.answer()
        return

    channel_names = {
        "both": "📱 Telegram + 💬 SMS (Ikkalasiga)",
        "sms": "💬 Faqat SMS",
        "telegram": "📱 Faqat Telegram"
    }

    await state.update_data(channel=channel)
    await state.set_state(BroadcastState.confirming)

    data = await state.get_data()
    selected_ids = data["selected_ids"]
    broadcast_text = data["broadcast_text"]

    preview = (
        "<b>📋 Xabarni yuborishni tasdiqlaysizmi?</b>\n\n"
        f"👥 Qabul qiluvchilar: <b>{len(selected_ids)} ta</b>\n"
        f"📡 Kanal: <b>{channel_names.get(channel)}</b>\n\n"
        f"💬 <b>Xabar matni:</b>\n"
        f"<i>\"{broadcast_text}\"</i>\n\n"
        "Yuborishni tasdiqlash uchun quyidagi tugmani bosing:"
    )

    await callback.message.edit_text(
        preview,
        reply_markup=get_confirm_send_kb(),
        parse_mode="HTML"
    )
    await callback.answer()

# --- YUBORISHNI TASDIQLASH VA AMALGA OSHIRISH ---
@router.callback_query(BroadcastState.confirming, F.data.startswith("confirm_send:"))
async def confirm_and_send(callback: types.CallbackQuery, state: FSMContext, bot: Bot):
    choice = callback.data.split(":")[1]
    if choice != "yes":
        await state.clear()
        await callback.message.edit_text("Yuborish bekor qilindi.")
        await callback.message.answer("Asosiy menyu:", reply_markup=get_admin_main_kb())
        await callback.answer()
        return

    data = await state.get_data()
    selected_ids: List[int] = data["selected_ids"]
    broadcast_text: str = data["broadcast_text"]
    channel: str = data["channel"]
    admin_id = callback.from_user.id

    await state.clear()
    await callback.message.edit_text(
        "⏳ <b>Xabarlar yuborilmoqda, iltimos kuting...</b>",
        parse_mode="HTML"
    )
    await callback.answer()

    contacts = await get_contacts_by_ids(selected_ids)

    tg_success = 0
    tg_failed = 0
    sms_success = 0
    sms_failed = 0
    failed_details = []

    for c in contacts:
        name = c["name"]
        phone = c["phone_number"]
        tg_id = c.get("telegram_id")

        # 1. Telegram orqali yuborish
        if channel in ("both", "telegram"):
            if tg_id:
                try:
                    await bot.send_message(
                        chat_id=tg_id,
                        text=broadcast_text
                    )
                    tg_success += 1
                except TelegramAPIError as e:
                    tg_failed += 1
                    failed_details.append(f"Telegram ({name}): {e.message}")
                except Exception as e:
                    tg_failed += 1
                    failed_details.append(f"Telegram ({name}): {str(e)}")
            else:
                tg_failed += 1
                failed_details.append(f"Telegram ({name}): Telegram hisobi ulanmagan")

        # 2. SMS orqali yuborish
        if channel in ("both", "sms"):
            if phone:
                try:
                    ok, res_msg = await sms_service.send_sms(phone, broadcast_text)
                    if ok:
                        sms_success += 1
                    else:
                        sms_failed += 1
                        failed_details.append(f"SMS ({name} - {phone}): {res_msg}")
                except Exception as e:
                    sms_failed += 1
                    failed_details.append(f"SMS ({name} - {phone}): {str(e)}")
            else:
                sms_failed += 1
                failed_details.append(f"SMS ({name}): Telefon raqam mavjud emas")

        # Kichik oraliq (Telegram spam limitlaridan qochish uchun)
        await asyncio.sleep(0.05)

    # Tarixga saqlash
    await log_broadcast_history(
        text=broadcast_text,
        sent_by=admin_id,
        channel=channel,
        total=len(contacts),
        tg_success=tg_success,
        tg_failed=tg_failed,
        sms_success=sms_success,
        sms_failed=sms_failed
    )

    # Natija hisoboti
    report = [
        "✅ <b>Xabarlar yuborish yakunlandi!</b>\n",
        f"👥 Tanlangan kontaktlar: <b>{len(contacts)} ta</b>"
    ]

    if channel in ("both", "telegram"):
        report.append(f"📱 Telegram: <b>{tg_success} ta muvaffaqiyatli</b>, {tg_failed} ta yetkazilmadi")
    if channel in ("both", "sms"):
        report.append(f"💬 SMS: <b>{sms_success} ta muvaffaqiyatli</b>, {sms_failed} ta yetkazilmadi")

    if failed_details:
        report.append("\n<b>⚠️ Xatoliklar tafsiloti (birinchilari):</b>")
        for err in failed_details[:5]:
            report.append(f"• <i>{err}</i>")
        if len(failed_details) > 5:
            report.append(f"• <i>... va yana {len(failed_details) - 5} ta xatolik</i>")

    await callback.message.answer(
        "\n".join(report),
        reply_markup=get_admin_main_kb(),
        parse_mode="HTML"
    )
