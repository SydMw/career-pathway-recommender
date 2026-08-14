"""Prints the facts about the trained models: how much data they were built
from, how the Decision Tree is shaped, and how each model scored.

Everything here is read from the saved model files, the dataset, and the
database. Nothing is typed in by hand, so the numbers are always current.

Run from the project root:

    python ml/model_facts.py

Read-only. It never trains, never saves, and never touches metrics.json.
"""
import collections
import os

import joblib
import numpy as np
import pandas as pd

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MODEL_DIR = os.path.join(ROOT, "ml", "model")
DATA_CSV = os.path.join(ROOT, "ml", "data", "students.csv")
METRICS = os.path.join(MODEL_DIR, "metrics.json")

DB_CONFIG = dict(host="localhost", user="root", password="", database="career_system")

# These live in ml/api.py. Change them there, not here.
CONTENT_WEIGHT = 0.7
COLLAB_WEIGHT = 0.3

TEST_SIZE = 0.2  # ml/train_model.py


def heading(text):
    print()
    print(text)
    print("-" * len(text))


def real_records_at_retrain(trained_at):
    """How many real student records existed when the model was last trained."""
    import pymysql

    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT COUNT(*)
                FROM academic_records a
                JOIN recommendations r ON r.record_id = a.record_id
                JOIN pathways p ON p.pathway_id = r.pathway_id
                WHERE r.created_at < %s
                """,
                (trained_at,),
            )
            return int(cur.fetchone()[0])
    finally:
        conn.close()


def main():
    tree = joblib.load(os.path.join(MODEL_DIR, "decision_tree.joblib"))
    knn = joblib.load(os.path.join(MODEL_DIR, "collab_knn.joblib"))
    label_encoder = joblib.load(os.path.join(MODEL_DIR, "label_encoder.joblib"))
    interest_encoder = joblib.load(os.path.join(MODEL_DIR, "interest_encoder.joblib"))

    trained_at = pd.Timestamp(os.path.getmtime(os.path.join(MODEL_DIR, "decision_tree.joblib")), unit="s", tz="UTC")
    trained_at = trained_at.tz_convert("Africa/Nairobi").tz_localize(None)

    heading("The data the model was built from")
    synthetic = sum(1 for _ in open(DATA_CSV)) - 1
    print(f"model last trained     : {trained_at.day} {trained_at:%B %Y at %H:%M}")
    print(f"synthetic records      : {synthetic}")
    try:
        real = real_records_at_retrain(trained_at)
        print(f"real student records   : {real}  (submitted before that retrain)")
    except Exception as exc:
        real = 0
        print(f"real student records   : could not read the database ({type(exc).__name__})")
        print("                         start MySQL in XAMPP and run again")

    total = synthetic + real
    n_test = int(np.ceil(total * TEST_SIZE))
    print(f"total dataset          : {total}")
    print(f"trained on             : {total - n_test}   ({int((1 - TEST_SIZE) * 100)}%)")
    print(f"tested on              : {n_test}   ({int(TEST_SIZE * 100)}%, never seen while training)")

    heading("Shape of the Decision Tree")
    t = tree.tree_
    is_leaf = t.children_left == -1
    leaves = np.where(is_leaf)[0]
    sizes = t.n_node_samples[leaves]

    depth = np.zeros(t.node_count, dtype=int)
    stack = [(0, 0)]
    while stack:
        node, d = stack.pop()
        depth[node] = d
        if not is_leaf[node]:
            stack.append((t.children_left[node], d + 1))
            stack.append((t.children_right[node], d + 1))

    print(f"questions it asks      : {t.node_count - len(leaves)}")
    print(f"end groups (leaves)    : {len(leaves)}   (always questions + 1)")
    print(f"deepest chain          : {tree.get_depth()} questions   (capped at max_depth={tree.max_depth})")
    print(f"widest it could be     : {2 ** tree.get_depth()} leaves if every branch ran the full depth")
    print(f"students per leaf      : smallest {sizes.min()}, middle {int(np.median(sizes))}, largest {sizes.max()}")
    print(f"smallest allowed leaf  : {tree.min_samples_leaf}   (min_samples_leaf)")
    print(f"leaves under 20        : {(sizes < 20).sum()} of {len(leaves)}   (these give the least reliable answers)")
    print("leaves by depth        :", ", ".join(
        f"{n} at depth {d}" for d, n in sorted(collections.Counter(depth[leaves]).items())))

    heading("The peer model (KNN)")
    print(f"neighbours consulted   : {knn.n_neighbors}")
    print(f"built from             : {knn.n_samples_fit_} student records (the whole population)")
    print("scaled before use      : yes, via collab_scaler.joblib")

    heading("How the two are combined")
    print(f"Decision Tree weight   : {CONTENT_WEIGHT:.0%}")
    print(f"peer KNN weight        : {COLLAB_WEIGHT:.0%}")
    print("set in                 : ml/api.py")

    heading("Scores from the last training run")
    metrics = pd.read_json(METRICS).T
    for name, row in metrics.iterrows():
        print(f"{name:<16} accuracy {row['accuracy']:.1%}   macro F1 {row['macro_f1']:.3f}"
              f"   5-fold CV {row['cv_accuracy_mean']:.1%}")
    print()
    print("note: the 70/30 blend itself was never scored separately -")
    print("      these are the three models measured on their own.")

    heading("What it predicts")
    print("pathways  :", ", ".join(label_encoder.classes_))
    print("interests :", ", ".join(interest_encoder.classes_))


if __name__ == "__main__":
    main()
