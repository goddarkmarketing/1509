<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/site_content.php';
requireAdmin();

$tab = trim($_GET['tab'] ?? '');
$action = $_GET['action'] ?? 'list';
$block = trim($_GET['block'] ?? '');
$itemIndex = isset($_GET['item']) ? (int) $_GET['item'] : -1;
$allowedTabs = ['home', 'contact', 'footer', 'faq'];
if ($action !== 'edit' && $tab !== '' && in_array($tab, $allowedTabs, true)) {
    redirect('/admin/content.php?action=edit&tab=' . urlencode($tab));
}
if ($action === 'edit' && !in_array($tab, $allowedTabs, true)) {
    $tab = 'home';
}
if ($action !== 'edit') {
    $action = 'list';
    $tab = '';
    $block = '';
}
if ($action === 'edit' && $block !== '' && !contentBlockAllowed($tab, $block)) {
    $block = '';
    $itemIndex = -1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $section = $_POST['section'] ?? '';
    $saveBlock = trim($_POST['block'] ?? '');
    $saveItem = isset($_POST['item']) ? (int) $_POST['item'] : -1;

    try {
        if ($section === 'home') {
            $home = getHomepageContent();
            if ($saveBlock === 'general') {
                saveSettings([
                    'site_tagline' => trim($_POST['site_tagline'] ?? ''),
                    'hero_title' => trim($_POST['hero_title'] ?? ''),
                    'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
                ]);
            } elseif ($saveBlock === 'trust') {
                foreach (($_POST['trust_label'] ?? []) as $i => $label) {
                    $home['trust'][$i]['label'] = trim((string) $label);
                    $home['trust'][$i]['value'] = trim((string) ($_POST['trust_value'][$i] ?? ''));
                    $home['trust'][$i]['mode'] = (string) ($_POST['trust_mode'][$i] ?? 'manual');
                }
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'why') {
                $home['why']['eyebrow'] = trim($_POST['why_eyebrow'] ?? '');
                $home['why']['title'] = trim($_POST['why_title'] ?? '');
                $home['why']['subtitle'] = trim($_POST['why_subtitle'] ?? '');
                foreach (['title', 'text'] as $field) {
                    foreach (($_POST['why_card_' . $field] ?? []) as $i => $val) {
                        $home['why']['cards'][$i][$field] = trim((string) $val);
                    }
                }
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'courses') {
                $home['courses']['title'] = trim($_POST['courses_title'] ?? '');
                $home['courses']['subtitle'] = trim($_POST['courses_subtitle'] ?? '');
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'instructor') {
                $home['instructor']['title'] = trim($_POST['instructor_title'] ?? '');
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'reviews') {
                $home['reviews']['title'] = trim($_POST['reviews_title'] ?? '');
                $home['reviews']['subtitle'] = trim($_POST['reviews_subtitle'] ?? '');
                $home['reviews']['items'] = parseReviewItemsFromPost(
                    $_POST['review_quote'] ?? [],
                    $_POST['review_name'] ?? [],
                    $_POST['review_course'] ?? [],
                    $_POST['review_initial'] ?? [],
                    $_POST['review_hue'] ?? []
                );
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'steps') {
                $home['steps']['title'] = trim($_POST['steps_title'] ?? '');
                $home['steps']['subtitle'] = trim($_POST['steps_subtitle'] ?? '');
                foreach (['title', 'text'] as $field) {
                    foreach (($_POST['step_' . $field] ?? []) as $i => $val) {
                        $home['steps']['items'][$i][$field] = trim((string) $val);
                    }
                }
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'faq') {
                $home['faq']['title'] = trim($_POST['faq_title'] ?? '');
                $home['faq']['subtitle'] = trim($_POST['faq_subtitle'] ?? '');
                saveJsonSetting('content_homepage_json', $home);
            } elseif ($saveBlock === 'newsletter') {
                $home['newsletter']['title'] = trim($_POST['newsletter_title'] ?? '');
                $home['newsletter']['subtitle'] = trim($_POST['newsletter_subtitle'] ?? '');
                $home['newsletter']['placeholder'] = trim($_POST['newsletter_placeholder'] ?? '');
                $home['newsletter']['button'] = trim($_POST['newsletter_button'] ?? '');
                saveJsonSetting('content_homepage_json', $home);
            }
            flash('admin_success', 'บันทึกเนื้อหาเรียบร้อย');
            $tab = 'home';
            $block = $saveBlock;
        } elseif ($section === 'contact') {
            if ($saveBlock === 'channels') {
                saveSettings([
                    'line_id' => trim($_POST['line_id'] ?? ''),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'facebook_url' => trim($_POST['facebook_url'] ?? ''),
                    'youtube_url' => trim($_POST['youtube_url'] ?? ''),
                    'tiktok_url' => trim($_POST['tiktok_url'] ?? ''),
                    'site_tagline' => trim($_POST['site_tagline'] ?? ''),
                ]);
            } else {
                $contact = getContactContent();
                foreach (array_keys(defaultContactContent()) as $key) {
                    if ($key === 'perks') {
                        continue;
                    }
                    if (isset($_POST[$key])) {
                        $contact[$key] = trim((string) $_POST[$key]);
                    }
                }
                $contact['perks'] = array_values(array_filter(array_map(
                    static fn($v) => trim((string) $v),
                    $_POST['perks'] ?? []
                ), static fn($v) => $v !== ''));
                saveJsonSetting('content_contact_json', $contact);
            }
            flash('admin_success', 'บันทึกข้อมูลติดต่อเรียบร้อย');
            $tab = 'contact';
            $block = $saveBlock;
        } elseif ($section === 'footer') {
            $footer = getFooterContent();
            if ($saveBlock === 'general') {
                $footer['copyright'] = trim($_POST['copyright'] ?? '');
                $footer['col_courses'] = trim($_POST['col_courses'] ?? '');
                $footer['col_about'] = trim($_POST['col_about'] ?? '');
                $footer['col_help'] = trim($_POST['col_help'] ?? '');
                $footer['col_contact'] = trim($_POST['col_contact'] ?? '');
                saveSettings(['site_tagline' => trim($_POST['site_tagline'] ?? '')]);
            } elseif ($saveBlock === 'about_links') {
                $aboutLabels = $_POST['about_label'] ?? [];
                $aboutUrls = $_POST['about_url'] ?? [];
                $footer['about_links'] = [];
                foreach ($aboutLabels as $i => $label) {
                    $label = trim((string) $label);
                    $url = trim((string) ($aboutUrls[$i] ?? ''));
                    if ($label === '' || $url === '') {
                        continue;
                    }
                    $footer['about_links'][] = ['label' => $label, 'url' => $url];
                }
            } elseif ($saveBlock === 'help_links') {
                $helpLabels = $_POST['help_label'] ?? [];
                $helpUrls = $_POST['help_url'] ?? [];
                $footer['help_links'] = [];
                foreach ($helpLabels as $i => $label) {
                    $label = trim((string) $label);
                    $url = trim((string) ($helpUrls[$i] ?? ''));
                    if ($label === '' || $url === '') {
                        continue;
                    }
                    $footer['help_links'][] = ['label' => $label, 'url' => $url];
                }
            }
            saveJsonSetting('content_footer_json', $footer);
            flash('admin_success', 'บันทึก Footer เรียบร้อย');
            $tab = 'footer';
            $block = $saveBlock;
        } elseif ($section === 'faq') {
            if ($saveBlock === 'page') {
                $faqPage = getFaqPageContent();
                foreach (array_keys(defaultFaqPageContent()) as $key) {
                    if (isset($_POST[$key])) {
                        $faqPage[$key] = trim((string) $_POST[$key]);
                    }
                }
                saveJsonSetting('content_faq_page_json', $faqPage);
            } elseif ($saveBlock === 'item') {
                $faqItems = getStoredFaqItems();
                $q = trim($_POST['faq_q'] ?? '');
                $a = trim($_POST['faq_a'] ?? '');
                $scope = ($_POST['faq_scope'] ?? 'main') === 'homepage_extra' ? 'homepage_extra' : 'main';
                $entry = ['q' => $q, 'a' => $a, 'scope' => $scope];
                if ($saveItem < 0) {
                    if ($q !== '' && $a !== '') {
                        $faqItems[] = $entry;
                    }
                    $block = 'items';
                    $itemIndex = -1;
                } elseif (isset($faqItems[$saveItem])) {
                    if ($q === '' && $a === '') {
                        array_splice($faqItems, $saveItem, 1);
                        $block = 'items';
                        $itemIndex = -1;
                    } else {
                        $faqItems[$saveItem] = $entry;
                        $block = 'item';
                        $itemIndex = $saveItem;
                    }
                }
                saveJsonSetting('content_faq_json', array_values($faqItems));
            } else {
                $faqItems = parseFaqItemsFromPost(
                    $_POST['faq_q'] ?? [],
                    $_POST['faq_a'] ?? [],
                    $_POST['faq_scope'] ?? []
                );
                saveJsonSetting('content_faq_json', $faqItems);
            }
            flash('admin_success', 'บันทึกคำถามที่พบบ่อยเรียบร้อย');
            $tab = 'faq';
            if ($saveBlock !== 'item') {
                $block = $saveBlock;
            }
        }
    } catch (Throwable $e) {
        flash('admin_error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    $redirect = '/admin/content.php?action=edit&tab=' . urlencode($tab);
    if ($block !== '') {
        $redirect .= '&block=' . urlencode($block);
    }
    if ($block === 'item' && $itemIndex >= 0) {
        $redirect .= '&item=' . $itemIndex;
    }
    redirect($redirect);
}

$pageTitle = 'จัดการเนื้อหาเว็บไซต์';
require_once dirname(__DIR__) . '/includes/admin_header.php';

$message = flash('admin_success');
$error = flash('admin_error');
$settings = getSettings();
$home = getHomepageContent();
$contact = getContactContent();
$footer = getFooterContent();
$faqItems = getStoredFaqItems();
$faqPage = getFaqPageContent();
$contentSections = getContentSectionRows();

$tabLabels = [
    'home' => 'หน้าแรก',
    'contact' => 'ติดต่อเรา',
    'footer' => 'Footer',
    'faq' => 'คำถามที่พบบ่อย',
];

$contentSectionIcon = static function (string $icon): string {
    $icons = [
        'home' => lucide_icon('house', ['size' => 18, 'stroke' => '1.75']),
        'contact' => lucide_icon('phone', ['size' => 18, 'stroke' => '1.75']),
        'footer' => lucide_icon('panel-bottom', ['size' => 18, 'stroke' => '1.75']),
        'faq' => lucide_icon('circle-help', ['size' => 18, 'stroke' => '1.75']),
    ];
    return $icons[$icon] ?? $icons['home'];
};
$tabViewUrls = [
    'home' => APP_URL . '/public/index.php',
    'contact' => APP_URL . '/public/contact.php',
    'footer' => APP_URL . '/public/index.php#contact',
    'faq' => APP_URL . '/public/faq.php',
];
$editHeading = 'รายการเนื้อหาเว็บไซต์ (' . count($contentSections) . ')';
if ($action === 'edit') {
  if ($block === '') {
      $editHeading = 'แก้ไข: ' . ($tabLabels[$tab] ?? '');
  } elseif ($block === 'items') {
      $editHeading = 'รายการคำถาม FAQ';
  } elseif ($block === 'item') {
      $editHeading = $itemIndex >= 0 ? 'แก้ไขคำถาม' : 'เพิ่มคำถามใหม่';
  } else {
      $editHeading = 'แก้ไข: ' . contentBlockTitle($tab, $block);
  }
}
?>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><?= e($editHeading) ?></h2>
        <div class="table-actions">
            <?php if ($action === 'edit'): ?>
                <?php if ($block === 'item'): ?>
                <a href="?action=edit&amp;tab=faq&amp;block=items" class="btn btn-secondary btn-sm">← กลับรายการคำถาม</a>
                <?php elseif ($block !== ''): ?>
                <a href="?action=edit&amp;tab=<?= urlencode($tab) ?>" class="btn btn-secondary btn-sm">← กลับรายการส่วน</a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/admin/content.php" class="btn btn-secondary btn-sm">← กลับรายการ</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($action === 'edit'): ?>
            <a href="<?= e($tabViewUrls[$tab] ?? APP_URL . '/public/index.php') ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-with-icon"><?= adminBtnIcon('eye') ?> ดูหน้าเว็บ</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($action === 'list'): ?>
    <div class="admin-card-body is-flush">
        <div class="table-responsive">
            <table class="admin-table content-sections-table">
                <thead>
                    <tr>
                        <th class="col-thumb">ไอคอน</th>
                        <th>หัวข้อ</th>
                        <th>หมวด</th>
                        <th>รายละเอียด</th>
                        <th>สถานะ</th>
                        <th class="actions">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contentSections as $section): ?>
                    <tr>
                        <td class="col-thumb">
                            <span class="admin-table-thumb content-section-thumb content-section-thumb--<?= e($section['icon']) ?>" aria-hidden="true">
                                <?= $contentSectionIcon($section['icon']) ?>
                            </span>
                        </td>
                        <td>
                            <strong class="content-section-title"><?= e($section['title']) ?></strong>
                            <?php if (!empty($section['summary'])): ?>
                                <small class="content-section-summary"><?= e(mb_strimwidth($section['summary'], 0, 80, '...')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-active"><?= e($section['category']) ?></span></td>
                        <td class="content-section-meta"><?= e($section['meta'] ?? '') ?></td>
                        <td><span class="badge badge-verified"><?= e($section['status'] ?? 'เผยแพร่') ?></span></td>
                        <td class="actions">
                            <div class="table-actions">
                                <a href="<?= e($section['view_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm btn-with-icon"><?= adminBtnIcon('eye') ?> ดู</a>
                                <a href="?action=edit&amp;tab=<?= urlencode($section['tab']) ?>" class="btn btn-secondary btn-sm btn-with-icon"><?= adminBtnIcon('square-pen') ?> แก้ไข</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($action === 'edit' && $block === ''): ?>
    <div class="admin-card-body is-flush">
        <?php
        $rows = getContentBlockRows($tab);
        require __DIR__ . '/includes/content_blocks_table.php';
        ?>
    </div>
    <?php elseif ($action === 'edit' && $tab === 'faq' && $block === 'items'): ?>
    <div class="admin-card-header" style="border-top:1px solid var(--border);margin:0">
        <h3>รายการคำถาม (<?= count($faqItems) ?>)</h3>
        <a href="?action=edit&amp;tab=faq&amp;block=item&amp;item=-1" class="btn btn-primary btn-sm btn-with-icon"><?= adminBtnIcon('plus') ?> เพิ่มคำถาม</a>
    </div>
    <div class="admin-card-body is-flush">
        <?php if ($faqItems): ?>
        <?php
        $rows = getFaqItemAdminRows();
        $editKey = 'index';
        require __DIR__ . '/includes/content_blocks_table.php';
        ?>
        <?php else: ?>
        <p class="table-empty">ยังไม่มีคำถาม — <a href="?action=edit&amp;tab=faq&amp;block=item&amp;item=-1">เพิ่มคำถามแรก</a></p>
        <?php endif; ?>
    </div>
    <?php elseif ($action === 'edit'): ?>
    <div class="admin-card-body">
        <?php require __DIR__ . '/includes/content_block_forms.php'; ?>
    </div>
    <?php endif; ?>
</div>

<template id="reviewRowTemplate">
    <div class="content-repeat-item admin-subform-panel">
        <div class="form-group"><label>รีวิว</label><textarea name="review_quote[]" class="form-control" rows="2"></textarea></div>
        <div class="form-row form-row-3">
            <div class="form-group"><label>ชื่อ</label><input type="text" name="review_name[]" class="form-control"></div>
            <div class="form-group"><label>คอร์ส</label><input type="text" name="review_course[]" class="form-control"></div>
            <div class="form-group"><label>ตัวย่อ</label><input type="text" name="review_initial[]" class="form-control" maxlength="2"></div>
        </div>
        <input type="hidden" name="review_hue[]" value="0">
    </div>
</template>

<script>
(function () {
  function appendFromTemplate(listId, templateId) {
    var list = document.getElementById(listId);
    var tpl = document.getElementById(templateId);
    if (!list || !tpl) return;
    list.appendChild(tpl.content.cloneNode(true));
  }
  var addReview = document.getElementById('addReviewRow');
  if (addReview) addReview.addEventListener('click', function () { appendFromTemplate('reviewList', 'reviewRowTemplate'); });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin_footer.php'; ?>
