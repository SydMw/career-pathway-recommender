"""
Tests for the ML Module API (ml/api.py). Requires the Flask service to be
running on http://127.0.0.1:5001 (python ml/api.py).

Run with: pytest tests/test_ml_api.py -v
"""
import pytest
import requests

BASE_URL = "http://127.0.0.1:5001"

VALID_PAYLOAD = {
    "math_score": 85,
    "english_score": 60,
    "science_score": 82,
    "humanities_score": 55,
    "creative_arts_score": 40,
    "interest": "technology",
}


def test_health():
    resp = requests.get(f"{BASE_URL}/health", timeout=5)
    assert resp.status_code == 200
    assert resp.json()["status"] == "ok"


def test_predict_valid_payload_returns_stem_for_strong_math_science():
    resp = requests.post(f"{BASE_URL}/predict", json=VALID_PAYLOAD, timeout=5)
    assert resp.status_code == 200
    data = resp.json()
    assert data["pathway"] == "STEM"
    assert 0 <= data["confidence"] <= 100
    assert "explanation" in data and len(data["explanation"]) > 0
    assert len(data["ranking"]) == 3
    assert "content_based" in data and "collaborative" in data


def test_predict_missing_field_returns_400():
    payload = dict(VALID_PAYLOAD)
    del payload["math_score"]
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400
    assert "error" in resp.json()


def test_predict_unknown_interest_returns_400():
    payload = dict(VALID_PAYLOAD, interest="astrology")
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400
    assert "error" in resp.json()


# --- Negative test cases: deliberately wrong/invalid data ----------------
# Each of these MUST fail (HTTP 400) for the system to be considered
# correct. A 200 response here would mean bad data is silently accepted.

@pytest.mark.parametrize("field,bad_value", [
    ("math_score", 150),       # above the 0-100 scale
    ("english_score", -5),     # negative score
    ("science_score", 101.5),  # just over the boundary
    ("humanities_score", -0.1),  # just under the boundary
])
def test_predict_rejects_out_of_range_scores(field, bad_value):
    payload = dict(VALID_PAYLOAD, **{field: bad_value})
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400, f"Expected rejection for {field}={bad_value}, got {resp.status_code}"
    assert "error" in resp.json()


def test_predict_rejects_non_numeric_score():
    payload = dict(VALID_PAYLOAD, math_score="not-a-number")
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400
    assert "must be a number" in resp.json()["error"]


def test_predict_rejects_null_score():
    payload = dict(VALID_PAYLOAD, math_score=None)
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400


def test_predict_rejects_empty_interest_string():
    payload = dict(VALID_PAYLOAD, interest="")
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400


def test_predict_rejects_sql_injection_style_interest():
    # The interest field is matched against a fixed encoder vocabulary, so
    # injection-style strings should be rejected the same as any unknown
    # value, never reach the database (the ML API doesn't touch SQL at all
    # in /predict), and never crash the server.
    payload = dict(VALID_PAYLOAD, interest="technology'; DROP TABLE users;--")
    resp = requests.post(f"{BASE_URL}/predict", json=payload, timeout=5)
    assert resp.status_code == 400
    assert "error" in resp.json()


def test_predict_ranking_sums_to_roughly_100_percent():
    resp = requests.post(f"{BASE_URL}/predict", json=VALID_PAYLOAD, timeout=5)
    total = sum(r["score"] for r in resp.json()["ranking"])
    assert 99.0 <= total <= 101.0


@pytest.mark.slow
def test_retrain_endpoint_returns_metrics():
    resp = requests.post(f"{BASE_URL}/retrain", timeout=60)
    assert resp.status_code == 200
    data = resp.json()
    assert data["status"] == "retrained"
    assert data["total_records_used"] > 0
    assert "Decision Tree" in data["metrics"]
    assert data["metrics"]["Decision Tree"]["accuracy"] > 0.7
