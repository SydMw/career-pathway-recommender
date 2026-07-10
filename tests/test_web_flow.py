"""
End-to-end smoke test for the PHP web app: register -> submit scores ->
get recommendation -> admin sees it in reports. Requires XAMPP Apache + MySQL
running and the schema imported (database/schema.sql), plus the ML API
running (python ml/api.py).

Run with: pytest tests/test_web_flow.py -v
"""
import re
import time
import uuid

import pytest
import requests

BASE_URL = "http://localhost/career_system/public"
ADMIN_EMAIL = "admin@career.local"
ADMIN_PASSWORD = "Admin123!"  # seeded per README setup instructions


def extract_csrf(html: str) -> str:
    match = re.search(r'name="csrf_token" value="([a-f0-9]+)"', html)
    assert match, "CSRF token not found on page"
    return match.group(1)


@pytest.fixture
def student_email():
    return f"student_{uuid.uuid4().hex[:8]}@test.com"


def test_register_login_submit_and_admin_visibility(student_email):
    session = requests.Session()

    # Register a new student
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    reg_resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Pytest Student",
            "email": student_email,
            "password": "Pass123!",
            "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        allow_redirects=True,
        timeout=5,
    )
    assert "Enter Academic Performance" in reg_resp.text

    # Submit academic scores -> expect a STEM-leaning recommendation
    token = extract_csrf(reg_resp.text)
    submit_resp = session.post(
        f"{BASE_URL}/student_dashboard.php",
        data={
            "math_score": 90,
            "english_score": 50,
            "science_score": 88,
            "humanities_score": 45,
            "creative_arts_score": 30,
            "interest": "technology",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "Recommended Pathway" in submit_resp.text
    assert "STEM" in submit_resp.text

    # Reject bad CSRF token
    bad_resp = session.post(
        f"{BASE_URL}/student_dashboard.php",
        data={
            "math_score": 90, "english_score": 50, "science_score": 88,
            "humanities_score": 45, "creative_arts_score": 30,
            "interest": "technology", "csrf_token": "not-a-real-token",
        },
        timeout=5,
    )
    assert bad_resp.status_code == 403

    # Admin should see this student's submission in reports
    admin_session = requests.Session()
    login_page = admin_session.get(f"{BASE_URL}/login.php", timeout=5)
    token = extract_csrf(login_page.text)
    admin_session.post(
        f"{BASE_URL}/login.php",
        data={"email": ADMIN_EMAIL, "password": ADMIN_PASSWORD, "csrf_token": token},
        timeout=5,
    )
    dash = admin_session.get(f"{BASE_URL}/admin_dashboard.php", timeout=5)
    assert student_email in dash.text


def test_login_rejects_wrong_password():
    session = requests.Session()
    login_page = session.get(f"{BASE_URL}/login.php", timeout=5)
    token = extract_csrf(login_page.text)
    resp = session.post(
        f"{BASE_URL}/login.php",
        data={"email": ADMIN_EMAIL, "password": "wrong-password", "csrf_token": token},
        timeout=5,
    )
    assert "Incorrect password" in resp.text


def test_login_locks_account_after_repeated_failures():
    session = requests.Session()
    for _ in range(5):
        login_page = session.get(f"{BASE_URL}/login.php", timeout=5)
        token = extract_csrf(login_page.text)
        resp = session.post(
            f"{BASE_URL}/login.php",
            data={"email": ADMIN_EMAIL, "password": "wrong-password", "csrf_token": token},
            timeout=5,
        )
    assert "too many failed attempts" in resp.text.lower()

    # Even the correct password should now be rejected until the lock expires.
    login_page = session.get(f"{BASE_URL}/login.php", timeout=5)
    token = extract_csrf(login_page.text)
    locked_resp = session.post(
        f"{BASE_URL}/login.php",
        data={"email": ADMIN_EMAIL, "password": ADMIN_PASSWORD, "csrf_token": token},
        timeout=5,
    )
    assert "too many failed attempts" in locked_resp.text.lower()
    # Lock window should be close to the configured 5 minutes, not skewed by
    # a PHP/MySQL timezone mismatch (regression check).
    assert "try again in 1 minute" in locked_resp.text.lower() \
        or "try again in 2 minute" in locked_resp.text.lower() \
        or "try again in 3 minute" in locked_resp.text.lower() \
        or "try again in 4 minute" in locked_resp.text.lower() \
        or "try again in 5 minute" in locked_resp.text.lower()


# --- Negative test cases: deliberately wrong/invalid data ----------------
# Each of these MUST fail (registration/login/submission rejected, with a
# clear error message) for the system to be considered correct.

def test_login_tells_user_email_is_not_registered():
    session = requests.Session()
    login_page = session.get(f"{BASE_URL}/login.php", timeout=5)
    token = extract_csrf(login_page.text)
    resp = session.post(
        f"{BASE_URL}/login.php",
        data={"email": "no-such-user@nowhere.com", "password": "whatever", "csrf_token": token},
        timeout=5,
    )
    assert "No account found" in resp.text


def test_login_tells_user_password_is_incorrect():
    session = requests.Session()
    login_page = session.get(f"{BASE_URL}/login.php", timeout=5)
    token = extract_csrf(login_page.text)
    resp = session.post(
        f"{BASE_URL}/login.php",
        data={"email": ADMIN_EMAIL, "password": "wrong-password", "csrf_token": token},
        timeout=5,
    )
    assert "Incorrect password" in resp.text


def test_register_rejects_invalid_email_format():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Bad Email", "email": "not-an-email",
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "valid email" in resp.text.lower()


def test_register_rejects_short_password():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Short Pw", "email": email,
            "password": "abc", "confirm_password": "abc",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "at least 8 characters" in resp.text


def test_register_rejects_password_without_uppercase():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "No Upper", "email": email,
            "password": "pass1234!", "confirm_password": "pass1234!",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "uppercase" in resp.text


def test_register_rejects_password_without_number():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "No Num", "email": email,
            "password": "Password!", "confirm_password": "Password!",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "number" in resp.text


def test_register_rejects_password_without_special_char():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "No Special", "email": email,
            "password": "Password1", "confirm_password": "Password1",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "special character" in resp.text


def test_register_rejects_mismatched_passwords():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Mismatch", "email": email,
            "password": "Pass123!", "confirm_password": "Different456!",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "do not match" in resp.text


def test_register_rejects_duplicate_email():
    session = requests.Session()
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"

    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    first = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "First", "email": email,
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        timeout=5,
    )
    assert "Enter Academic Performance" in first.text  # first registration succeeded

    # second registration with the same email, from a fresh session
    session2 = requests.Session()
    reg_page2 = session2.get(f"{BASE_URL}/register.php", timeout=5)
    token2 = extract_csrf(reg_page2.text)
    second = session2.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Duplicate", "email": email,
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token2,
        },
        timeout=5,
    )
    assert "already exists" in second.text


@pytest.mark.parametrize("field,bad_value", [
    ("math_score", 150),     # above 100
    ("english_score", -10),  # negative
])
def test_student_submission_rejects_out_of_range_scores(field, bad_value):
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    reg_resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Bad Scores", "email": email,
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        timeout=5,
    )
    token = extract_csrf(reg_resp.text)
    payload = {
        "math_score": 90, "english_score": 50, "science_score": 88,
        "humanities_score": 45, "creative_arts_score": 30,
        "interest": "technology", "csrf_token": token,
    }
    payload[field] = bad_value
    resp = session.post(f"{BASE_URL}/student_dashboard.php", data=payload, timeout=5)
    assert "between 0 and 100" in resp.text
    assert "Recommended Pathway" not in resp.text


def test_student_submission_rejects_invalid_interest():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    reg_resp = session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "Bad Interest", "email": email,
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        timeout=5,
    )
    token = extract_csrf(reg_resp.text)
    resp = session.post(
        f"{BASE_URL}/student_dashboard.php",
        data={
            "math_score": 90, "english_score": 50, "science_score": 88,
            "humanities_score": 45, "creative_arts_score": 30,
            "interest": "music", "csrf_token": token,
        },
        timeout=5,
    )
    assert "valid interest" in resp.text.lower()
    assert "Recommended Pathway" not in resp.text


def test_student_cannot_access_admin_dashboard():
    session = requests.Session()
    reg_page = session.get(f"{BASE_URL}/register.php", timeout=5)
    token = extract_csrf(reg_page.text)
    email = f"student_{uuid.uuid4().hex[:8]}@test.com"
    session.post(
        f"{BASE_URL}/register.php",
        data={
            "full_name": "No Access Student", "email": email,
            "password": "Pass123!", "confirm_password": "Pass123!",
            "csrf_token": token,
        },
        timeout=5,
    )
    resp = session.get(f"{BASE_URL}/admin_dashboard.php", timeout=5)
    assert resp.status_code == 403
