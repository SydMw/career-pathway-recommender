"""
Measures the accuracy of the hybrid recommendation actually served by the
system, as opposed to the three individual classifiers benchmarked in
train_model.py.

train_model.py scores the Decision Tree, Random Forest and KNN separately
and writes those figures to ml/model/metrics.json. It never scores the
70/30 blend of Decision Tree and collaborative KNN that api.py returns to
students, so this script fills that gap.

It reads the saved models and writes nothing. Nothing in ml/model/ is
modified, so it is safe to run at any time.

Run from the project root with:

    python ml/evaluate_hybrid.py
"""
import json

import numpy as np
import pandas as pd
import pymysql
from sklearn.metrics import accuracy_score
from sklearn.model_selection import train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import StandardScaler

# Reuse the deployed configuration and the loaded model objects rather than
# redeclaring them, so this measures exactly what students are served.
from api import (COLLAB_WEIGHT, CONTENT_WEIGHT, DB_CONFIG, collab_knn,
                 collab_scaler, interest_encoder, label_encoder, model)
from features import FEATURES

# The saved models come from the retrain of 28 July 2026, which combined the
# synthetic baseline with the 18 real records that existed at that moment.
# Reproducing that exact dataset is what lets the train/test split below line
# up with the one those models were fitted on. If the model is retrained,
# update this to the new retrain time, or the check in main() will fail.
RETRAIN_CUTOFF = "2026-07-28 22:02:00"


def build_dataset() -> pd.DataFrame:
    """Rebuilds the synthetic baseline plus the real records as of the retrain."""
    baseline = pd.read_csv("ml/data/students.csv")

    conn = pymysql.connect(**DB_CONFIG, cursorclass=pymysql.cursors.DictCursor)
    try:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT a.math_score, a.english_score, a.science_score,
                       a.humanities_score, a.creative_arts_score, a.interest,
                       p.name AS pathway
                FROM academic_records a
                JOIN recommendations r ON r.record_id = a.record_id
                JOIN pathways p ON p.pathway_id = r.pathway_id
                WHERE r.created_at < %s
                """,
                (RETRAIN_CUTOFF,),
            )
            real = pd.DataFrame(cur.fetchall())
    finally:
        conn.close()

    for column in [c for c in real.columns if c.endswith("_score")]:
        real[column] = real[column].astype(float)

    print(f"Dataset: {len(baseline)} synthetic + {len(real)} real = {len(baseline) + len(real)}")
    return pd.concat([baseline, real], ignore_index=True)


def main():
    df = build_dataset()
    df["interest_encoded"] = interest_encoder.transform(df["interest"])
    X = df[FEATURES]
    y = label_encoder.transform(df["pathway"])

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
    print(f"Split:   {len(X_train)} train / {len(X_test)} test\n")

    # Sanity check. If this does not match metrics.json the split has drifted
    # and every number below it is meaningless, so say so loudly.
    dt_accuracy = accuracy_score(y_test, model.predict(X_test))
    with open("ml/model/metrics.json") as f:
        recorded = json.load(f)["Decision Tree"]["accuracy"]
    if abs(dt_accuracy - recorded) > 1e-9:
        print(f"WARNING: Decision Tree scores {dt_accuracy:.4f} here but metrics.json")
        print(f"         records {recorded:.4f}. The dataset no longer reproduces the")
        print(f"         one the saved models were trained on. Check RETRAIN_CUTOFF.\n")

    content = model.predict_proba(X_test)

    # The collaborative KNN is deliberately fitted on every student in the
    # system, which is correct in production because a genuinely new student
    # is never in their own peer pool. Evaluating with it would put each test
    # student among their own 25 neighbours and flatter the result, so the
    # honest measurement refits the peer pool on training students only.
    scaler = StandardScaler().fit(X_train)
    peers = KNeighborsClassifier(n_neighbors=25).fit(scaler.transform(X_train), y_train)
    collaborative = peers.predict_proba(scaler.transform(X_test))

    blended = CONTENT_WEIGHT * content + COLLAB_WEIGHT * collaborative
    hybrid_accuracy = accuracy_score(y_test, np.argmax(blended, axis=1))
    collaborative_only = accuracy_score(y_test, np.argmax(collaborative, axis=1))

    inflated = accuracy_score(
        y_test,
        np.argmax(
            CONTENT_WEIGHT * content
            + COLLAB_WEIGHT * collab_knn.predict_proba(collab_scaler.transform(X_test)),
            axis=1,
        ),
    )

    print(f"Decision Tree alone         {dt_accuracy * 100:.2f}%   content-based, the figure in the report")
    print(f"Collaborative KNN alone     {collaborative_only * 100:.2f}%   k=25 on standardised features")
    print(f"Hybrid {int(CONTENT_WEIGHT * 100)}/{int(COLLAB_WEIGHT * 100)}                {hybrid_accuracy * 100:.2f}%   what the system actually serves")
    print(f"\nThe blend gains {(hybrid_accuracy - dt_accuracy) * 100:+.2f} points over the Decision Tree alone.")
    print(f"\nFor reference, scoring the blend with the deployed peer pool instead")
    print(f"gives {inflated * 100:.2f}%, inflated because the test students sit inside it.")


if __name__ == "__main__":
    main()
