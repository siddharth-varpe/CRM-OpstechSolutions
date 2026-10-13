#!/usr/bin/env python3
"""
Lightweight XLSX parser using Python standard library (zipfile + xml.etree.ElementTree).
Accepts an xlsx file path as argv[1] and outputs JSON array of rows to stdout.
Zero external dependencies required.
"""
import sys
import json
import zipfile
import xml.etree.ElementTree as ET

def col_to_index(col_str):
    idx = 0
    for ch in col_str.upper():
        if 'A' <= ch <= 'Z':
            idx = idx * 26 + (ord(ch) - ord('A') + 1)
    return max(0, idx - 1)

def parse_xlsx(file_path):
    try:
        with zipfile.ZipFile(file_path, 'r') as z:
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
                ns = '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
                for si in tree.findall(f'.//{ns}si'):
                    texts = [t.text for t in si.findall(f'.//{ns}t') if t.text]
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
            ns = '{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
            rows_data = []

            for row in sheet.findall(f'.//{ns}row'):
                cell_dict = {}
                max_idx = -1
                for c in row.findall(f'{ns}c'):
                    r_ref = c.get('r', '')
                    col_letters = ''.join(filter(str.isalpha, r_ref))
                    col_idx = col_to_index(col_letters) if col_letters else len(cell_dict)
                    if col_idx > max_idx:
                        max_idx = col_idx

                    t_attr = c.get('t')
                    v = c.find(f'{ns}v')
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
                    elif c.find(f'{ns}is/{ns}t') is not None:
                        val = c.find(f'{ns}is/{ns}t').text or ''
                    
                    cell_dict[col_idx] = val

                if cell_dict:
                    row_list = [cell_dict.get(i, '') for i in range(max_idx + 1)]
                    rows_data.append(row_list)

            return rows_data
    except Exception as e:
        sys.stderr.write(f"Error parsing xlsx: {str(e)}\n")
        return []

if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.stderr.write("Usage: parse_excel.py <path_to_xlsx>\n")
        sys.exit(1)
    
    file_path = sys.argv[1]
    rows = parse_xlsx(file_path)
    print(json.dumps(rows))
