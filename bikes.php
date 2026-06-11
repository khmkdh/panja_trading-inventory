<?php
include 'config.php';
session_start();

$activePage = 'bikes';

// ── AJAX: load compatible parts for a bike ──────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'get_parts') {
    $bike_id = (int)$_GET['bike_id'];

    // Get bike's engine_capacity for auto-match detection
    $bikeRow = $conn->query("SELECT engine_capacity FROM bikes WHERE id=$bike_id")->fetch_assoc();
    $eng_cc  = $bikeRow['engine_capacity'] ?? '';

    $linked = [];
    $res = $conn->query("
        SELECT s.id, s.part_name, s.category, s.quantity, s.selling_price, s.compatible_cc
        FROM bike_parts bp
        JOIN stock s ON s.id = bp.stock_id
        WHERE bp.bike_id = $bike_id
        ORDER BY s.part_name
    ");
    while ($r = $res->fetch_assoc()) {
        // Flag as auto-matched if the part's compatible_cc matches this bike's engine_capacity
        $r['auto'] = (!empty($eng_cc) && !empty($r['compatible_cc']) && $r['compatible_cc'] === $eng_cc);
        $linked[] = $r;
    }

    // All stock items not already linked
    $linked_ids = array_column($linked, 'id');
    $exclude = count($linked_ids) ? implode(',', $linked_ids) : '0';
    $available = [];
    $res2 = $conn->query("
        SELECT id, part_name, category, quantity, selling_price, compatible_cc
        FROM stock
        WHERE id NOT IN ($exclude)
        ORDER BY part_name
    ");
    while ($r = $res2->fetch_assoc()) $available[] = $r;

    header('Content-Type: application/json');
    echo json_encode(['linked' => $linked, 'available' => $available, 'engine_cc' => $eng_cc]);
    exit;
}

// ── AJAX: add a part link ───────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add_part') {
    $bike_id  = (int)$_POST['bike_id'];
    $stock_id = (int)$_POST['stock_id'];
    $check = $conn->query("SELECT id FROM bike_parts WHERE bike_id=$bike_id AND stock_id=$stock_id");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO bike_parts (bike_id, stock_id) VALUES ($bike_id, $stock_id)");
    }
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX: remove a part link ────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'remove_part') {
    $bike_id  = (int)$_POST['bike_id'];
    $stock_id = (int)$_POST['stock_id'];
    $conn->query("DELETE FROM bike_parts WHERE bike_id=$bike_id AND stock_id=$stock_id");
    echo json_encode(['ok' => true]);
    exit;
}

// ── Normal POST: add / update / delete bike ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add'])) {
        $bike_name       = trim($_POST['bike_name']);
        $brand           = trim($_POST['brand']);
        $engine_capacity = trim($_POST['engine_capacity']);
        if ($bike_name && $brand && $engine_capacity) {
            $stmt = $conn->prepare("INSERT INTO bikes (bike_name, brand, engine_capacity) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $bike_name, $brand, $engine_capacity);
            $stmt->execute();
            $new_bike_id = $conn->insert_id;
            $stmt->close();

            // Auto-link all stock parts whose compatible_cc matches engine_capacity
            $autoCount = 0;
            $partRes = $conn->query("SELECT id FROM stock WHERE compatible_cc = '" . $conn->real_escape_string($engine_capacity) . "'");
            while ($pr = $partRes->fetch_assoc()) {
                $check = $conn->query("SELECT id FROM bike_parts WHERE bike_id=$new_bike_id AND stock_id={$pr['id']}");
                if ($check->num_rows === 0) {
                    $conn->query("INSERT INTO bike_parts (bike_id, stock_id) VALUES ($new_bike_id, {$pr['id']})");
                    $autoCount++;
                }
            }

            $success = "Bike model added successfully.";
            if ($autoCount > 0) {
                $success .= " <strong>$autoCount compatible part" . ($autoCount > 1 ? 's' : '') . " auto-linked</strong> based on engine capacity ({$engine_capacity}).";
            } else {
                $success .= " No parts tagged for {$engine_capacity} yet — add parts with this CC in Inventory to auto-link.";
            }
        }
    }

    if (isset($_POST['update'])) {
        $id              = (int)$_POST['id'];
        $bike_name       = trim($_POST['bike_name']);
        $brand           = trim($_POST['brand']);
        $engine_capacity = trim($_POST['engine_capacity']);
        $stmt = $conn->prepare("UPDATE bikes SET bike_name=?, brand=?, engine_capacity=? WHERE id=?");
        $stmt->bind_param("sssi", $bike_name, $brand, $engine_capacity, $id);
        $stmt->execute();
        $stmt->close();

        // Re-sync auto-links for the new engine_capacity
        $partRes = $conn->query("SELECT id FROM stock WHERE compatible_cc = '" . $conn->real_escape_string($engine_capacity) . "'");
        $newLinks = 0;
        while ($pr = $partRes->fetch_assoc()) {
            $check = $conn->query("SELECT id FROM bike_parts WHERE bike_id=$id AND stock_id={$pr['id']}");
            if ($check->num_rows === 0) {
                $conn->query("INSERT INTO bike_parts (bike_id, stock_id) VALUES ($id, {$pr['id']})");
                $newLinks++;
            }
        }
        $success = "Bike model updated." . ($newLinks > 0 ? " $newLinks new part(s) auto-linked." : "");
    }

    if (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM bike_parts WHERE bike_id=$id");
        $stmt = $conn->prepare("DELETE FROM bikes WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $success = "Bike model deleted.";
    }
}

$bikes = $conn->query("SELECT * FROM bikes ORDER BY brand, bike_name");

$partCounts = [];
$res = $conn->query("SELECT bike_id, COUNT(*) as cnt FROM bike_parts GROUP BY bike_id");
if ($res) while ($r = $res->fetch_assoc()) $partCounts[$r['bike_id']] = $r['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bike Models – Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .parts-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 1050;
            align-items: center; justify-content: center;
        }
        .parts-modal-overlay.open { display: flex; }
        .parts-modal {
            background: #fff; border-radius: 12px;
            width: min(740px, 95vw); max-height: 88vh;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
        }
        .parts-modal-header {
            padding: 18px 24px; border-bottom: 1px solid #e9ecef;
            display: flex; align-items: center; gap: 10px;
        }
        .parts-modal-header h5 { margin: 0; font-size: 1rem; font-weight: 700; }
        .parts-modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
        .parts-modal-footer {
            padding: 14px 24px; border-top: 1px solid #e9ecef;
            display: flex; align-items: center; gap: 10px;
        }
        .parts-section-label {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #6c757d; margin-bottom: 10px;
            display: flex; align-items: center; gap: 6px;
        }
        .parts-chip-list { display: flex; flex-wrap: wrap; gap: 8px; min-height: 36px; }
        .parts-chip {
            display: inline-flex; align-items: center; gap: 6px;
            background: #e8f5e9; border: 1px solid #a5d6a7; color: #1b5e20;
            border-radius: 20px; padding: 4px 12px 4px 10px;
            font-size: .82rem; font-weight: 500;
        }
        .parts-chip.auto-chip {
            background: #fff8e1; border-color: #ffe082; color: #7d5a00;
        }
        .parts-chip .remove-btn {
            background: none; border: none; padding: 0; line-height: 1;
            color: #c62828; cursor: pointer; font-size: .9rem;
            display: flex; align-items: center;
        }
        .parts-chip .remove-btn:hover { color: #b71c1c; }
        .parts-divider { border: none; border-top: 1px dashed #dee2e6; margin: 18px 0; }
        .parts-badge {
            display: inline-flex; align-items: center; justify-content: center;
            background: #1976d2; color: #fff; border-radius: 10px;
            font-size: .7rem; font-weight: 700; min-width: 20px; height: 20px;
            padding: 0 5px; margin-left: 4px;
        }
        .parts-badge.zero { background: #bdbdbd; }
        .auto-badge {
            font-size: .65rem; background: #f59e0b; color: #fff;
            border-radius: 4px; padding: 1px 5px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 2px;
        }
        #partsAddSelect { flex: 1; }
        .empty-note { color: #9e9e9e; font-size: .85rem; font-style: italic; }
        .cc-info-bar {
            background: #fff8e1; border: 1px solid #ffe082; border-radius: 8px;
            padding: 8px 14px; font-size: .8rem; color: #7d5a00;
            display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Bike Models</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if (!empty($success)): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
            </div>
            <?php endif; ?>

            <!-- Add New Model -->
            <div class="card-section" style="max-width: 760px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-bicycle"></i> Add New Model</span>
                </div>
                <div style="padding: 20px;">
                    <div style="background:#e8f0fe; border:1px solid #90b4f5; border-radius:8px;
                                padding:10px 14px; font-size:.82rem; color:#1a56db; margin-bottom:16px;">
                        <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i>
                        <strong>Auto-link active:</strong> When you add a bike, all stock parts tagged with the same engine capacity will be linked automatically.
                        Tag your parts in <a href="inventory.php?tab=add" style="color:#1a56db; font-weight:700;">Inventory → Add Item</a>.
                    </div>
                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Model Name</label>
                                <input type="text" name="bike_name" class="form-control"
                                       placeholder="e.g. Splendor Plus" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control"
                                       placeholder="e.g. Hero" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Engine Capacity</label>
                                <input type="text" name="engine_capacity" class="form-control"
                                       placeholder="e.g. 100cc" required>
                            </div>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Model
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bikes Table -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">All Bike Models</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Model Name</th>
                                <th>Brand</th>
                                <th>Engine Capacity</th>
                                <th>Compatible Parts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = $bikes->fetch_assoc()):
                                $cnt = $partCounts[$row['id']] ?? 0;
                            ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <input type="text" name="bike_name" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['bike_name']) ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="brand" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['brand']) ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="engine_capacity" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['engine_capacity']) ?>">
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary open-parts-btn"
                                                data-bike-id="<?= $row['id'] ?>"
                                                data-bike-name="<?= htmlspecialchars($row['brand'] . ' ' . $row['bike_name']) ?>"
                                                data-engine-cc="<?= htmlspecialchars($row['engine_capacity']) ?>">
                                            <i class="bi bi-puzzle"></i> Parts
                                            <span class="parts-badge <?= $cnt === 0 ? 'zero' : '' ?>"
                                                  id="badge-<?= $row['id'] ?>"><?= $cnt ?></span>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update" class="btn btn-sm btn-primary">
                                                <i class="bi bi-check-lg"></i> Update
                                            </button>
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this model and all its part links?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Compatible Parts Modal ──────────────────────────────────────────── -->
<div class="parts-modal-overlay" id="partsOverlay">
    <div class="parts-modal">
        <div class="parts-modal-header">
            <i class="bi bi-puzzle-fill text-primary fs-5"></i>
            <h5 id="partsModalTitle">Compatible Parts</h5>
            <button class="btn btn-sm btn-outline-secondary ms-auto" id="closePartsModal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="parts-modal-body">
            <div id="ccInfoBar" class="cc-info-bar" style="display:none;">
                <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i>
                <span id="ccInfoText"></span>
            </div>

            <div class="parts-section-label">
                <i class="bi bi-link-45deg"></i> Linked Parts
                <span style="font-weight:400; color:#9aa5b4; font-size:.7rem; margin-left:4px;">(yellow = auto-matched by CC)</span>
            </div>
            <div class="parts-chip-list" id="linkedPartsList">
                <span class="empty-note">Loading…</span>
            </div>

            <hr class="parts-divider">

            <div class="parts-section-label">
                <i class="bi bi-box-seam"></i> Available Stock Items
            </div>
            <div class="table-responsive" id="availablePartsTable"></div>
        </div>
        <div class="parts-modal-footer">
            <select class="form-select form-select-sm" id="partsAddSelect">
                <option value="">— Select a part to add —</option>
            </select>
            <button class="btn btn-sm btn-success" id="addPartBtn">
                <i class="bi bi-plus-lg"></i> Link Part
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentBikeId = null;
let currentEngineCC = null;

document.querySelectorAll('.open-parts-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentBikeId  = btn.dataset.bikeId;
        currentEngineCC = btn.dataset.engineCc || '';
        document.getElementById('partsModalTitle').textContent =
            'Compatible Parts — ' + btn.dataset.bikeName;
        document.getElementById('partsOverlay').classList.add('open');
        loadParts();
    });
});

document.getElementById('closePartsModal').addEventListener('click', closeModal);
document.getElementById('partsOverlay').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});
function closeModal() {
    document.getElementById('partsOverlay').classList.remove('open');
    currentBikeId = null;
}

function loadParts() {
    document.getElementById('linkedPartsList').innerHTML = '<span class="empty-note">Loading…</span>';
    document.getElementById('availablePartsTable').innerHTML = '';
    document.getElementById('partsAddSelect').innerHTML = '<option value="">— Select a part to add —</option>';

    fetch(`bikes.php?action=get_parts&bike_id=${currentBikeId}`)
        .then(r => r.json())
        .then(data => {
            // CC info bar
            const bar  = document.getElementById('ccInfoBar');
            const barTxt = document.getElementById('ccInfoText');
            const autoCount = data.linked.filter(p => p.auto).length;
            if (data.engine_cc) {
                barTxt.textContent = `Engine: ${data.engine_cc} — ${autoCount} part(s) auto-matched by CC tag.`;
                bar.style.display = 'flex';
            } else {
                bar.style.display = 'none';
            }

            renderLinked(data.linked);
            renderAvailable(data.available, data.engine_cc);
            updateBadge(currentBikeId, data.linked.length);
        });
}

function renderLinked(parts) {
    const el = document.getElementById('linkedPartsList');
    if (!parts.length) {
        el.innerHTML = '<span class="empty-note">No parts linked yet.</span>';
        return;
    }
    el.innerHTML = parts.map(p => `
        <span class="parts-chip ${p.auto ? 'auto-chip' : ''}">
            ${p.auto
                ? '<i class="bi bi-lightning-fill" style="font-size:.7rem; color:#f59e0b;"></i>'
                : '<i class="bi bi-wrench" style="font-size:.75rem;"></i>'}
            ${escHtml(p.part_name)}
            <span style="color:#888; font-size:.75rem;">(${escHtml(p.category)})</span>
            ${p.auto ? '<span class="auto-badge"><i class="bi bi-lightning-fill"></i> auto</span>' : ''}
            <button class="remove-btn" title="Remove link" onclick="removePart(${p.id})">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </span>
    `).join('');
}

function renderAvailable(parts, engineCC) {
    const sel = document.getElementById('partsAddSelect');
    const tbl = document.getElementById('availablePartsTable');

    if (!parts.length) {
        tbl.innerHTML = '<p class="empty-note">All stock items are already linked.</p>';
        sel.innerHTML = '<option value="">All parts already linked</option>';
        return;
    }

    sel.innerHTML = '<option value="">— Select a part to add —</option>' +
        parts.map(p => `<option value="${p.id}">${escHtml(p.part_name)} (${escHtml(p.category)}) — Qty: ${p.quantity}</option>`).join('');

    tbl.innerHTML = `
        <table class="data-table" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th>Part Name</th>
                    <th>Category</th>
                    <th>CC Tag</th>
                    <th>In Stock</th>
                    <th>Selling Price</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                ${parts.map(p => {
                    const ccMatch = engineCC && p.compatible_cc && p.compatible_cc === engineCC;
                    return `<tr ${ccMatch ? 'style="background:#fffbea;"' : ''}>
                        <td>${escHtml(p.part_name)}</td>
                        <td>${escHtml(p.category)}</td>
                        <td>
                            ${p.compatible_cc
                                ? `<span style="background:#e3f2fd;color:#1565c0;border-radius:4px;padding:1px 6px;font-size:.72rem;font-weight:600;">${escHtml(p.compatible_cc)}</span>`
                                : '<span style="color:#bbb;font-size:.8rem;">—</span>'}
                        </td>
                        <td>${p.quantity}</td>
                        <td>₹${parseFloat(p.selling_price).toFixed(2)}</td>
                        <td>
                            <button class="btn btn-outline-success" style="font-size:.75rem;padding:2px 8px;"
                                    onclick="addPartDirect(${p.id})">
                                <i class="bi bi-plus-lg"></i> Link
                            </button>
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>`;
}

document.getElementById('addPartBtn').addEventListener('click', () => {
    const sel = document.getElementById('partsAddSelect');
    if (!sel.value) return;
    addPartDirect(sel.value);
});

function addPartDirect(stockId) {
    fetch('bikes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_part&bike_id=${currentBikeId}&stock_id=${stockId}`
    }).then(() => loadParts());
}

function removePart(stockId) {
    if (!confirm('Remove this part link?')) return;
    fetch('bikes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove_part&bike_id=${currentBikeId}&stock_id=${stockId}`
    }).then(() => loadParts());
}

function updateBadge(bikeId, count) {
    const badge = document.getElementById('badge-' + bikeId);
    if (!badge) return;
    badge.textContent = count;
    badge.className = 'parts-badge' + (count === 0 ? ' zero' : '');
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>