"""Shared pytest fixtures."""
import pymysql
import pytest

DB_CONFIG = dict(host="localhost", user="root", password="", database="career_system")


@pytest.fixture(autouse=True)
def reset_admin_lockout():
    """Tests intentionally trigger failed admin logins; reset lockout state
    before every test so repeated test runs never get the real admin account
    locked out (login_controller.php enforces a 5-attempt lockout)."""
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE users SET failed_login_attempts = 0, locked_until = NULL "
                "WHERE email = 'admin@career.local'"
            )
        conn.commit()
    finally:
        conn.close()
    yield
