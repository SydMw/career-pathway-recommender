"""
Generates a synthetic dataset of secondary school student records for the
AI-Powered Career and Pathway Recommendation System.

Until a real anonymized KNEC/school dataset is available, this produces a
realistic ~3000-row dataset (matching the sample size described in the
project report, Section 3.3.3) with believable correlations between subject
scores, interests, and CBC pathway outcome so the Decision Tree / Random
Forest / KNN models have a genuine pattern to learn.
"""
import numpy as np
import pandas as pd

np.random.seed(42)

N = 3000
INTERESTS = ["technology", "business", "science", "arts", "sports", "humanities"]
PATHWAYS = ["STEM", "Social Sciences", "Arts and Sports Science"]


def generate_student(_):
    interest = np.random.choice(INTERESTS, p=[0.20, 0.18, 0.20, 0.14, 0.13, 0.15])

    # Base scores drawn from a normal distribution per subject, then biased
    # by the student's interest to create a learnable signal.
    math = np.random.normal(60, 15)
    english = np.random.normal(62, 13)
    science = np.random.normal(60, 15)
    humanities = np.random.normal(62, 14)
    creative_arts = np.random.normal(60, 16)

    if interest == "technology":
        math += 12
        science += 10
    elif interest == "science":
        science += 14
        math += 8
    elif interest == "business":
        humanities += 10
        english += 6
    elif interest == "humanities":
        humanities += 14
        english += 8
    elif interest == "arts":
        creative_arts += 16
    elif interest == "sports":
        creative_arts += 8

    scores = {
        "math_score": math,
        "english_score": english,
        "science_score": science,
        "humanities_score": humanities,
        "creative_arts_score": creative_arts,
    }
    scores = {k: float(np.clip(v, 0, 100)) for k, v in scores.items()}

    # Rule-of-thumb labeling that mirrors how counselors actually reason
    # (strongest relevant subjects + stated interest), plus label noise to
    # simulate real-world inconsistency the model must generalize over.
    stem_strength = (scores["math_score"] + scores["science_score"]) / 2
    social_strength = (scores["humanities_score"] + scores["english_score"]) / 2
    arts_strength = scores["creative_arts_score"]

    interest_bonus = {
        "STEM": 15 if interest in ("technology", "science") else 0,
        "Social Sciences": 15 if interest in ("business", "humanities") else 0,
        "Arts and Sports Science": 15 if interest in ("arts", "sports") else 0,
    }

    pathway_scores = {
        "STEM": stem_strength + interest_bonus["STEM"],
        "Social Sciences": social_strength + interest_bonus["Social Sciences"],
        "Arts and Sports Science": arts_strength + interest_bonus["Arts and Sports Science"],
    }

    label = max(pathway_scores, key=pathway_scores.get)

    # 8% label noise to avoid a trivially separable dataset.
    if np.random.rand() < 0.08:
        label = np.random.choice(PATHWAYS)

    scores["interest"] = interest
    scores["pathway"] = label
    return scores


def main():
    rows = [generate_student(i) for i in range(N)]
    df = pd.DataFrame(rows)
    df = df.round(2)
    out_path = "ml/data/students.csv"
    df.to_csv(out_path, index=False)
    print(f"Wrote {len(df)} rows to {out_path}")
    print(df["pathway"].value_counts())


if __name__ == "__main__":
    main()
