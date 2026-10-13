from typing import List, Dict, Any, Set
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton

PAGE_SIZE = 6

def get_contacts_selection_kb(
    contacts: List[Dict[str, Any]],
    selected_ids: Set[int],
    page: int = 0
) -> InlineKeyboardMarkup:
    """Kontaktlarni tanlash uchun dinamik checkboxli klaviatura"""
    total_contacts = len(contacts)
    total_pages = max(1, (total_contacts + PAGE_SIZE - 1) // PAGE_SIZE)
    page = max(0, min(page, total_pages - 1))

    start_idx = page * PAGE_SIZE
    end_idx = start_idx + PAGE_SIZE
    page_contacts = contacts[start_idx:end_idx]

    rows = []

    # Har bir kontakt uchun qator
    for c in page_contacts:
        c_id = c["id"]
        is_selected = c_id in selected_ids
        mark = "✅" if is_selected else "⬜"
        
        # Telegram borligini bildirish belgisi
        tg_badge = "📱" if c.get("telegram_id") else ""
        button_text = f"{mark} {c['name']} ({c['phone_number']}) {tg_badge}"
        
        rows.append([
            InlineKeyboardButton(
                text=button_text,
                callback_data=f"sel_c:{c_id}:{page}"
            )
        ])

    # Barchasini tanlash / Tozalash tugmalari
    control_row = [
        InlineKeyboardButton(text="🔘 Barchasini tanlash", callback_data=f"sel_all:{page}"),
        InlineKeyboardButton(text="⚪ Tozalash", callback_data=f"desel_all:{page}")
    ]
    rows.append(control_row)

    # Navigatsiya (Sahifalar)
    nav_row = []
    if page > 0:
        nav_row.append(InlineKeyboardButton(text="⬅️ Oldingi", callback_data=f"page_c:{page - 1}"))
    
    nav_row.append(InlineKeyboardButton(text=f"📄 {page + 1}/{total_pages}", callback_data="noop"))
    
    if page < total_pages - 1:
        nav_row.append(InlineKeyboardButton(text="Keyingi ➡️", callback_data=f"page_c:{page + 1}"))

    if nav_row:
        rows.append(nav_row)

    # Tayyor va Bekor qilish
    selected_count = len(selected_ids)
    rows.append([
        InlineKeyboardButton(
            text=f"🚀 Tayyor ({selected_count} ta tanlandi)",
            callback_data="sel_done"
        ),
        InlineKeyboardButton(
            text="❌ Bekor qilish",
            callback_data="sel_cancel"
        )
    ])

    return InlineKeyboardMarkup(inline_keyboard=rows)

def get_channel_choice_kb() -> InlineKeyboardMarkup:
    """Xabar yuborish kanalini tanlash"""
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [
                InlineKeyboardButton(
                    text="📱 Telegram + 💬 SMS (Ikkalasiga)",
                    callback_data="chan:both"
                )
            ],
            [
                InlineKeyboardButton(
                    text="💬 Faqat SMS orqali",
                    callback_data="chan:sms"
                )
            ],
            [
                InlineKeyboardButton(
                    text="📱 Faqat Telegram orqali",
                    callback_data="chan:telegram"
                )
            ],
            [
                InlineKeyboardButton(
                    text="❌ Bekor qilish",
                    callback_data="chan:cancel"
                )
            ]
        ]
    )

def get_confirm_send_kb() -> InlineKeyboardMarkup:
    """Yuborishni tasdiqlash klaviaturasi"""
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [
                InlineKeyboardButton(text="🚀 Ha, yuborilsin!", callback_data="confirm_send:yes"),
                InlineKeyboardButton(text="❌ Bekor qilish", callback_data="confirm_send:no")
            ]
        ]
    )

def get_contact_actions_kb(contact_id: int) -> InlineKeyboardMarkup:
    """Kontakt tafsilotlari klaviaturasi (O'chirish tugmasi bilan)"""
    return InlineKeyboardMarkup(
        inline_keyboard=[
            [
                InlineKeyboardButton(text="🗑 O'chirish", callback_data=f"del_c:{contact_id}")
            ]
        ]
    )
