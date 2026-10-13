import asyncio
import logging
import ssl
import xmlrpc.client
from typing import Tuple, List, Optional
import aiohttp

from config import (
    SMS_PROVIDER,
    SMS_ANDROID_LOCAL_URL,
    SMS_ANDROID_USERNAME,
    SMS_ANDROID_PASSWORD,
    SMS_ANDROID_CLOUD_URL,
    SMS_ANDROID_CLOUD_TOKEN,
    SMS_SIM_SLOT,
    SMSGATE_URL,
    SMSGATE_TOKEN,
    SMSGATE_MODEM_ID,
    SMSGATE_VERIFY_SSL,
)

logger = logging.getLogger(__name__)

class SMSGatewayService:
    def __init__(self):
        self.provider = SMS_PROVIDER

    async def send_sms(self, phone_number: str, message_text: str) -> Tuple[bool, str]:
        """Bitta telefon raqamiga SMS yuborish."""
        return await self.send_sms_batch([phone_number], message_text)

    async def send_sms_batch(self, phone_numbers: List[str], message_text: str) -> Tuple[bool, str]:
        """Bir yoki bir nechta telefon raqamlariga SMS yuborish."""
        if not phone_numbers:
            return False, "Telefon raqamlari ko'rsatilmadi"

        # 1. TEST (MOCK) REJIMI
        if self.provider == "mock":
            logger.info(f"[TEST MOCK SMS] Raqamlar: {phone_numbers} | Matn: {message_text}")
            return True, f"Simulyatsiya (Mock): {len(phone_numbers)} ta raqamga SMS jo'natildi"

        # 2. CAPCOM6 / ANDROID-SMS-GATEWAY (LOCAL REJIM)
        elif self.provider == "android_local":
            endpoint = f"{SMS_ANDROID_LOCAL_URL}/message"
            payload = {
                "textMessage": {
                    "text": message_text
                },
                "phoneNumbers": phone_numbers,
                "simNumber": SMS_SIM_SLOT
            }
            auth = None
            if SMS_ANDROID_USERNAME and SMS_ANDROID_PASSWORD:
                auth = aiohttp.BasicAuth(SMS_ANDROID_USERNAME, SMS_ANDROID_PASSWORD)

            try:
                timeout = aiohttp.ClientTimeout(total=15)
                async with aiohttp.ClientSession(timeout=timeout) as session:
                    async with session.post(
                        endpoint,
                        json=payload,
                        auth=auth,
                        headers={"Content-Type": "application/json"}
                    ) as resp:
                        if resp.status in (200, 201, 202):
                            return True, "SMS muvaffaqiyatli yuborildi (Android Local)"
                        else:
                            resp_text = await resp.text()
                            err_msg = f"Android SMS xatosi (HTTP {resp.status}): {resp_text[:120]}"
                            logger.error(err_msg)
                            return False, err_msg
            except Exception as e:
                err_msg = f"Android telefonga ulanishda xatolik ({SMS_ANDROID_LOCAL_URL}): {str(e)}"
                logger.error(err_msg)
                return False, err_msg

        # 3. CAPCOM6 / ANDROID-SMS-GATEWAY (CLOUD REJIM)
        elif self.provider == "android_cloud":
            endpoint = f"{SMS_ANDROID_CLOUD_URL}/3rdparty/v1/messages"
            payload = {
                "textMessage": {
                    "text": message_text
                },
                "phoneNumbers": phone_numbers
            }
            if SMS_SIM_SLOT:
                payload["simNumber"] = SMS_SIM_SLOT

            headers = {"Content-Type": "application/json"}
            if SMS_ANDROID_CLOUD_TOKEN:
                headers["Authorization"] = f"Bearer {SMS_ANDROID_CLOUD_TOKEN}"

            try:
                timeout = aiohttp.ClientTimeout(total=15)
                async with aiohttp.ClientSession(timeout=timeout) as session:
                    async with session.post(endpoint, json=payload, headers=headers) as resp:
                        if resp.status in (200, 201, 202):
                            return True, "SMS muvaffaqiyatli yuborildi (Android Cloud)"
                        else:
                            resp_text = await resp.text()
                            err_msg = f"Cloud SMS xatosi (HTTP {resp.status}): {resp_text[:120]}"
                            logger.error(err_msg)
                            return False, err_msg
            except Exception as e:
                err_msg = f"SMS Cloud serveriga ulanishda xatolik: {str(e)}"
                logger.error(err_msg)
                return False, err_msg

        # 4. PENTAGRIDSEC / SMSGATE (XML-RPC MODEM / SIM POOL REJIMI)
        elif self.provider == "smsgate":
            return await self._send_via_pentagrid_smsgate(phone_numbers, message_text)

        else:
            return False, f"Noma'lum SMS_PROVIDER: '{self.provider}' (mock, android_local, android_cloud, smsgate)"

    async def _send_via_pentagrid_smsgate(self, phone_numbers: List[str], message_text: str) -> Tuple[bool, str]:
        """Pentagrid SMSgate XML-RPC orqali SMS yuborish"""
        def _sync_send():
            context = None
            if SMSGATE_URL.startswith("https") and not SMSGATE_VERIFY_SSL:
                context = ssl._create_unverified_context()

            proxy = xmlrpc.client.ServerProxy(SMSGATE_URL, context=context, verbose=False)
            success_count = 0
            errors = []

            for phone in phone_numbers:
                try:
                    # XML-RPC send_sms(token, recipient, message, modem_identifier)
                    res = proxy.send_sms(SMSGATE_TOKEN, phone, message_text, SMSGATE_MODEM_ID)
                    success_count += 1
                except xmlrpc.client.Fault as err:
                    errors.append(f"{phone}: Fault {err.faultCode} - {err.faultString}")
                except Exception as err:
                    errors.append(f"{phone}: {str(err)}")

            if success_count == len(phone_numbers):
                return True, f"Pentagrid SMSgate: {success_count} ta SMS jo'natildi"
            elif success_count > 0:
                return True, f"Pentagrid SMSgate: {success_count}/{len(phone_numbers)} ta jo'natildi. Xatolar: {'; '.join(errors)}"
            else:
                return False, f"Pentagrid SMSgate xatosi: {'; '.join(errors)}"

        try:
            return await asyncio.to_thread(_sync_send)
        except Exception as e:
            return False, f"Pentagrid SMSgate tizim xatosi: {str(e)}"

    async def check_health(self) -> Tuple[bool, str]:
        """SMS Gateway ulanish holatini tekshirish"""
        if self.provider == "mock":
            return True, "Mock test rejimi (faol, simulyatsiya)"

        elif self.provider == "android_local":
            try:
                timeout = aiohttp.ClientTimeout(total=5)
                async with aiohttp.ClientSession(timeout=timeout) as session:
                    auth = None
                    if SMS_ANDROID_USERNAME and SMS_ANDROID_PASSWORD:
                        auth = aiohttp.BasicAuth(SMS_ANDROID_USERNAME, SMS_ANDROID_PASSWORD)
                    async with session.get(f"{SMS_ANDROID_LOCAL_URL}/", auth=auth) as resp:
                        if resp.status in (200, 401, 403, 404):
                            return True, f"Android telefon aloqada ({SMS_ANDROID_LOCAL_URL})"
                        return False, f"Server javob kodi: {resp.status}"
            except Exception as e:
                return False, f"Android telefonga ulanib bo'lmadi: {str(e)}"

        elif self.provider == "android_cloud":
            return True, f"Android Cloud rejim sozlangan ({SMS_ANDROID_CLOUD_URL})"

        elif self.provider == "smsgate":
            def _sync_check():
                context = None
                if SMSGATE_URL.startswith("https") and not SMSGATE_VERIFY_SSL:
                    context = ssl._create_unverified_context()
                proxy = xmlrpc.client.ServerProxy(SMSGATE_URL, context=context, verbose=False)
                # smsgate health_state yoki stats tekshirish
                try:
                    proxy.get_health_state(SMSGATE_TOKEN)
                    return True, f"Pentagrid SMSgate aloqada ({SMSGATE_URL})"
                except xmlrpc.client.Fault:
                    # Agar token cheklangan bo'lsa ham server javob beryapti
                    return True, f"Pentagrid SMSgate server javob beryapti ({SMSGATE_URL})"
                except Exception as e:
                    return False, f"Pentagrid SMSgate ulanish xatosi: {str(e)}"
            try:
                return await asyncio.to_thread(_sync_check)
            except Exception as e:
                return False, f"Pentagrid SMSgate tekshirib bo'lmadi: {str(e)}"

        return False, "Noma'lum rejim"

sms_service = SMSGatewayService()
