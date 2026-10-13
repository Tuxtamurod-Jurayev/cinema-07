from aiogram import Router
from .start import router as start_router
from .contacts import router as contacts_router
from .broadcast import router as broadcast_router

def setup_routers() -> Router:
    main_router = Router()
    main_router.include_router(start_router)
    main_router.include_router(contacts_router)
    main_router.include_router(broadcast_router)
    return main_router
