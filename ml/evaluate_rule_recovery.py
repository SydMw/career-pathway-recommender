"""
Tests whether the Decision Tree merely memorises the rule that produced the
synthetic labels, or actually learns the pattern underneath them.

The synthetic dataset is labelled by rule_label() in generate_data.py, and
then 8 percent of those labels are replaced at random. A fair criticism of
any project built this way is that the model has been handed a rule and
asked to give it back, which would make the accuracy figures meaningless.

This measures the answer directly. It recomputes what the rule would have
said for every student, with no noise applied, and compares that against
both the stored labels and the tree's own predictions.

Two results matter. The tree agrees with the rule well short of 100 percent,
because a depth 6 tree splitting one feature at a time cannot exactly
represent averaging two subjects and comparing three totals. And on the
students whose stored label the noise had corrupted, the tree still tends to
produce the rule's original answer, which is generalisation rather than
memorisation.

Uses only the synthetic dataset, since the real records in the database were
labelled by the system rather than by the rule. No database is needed, and
nothing is written.

Run from the project root with:

    python ml/evaluate_rule_recovery.py
"""
import numpy as np
import pandas as pd
from sklearn.model_selection import train_test_split

# One definition of the rule, shared with the generator that applied it.
from api import interest_encoder, label_encoder, model
from features import FEATURES
from generate_data import NOISE_RATE, PATHWAYS, rule_label


def main():
    df = pd.read_csv("ml/data/students.csv")
    df["rule_label"] = [
        rule_label(row._asdict(), row.interest) for row in df.itertuples()
    ]
    df["interest_encoded"] = interest_encoder.transform(df["interest"])

    # A label survives untouched with probability (1 - NOISE_RATE), and a
    # replaced one lands back on its original pathway by chance one time in
    # three, so this is the most any perfect learner could agree with.
    ceiling = (1 - NOISE_RATE) + NOISE_RATE / len(PATHWAYS)
    matches = (df["pathway"] == df["rule_label"]).mean()
    print(f"Dataset: {len(df)} synthetic students")
    print(f"  {'stored labels matching the noiseless rule':<44}{matches * 100:.2f}%")
    print(f"  {f'most that {NOISE_RATE:.0%} label noise would allow':<44}{ceiling * 100:.2f}%\n")

    y = label_encoder.transform(df["pathway"])
    _, test_idx = train_test_split(
        np.arange(len(df)), test_size=0.2, random_state=42, stratify=y
    )
    predicted = label_encoder.inverse_transform(model.predict(df[FEATURES].iloc[test_idx]))
    stored = df["pathway"].iloc[test_idx].values
    rule = df["rule_label"].iloc[test_idx].values

    print(f"Held-out test rows: {len(test_idx)}")
    print(f"  {'tree agrees with the stored, noisy label':<44}{(predicted == stored).mean() * 100:.2f}%")
    print(f"  {'tree agrees with the noiseless rule':<44}{(predicted == rule).mean() * 100:.2f}%\n")

    corrupted = stored != rule
    recovered = (predicted[corrupted] == rule[corrupted]).sum()
    print(f"Of the {corrupted.sum()} test students whose label the noise corrupted,")
    print(f"the tree still gave the rule's original answer for {recovered}.\n")

    clean = ~corrupted
    print(f"On the {clean.sum()} uncorrupted rows it matches the rule "
          f"{(predicted[clean] == rule[clean]).mean() * 100:.2f}% of the time, so it")
    print("approximates the rule rather than reproducing it.")


if __name__ == "__main__":
    main()
