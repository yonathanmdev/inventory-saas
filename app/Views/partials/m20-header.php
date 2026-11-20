<?php
/**
 * Needs: $letterhead, $e, $formTitle, $formModel, $docNo, $pageLabel
 *
 * Grid (4 columns):
 *   [logo, 2 rows]  [Institution Name]            [Document No ........ (2 cols)]
 *                   [Title (2 cols, 2 rows)]        [Page No. (2 rows)]
 *   [empty strip]
 */
?>
<table class="m20-head">
    <colgroup>
        <col class="c1"><col class="c2"><col class="c3"><col class="c4">
    </colgroup>

    <tr class="r1">
        <td rowspan="2" class="m20-logo">
            <?php if (!empty($letterhead['logo_url'])): ?>
                <img src="<?= $e($letterhead['logo_url']) ?>" alt="">
            <?php endif; ?>
        </td>

        <td class="m20-inst">
            <span class="hl">Institution Name</span>
            <div class="am">አብክመ <?= $e($letterhead['heading_am'] ?? '') ?></div>
            <div class="en">ANRS<?= $e(strtoupper($letterhead['heading_en'] ?? '')) ?></div>
        </td>

        <td colspan="2" class="m20-docno">
            <span class="hl">Document No</span>
            <div class="val"><?= $e($docNo) ?></div>
        </td>
    </tr>

    <tr class="r2">
        <td colspan="2" rowspan="2" class="m20-title">
            <span class="hl">Title</span>
            <div class="am"><?= $e($formTitle) ?></div>
            <div class="am model"><?= $e($formModel) ?></div>
        </td>

        <td rowspan="2" class="m20-pageno">
            <span class="hl">Page No.</span>
            <div class="val"><?= $e($pageLabel) ?></div>
        </td>
    </tr>

    <tr class="r3">
        <td class="m20-logo-foot"></td>
    </tr>
</table>