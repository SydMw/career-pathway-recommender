"""
Trains and benchmarks Decision Tree, Random Forest, and K-Nearest Neighbors
classifiers on the student dataset (Section 3.3.5 / 3.4.2 of the project
report). Decision Tree is selected as the production model for its
interpretability; Random Forest and KNN serve as comparison benchmarks.

Saves the trained Decision Tree model and the label/feature encoders to
ml/model/ for use by the prediction API (ml/api.py).
"""
import json

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import cross_val_score, train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.tree import DecisionTreeClassifier

from features import FEATURES


def load_data(df=None):
    if df is None:
        df = pd.read_csv("ml/data/students.csv")
    interest_encoder = LabelEncoder()
    df["interest_encoded"] = interest_encoder.fit_transform(df["interest"])

    label_encoder = LabelEncoder()
    df["pathway_encoded"] = label_encoder.fit_transform(df["pathway"])

    X = df[FEATURES]
    y = df["pathway_encoded"]
    return X, y, interest_encoder, label_encoder


def evaluate(name, model, X_test, y_test, label_encoder):
    preds = model.predict(X_test)
    acc = accuracy_score(y_test, preds)
    report = classification_report(
        y_test, preds, target_names=label_encoder.classes_, output_dict=True
    )
    print(f"\n=== {name} ===")
    print(f"Accuracy: {acc:.4f}")
    print(
        f"Precision (macro): {report['macro avg']['precision']:.4f}  "
        f"Recall (macro): {report['macro avg']['recall']:.4f}  "
        f"F1 (macro): {report['macro avg']['f1-score']:.4f}"
    )
    print("Confusion matrix:")
    print(confusion_matrix(y_test, preds))
    return acc, report


def train_and_save(df=None):
    """Trains, benchmarks, and persists all models. Returns the metrics dict.

    Pass a DataFrame (columns: math_score, english_score, science_score,
    humanities_score, creative_arts_score, interest, pathway) to retrain on
    different data, e.g. real accumulated student submissions (FR8).
    """
    X, y, interest_encoder, label_encoder = load_data(df)
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    dt = DecisionTreeClassifier(max_depth=6, min_samples_leaf=10, random_state=42)
    rf = RandomForestClassifier(n_estimators=200, max_depth=10, random_state=42)
    knn = KNeighborsClassifier(n_neighbors=9)

    results = {}
    for name, model in [("Decision Tree", dt), ("Random Forest", rf), ("KNN", knn)]:
        model.fit(X_train, y_train)
        acc, report = evaluate(name, model, X_test, y_test, label_encoder)
        cv_scores = cross_val_score(model, X, y, cv=5)
        print(f"5-fold CV accuracy: {cv_scores.mean():.4f} (+/- {cv_scores.std():.4f})")
        results[name] = {
            "accuracy": acc,
            "macro_precision": report["macro avg"]["precision"],
            "macro_recall": report["macro avg"]["recall"],
            "macro_f1": report["macro avg"]["f1-score"],
            "cv_accuracy_mean": float(cv_scores.mean()),
        }

    # Decision Tree is the production content-based model (interpretability
    # requirement, report Section 3.4.2 / 4.5.2).
    joblib.dump(dt, "ml/model/decision_tree.joblib")
    joblib.dump(interest_encoder, "ml/model/interest_encoder.joblib")
    joblib.dump(label_encoder, "ml/model/label_encoder.joblib")

    # Collaborative-filtering component (report Objective 2 / 2.4.2):
    # a scaled KNN over the full student population is used at inference
    # time to find "students with a similar profile" and surface what
    # pathway those peers ended up in, independent of the Decision Tree's
    # content-based reasoning.
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    collab_knn = KNeighborsClassifier(n_neighbors=25)
    collab_knn.fit(X_scaled, y)
    joblib.dump(scaler, "ml/model/collab_scaler.joblib")
    joblib.dump(collab_knn, "ml/model/collab_knn.joblib")

    with open("ml/model/metrics.json", "w") as f:
        json.dump(results, f, indent=2)

    print("\nSaved production model (Decision Tree) and encoders to ml/model/")
    print(json.dumps(results, indent=2))
    return results


if __name__ == "__main__":
    train_and_save()
