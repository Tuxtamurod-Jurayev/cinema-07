import os
from typing import List
from dotenv import load_dotenv

load_dotenv()

BOT_TOKEN: str = os.getenv("BOT_TOKEN", "").strip()

# Admin ID lar (vergul bilan ajratilgan, masalan: 12345678,87654321)
admin_ids_raw = os.getenv("ADMIN_IDS", "").strip()
ADMIN_IDS: List[int] = [
    int(x.strip()) for x in admin_ids_raw.split(",") if x.strip().isdigit()
]

# SMS Provayder turi:
# 1) 'android_local' - capcom6/android-sms-gateway (Wi-Fi orqali telefon REST API)
# 2) 'android_cloud' - capcom6/android-sms-gateway (sms-gate.app Cloud API)
# 3) 'smsgate'       - pentagridsec/smsgate (GSM modem / SIM pool XML-RPC)
# 4) 'mock'          - Test rejimi (xabar yuborishni simulyatsiya qilish)
SMS_PROVIDER: str = os.getenv("SMS_PROVIDER", "mock").lower().strip()

# --- 1 & 2. android-sms-gateway sozlamalari ---
# Local REST API
SMS_ANDROID_LOCAL_URL: str = os.getenv("SMS_ANDROID_LOCAL_URL", "http://192.168.1.50:8080").rstrip("/")
SMS_ANDROID_USERNAME: str = os.getenv("SMS_ANDROID_USERNAME", "admin").strip()
SMS_ANDROID_PASSWORD: str = os.getenv("SMS_ANDROID_PASSWORD", "").strip()

# Cloud REST API
SMS_ANDROID_CLOUD_URL: str = os.getenv("SMS_ANDROID_CLOUD_URL", "https://api.sms-gate.app").rstrip("/")
SMS_ANDROID_CLOUD_TOKEN: str = os.getenv("SMS_ANDROID_CLOUD_TOKEN", "").strip()

# SIM-karta sloti (1 yoki 2)
SMS_SIM_SLOT: int = int(os.getenv("SMS_SIM_SLOT", "1"))

# --- 3. pentagridsec/smsgate sozlamalari ---
SMSGATE_URL: str = os.getenv("SMSGATE_URL", "https://127.0.0.1:8443/").strip()
SMSGATE_TOKEN: str = os.getenv("SMSGATE_TOKEN", "").strip()
SMSGATE_MODEM_ID: str = os.getenv("SMSGATE_MODEM_ID", "").strip() or None
SMSGATE_VERIFY_SSL: bool = os.getenv("SMSGATE_VERIFY_SSL", "False").lower() in ("true", "1", "yes")

# Ma'lumotlar bazasi fayli
DB_FILE: str = os.getenv("DB_FILE", "sms_bot.db")
