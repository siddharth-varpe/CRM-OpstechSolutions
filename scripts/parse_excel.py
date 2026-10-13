#!/usr/bin/env python3
"""
High-performance Excel (XLSX & XLS) and CSV parser using Python standard library.
Accepts a file path as argv[1] and outputs JSON array of rows to stdout.
Zero external dependencies required.
"""
import sys
import os
import json
import csv
import tempfile
import subprocess
import zipfile
import xml.etree.ElementTree as ET

def col_to_index(col_str):
    idx = 0
    for ch in col_str.upper():
        if 'A' <= ch <= 'Z':
            idx = idx * 26 + (ord(ch) - ord('A') + 1)
    return max(0, idx - 1)

def clean_val(v):
    if v is None:
        return ''
    s = str(v).strip()
    if s.endswith('.0') and s[:-2].replace('-', '').isdigit():
        return s[:-2]
    return s

def parse_csv(file_path):
    rows = []
    try:
        with open(file_path, 'r', encoding='utf-8-sig', errors='replace') as f:
            sample = f.read(4096)
            f.seek(0)
            delimiter = ','
            if sample.count(';') > sample.count(','):
                delimiter = ';'
            elif sample.count('\t') > sample.count(','):
                delimiter = '\t'
            reader = csv.reader(f, delimiter=delimiter)
            for r in reader:
                rows.append([clean_val(c) for c in r])
    except Exception as e:
        sys.stderr.write(f"Error parsing CSV: {e}\n")
    return rows

def parse_xls_via_libreoffice(file_path):
    with tempfile.TemporaryDirectory() as tmpdir:
        try:
            cmd = ['libreoffice', '--headless', '--convert-to', 'csv', file_path, '--outdir', tmpdir]
            res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=20)
            if res.returncode == 0:
                base_name = os.path.splitext(os.path.basename(file_path))[0]
                csv_path = os.path.join(tmpdir, base_name + '.csv')
                if os.path.exists(csv_path):
                    return parse_csv(csv_path)
        except Exception as e:
            sys.stderr.write(f"LibreOffice conversion failed: {e}\n")
    return []

def parse_xlsx(file_path):
    if not zipfile.is_zipfile(file_path):
        return parse_xls_via_libreoffice(file_path)

    try:
        with zipfile.ZipFile(file_path, 'r') as z:
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
                for si in tree.iter():
                    if si.tag.endswith('si'):
                        texts = [t.text for t in si.iter() if t.tag.endswith('t') and t.text]
                        shared_strings.append(''.join(texts))
            
            # Find first sheet
            sheet_name = 'xl/worksheets/sheet1.xml'
            if sheet_name not in z.namelist():
                worksheets = [n for n in z.namelist() if n.startswith('xl/worksheets/sheet') and n.endswith('.xml')]
                if worksheets:
                    sheet_name = sorted(worksheets)[0]
                else:
                    return []

            sheet = ET.fromstring(z.read(sheet_name))
            rows_data = []

            for row in sheet.iter():
                if not row.tag.endswith('row'):
                    continue
                cell_dict = {}
                max_idx = -1
                for c in row.iter():
                    if not c.tag.endswith('c'):
                        continue
                    r_ref = c.get('r', '')
                    col_letters = ''.join(filter(str.isalpha, r_ref))
                    col_idx = col_to_index(col_letters) if col_letters else len(cell_dict)
                    if col_idx > max_idx:
                        max_idx = col_idx

                    t_attr = c.get('t')
                    v = None
                    for child in c:
                        if child.tag.endswith('v'):
                            v = child
                            break

                    val = ''
                    if v is not None and v.text is not None:
                        if t_attr == 's':
                            try:
                                s_idx = int(v.text)
                                val = shared_strings[s_idx] if s_idx < len(shared_strings) else ''
                            except (ValueError, IndexError):
                                val = ''
                        else:
                            val = v.text
                    else:
                        inline_texts = [t.text for t in c.iter() if t.tag.endswith('t') and t.text]
                        if inline_texts:
                            val = ''.join(inline_texts)
                    
                    cell_dict[col_idx] = clean_val(val)

                if cell_dict:
                    row_list = [cell_dict.get(i, '') for i in range(max_idx + 1)]
                    rows_data.append(row_list)

            return rows_data
    except Exception as e:
        sys.stderr.write(f"Error parsing xlsx: {str(e)}\n")
        return parse_xls_via_libreoffice(file_path)

if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.stderr.write("Usage: parse_excel.py <path_to_file>\n")
        sys.exit(1)
    
    file_path = sys.argv[1]
    ext = os.path.splitext(file_path)[1].lower()
    if ext in ['.csv', '.txt']:
        rows = parse_csv(file_path)
    else:
        rows = parse_xlsx(file_path)
        if not rows and ext == '.xls':
            rows = parse_xls_via_libreoffice(file_path)
            
    print(json.dumps(rows))
