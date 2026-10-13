import aiosqlite
from typing import List, Optional, Dict, Any
from config import DB_FILE

def normalize_phone(phone: str) -> str:
    """Telefon raqamni tozalash va standart formatga keltirish (+998...)"""
    phone = phone.strip().replace(" ", "").replace("-", "").replace("(", "").replace(")", "")
    if not phone.startswith("+"):
        if phone.startswith("998"):
            phone = "+" + phone
        elif len(phone) == 9:
            phone = "+998" + phone
    return phone

async def init_db():
    """Ma'lumotlar bazasini initsializatsiya qilish"""
    async with aiosqlite.connect(DB_FILE) as db:
        await db.execute("""
            CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone_number TEXT NOT NULL UNIQUE,
                telegram_id INTEGER UNIQUE,
                username TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        await db.execute("""
            CREATE TABLE IF NOT EXISTS messages_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                text TEXT NOT NULL,
                sent_by INTEGER NOT NULL,
                channel TEXT NOT NULL,
                total_recipients INTEGER DEFAULT 0,
                telegram_success INTEGER DEFAULT 0,
                telegram_failed INTEGER DEFAULT 0,
                sms_success INTEGER DEFAULT 0,
                sms_failed INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        await db.commit()

async def add_or_update_contact(
    name: str,
    phone: str,
    telegram_id: Optional[int] = None,
    username: Optional[str] = None
) -> int:
    """Kontakt qo'shish yoki mavjud bo'lsa yangilash"""
    clean_phone = normalize_phone(phone)
    async with aiosqlite.connect(DB_FILE) as db:
        db.row_factory = aiosqlite.Row
        # Avval raqam yoki telegram_id bo'yicha tekshiramiz
        async with db.execute(
            "SELECT id, telegram_id, name FROM contacts WHERE phone_number = ? OR (telegram_id IS NOT NULL AND telegram_id = ?)",
            (clean_phone, telegram_id if telegram_id else -1)
        ) as cursor:
            row = await cursor.fetchone()

        if row:
            contact_id = row["id"]
            # Yangilaymiz
            await db.execute(
                """
                UPDATE contacts
                SET name = COALESCE(NULLIF(?, ''), name),
                    phone_number = ?,
                    telegram_id = COALESCE(?, telegram_id),
                    username = COALESCE(?, username)
                WHERE id = ?
                """,
                (name, clean_phone, telegram_id, username, contact_id)
            )
            await db.commit()
            return contact_id
        else:
            async with db.execute(
                """
                INSERT INTO contacts (name, phone_number, telegram_id, username)
                VALUES (?, ?, ?, ?)
                """,
                (name, clean_phone, telegram_id, username)
            ) as cursor:
                await db.commit()
                return cursor.lastrowid

async def get_all_contacts(search: str = "") -> List[Dict[str, Any]]:
    """Barcha kontaktlarni olish"""
    async with aiosqlite.connect(DB_FILE) as db:
        db.row_factory = aiosqlite.Row
        if search:
            query = """
                SELECT * FROM contacts 
                WHERE name LIKE ? OR phone_number LIKE ? 
                ORDER BY name ASC
            """
            s = f"%{search}%"
            async with db.execute(query, (s, s)) as cursor:
                rows = await cursor.fetchall()
        else:
            async with db.execute("SELECT * FROM contacts ORDER BY name ASC") as cursor:
                rows = await cursor.fetchall()
        
        return [dict(row) for row in rows]

async def get_contacts_by_ids(contact_ids: List[int]) -> List[Dict[str, Any]]:
    """Berilgan ID lar bo'yicha kontaktlarni olish"""
    if not contact_ids:
        return []
    placeholders = ",".join("?" for _ in contact_ids)
    async with aiosqlite.connect(DB_FILE) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute(
            f"SELECT * FROM contacts WHERE id IN ({placeholders}) ORDER BY name ASC",
            contact_ids
        ) as cursor:
            rows = await cursor.fetchall()
        return [dict(row) for row in rows]

async def get_contact_by_id(contact_id: int) -> Optional[Dict[str, Any]]:
    """ID bo'yicha kontaktni olish"""
    async with aiosqlite.connect(DB_FILE) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute("SELECT * FROM contacts WHERE id = ?", (contact_id,)) as cursor:
            row = await cursor.fetchone()
            return dict(row) if row else None

async def delete_contact(contact_id: int) -> bool:
    """Kontaktni o'chirish"""
    async with aiosqlite.connect(DB_FILE) as db:
        async with db.execute("DELETE FROM contacts WHERE id = ?", (contact_id,)) as cursor:
            await db.commit()
            return cursor.rowcount > 0

async def log_broadcast_history(
    text: str,
    sent_by: int,
    channel: str,
    total: int,
    tg_success: int,
    tg_failed: int,
    sms_success: int,
    sms_failed: int
):
    """Xabar yuborish statistikasini bazaga saqlash"""
    async with aiosqlite.connect(DB_FILE) as db:
        await db.execute(
            """
            INSERT INTO messages_history (
                text, sent_by, channel, total_recipients,
                telegram_success, telegram_failed, sms_success, sms_failed
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            """,
            (text, sent_by, channel, total, tg_success, tg_failed, sms_success, sms_failed)
        )
        await db.commit()

async def get_stats() -> Dict[str, Any]:
    """Umumiy statistika"""
    async with aiosqlite.connect(DB_FILE) as db:
        db.row_factory = aiosqlite.Row
        async with db.execute("SELECT COUNT(*) as total FROM contacts") as cursor:
            total_contacts = (await cursor.fetchone())["total"]
        
        async with db.execute("SELECT COUNT(*) as tg_linked FROM contacts WHERE telegram_id IS NOT NULL") as cursor:
            tg_linked = (await cursor.fetchone())["tg_linked"]

        async with db.execute("SELECT COUNT(*) as total_msgs FROM messages_history") as cursor:
            total_msgs = (await cursor.fetchone())["total_msgs"]

        return {
            "total_contacts": total_contacts,
            "tg_linked_contacts": tg_linked,
            "total_broadcasts": total_msgs
        }
