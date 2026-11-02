#!/usr/bin/env python3
"""
Làm sạch dữ liệu crawl (Biti's + Sneaker Buzz) và sinh SQL nhập vào database SoleHouse.

  python3 build.py --src ~/Documents/thangdv/Scraping/data

Đầu ra:
  - ../api/storage/app/public/products/{id}/*.webp   (ảnh chuẩn hóa: gốc ≤1600px, _md 800px, _sm 300px)
  - import_products.sql          (nhập: brands, categories, colors, sizes, products, variants, images, inventory + movements)
  - 00_clear_demo_catalog.sql    (TÙY CHỌN: xóa 16 sản phẩm demo của seeder)
  - out/cleaning_report.txt      (thống kê, dòng bị loại, bảng chuẩn hóa màu/thương hiệu)

Quy tắc làm sạch chính (xem README.md):
  * 1 dòng CSV = 1 sản phẩm-màu ⇒ gộp các dòng cùng mã mẫu (Biti's) / cùng tên (Sneaker Buzz) thành 1 sản phẩm, mỗi màu là biến thể.
  * Biến thể = Màu × Size; mỗi dòng góp (các màu của dòng) × (các size của dòng).
"""
import argparse, csv, hashlib, os, re, sys, unicodedata, uuid, collections, concurrent.futures as cf
from pathlib import Path

ROOT = Path(__file__).resolve().parent
ID_BASE = 1000  # id sản phẩm bắt đầu từ đây (seeder demo dùng 1..16)

# ---------------------------------------------------------------- chuẩn hóa văn bản
def nfc(s): return unicodedata.normalize('NFC', s or '')
def clean_ws(s): return re.sub(r'\s+', ' ', nfc(s).replace('\xa0', ' ')).strip()
def slugify(s):
    s = unicodedata.normalize('NFD', s.replace('đ', 'd').replace('Đ', 'D'))
    s = ''.join(c for c in s if unicodedata.category(c) != 'Mn')
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-')
def sql(v):
    if v is None: return 'NULL'
    if isinstance(v, bool): return '1' if v else '0'
    if isinstance(v, (int, float)): return str(v)
    return "'" + str(v).replace('\\', '\\\\').replace("'", "''") + "'"

# ---------------------------------------------------------------- thương hiệu
BRAND_MAP = {
    "biti's": "Biti's", 'hunter': "Biti's Hunter", 'sneaker buzz': 'Sneaker Buzz', 'sneakerbuzz': 'Sneaker Buzz', 'buzz': 'Sneaker Buzz',
    'vans': 'Vans', 'converse': 'Converse', 'everlast': 'Everlast', 'ncaa': 'NCAA', 'paul frank': 'Paul Frank', 'kangol': 'Kangol',
    'b&m': 'B&M', 'êmbrace': 'Êmbrace', 'helio': 'Helio', 'gosto': 'Gosto',
}
def brand_of(raw):
    k = clean_ws(raw).lower()
    return BRAND_MAP.get(k, clean_ws(raw).title())

# ---------------------------------------------------------------- màu
VI = {'black': 'Đen', 'white': 'Trắng', 'grey': 'Xám', 'gray': 'Xám', 'green': 'Xanh Lá', 'blue': 'Xanh Dương', 'red': 'Đỏ', 'pink': 'Hồng',
      'purple': 'Tím', 'orange': 'Cam', 'yellow': 'Vàng', 'brown': 'Nâu', 'cream': 'Kem', 'silver': 'Bạc', 'beige': 'Be', 'gold': 'Vàng Kim'}
ABBR = {'dk.': 'dark ', 'lt.': 'light '}
FIX = {'khahi': 'khaki', 'bluegreen': 'blue green', 'navygrey': 'navy grey'}
HEX = [  # (từ khóa, mã màu). Màu của chuỗi = từ khóa xuất hiện SỚM nhất (hòa thì từ khóa dài hơn)
    ('đen', '#111111'), ('black', '#111111'), ('trắng', '#f5f5f5'), ('white', '#f5f5f5'), ('kem', '#f3ead3'), ('cream', '#f3ead3'), ('egret', '#f3ead3'),
    ('beige', '#d9c7a3'), ('be', '#d9c7a3'), ('almond', '#d9c7a3'), ('camel', '#c19a6b'), ('khaki', '#bdb76b'), ('rêu', '#6b7f3f'), ('nhớt', '#8a8f3a'),
    ('xám', '#9ca3af'), ('grey', '#9ca3af'), ('gray', '#9ca3af'), ('bạc', '#c0c0c0'), ('silver', '#c0c0c0'), ('nâu', '#7b4a2d'), ('brown', '#7b4a2d'),
    ('coffee', '#6f4e37'), ('cocoa', '#6f4e37'), ('taupe', '#8b7765'), ('đỏ', '#dc2626'), ('red', '#dc2626'), ('hồng', '#f472b6'), ('pink', '#f472b6'),
    ('cam', '#f97316'), ('orange', '#f97316'), ('vàng', '#facc15'), ('yellow', '#facc15'), ('lime', '#a3e635'), ('tím', '#8b5cf6'), ('purple', '#8b5cf6'),
    ('navy', '#1e3a8a'), ('midnight', '#1e3a8a'), ('xanh dương', '#2563eb'), ('blue', '#2563eb'), ('xanh lá', '#16a34a'), ('green', '#16a34a'), ('forest', '#166534'),
    ('xanh ngọc', '#14b8a6'), ('xanh', '#2563eb'),
]
def color_hex(name):
    n = name.lower(); best = None
    for kw, hx in HEX:
        m = re.search(r'(?<![\w])' + re.escape(kw) + r'(?![\w])' if len(kw) <= 3 else r'(?<![\w])' + re.escape(kw), n)
        if m and (best is None or (m.start(), -len(kw)) < best[0]): best = ((m.start(), -len(kw)), hx)
    return best[1] if best else '#9ca3af'

def title_case(s):
    return ' '.join(w if any(ch.isdigit() for ch in w) else w[:1].upper() + w[1:].lower() for w in s.split(' ') if w)

def norm_color(raw):
    """'20Black' → 'Đen'; 'BLACK / WHITE' → 'Đen / Trắng'; 'xanh dương lợt' → 'Xanh Dương Lợt'; tên colorway (Checkerboard Pink Dawn) giữ nguyên dạng Title Case."""
    s = clean_ws(raw.replace('\n', ' '))
    if not s: return 'Mặc định'
    s = re.sub(r'^\d{2,}(?=[A-Za-z])', '', s)                  # 20Black, 00White
    s = re.sub(r'^\d{3,}[A-Za-z]?\s*/\s*', '', s)              # 7595U / ANTIQUE WHITE
    s = re.sub(r'\s+\d{2}-\d{4}\w*$', '', s)                  # mã Pantone: Raw Umber 18-1324TPG
    low = s.lower()
    for a, b in ABBR.items(): low = low.replace(a, b)
    for a, b in FIX.items(): low = low.replace(a, b)
    parts = [p.strip() for p in re.split(r'\s*/\s*', low) if p.strip()]
    # chỉ dịch sang tiếng Việt khi TOÀN BỘ các đoạn đều là màu cơ bản; tên colorway (Mesh Black/White, Dark Grey/Black…) giữ nguyên tiếng Anh
    if all(p in VI or not p.isascii() for p in parts): return ' / '.join(VI.get(p) or title_case(p) for p in parts)
    return ' / '.join(title_case(p) for p in parts) or 'Mặc định'

# ---------------------------------------------------------------- phân loại
CATS = {  # slug: (tên, slug cha | None)
    'sneaker': ('Sneaker', None), 'chay-bo': ('Chạy bộ', None), 'giay-da': ('Giày da', None), 'sandal': ('Sandal', None),
    'dep': ('Dép', None), 'giay-thoi-trang': ('Giày thời trang', None), 'giay-tap-di': ('Giày tập đi', None),
}
def category_of(ptype, name, brand):
    t = ptype.lower()
    if 'tập đi' in t: return 'giay-tap-di'
    if t.startswith('dép'): return 'dep'
    if t.startswith('sandal'): return 'sandal'
    if 'chạy bộ' in t: return 'chay-bo'
    if 'giày tây' in t: return 'giay-da'
    if 'búp bê' in t or 'thời trang' in t: return 'giay-thoi-trang'
    if 'thể thao' in t or 'tennis' in t: return 'sneaker'
    n = name.lower()
    if any(k in n for k in ('boot', 'búp bê')): return 'giay-thoi-trang'
    return 'sneaker'

def gender_of(name, ptype):
    n = ' ' + name.lower() + ' '
    if re.search(r'\b(bé trai|bé gái|trẻ em|học sinh|tập đi|kid|kids)\b', n): return 'kids'
    if re.search(r'\b(nữ|women|woman)\b', n): return 'women'
    if re.search(r'\b(nam|men|man)\b', n): return 'men'
    return 'unisex'

def weight_of(cat, gender):
    if gender == 'kids': return 400
    return 500 if cat in ('dep', 'sandal') else 800

def size_key(label):
    m = re.match(r'(\d+(?:\.\d+)?)', label)
    return int(float(m.group(1)) * 10) if m else 9999

# ---------------------------------------------------------------- tên & nhóm sản phẩm
CODE_RE = re.compile(r'\s([A-Z]{2,4}\d{5,}[A-Z0-9]*)$')
def strip_marketing(n):
    n = re.sub(r'^\s*(\[[^\]]*\]\s*)+', '', n)           # [ĐẠI LÝ ĐỘC QUYỀN], [Hạ 01 size]
    return clean_ws(n)

def parse_row(r, color_lex):
    """Trả về (group_key, tên sản phẩm, mã mẫu|None)."""
    name = strip_marketing(r['name'])
    if r['site'] == 'biti':
        m = CODE_RE.search(name)
        if m:
            code = m.group(1)
            rest = name[:m.start()]
            model = (re.match(r'[A-Z]{2,4}\d{6}', code) or re.match(r'.*', code)).group(0)
            rest = re.sub(r'\s+Màu\s+.+$', '', rest, flags=re.I)
            changed = True
            while changed:
                changed = False
                for c in color_lex:
                    if rest.lower().endswith(' ' + c.lower()):
                        rest = rest[: -(len(c) + 1)]; changed = True; break
            rest = clean_ws(rest)
            return ('biti', model), f'{rest} {model}', model
        return ('biti', name.lower()), name, None
    # sneakerbuzz: bỏ màu ở cuối tên nếu trùng cột colors
    col = clean_ws(r['colors'].replace('\n', ' '))
    base = name
    if col and name.lower().endswith(' ' + col.lower()) and len(name) > len(col) + 4:
        base = clean_ws(name[: -(len(col) + 1)])
    base = re.sub(r'\s*\((?=[^)]*\d)[^)]*\)\s*$', lambda m: m.group(0), base)
    return ('sb', base.lower()), base, None

def build_description(name, brand, colors, sizes):
    return (f'<p><strong>{name}</strong> – thương hiệu {brand}.</p>'
            f'<ul><li>Màu có sẵn: {", ".join(colors)}</li><li>Size: {", ".join(sizes)}</li></ul>')

# ---------------------------------------------------------------- ảnh
def process_image(args):
    src, dst_dir, name = args
    from PIL import Image, ImageOps
    out = Path(dst_dir); out.mkdir(parents=True, exist_ok=True)
    with Image.open(src) as im:
        im = ImageOps.exif_transpose(im).convert('RGB')
        main = im.copy(); main.thumbnail((1600, 1600))
        main.save(out / f'{name}.webp', 'WEBP', quality=85, method=4)
        for sfx, px in (('md', 800), ('sm', 300)):
            v = im.copy(); v.thumbnail((px, px)); v.save(out / f'{name}_{sfx}.webp', 'WEBP', quality=82, method=4)
        w, h = main.size
    return (w, h, (out / f'{name}.webp').stat().st_size)

# ---------------------------------------------------------------- chính
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--src', required=True, help='thư mục chứa raw_shoes_*.csv và images_shoes/')
    ap.add_argument('--csv', help='tên file csv (mặc định: file raw_shoes_*.csv mới nhất)')
    ap.add_argument('--storage', default=str(ROOT.parent / 'api' / 'storage' / 'app' / 'public'))
    ap.add_argument('--stock', type=int, default=10, help='tồn kho giả định cho mỗi biến thể còn hàng (nguồn không có số lượng)')
    ap.add_argument('--skip-images', action='store_true', help='không xử lý ảnh (chỉ sinh SQL, giả định ảnh đã có)')
    a = ap.parse_args()
    src = Path(a.src).expanduser()
    csv_file = Path(a.csv) if a.csv else sorted(src.glob('raw_shoes_*.csv'))[-1]
    rows = list(csv.DictReader(open(csv_file, encoding='utf-8-sig')))
    rep = [f'Nguồn: {csv_file}  ({len(rows)} dòng)']
    img_root = src.parent  # image_path trong CSV dạng data/images_shoes/... tính từ thư mục cha của data/

    # --- loại bỏ
    kept, dropped = [], collections.defaultdict(list)
    for r in rows:
        r = {k: (v or '') for k, v in r.items()}
        if 'dây giày' in r['product_type'].lower() or re.match(r'^dây giày', r['name'].strip().lower()):
            dropped['Phụ kiện (dây giày) – không phải giày/dép'].append(r['name']); continue
        price = int(r['price_vnd'] or 0); comp = int(r['compare_at_price_vnd'] or 0)
        if price == 0 and comp == 0:
            dropped['Không có giá (price=0 và không có giá gốc)'].append(r['name']); continue
        ip = img_root / r['image_path']
        if not ip.exists():
            dropped['Thiếu file ảnh'].append(r['name']); continue
        r['_price'], r['_comp'] = price, comp
        kept.append(r)

    # --- bảng màu tiếng Việt của Biti's để cắt đuôi tên
    lex = sorted({clean_ws(c) for r in kept if r['site'] == 'biti' for c in r['colors'].split('|') if c.strip()}, key=len, reverse=True)

    groups = collections.OrderedDict()
    for r in kept:
        key, name, model = parse_row(r, lex)
        g = groups.setdefault(key, {'name': name, 'rows': [], 'model': model})
        g['rows'].append(r)

    brands, colors_used, sizes_used = {}, {}, {}
    products, variants, images, notes = [], [], [], collections.Counter()
    color_map_report = collections.defaultdict(set)
    slugs = set()
    img_jobs = []
    for idx, (key, g) in enumerate(groups.items()):
        pid = ID_BASE + idx
        first = g['rows'][0]
        name = g['name']
        brand = brand_of(first['brand']); brands[brand] = slugify(brand)
        cat = category_of(first['product_type'], name, brand)
        gender = gender_of(name, first['product_type'])
        slug = slugify(name) or f'san-pham-{pid}'
        base, n = slug, 2
        while slug in slugs: slug = f'{base}-{n}'; n += 1
        slugs.add(slug)

        seen, plist = set(), []
        for r in g['rows']:
            price = r['_price'] or r['_comp']
            comp = r['_comp'] if (r['_price'] and r['_comp'] > r['_price']) else None
            if r['_price'] == 0: notes['dòng giá=0 → dùng giá gốc làm giá bán (đang hết hàng)'] += 1
            if r['_price'] and r['_comp'] and r['_comp'] <= r['_price']: notes['giá gốc ≤ giá bán → bỏ giá gạch'] += 1
            avail = r['available'].strip().lower() == 'true'
            cols = [norm_color(c) for c in (r['colors'].split('|') if r['colors'].strip() else [''])]
            for raw_c, c in zip(r['colors'].split('|') if r['colors'].strip() else [''], cols): color_map_report[c].add(raw_c)
            sizes = [clean_ws(s) for s in r['sizes'].split('|') if clean_ws(s)] or ['Free']
            for c in cols:
                colors_used[c] = color_hex(c)
                for s in sizes:
                    if (c, s) in seen: notes['trùng (màu,size) trong cùng sản phẩm → giữ bản đầu'] += 1; continue
                    seen.add((c, s)); sizes_used[s] = size_key(s)
                    code = g['model'] or f"{r['site'][:2].upper()}{r['id']}"
                    sku = re.sub(r'[^A-Z0-9]+', '-', f"{code}-{slugify(c)}-{s}".upper()).strip('-')
                    plist.append({'pid': pid, 'sku': sku, 'color': c, 'size': s, 'price': price, 'compare': comp,
                                  'stock': a.stock if avail else 0, 'weight': weight_of(cat, gender)})
            ip = img_root / r['image_path']
            color_for_img = cols[0] if len(cols) == 1 else None
            images.append({'pid': pid, 'src': ip, 'color': color_for_img, 'alt': f'{name} {cols[0] if len(cols)==1 else ""}'.strip()})
        # SKU phải duy nhất toàn cục
        for v in plist:
            variants.append(v)
        pcolors = list(dict.fromkeys(v['color'] for v in plist))
        psizes = sorted({v['size'] for v in plist}, key=size_key)
        products.append({'id': pid, 'name': name, 'slug': slug, 'brand': brand, 'cat': cat, 'gender': gender,
                         'desc': build_description(name, brand, pcolors, psizes), 'base_price': min(v['price'] for v in plist),
                         'status': 'active', 'order': idx})

    # SKU trùng toàn cục → thêm hậu tố
    cnt = collections.Counter(v['sku'] for v in variants); dup = collections.Counter()
    for v in variants:
        if cnt[v['sku']] > 1:
            dup[v['sku']] += 1
            if dup[v['sku']] > 1: v['sku'] = f"{v['sku']}-{v['pid']}"
    assert len({v['sku'] for v in variants}) == len(variants), 'SKU vẫn trùng'

    # --- ảnh: xử lý & đặt tên ổn định (uuid5 theo đường dẫn nguồn)
    by_prod = collections.defaultdict(list)
    for im in images:
        im['name'] = str(uuid.uuid5(uuid.NAMESPACE_URL, str(im['src'])))
        by_prod[im['pid']].append(im)
    final_images = []
    for pid, ims in by_prod.items():
        seen_src = set()
        for n, im in enumerate(ims):
            if im['src'] in seen_src: continue
            seen_src.add(im['src']); im['order'] = n; im['primary'] = (len(seen_src) == 1); final_images.append(im)
    if not a.skip_images:
        storage = Path(a.storage)
        with cf.ProcessPoolExecutor() as ex:
            futs = {ex.submit(process_image, (str(im['src']), storage / 'products' / str(im['pid']), im['name'])): im for im in final_images}
            for i, f in enumerate(cf.as_completed(futs), 1):
                im = futs[f]; im['w'], im['h'], im['bytes'] = f.result()
                if i % 200 == 0: print(f'  ảnh {i}/{len(final_images)}', file=sys.stderr)
    else:
        for im in final_images: im['w'] = im['h'] = im['bytes'] = None

    # ------------------------------------------------------------ SQL
    L = ['-- Sinh bởi data-migration/build.py — KHÔNG sửa tay. Chạy lại script để cập nhật.',
         f'-- {len(products)} sản phẩm · {len(variants)} biến thể · {len(final_images)} ảnh',
         'SET NAMES utf8mb4;', 'SET time_zone = "+00:00";', 'START TRANSACTION;', '']
    L.append('-- 1. Thương hiệu')
    L.append('INSERT INTO brands (name, slug, is_active, created_at, updated_at) VALUES\n' + ',\n'.join(
        f'({sql(n)}, {sql(s)}, 1, NOW(), NOW())' for n, s in sorted(brands.items())) + '\nON DUPLICATE KEY UPDATE name = VALUES(name);')
    L.append('\n-- 2. Danh mục (chỉ thêm nếu chưa có slug)')
    for slug, (n, parent) in CATS.items():
        L.append(f'INSERT INTO categories (name, slug, sort_order, is_active, created_at, updated_at) SELECT {sql(n)}, {sql(slug)}, 0, 1, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = {sql(slug)});')
    L.append('\n-- 3. Màu (chỉ thêm nếu chưa có tên)')
    for n, hx in sorted(colors_used.items()):
        L.append(f'INSERT INTO colors (name, hex, created_at, updated_at) SELECT {sql(n)}, {sql(hx)}, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM colors WHERE name = {sql(n)});')
    L.append('\n-- 4. Size (hệ EU; cập nhật thứ tự sắp xếp cho cả size đã có để sắp đúng 24 → 46)')
    L.append('INSERT INTO sizes (`system`, label, sort_order, created_at, updated_at) VALUES\n' + ',\n'.join(
        f"('EU', {sql(s)}, {k}, NOW(), NOW())" for s, k in sorted(sizes_used.items(), key=lambda x: (x[1], x[0]))) + '\nON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order);')
    L.append('UPDATE sizes SET sort_order = CAST(label AS DECIMAL(5,1)) * 10 WHERE `system` = "EU" AND label REGEXP "^[0-9]+(\\\\.[0-9])?$";')
    L.append(f'\n-- 5. Dọn lần nhập trước (id {ID_BASE}..{ID_BASE + 99999}); biến thể/ảnh/tồn kho xóa theo cascade')
    L.append(f'DELETE FROM inventory_movements WHERE ref_type = "migration";')
    L.append(f'DELETE FROM products WHERE id BETWEEN {ID_BASE} AND {ID_BASE + 99999};')
    L.append('\n-- 6. Sản phẩm')
    cols = '(id, name, slug, brand_id, category_id, gender, description, material, base_price, status, seo_title, seo_description, sold_count, created_at, updated_at)'
    chunk = []
    for p in products:
        chunk.append(f"({p['id']}, {sql(p['name'])}, {sql(p['slug'])}, (SELECT id FROM brands WHERE slug = {sql(brands[p['brand']])}), "
                     f"(SELECT id FROM categories WHERE slug = {sql(p['cat'])}), {sql(p['gender'])}, {sql(p['desc'])}, NULL, {p['base_price']}, {sql(p['status'])}, "
                     f"NULL, NULL, 0, NOW() - INTERVAL {p['order']} MINUTE, NOW())")
        if len(chunk) == 100: L.append(f'INSERT INTO products {cols} VALUES\n' + ',\n'.join(chunk) + ';'); chunk = []
    if chunk: L.append(f'INSERT INTO products {cols} VALUES\n' + ',\n'.join(chunk) + ';')

    L.append('\n-- 7. Biến thể + tồn kho: nạp vào bảng tạm rồi ghép với colors/sizes')
    L.append('DROP TEMPORARY TABLE IF EXISTS stg_variant;')
    L.append('CREATE TEMPORARY TABLE stg_variant (product_id BIGINT UNSIGNED, sku VARCHAR(64), color_name VARCHAR(60), size_label VARCHAR(20), price BIGINT UNSIGNED, compare_at BIGINT UNSIGNED NULL, stock INT, weight INT) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;')
    for i in range(0, len(variants), 500):
        part = variants[i:i + 500]
        L.append('INSERT INTO stg_variant VALUES\n' + ',\n'.join(
            f"({v['pid']}, {sql(v['sku'])}, {sql(v['color'])}, {sql(v['size'])}, {v['price']}, {sql(v['compare'])}, {v['stock']}, {v['weight']})" for v in part) + ';')
    L.append("""INSERT INTO product_variants (product_id, sku, color_id, size_id, price, compare_at_price, barcode, weight_g, is_active, created_at, updated_at)
SELECT s.product_id, s.sku, c.id, z.id, s.price, s.compare_at, NULL, s.weight, 1, NOW(), NOW()
FROM stg_variant s
JOIN (SELECT name, MIN(id) id FROM colors GROUP BY name) c ON c.name = s.color_name
JOIN sizes z ON z.`system` = 'EU' AND z.label = s.size_label;""")
    L.append("""INSERT INTO inventory (variant_id, on_hand, reserved, low_stock_threshold, version, created_at, updated_at)
SELECT v.id, s.stock, 0, 5, 0, NOW(), NOW() FROM stg_variant s JOIN product_variants v ON v.sku = s.sku;""")
    L.append("""INSERT INTO inventory_movements (variant_id, type, qty_delta, reason, ref_type, ref_id, staff_id, created_at)
SELECT v.id, 'receipt', s.stock, 'Tồn đầu kỳ khi nhập dữ liệu crawl (số lượng giả định)', 'migration', s.product_id, NULL, NOW() FROM stg_variant s JOIN product_variants v ON v.sku = s.sku WHERE s.stock > 0;""")
    L.append('-- kiểm tra: mọi dòng staging đều ghép được (không mất biến thể)')
    L.append('SELECT COUNT(*) AS staged, (SELECT COUNT(*) FROM product_variants WHERE product_id >= %d) AS inserted FROM stg_variant;' % ID_BASE)

    L.append('\n-- 8. Ảnh sản phẩm (tệp nằm ở storage/app/public/products/{id}/)')
    L.append('DROP TEMPORARY TABLE IF EXISTS stg_image;')
    L.append('CREATE TEMPORARY TABLE stg_image (product_id BIGINT UNSIGNED, color_name VARCHAR(60) NULL, path VARCHAR(255), alt VARCHAR(255), sort_order INT, is_primary TINYINT, width INT NULL, height INT NULL, size_bytes INT NULL) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;')
    for i in range(0, len(final_images), 500):
        part = final_images[i:i + 500]
        L.append('INSERT INTO stg_image VALUES\n' + ',\n'.join(
            f"({im['pid']}, {sql(im['color'])}, {sql(f'products/{im['pid']}/{im['name']}.webp')}, {sql(im['alt'])}, {im['order']}, {1 if im['primary'] else 0}, {sql(im['w'])}, {sql(im['h'])}, {sql(im['bytes'])})" for im in part) + ';')
    L.append("""INSERT INTO product_images (product_id, color_id, path, alt, sort_order, is_primary, width, height, size_bytes, created_at, updated_at)
SELECT s.product_id, c.id, s.path, s.alt, s.sort_order, s.is_primary, s.width, s.height, s.size_bytes, NOW(), NOW()
FROM stg_image s LEFT JOIN (SELECT name, MIN(id) id FROM colors GROUP BY name) c ON c.name = s.color_name;""")
    L.append('\nCOMMIT;')
    L.append('-- Sau khi chạy: xóa cache (php artisan cache:clear) và nếu dùng Meilisearch: php artisan search:reindex')
    (ROOT / 'import_products.sql').write_text('\n'.join(L) + '\n', encoding='utf-8')

    (ROOT / '00_clear_demo_catalog.sql').write_text(
        f"""-- TÙY CHỌN: xóa các sản phẩm DEMO do seeder tạo (id < {ID_BASE}). Chạy TRƯỚC import_products.sql nếu muốn chỉ còn dữ liệu thật.
-- Ảnh demo trong storage/app/public/products/1..16 không bị xóa bởi SQL (xóa tay nếu cần).
START TRANSACTION;
DELETE FROM inventory_movements WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id < {ID_BASE});
DELETE FROM products WHERE id < {ID_BASE};   -- cascade: biến thể, ảnh, tồn kho, wishlist, đánh giá
COMMIT;
""", encoding='utf-8')

    # ------------------------------------------------------------ báo cáo
    rep.append(f'Giữ lại {len(kept)} dòng → {len(products)} sản phẩm, {len(variants)} biến thể, {len(final_images)} ảnh')
    rep.append('\n== Dòng bị loại ==')
    for why, names in dropped.items(): rep.append(f'- {why}: {len(names)}'); rep += [f'    · {n}' for n in names[:12]]
    rep.append('\n== Ghi chú xử lý =='); rep += [f'- {k}: {v}' for k, v in notes.items()]
    rep.append('\n== Thương hiệu =='); rep += [f'- {n}' for n in sorted(brands)]
    rep.append('\n== Chuẩn hóa màu (chuẩn ← giá trị gốc) =='); rep += [f"- {k} ← {' | '.join(sorted(v))}" for k, v in sorted(color_map_report.items()) if len(v) > 1 or k not in v]
    rep.append('\n== Phân bố danh mục / giới tính ==')
    rep.append(str(collections.Counter(p['cat'] for p in products))); rep.append(str(collections.Counter(p['gender'] for p in products)))
    rep.append('\n== Tồn kho ==\nNguồn không có số lượng: biến thể còn hàng = %d, hết hàng = 0.' % a.stock)
    (ROOT / 'out').mkdir(exist_ok=True); (ROOT / 'out' / 'cleaning_report.txt').write_text('\n'.join(rep), encoding='utf-8')
    print('\n'.join(rep[:12])); print('→ import_products.sql, 00_clear_demo_catalog.sql, out/cleaning_report.txt')

if __name__ == '__main__': main()
