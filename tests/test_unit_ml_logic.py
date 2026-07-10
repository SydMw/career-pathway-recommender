"""
True unit tests: each test exercises a single pure function in isolation,
with no database, network, or Flask server involved. Distinct from
test_ml_api.py and test_web_flow.py, which are integration/system tests
that hit the running ML API / web app over real HTTP.

Run with: pytest tests/test_unit_ml_logic.py -v
"""
import sys
from pathlib import Path

import numpy as np

# ml/ is not a package; add it to the path so we can import its modules
# directly, the same way ml/api.py does when run from the project root.
sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "ml"))

from api import build_explanation  # noqa: E402
from generate_data import INTERESTS, PATHWAYS, generate_student  # noqa: E402


# --- build_explanation() -----------------------------------------------

def test_explanation_cites_strongest_subject_area_when_it_matches_prediction():
    scores = {
        "math_score": 90, "english_score": 50, "science_score": 88,
        "humanities_score": 45, "creative_arts_score": 30,
    }
    explanation = build_explanation(scores, "technology", "STEM")
    assert "STEM" in explanation
    assert "strongest academic performance" in explanation


def test_explanation_cites_matching_interest():
    scores = {
        "math_score": 90, "english_score": 50, "science_score": 88,
        "humanities_score": 45, "creative_arts_score": 30,
    }
    explanation = build_explanation(scores, "technology", "STEM")
    assert "interest in technology" in explanation


def test_explanation_falls_back_when_neither_strength_nor_interest_align():
    # Strongest subjects are humanities-leaning but the prediction is Arts,
    # and the interest doesn't map to Arts either -> no direct reason matches.
    scores = {
        "math_score": 40, "english_score": 80, "science_score": 40,
        "humanities_score": 85, "creative_arts_score": 30,
    }
    explanation = build_explanation(scores, "business", "Arts and Sports Science")
    assert "best balances your subject scores" in explanation


def test_explanation_never_empty_for_any_pathway():
    scores = {
        "math_score": 60, "english_score": 60, "science_score": 60,
        "humanities_score": 60, "creative_arts_score": 60,
    }
    for pathway in PATHWAYS:
        explanation = build_explanation(scores, "sports", pathway)
        assert isinstance(explanation, str) and len(explanation) > 10


# --- generate_student() (synthetic data generator) ----------------------

def test_generate_student_scores_within_valid_range():
    student = generate_student(0)
    for field in ("math_score", "english_score", "science_score",
                  "humanities_score", "creative_arts_score"):
        assert 0 <= student[field] <= 100


def test_generate_student_interest_is_known_category():
    student = generate_student(0)
    assert student["interest"] in INTERESTS


def test_generate_student_pathway_is_known_category():
    student = generate_student(0)
    assert student["pathway"] in PATHWAYS


def test_generate_student_technology_interest_biases_stem_scores():
    np.random.seed(123)
    # Average over several draws since generation has random noise; a
    # technology-interested student should average higher math/science
    # than the unconditioned population mean of 60.
    samples = [generate_student(i) for i in range(50)]
    tech_samples = [s for s in samples if s["interest"] == "technology"]
    assert len(tech_samples) > 0
    avg_math = sum(s["math_score"] for s in tech_samples) / len(tech_samples)
    assert avg_math > 60
