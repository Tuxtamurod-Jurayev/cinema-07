import asyncio
import logging
import sys

from aiogram import Bot, Dispatcher
from aiogram.fsm.storage.memory import MemoryStorage
from aiogram.client.default import DefaultBotProperties
from aiogram.enums import ParseMode

from config import BOT_TOKEN, SMS_PROVIDER, ADMIN_IDS
from database import init_db
from handlers import setup_routers

# Loglashni sozlash
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

async def main():
    if not BOT_TOKEN or BOT_TOKEN == "YOUR_TELEGRAM_BOT_TOKEN_HERE":
        logger.error(
            "\n" + "="*60 + "\n"
            "❌ XATOLIK: .env faylida BOT_TOKEN ko'rsatilmagan!\n"
            "Iltimos, @BotFather orqali bot oching va .env fayliga BOT_TOKEN ni yozing.\n"
            "="*60
        )
        sys.exit(1)

    # 1. Bazani ishga tushiramiz
    logger.info("Ma'lumotlar bazasi initsializatsiya qilinmoqda...")
    await init_db()
    logger.info("Ma'lumotlar bazasi tayyor!")

    # 2. Bot va Dispatcher yaratamiz
    bot = DefaultBotProperties(parse_mode=ParseMode.HTML)
    bot_instance = Bot(token=BOT_TOKEN, default=bot)
    dp = Dispatcher(storage=MemoryStorage())

    # 3. Routerlarni ulaymiz
    main_router = setup_routers()
    dp.include_router(main_router)

    logger.info("=" * 60)
    logger.info("🤖 Telegram Bot ishga tushdi!")
    logger.info(f"📡 Faol SMS provayderi: '{SMS_PROVIDER}'")
    if ADMIN_IDS:
        logger.info(f"👑 Admin ID lar: {ADMIN_IDS}")
    else:
        logger.info("👑 Admin ID lar belgilanmagan (birinchi foydalanuvchilar to'liq huquqqa ega)")
    logger.info("=" * 60)

    # 4. Pollingni boshlaymiz
    try:
        await bot_instance.delete_webhook(drop_pending_updates=True)
        await dp.start_polling(bot_instance)
    finally:
        await bot_instance.session.close()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except (KeyboardInterrupt, SystemExit):
        logger.info("Bot to'xtatildi.")
