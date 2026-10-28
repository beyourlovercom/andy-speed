#!/usr/bin/env python3
"""Fail-closed, one-SHA v0.2.1 source ZIP for manual signing verification.
Never publishes a Release and never accesses signing keys.
"""
from __future__ import annotations
import argparse
import hashlib
import json
import subprocess
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

SOURCE_SHA = "ebb64abb79273e4806806d4f3c1109f0a38fcd60"
EXPECTED_SHA256 = "cce5f9755f7944e4ddbf0776ed87ec3b4d29721101da2d88cff2a0eb84aba9ae"
FILES = (
    "andy-speed.php",
    "README.md",
    "assets/byl-v3/common.bundle.css",
    "assets/byl-v3/common.bundle.manifest.json",
)
PACKAGE_NAME = "andy-speed-v0.2.1.zip"

def run(*args: str) -> bytes:
    return subprocess.check_output(args, stderr=subprocess.DEVNULL)

def build(out: Path) -> dict:
    out.mkdir(mode=0o700, parents=True, exist_ok=True)
    current = run("git", "rev-parse", "HEAD").decode().strip()
    run("git", "merge-base", "--is-ancestor", SOURCE_SHA, current)
    contents = {f: run("git", "show", SOURCE_SHA + ":" + f) for f in FILES}
    if b"Version: 0.2.1" not in contents["andy-speed.php"]:
        raise RuntimeError("Version mismatch in immutable source")
    tmp = out / (PACKAGE_NAME + ".temporary")
    archive = out / PACKAGE_NAME
    if archive.exists() or tmp.exists():
        raise RuntimeError("Refuse to replace any previously signed package")
    with ZipFile(tmp, "w", compression=ZIP_DEFLATED, compresslevel=9) as z:
        for filename, data in contents.items():
            info = ZipInfo("andy-speed/" + filename, (1980, 1, 1, 0, 0, 0))
            info.compress_type = ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            z.writestr(info, data, compress_type=ZIP_DEFLATED, compresslevel=9)
    digest = hashlib.sha256(tmp.read_bytes()).hexdigest()
    if digest != EXPECTED_SHA256:
        tmp.unlink()
        raise RuntimeError(f"Cannot sign ZIP from unverified build: SHA256={digest}")
    tmp.replace(archive)
    manifest = {
        "schema_version": 1,
        "version": "0.2.1",
        "source_commit": SOURCE_SHA,
        "package": PACKAGE_NAME,
        "sha256": digest,
    }
    (out / "update-metadata.json").write_text(
        json.dumps(manifest, indent=2, sort_keys=True) + "\n", encoding="utf-8"
    )
    (out / "SHA256.txt").write_text(digest + "  " + PACKAGE_NAME + "\n", encoding="utf-8")
    return manifest

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    result = build(args.output.resolve())
    print("VERIFIED_MAIN_SOURCE=" + result["source_commit"])
    print("VERIFIED_PACKAGE_SHA256=" + result["sha256"])
    print("STABLE_PUBLICATION=NOT_PERFORMED")
