"""Shows how the Decision Tree and the peer KNN each voted for one student,
and how the 70/30 blend turned those two opinions into the final answer.

Run from the project root:

    python ml/explain_prediction.py 88 60 85 55 45 technology

Score order: maths english science humanities creative_arts, then the
interest. Run with no arguments to see a worked example.

Read-only. It never trains, never saves, and never touches metrics.json.
"""
import os
import sys

import joblib
import numpy as np
import pandas as pd

from features import FEATURES

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MODEL_DIR = os.path.join(ROOT, "ml", "model")

# These live in ml/api.py. Change them there, not here.
CONTENT_WEIGHT = 0.7
COLLAB_WEIGHT = 0.3


def load(name):
    return joblib.load(os.path.join(MODEL_DIR, name))


def main():
    args = sys.argv[1:]
    if len(args) == 6:
        try:
            scores = [float(a) for a in args[:5]]
        except ValueError:
            print("The five scores must be numbers.")
            return
        interest = args[5].lower().strip()
    elif args:
        print(__doc__)
        return
    else:
        scores = [40.0, 40.0, 40.0, 40.0, 40.0]
        interest = "humanities"
        print("No scores given, so here is a worked example.\n")

    tree = load("decision_tree.joblib")
    interest_encoder = load("interest_encoder.joblib")
    label_encoder = load("label_encoder.joblib")
    scaler = load("collab_scaler.joblib")
    knn = load("collab_knn.joblib")

    if interest not in interest_encoder.classes_:
        print(f"'{interest}' is not one of the interests this model knows.")
        print("Choose one of:", ", ".join(interest_encoder.classes_))
        return

    interest_encoded = int(interest_encoder.transform([interest])[0])
    row = pd.DataFrame([scores + [interest_encoded]], columns=FEATURES)

    tree_proba = tree.predict_proba(row)[0]
    knn_proba = knn.predict_proba(scaler.transform(row))[0]
    blended = CONTENT_WEIGHT * tree_proba + COLLAB_WEIGHT * knn_proba

    print("Scores  :", ", ".join(f"{name.replace('_score', '')} {value:.0f}"
                                 for name, value in zip(FEATURES[:5], scores)))
    print("Interest:", interest)
    print()

    print(f"{'Pathway':<26}{'Decision Tree':>15}{'KNN (' + str(knn.n_neighbors) + ' peers)':>17}{'Blended':>12}")
    print("-" * 70)
    for i, pathway in enumerate(label_encoder.classes_):
        print(f"{pathway:<26}{tree_proba[i] * 100:14.2f}%"
              f"{knn_proba[i] * 100:16.0f}%{blended[i] * 100:11.2f}%")

    winner = label_encoder.classes_[int(np.argmax(blended))]
    print()
    print(f"Recommended: {winner} at {blended.max() * 100:.2f}% confidence")
    print(f"Worked out as: ({CONTENT_WEIGHT} x tree) + ({COLLAB_WEIGHT} x peers), highest wins")
    print()

    # How much evidence sits behind the tree's answer.
    leaf = tree.apply(row)[0]
    in_leaf = int(tree.tree_.n_node_samples[leaf])
    print(f"The tree sorted this student into a group of {in_leaf} training students.")
    if in_leaf < 20:
        print("  That is one of its smallest groups, so treat this answer with care.")

    # How the neighbours actually voted.
    votes = np.rint(knn_proba * knn.n_neighbors).astype(int)
    detail = ", ".join(f"{v} to {p}" for p, v in zip(label_encoder.classes_, votes) if v)
    print(f"The {knn.n_neighbors} most similar students went: {detail}.")

    if label_encoder.classes_[int(np.argmax(tree_proba))] != label_encoder.classes_[int(np.argmax(knn_proba))]:
        print("\nThe two models disagreed. The tree carries more weight, so its answer won.")


if __name__ == "__main__":
    main()
