#!/usr/bin/env python3
"""Parse legacy BIFF4 .xls fingerprint scan logs (Format 1) via xlrd."""
import json
import sys
import warnings
from collections import defaultdict

warnings.filterwarnings("ignore")

try:
    import xlrd
except ImportError:
    print(json.dumps({"error": "xlrd not installed"}))
    sys.exit(1)


def parse_date(cell):
    if cell.ctype == xlrd.XL_CELL_DATE:
        return xlrd.xldate_as_datetime(cell.value, 0).strftime("%Y-%m-%d")
    val = str(cell.value).strip()
    if not val:
        return None
    from datetime import datetime
    for fmt in ("%Y-%m-%d", "%d-%m-%Y", "%d/%m/%Y", "%m/%d/%Y", "%Y/%m/%d"):
        try:
            return datetime.strptime(val[:10], fmt).strftime("%Y-%m-%d")
        except ValueError:
            continue
    return None


def parse_time(cell):
    if cell.ctype == xlrd.XL_CELL_DATE:
        dt = xlrd.xldate_as_datetime(cell.value, 0)
        return dt.strftime("%H:%M:%S")
    val = str(cell.value).strip()
    if ":" in val:
        parts = val.split(":")
        if len(parts) >= 2:
            return f"{parts[0].zfill(2)}:{parts[1].zfill(2)}:{parts[2].zfill(2) if len(parts) > 2 else '00'}"
    return None


def main(path):
    wb = xlrd.open_workbook(path)
    sh = wb.sheet_by_index(0)

    header_row = None
    col_map = {}
    for r in range(min(10, sh.nrows)):
        first = str(sh.cell_value(r, 0)).strip()
        if first.lower() in ("tanggal scan", "tanggal"):
            header_row = r
            for c in range(sh.ncols):
                h = str(sh.cell_value(r, c)).strip()
                if h:
                    col_map[h] = c
            break

    if header_row is None:
        print(json.dumps({"error": "header row not found"}))
        sys.exit(1)

    aggregated = defaultdict(lambda: {
        "raw_pin": "", "raw_nip": "", "raw_name": "",
        "scan_date": "", "check_ins": [], "check_outs": []
    })

    for r in range(header_row + 1, sh.nrows):
        scan_date_col = col_map.get("Tanggal scan", col_map.get("Tanggal", 0))
        scan_date = parse_date(sh.cell(r, scan_date_col))
        if not scan_date:
            continue

        nip_col = col_map.get("NIP", -1)
        if nip_col < 0:
            continue
        nip = str(sh.cell_value(r, nip_col)).strip()
        if not nip or nip.endswith(".0"):
            nip = nip.replace(".0", "")
        if not nip:
            continue

        pin_col = col_map.get("PIN", nip_col)
        pin = str(sh.cell_value(r, pin_col)).strip().replace(".0", "")
        name_col = col_map.get("Nama", -1)
        name = str(sh.cell_value(r, name_col)).strip() if name_col >= 0 else ""

        key = f"{nip}|{scan_date}"
        agg = aggregated[key]
        agg["raw_nip"] = nip
        agg["raw_pin"] = pin or nip
        agg["raw_name"] = name
        agg["scan_date"] = scan_date

        io_col = col_map.get("I/O", -1)
        jam_col = col_map.get("Jam", -1)
        if io_col >= 0 and jam_col >= 0:
            io = float(sh.cell_value(r, io_col) or 0)
            time = parse_time(sh.cell(r, jam_col))
            if time:
                if io == 1.0:
                    agg["check_ins"].append(time)
                elif io == 2.0:
                    agg["check_outs"].append(time)

    result = []
    for key, agg in aggregated.items():
        result.append({
            "raw_pin": agg["raw_pin"],
            "raw_nip": agg["raw_nip"],
            "raw_name": agg["raw_name"],
            "scan_date": agg["scan_date"],
            "check_in": min(agg["check_ins"]) if agg["check_ins"] else None,
            "check_out": max(agg["check_outs"]) if agg["check_outs"] else None,
        })

    print(json.dumps({"rows": result, "total": len(result)}))


if __name__ == "__main__":
    main(sys.argv[1])
