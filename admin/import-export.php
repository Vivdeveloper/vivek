<?php
require_once dirname(__DIR__) . '/includes/functions.php';
startSecureSession();
requireAdmin();

$_currentUser = currentUser();
$pageTitle = 'Sync Hub';

/**
 * Handle Unified Framework Extraction
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (isset($_POST['action']) && $_POST['action'] === 'do_export') {
        $selected_entities = $_POST['entities'] ?? [];
        if (empty($selected_entities)) { setFlash('error', 'Select target entities.'); redirect(APP_URL . '/admin/import-export.php'); }

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wp="http://wordpress.org/export/1.2/"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', htmlspecialchars(APP_NAME));
        
        $items_grouped = [];
        foreach ($selected_entities as $id) {
            if (strpos($id, 'cpt:') === 0) {
                $type = substr($id, 4); $stmt = db()->prepare("SELECT * FROM posts WHERE post_type = ?"); $stmt->execute([$type]);
                $items_grouped['posts_' . $type] = ['table' => 'posts', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
            } else {
                $table = preg_replace('/[^a-zA-Z0-9_]/', '', $id);
                $items_grouped[$table] = ['table' => $table, 'data' => db()->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC)];
            }
        }

        foreach ($items_grouped as $bundle) {
            $realT = $bundle['table'];
            foreach ($bundle['data'] as $row) {
                $item = $channel->addChild('item');
                $item->addChild('table_source', $realT, 'http://wordpress.org/export/1.2/');
                if ($realT === 'posts' && isset($row['post_type'])) $item->addChild('post_type', $row['post_type'], 'http://wordpress.org/export/1.2/');
                elseif ($realT === 'pages') $item->addChild('post_type', 'page', 'http://wordpress.org/export/1.2/');
                foreach ($row as $k => $v) {
                    if ($v === null) $v = '';
                    if ($k === 'content' || $k === 'setting_value') {
                        $node = dom_import_simplexml($item); $dom = $node->ownerDocument;
                        $cnode = $node->appendChild($dom->createElementNS('http://purl.org/rss/1.0/modules/content/', 'content:encoded'));
                        $cnode->appendChild($dom->createCDATASection($v));
                    } else { $item->addChild($k, htmlspecialchars($v), 'http://wordpress.org/export/1.2/'); }
                }
            }
        }
        header('Content-Type: text/xml'); header('Content-Disposition: attachment; filename="' . slugify(APP_NAME) . '_sync.xml"');
        echo $xml->asXML(); exit;
    }
    
    // Import (Deep Restore)
    if (isset($_FILES['import_file'])) {
        try {
            $xml = @simplexml_load_file($_FILES['import_file']['tmp_name'], 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml) throw new Exception("Corruption detected."); $updated = 0; $imported = 0;
            foreach ($xml->channel->item as $item) {
                $wp = $item->children('http://wordpress.org/export/1.2/'); $content = $item->children('http://purl.org/rss/1.0/modules/content/');
                $table = (string)$wp->table_source; $data = []; $encodedV = (string)$content->encoded;
                foreach ($wp as $k => $v) { if (in_array($k, ['content', 'table_source', 'post_type'])) continue; $data[$k] = (string)$v; }
                if (!empty($encodedV)) $data[($table==='settings'?'setting_value':'content')] = $encodedV;
                $exId = 0;
                if ($table === 'settings') {
                    $key = (string)$wp->setting_key; $chk = db()->prepare("SELECT id FROM settings WHERE setting_key = ?"); $chk->execute([$key]);
                    $row = $chk->fetch(); $exId = $row ? $row['id'] : 0;
                } elseif (in_array($table, ['posts', 'pages', 'custom_post_types', 'categories'])) {
                    $slug = (string)$wp->slug; $chk = db()->prepare("SELECT id FROM `$table` WHERE slug = ?"); $chk->execute([$slug]); $row = $chk->fetch(); $exId = $row ? $row['id'] : 0;
                }
                if ($exId) {
                    $setParts = []; $params = []; foreach ($data as $k => $v) { if ($k === 'id') continue; $setParts[] = "`$k` = ?"; $params[] = $v; } $params[] = $exId;
                    db()->prepare("UPDATE `$table` SET " . implode(", ", $setParts) . " WHERE id = ?")->execute($params); $updated++;
                } else {
                    if (isset($data['id'])) unset($data['id']); $cols = array_keys($data); $phs = array_fill(0, count($cols), '?');
                    try { db()->prepare("INSERT INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $phs) . ")")->execute(array_values($data)); $imported++; } catch (Exception $e) {}
                }
            }
            setFlash('success', "Deep Sync: $updated synced, $imported created.");
        } catch (Exception $e) { setFlash('error', $e->getMessage()); }
        redirect(APP_URL . '/admin/import-export.php');
    }
}

$all_tables = db()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$all_cpts = db()->query("SELECT slug, name FROM custom_post_types")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="max-width: 1400px; margin: 0 auto; padding-top: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px;">
        <div>
            <h2 style="margin:0; font-size: 24px; font-weight: 400; color: #1d2327;">Sync Hub</h2>
            <p style="margin:5px 0 0; font-size: 13px; color: #646970;">Elite Discovery Portability • Multi-Entity Synchronization</p>
        </div>
        <button type="submit" form="masterExportForm" class="btn btn-primary" style="height: 44px; padding: 0 40px; font-weight: 700; border-radius: 8px;">
            <i class="fas fa-file-download" style="margin-right: 10px;"></i> Generate Discovery Bundle
        </button>
    </div>

    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px; align-items: stretch;">
        
        <!-- FRAMEWORK LIST VIEW (The Discovery Matrix) -->
        <div class="modern-card no-padding overflow-hidden" style="border-radius: 12px; border: 1px solid #e2e4e7;">
            <form action="" method="POST" id="masterExportForm">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="do_export">
                
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="40" style="padding-left: 20px;"><input type="checkbox" id="selectAll"></th>
                            <th>Architectural Entity</th>
                            <th>Classification</th>
                            <th>Nodes</th>
                            <th style="text-align: right; padding-right: 20px;">Portability Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- INJECT LOGICAL CPTS -->
                        <?php if(!empty($all_cpts)): foreach($all_cpts as $cpt): 
                            $count = db()->prepare("SELECT COUNT(*) FROM posts WHERE post_type = ?");
                            $count->execute([$cpt['slug']]);
                            $cVal = $count->fetchColumn();
                        ?>
                        <tr class="framework-row cpt-row">
                            <td style="padding-left: 20px;"><input type="checkbox" name="entities[]" value="cpt:<?= $cpt['slug'] ?>" class="item-chk" checked></td>
                            <td><div class="title-cell"><strong><?= h($cpt['name']) ?></strong><small>Logical data partition</small></div></td>
                            <td><span class="status-badge status-active" style="background: #f0f6fb; color: #2271b1; border-color: #d1e4f3;">Content Model</span></td>
                            <td><small><?= $cVal ?> items</small></td>
                            <td style="text-align: right; padding-right: 20px;"><span class="status-badge status-approved">Discovery Ready</span></td>
                        </tr>
                        <?php endforeach; endif; ?>

                        <!-- PHYSICAL TABLES -->
                        <?php foreach ($all_tables as $table): 
                            if (in_array($table, ['users','failed_jobs','migrations'])) continue;
                            $count = db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                        ?>
                        <tr class="framework-row">
                            <td style="padding-left: 20px;"><input type="checkbox" name="entities[]" value="<?= $table ?>" class="item-chk" <?= in_array($table, ['settings','posts','pages','media','categories','custom_fields'])?'checked':'' ?>></td>
                            <td><div class="title-cell"><strong><?= h($table) ?></strong><small>Physical database table</small></div></td>
                            <td><span class="status-badge" style="background:#f6f7f7; color:#50575e; border-color:#dcdcde;">System Core</span></td>
                            <td><small><?= $count ?> records</small></td>
                            <td style="text-align: right; padding-right: 20px;"><span class="status-badge status-approved">Live Sync</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- RIGHT: RECOVERY STATION -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="modern-card" style="padding: 30px; border-radius: 12px; border: 2px dashed #e2e4e7; background: #fbfcfe; text-align: center; height: 100%; display: flex; flex-direction: column; justify-content: center;">
                <div style="width: 60px; height: 60px; background: #f0f6fb; color: #2271b1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 20px;">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h4 style="margin: 0; font-size: 16px; font-weight: 600;">Deep Sync Restore</h4>
                <p style="font-size: 12px; color: #646970; margin: 10px 0 25px; line-height: 1.6;">Inject a Discovery Bundle to overwrite content models and system settings with absolute 1:1 precision.</p>
                
                <form action="" method="POST" enctype="multipart/form-data" id="importForm">
                    <?php csrfField(); ?>
                    <input type="file" name="import_file" id="fup" accept=".xml" style="display:none;" onchange="this.form.submit()">
                    <button type="button" class="btn btn-outline" style="width: 100%; height: 48px; font-weight: 700; border-radius: 8px;" onclick="document.getElementById('fup').click()">
                        Inbound Discovery
                    </button>
                </form>
            </div>

            <div style="background: #fffcf0; border: 1px solid #ffe082; padding: 15px; border-radius: 8px;">
                <small style="display: flex; gap: 8px; color: #b78103; font-weight: 700;">
                    <i class="fas fa-info-circle"></i> Sync Logic
                </small>
                <p style="margin: 8px 0 0; font-size: 11px; color: #b78103; line-height: 1.4;">The engine uses "Upsert" logic. Existing items with matching slugs or keys will be updated; new items will be created.</p>
            </div>
        </div>

    </div>

</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.item-chk').forEach(c => c.checked = this.checked);
});
</script>

<style>
.framework-row:hover td { background: #fbfcfe !important; }
.cpt-row td { background: #fafbfc; }
.active-framework-nav { border-bottom: 2px solid #2271b1; padding-bottom: 5px; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>