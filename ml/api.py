"""
Machine Learning Module API (report Section 3.4.3 / FR4, FR5).

Exposes a small HTTP API the PHP backend calls to get a pathway prediction
and a human-readable explanation. Run with:

    python ml/api.py

Listens on http://127.0.0.1:5001
"""
import joblib
import numpy as np
import pandas as pd
import pymysql
from flask import Flask, jsonify, request

from features import FEATURES
from train_model import train_and_save

app = Flask(__name__)

DB_CONFIG = dict(host="localhost", user="root", password="", database="career_system")

model = joblib.load("ml/model/decision_tree.joblib")
interest_encoder = joblib.load("ml/model/interest_encoder.joblib")
label_encoder = joblib.load("ml/model/label_encoder.joblib")
collab_scaler = joblib.load("ml/model/collab_scaler.joblib")
collab_knn = joblib.load("ml/model/collab_knn.joblib")

# Hybrid blend weight: content-based (Decision Tree) vs. collaborative (peer KNN).
# Content-based leads because it is the interpretable, validated model (FR5);
# collaborative filtering nudges the result toward what similar students chose.
CONTENT_WEIGHT = 0.7
COLLAB_WEIGHT = 0.3


def build_explanation(scores, interest, predicted_pathway):
    """Produces a plain-language explanation for FR5 (interpretability)."""
    stem_strength = (scores["math_score"] + scores["science_score"]) / 2
    social_strength = (scores["humanities_score"] + scores["english_score"]) / 2
    arts_strength = scores["creative_arts_score"]

    strengths = {
        "STEM": stem_strength,
        "Social Sciences": social_strength,
        "Arts and Sports Science": arts_strength,
    }
    top_area = max(strengths, key=strengths.get)

    reasons = []
    if top_area == predicted_pathway:
        reasons.append(
            f"your strongest academic performance is in subjects aligned with "
            f"{predicted_pathway} (average score {strengths[top_area]:.1f}%)"
        )
    if interest in ("technology", "science") and predicted_pathway == "STEM":
        reasons.append(f"your stated interest in {interest} matches this pathway")
    if interest in ("business", "humanities") and predicted_pathway == "Social Sciences":
        reasons.append(f"your stated interest in {interest} matches this pathway")
    if interest in ("arts", "sports") and predicted_pathway == "Arts and Sports Science":
        reasons.append(f"your stated interest in {interest} matches this pathway")

    if not reasons:
        reasons.append(
            f"this pathway best balances your subject scores and stated interest ({interest})"
        )

    return "Recommended because " + " and ".join(reasons) + "."


@app.route("/predict", methods=["POST"])
def predict():
    data = request.get_json(force=True)

    required = [
        "math_score",
        "english_score",
        "science_score",
        "humanities_score",
        "creative_arts_score",
        "interest",
    ]
    missing = [f for f in required if f not in data]
    if missing:
        return jsonify({"error": f"Missing fields: {', '.join(missing)}"}), 400

    interest = str(data["interest"]).lower().strip()
    if interest not in interest_encoder.classes_:
        return jsonify({
            "error": f"Unknown interest '{interest}'. Expected one of: "
                      f"{list(interest_encoder.classes_)}"
        }), 400

    score_fields = [f for f in required if f != "interest"]
    scores = {}
    for f in score_fields:
        try:
            value = float(data[f])
        except (TypeError, ValueError):
            return jsonify({"error": f"'{f}' must be a number."}), 400
        if not (0 <= value <= 100):
            return jsonify({"error": f"'{f}' must be between 0 and 100 (got {value})."}), 400
        scores[f] = value
    interest_encoded = int(interest_encoder.transform([interest])[0])

    row = np.array([[
        scores["math_score"],
        scores["english_score"],
        scores["science_score"],
        scores["humanities_score"],
        scores["creative_arts_score"],
        interest_encoded,
    ]])

    # Content-based component: Decision Tree trained on this student's own
    # academic profile and stated interest (report Section 2.4.1).
    content_proba = model.predict_proba(row)[0]

    # Collaborative component: pathway distribution among the most similar
    # peer profiles in the training population, via scaled KNN (report
    # Section 2.4.2 / Objective 2).
    row_scaled = collab_scaler.transform(row)
    collab_proba = collab_knn.predict_proba(row_scaled)[0]

    # Hybrid blend (FR4 / Objective 2: hybrid content-based + collaborative).
    blended = CONTENT_WEIGHT * content_proba + COLLAB_WEIGHT * collab_proba

    pred_idx = int(np.argmax(blended))
    predicted_pathway = label_encoder.inverse_transform([pred_idx])[0]
    confidence = round(float(blended[pred_idx]) * 100, 2)

    ranking = sorted(
        zip(label_encoder.classes_, (blended * 100).round(2)),
        key=lambda x: x[1],
        reverse=True,
    )

    explanation = build_explanation(scores, interest, predicted_pathway)
    peer_pct = round(float(collab_proba[pred_idx]) * 100, 1)
    if peer_pct >= 40:
        explanation += f" Additionally, {peer_pct}% of students with a similar academic profile also pursued {predicted_pathway}."

    return jsonify({
        "pathway": predicted_pathway,
        "confidence": confidence,
        "explanation": explanation,
        "ranking": [{"pathway": p, "score": s} for p, s in ranking],
        "model_used": "Hybrid (DecisionTree content-based + KNN collaborative)",
        "content_based": {
            "pathway": label_encoder.inverse_transform([int(np.argmax(content_proba))])[0],
            "confidence": round(float(np.max(content_proba)) * 100, 2),
        },
        "collaborative": {
            "pathway": label_encoder.inverse_transform([int(np.argmax(collab_proba))])[0],
            "confidence": round(float(np.max(collab_proba)) * 100, 2),
        },
    })


def reload_models():
    global model, interest_encoder, label_encoder, collab_scaler, collab_knn
    model = joblib.load("ml/model/decision_tree.joblib")
    interest_encoder = joblib.load("ml/model/interest_encoder.joblib")
    label_encoder = joblib.load("ml/model/label_encoder.joblib")
    collab_scaler = joblib.load("ml/model/collab_scaler.joblib")
    collab_knn = joblib.load("ml/model/collab_knn.joblib")


def fetch_real_student_data() -> pd.DataFrame:
    """Pulls accumulated student submissions + the pathway they were issued
    (report FR8: admin-triggered retraining as new data becomes available)."""
    conn = pymysql.connect(**DB_CONFIG, cursorclass=pymysql.cursors.DictCursor)
    try:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT a.math_score, a.english_score, a.science_score,
                       a.humanities_score, a.creative_arts_score, a.interests AS interest,
                       p.name AS pathway
                FROM academic_records a
                JOIN recommendations r ON r.record_id = a.record_id
                JOIN pathways p ON p.pathway_id = r.pathway_id
                """
            )
            rows = cur.fetchall()
    finally:
        conn.close()
    return pd.DataFrame(rows)


@app.route("/retrain", methods=["POST"])
def retrain():
    """FR8: allows an authorized administrator to retrain the model as new
    student data becomes available. Combines the synthetic baseline dataset
    with real accumulated submissions so a handful of new records doesn't
    destabilize the model."""
    try:
        baseline = pd.read_csv("ml/data/students.csv")
        real_data = fetch_real_student_data()
        combined = pd.concat([baseline, real_data], ignore_index=True) if not real_data.empty else baseline

        results = train_and_save(combined)
        reload_models()

        return jsonify({
            "status": "retrained",
            "real_records_used": int(len(real_data)),
            "total_records_used": int(len(combined)),
            "metrics": results,
        })
    except Exception as e:
        return jsonify({"error": f"Retrain failed: {e}"}), 500


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False)
