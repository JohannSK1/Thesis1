#!/usr/bin/env python3
import re, sys
from pathlib import Path
from PyPDF2 import PdfReader

HEADERS = {
    "ABSTRACT"     : {"ABSTRACT"},
    "INTRODUCTION" : {"INTRODUCTION"},
    "METHODOLOGY"  : {"METHODOLOGY", "METHODS"},
    "CONCLUSION"   : {"CONCLUSION", "CONCLUSION AND RECOMMENDATIONS"},
    "REFERENCES"   : {"REFERENCES", "BIBLIOGRAPHY"},
}

STRUCTURAL      = {"INTRODUCTION", "METHODOLOGY", "CONCLUSION"}
PLACEHOLDER_RX  = re.compile(r"lorem ipsum", re.I)

# ──────────────────────────────────────────────────────────────────────────
def extract_text(pdf: Path) -> str:
    rd = PdfReader(str(pdf))
    return "\n".join(p.extract_text() or "" for p in rd.pages)

def clean_lines(txt: str):
    for ln in txt.splitlines():
        yield ln.strip().upper()

def header_present(lines, variants):
    return any(ln in variants for ln in lines)

# ──────────────────────────────────────────────────────────────────────────
def main() -> None:
    if len(sys.argv) != 2:
        print("FAIL: Usage: check_papers.py <pdf_file>"); return
    pdf = Path(sys.argv[1])
    if not pdf.is_file():
        print("FAIL: Cannot open PDF."); return

    raw   = extract_text(pdf)
    lines = list(clean_lines(raw))
    words = len(re.findall(r'\b\w+\b', raw))

    notes          = []
    missing_struct = []

    # ---------- ABSTRACT & REFERENCES -------------------------------------
    for logical in ("ABSTRACT", "REFERENCES"):
        if header_present(lines, HEADERS[logical]):
            notes.append(f"PASS: {logical} is present in the paper.")
        else:
            notes.append(f"FAIL: {logical} is not present in the paper.")

    # ---------- INTRO / METHOD / CONCLUSION (collapsed) -------------------
    for logical in STRUCTURAL:
        if not header_present(lines, HEADERS[logical]):
            missing_struct.append(logical)

    if missing_struct:
        notes.append("FAIL: Paper is incomplete. Some chapters are missing")
    else:
        notes.append("PASS: Paper is complete. All required chapters is present in the paper")

    # ---------- length & placeholder --------------------------------------
    if words >= 1500:
        notes.append(f"PASS: The paper consists of {words} words (>= 1500) which met the required count of words.")
    else:
        notes.append(f"FAIL: Full paper only {words} words (< 1500) which did not met the required count of words.")

    if PLACEHOLDER_RX.search(raw):
        notes.append("FAIL: Contains unnecessary placeholders.")
    else:
        notes.append("PASS: No unnecessary placeholders found in the paper.")

    # ---------- print results (no global summary) --------------------------
    for n in notes:
        print(n)

# ──────────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    main()
