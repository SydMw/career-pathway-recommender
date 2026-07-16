"""Single source of truth for the model's input feature order.

Both train_model.py and api.py import this list rather than each
defining their own copy — the order here must match the order values
are packed into a row at prediction time.
"""
FEATURES = [
    "math_score",
    "english_score",
    "science_score",
    "humanities_score",
    "creative_arts_score",
    "interest_encoded",
]
